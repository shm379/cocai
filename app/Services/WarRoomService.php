<?php

namespace App\Services;

use App\Models\User;
use App\Models\WarTargetCall;
use App\Services\GameData\GameDataService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class WarRoomService
{
    public function __construct(
        protected GameDataService $gameData,
        protected ChatbotService $chatbot,
    ) {
    }

    /**
     * دریافت وضعیت کلی نقشه وار و اهداف برای کلن مشخص.
     */
    public function getWarMapState(string $clanTag, int $totalTargets = 15): array
    {
        $cleanClanTag = $this->normalizeClanTag($clanTag);

        // به‌روزرسانی کال‌های منقضی‌شده
        WarTargetCall::where('clan_tag', $cleanClanTag)
            ->where('status', 'called')
            ->where('expires_at', '<', Carbon::now())
            ->update(['status' => 'expired']);

        $calls = WarTargetCall::where('clan_tag', $cleanClanTag)
            ->whereIn('status', ['called', 'cleared', 'attacked'])
            ->orderBy('target_number')
            ->get();

        $callsByTarget = $calls->groupBy('target_number');

        $grid = [];
        $clearedCount = 0;
        $totalStars = 0;

        for ($i = 1; $i <= $totalTargets; $i++) {
            $targetCalls = $callsByTarget->get($i, collect());
            
            // اگر اتک ۳ ستاره ثبت شده باشد
            $clearedCall = $targetCalls->firstWhere('status', 'cleared');
            $activeCall = $targetCalls->firstWhere('status', 'called');
            $attackedCall = $targetCalls->where('status', 'attacked')->sortByDesc('attack_result_stars')->first();

            $status = 'open';
            $displayCall = null;

            if ($clearedCall) {
                $status = 'cleared';
                $displayCall = $clearedCall;
                $clearedCount++;
                $totalStars += 3;
            } elseif ($activeCall) {
                $status = 'called';
                $displayCall = $activeCall;
            } elseif ($attackedCall) {
                $status = 'attacked';
                $displayCall = $attackedCall;
                $totalStars += ($attackedCall->attack_result_stars ?? 0);
            }

            $grid[] = [
                'target_number' => $i,
                'status' => $status,
                'call' => $displayCall,
                'target_th_level' => $displayCall?->target_th_level ?? $this->estimateDefaultThForPosition($i, $totalTargets),
            ];
        }

        return [
            'ok' => true,
            'clan_tag' => $cleanClanTag,
            'total_targets' => $totalTargets,
            'cleared_count' => $clearedCount,
            'total_stars' => $totalStars,
            'grid' => $grid,
        ];
    }

    /**
     * رزرو / کال کردن یک هدف در وار.
     */
    public function callTarget(User $user, array $data): array
    {
        $clanTag = $this->normalizeClanTag($data['clan_tag'] ?? '');
        $targetNumber = (int) ($data['target_number'] ?? 1);
        $targetTh = (int) ($data['target_th_level'] ?? 15);
        $notes = $data['tactical_notes'] ?? null;

        if (empty($clanTag)) {
            return ['ok' => false, 'message' => 'تگ کلن الزامی است.'];
        }

        // استخراج اطلاعات بازیکن
        $gameProfile = $user->gameProfile?->game_data ?? [];
        $callerName = $data['caller_name'] ?? $gameProfile['name'] ?? $user->name;
        $callerTag = $data['caller_tag'] ?? $gameProfile['tag'] ?? $user->gameProfile?->player_tag ?? 'DEMO';
        $callerTh = (int) ($data['caller_th_level'] ?? $gameProfile['townHallLevel'] ?? 15);

        // بررسی اینکه هدف قبلاً ۳ ستاره نشده باشد
        $alreadyCleared = WarTargetCall::where('clan_tag', $clanTag)
            ->where('target_number', $targetNumber)
            ->where('status', 'cleared')
            ->exists();

        if ($alreadyCleared) {
            return ['ok' => false, 'message' => "هدف شماره {$targetNumber} قبلاً ۳ ستاره و پاکسازی شده است!"];
        }

        // بررسی اینکه هدف توسط بازیکن دیگری در حال حاضر رزرو نباشد
        $existingCall = WarTargetCall::where('clan_tag', $clanTag)
            ->where('target_number', $targetNumber)
            ->where('status', 'called')
            ->where('expires_at', '>', Carbon::now())
            ->where('user_id', '!=', $user->id)
            ->first();

        if ($existingCall) {
            return [
                'ok' => false,
                'message' => "هدف شماره {$targetNumber} هم‌اکنون توسط «{$existingCall->caller_name}» رزرو است.",
            ];
        }

        // منقضی کردن رزروهای قبلی همین کاربر در این کلن
        WarTargetCall::where('clan_tag', $clanTag)
            ->where('user_id', $user->id)
            ->where('status', 'called')
            ->update(['status' => 'canceled']);

        // محاسبه تخمین ۳ ستاره و ارتش پیشنهادی
        $estimation = $this->calculateMatchupEstimation($callerTh, $targetTh, $gameProfile);

        $call = WarTargetCall::create([
            'user_id' => $user->id,
            'clan_tag' => $clanTag,
            'clan_name' => $data['clan_name'] ?? $gameProfile['clan']['name'] ?? 'کلن من',
            'target_number' => $targetNumber,
            'target_player_tag' => $data['target_player_tag'] ?? null,
            'target_player_name' => $data['target_player_name'] ?? "شماره {$targetNumber}",
            'target_th_level' => $targetTh,
            'caller_name' => $callerName,
            'caller_tag' => $callerTag,
            'caller_th_level' => $callerTh,
            'status' => 'called',
            'recommended_army' => $estimation['recommended_army'] ?? null,
            'win_probability' => $estimation['win_probability'] ?? 75,
            'tactical_notes' => $notes ?: ($estimation['tactical_advice'] ?? null),
            'expires_at' => Carbon::now()->addHours(2),
        ]);

        return [
            'ok' => true,
            'message' => "هدف شماره {$targetNumber} با موفقیت برای شما رزرو شد (انقضا ۲ ساعت).",
            'call' => $call,
            'estimation' => $estimation,
        ];
    }

    /**
     * ثبت نتیجه اتک (ستاره و درصد تخریب).
     */
    public function recordAttackResult(User $user, int $callId, int $stars, int $percent): array
    {
        $call = WarTargetCall::where('id', $callId)
            ->where('user_id', $user->id)
            ->first();

        if (! $call) {
            return ['ok' => false, 'message' => 'رزرو مورد نظر یافت نشد یا متعلق به شما نیست.'];
        }

        $stars = max(0, min(3, $stars));
        $percent = max(0, min(100, $percent));

        $status = $stars === 3 ? 'cleared' : 'attacked';

        $call->update([
            'status' => $status,
            'attack_result_stars' => $stars,
            'attack_destruction_percent' => $percent,
        ]);

        $message = $stars === 3
            ? "تبریک فرمانده! هدف شماره {$call->target_number} با ۳ ستاره کامل تسخیر شد 👑"
            : "نتیجه اتک به هدف {$call->target_number} ({$stars} ستاره، {$percent}%) ثبت شد.";

        return [
            'ok' => true,
            'message' => $message,
            'call' => $call,
        ];
    }

    /**
     * لغو رزرو هدف توسط کاربر.
     */
    public function cancelCall(User $user, int $callId): array
    {
        $call = WarTargetCall::where('id', $callId)
            ->where('user_id', $user->id)
            ->where('status', 'called')
            ->first();

        if (! $call) {
            return ['ok' => false, 'message' => 'رزرو فعالی یافت نشد.'];
        }

        $call->update(['status' => 'canceled']);

        return [
            'ok' => true,
            'message' => "رزرو هدف شماره {$call->target_number} لغو شد.",
        ];
    }

    /**
     * محاسبه قطعی شانس ۳ ستاره و ارتش متای مناسب.
     */
    public function calculateMatchupEstimation(int $attackerTh, int $defenderTh, array $gameData = []): array
    {
        $diff = $attackerTh - $defenderTh;

        if ($diff >= 2) {
            $prob = 98;
            $rating = 'بسیار آسان (Overkill)';
        } elseif ($diff === 1) {
            $prob = 92;
            $rating = 'آسان / ۳ ستاره قطعی';
        } elseif ($diff === 0) {
            $prob = 78;
            $rating = 'متعادل / نیازمند دقت در فانلینگ';
        } elseif ($diff === -1) {
            $prob = 48;
            $rating = 'سخت (Upscale 2-Star / High Skill 3-Star)';
        } else {
            $prob = 22;
            $rating = 'فوق‌العاده سنگین / هدف ۲ ستاره سیف';
        }

        // دریافت ارتش متای وار
        $armies = $this->gameData->armiesForTh($attackerTh);
        $warArmy = $armies['war'][0] ?? [
            'name' => 'Root Rider Smash',
            'name_fa' => 'روت رایدر اسمش با واردن واک',
            'description_fa' => 'ورود سهمگین با والکایری و روت رایدر به همراه ابیلیتی Eternal Tome واردن.',
        ];

        $tacticalAdvice = "برای شکست تاون‌هال {$defenderTh} با تاون‌هال {$attackerTh}، بهترین گزینه استفاده از «{$warArmy['name_fa']}» است. "
            ."ابتدا با کینگ و کویین دو بال ورودی را بسوزانید (فانل تمیز) و نیروهای اصلی را به سمت هسته بیس هدایت کنید.";

        return [
            'attacker_th' => $attackerTh,
            'defender_th' => $defenderTh,
            'win_probability' => $prob,
            'rating_fa' => $rating,
            'recommended_army' => $warArmy,
            'tactical_advice' => $tacticalAdvice,
        ];
    }

    /**
     * تخمین سطح تاون‌هال پیش‌فرض بر اساس رتبه در جدول وار.
     */
    protected function estimateDefaultThForPosition(int $position, int $totalTargets): int
    {
        $ratio = $position / max(1, $totalTargets);

        if ($ratio <= 0.2) {
            return 16;
        } elseif ($ratio <= 0.4) {
            return 15;
        } elseif ($ratio <= 0.7) {
            return 14;
        } elseif ($ratio <= 0.9) {
            return 13;
        }

        return 12;
    }

    /**
     * نرمال‌سازی تگ کلن.
     */
    protected function normalizeClanTag(string $tag): string
    {
        $tag = strtoupper(trim($tag));
        return ltrim($tag, '#');
    }
}
