<?php

namespace App\Services;

use App\Models\GameProfile;
use App\Models\TrophyLog;
use Illuminate\Support\Facades\Http;

class ClashOfClansService
{
    protected $apiBase;

    protected $apiToken;

    public function __construct()
    {
        $this->apiBase = config('services.clash.api_base');
        $this->apiToken = config('services.clash.api_token');
    }

    /**
     * دریافت دادهٔ بازیکن. تگ نرمال و URL-encode می‌شود؛ پاسخ ۵ دقیقه کش می‌شود
     * تا رفرش‌های پیاپی به API فشار نیاورند.
     */
    public function getPlayerData(string $playerTag): array
    {
        if (empty($this->apiBase)) {
            throw new \RuntimeException('CLASH_API_BASE is not configured.');
        }

        $cleanTag = $this->normalizeTag($playerTag);

        return cache()->remember("coc.player.{$cleanTag}", now()->addMinutes(5), function () use ($cleanTag) {
            $request = Http::timeout(30)->retry(2, 500)->acceptJson();

            if (! empty($this->apiToken)) {
                $request = $request->withToken($this->apiToken);
            }

            $response = $request->get($this->apiBase.rawurlencode($cleanTag));

            if ($response->status() === 404) {
                throw new \RuntimeException('Player tag not found.');
            }

            if (! $response->ok()) {
                throw new \RuntimeException('Unable to fetch player data (HTTP '.$response->status().').');
            }

            $data = $response->json();

            if (! is_array($data) || empty($data['townHallLevel'])) {
                throw new \RuntimeException('Player API returned unexpected payload.');
            }

            return $data;
        });
    }

    /**
     * دریافت + ذخیرهٔ پروفایل و ثبت تروفی (حداکثر یک لاگ در روز).
     * اگر تگ عوض شده باشد، تاریخچهٔ تروفی و تقویم اکانت قبلی پاک می‌شود
     * تا دادهٔ دو اکانت قاطی نشود.
     */
    public function refreshGameProfile($user): GameProfile
    {
        $tag = $user->player_tag;

        if (! $tag) {
            throw new \RuntimeException('Player Tag is not set for this user.');
        }

        return $this->storeProfile($user, $tag);
    }

    public function storeProfile($user, string $playerTag): GameProfile
    {
        $cleanTag = $this->normalizeTag($playerTag);
        $playerData = $this->getPlayerData($cleanTag);

        $existing = GameProfile::where('user_id', $user->id)->first();
        $tagChanged = $existing && $existing->player_tag !== $cleanTag;

        $gameProfile = GameProfile::updateOrCreate(
            ['user_id' => $user->id],
            ['player_tag' => $cleanTag, 'game_data' => $playerData]
        );

        if ($tagChanged) {
            $gameProfile->trophyHistory()->delete();
            $gameProfile->calendars()->delete();
        }

        $alreadyLoggedToday = TrophyLog::where('game_profile_id', $gameProfile->id)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if (! $alreadyLoggedToday) {
            TrophyLog::create([
                'game_profile_id' => $gameProfile->id,
                'trophy_count' => $playerData['trophies'] ?? 0,
            ]);
        }

        return $gameProfile;
    }

    /**
     * سازگاری با فراخوانی‌های قدیمی.
     */
    public function updateGameProfile($user)
    {
        $this->refreshGameProfile($user);

        return true;
    }

    private function normalizeTag(string $tag): string
    {
        // بدون # ذخیره و ارسال می‌شود (سازگار با proxy و رکوردهای موجود).
        // O اشتباه تایپی رایج به‌جای 0 است.
        $tag = strtoupper(str_replace(['#', ' '], '', trim($tag)));

        return str_replace('O', '0', $tag);
    }
}
