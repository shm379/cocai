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
        $this->apiBase = config('services.clash.api_base') ?: 'https://api.clashofclans.com/v1/players/%23';
        $this->apiToken = config('services.clash.api_token') ?: config('services.clash.api_token');
    }

    /**
     * دریافت دادهٔ بازیکن. تگ نرمال و URL-encode می‌شود؛ پاسخ ۵ دقیقه کش می‌شود
     * تا رفرش‌های پیاپی به API فشار نیاورند.
     */
    public function getPlayerData(string $playerTag): array
    {
        $cleanTag = $this->normalizeTag($playerTag);

        // تگ‌های آزمایشی مستقیم داده ماک دریافت می‌کنند
        if (in_array($cleanTag, ['DEMO', 'TEST', 'TH11', 'TH12', 'TH13', 'TH14', 'TH15', 'TH16', 'TH17'])) {
            return $this->generateDemoProfile($cleanTag);
        }

        return cache()->remember("coc.player.{$cleanTag}", now()->addMinutes(5), function () use ($cleanTag) {
            if (! empty($this->apiToken) && ! empty($this->apiBase)) {
                try {
                    $response = Http::timeout(10)->retry(1, 300)
                        ->acceptJson()
                        ->withToken($this->apiToken)
                        ->get($this->apiBase.rawurlencode($cleanTag));

                    if ($response->ok()) {
                        $data = $response->json();
                        if (is_array($data) && ! empty($data['townHallLevel'])) {
                            return $data;
                        }
                    }

                    if ($response->status() === 404) {
                        throw new \RuntimeException('تگ بازیکن مورد نظر یافت نشد.');
                    }
                } catch (\RuntimeException $re) {
                    throw $re;
                } catch (\Throwable $e) {
                    \Log::warning('Clash API request failed: '.$e->getMessage().', using fallback demo profile.');
                }
            }

            // فال‌بک در صورت نبود توکن یا عدم اتصال به سرور رسمی
            return $this->generateDemoProfile($cleanTag);
        });
    }

    /**
     * ساخت داده استاندارد و کامل بازیکن مطابق ساختار رسمی API سوپرسل
     */
    public function generateDemoProfile(string $tag): array
    {
        $th = 12;
        if (preg_match('/TH(\d+)/i', $tag, $m)) {
            $th = min(17, max(2, (int) $m[1]));
        }

        return [
            'name' => 'Chief '.substr($tag, 0, 6),
            'tag' => '#'.$tag,
            'townHallLevel' => $th,
            'townHallWeaponLevel' => $th >= 12 ? 5 : null,
            'expLevel' => 120 + ($th * 8),
            'trophies' => 2000 + ($th * 150),
            'bestTrophies' => 2400 + ($th * 150),
            'warStars' => 450 + ($th * 30),
            'attackWins' => 74,
            'defenseWins' => 12,
            'builderHallLevel' => min(10, max(4, $th - 2)),
            'clan' => [
                'tag' => '#22CLAN',
                'name' => 'Persian Elite',
                'clanLevel' => 15,
                'badgeUrls' => [
                    'small' => 'https://api-assets.clashofclans.com/badges/70/4e8o4N6U8.png',
                    'large' => 'https://api-assets.clashofclans.com/badges/512/4e8o4N6U8.png',
                    'medium' => 'https://api-assets.clashofclans.com/badges/200/4e8o4N6U8.png',
                ],
            ],
            'heroes' => [
                ['name' => 'Barbarian King', 'level' => min(95, max(10, ($th - 6) * 10)), 'maxLevel' => min(95, ($th - 6) * 10 + 10), 'village' => 'home'],
                ['name' => 'Archer Queen', 'level' => min(95, max(10, ($th - 8) * 10)), 'maxLevel' => min(95, ($th - 8) * 10 + 10), 'village' => 'home'],
                ['name' => 'Grand Warden', 'level' => $th >= 11 ? min(70, max(5, ($th - 10) * 10)) : 0, 'maxLevel' => $th >= 11 ? 40 : 0, 'village' => 'home'],
                ['name' => 'Royal Champion', 'level' => $th >= 13 ? min(45, max(5, ($th - 12) * 5)) : 0, 'maxLevel' => $th >= 13 ? 45 : 0, 'village' => 'home'],
            ],
            'troops' => [
                ['name' => 'Barbarian', 'level' => min(12, $th - 2), 'maxLevel' => 12, 'village' => 'home'],
                ['name' => 'Archer', 'level' => min(12, $th - 2), 'maxLevel' => 12, 'village' => 'home'],
                ['name' => 'Giant', 'level' => min(12, $th - 2), 'maxLevel' => 12, 'village' => 'home'],
                ['name' => 'Goblin', 'level' => min(9, $th - 3), 'maxLevel' => 9, 'village' => 'home'],
                ['name' => 'Wall Breaker', 'level' => min(11, $th - 2), 'maxLevel' => 11, 'village' => 'home'],
                ['name' => 'Balloon', 'level' => min(11, $th - 2), 'maxLevel' => 11, 'village' => 'home'],
                ['name' => 'Wizard', 'level' => min(12, $th - 2), 'maxLevel' => 12, 'village' => 'home'],
                ['name' => 'Healer', 'level' => min(9, $th - 3), 'maxLevel' => 9, 'village' => 'home'],
                ['name' => 'Dragon', 'level' => min(11, $th - 2), 'maxLevel' => 11, 'village' => 'home'],
                ['name' => 'P.E.K.K.A', 'level' => min(10, $th - 3), 'maxLevel' => 10, 'village' => 'home'],
                ['name' => 'Baby Dragon', 'level' => min(9, $th - 3), 'maxLevel' => 9, 'village' => 'home'],
                ['name' => 'Miner', 'level' => $th >= 10 ? min(9, $th - 4) : 0, 'maxLevel' => 9, 'village' => 'home'],
                ['name' => 'Electro Dragon', 'level' => $th >= 11 ? min(7, $th - 8) : 0, 'maxLevel' => 7, 'village' => 'home'],
                ['name' => 'Yeti', 'level' => $th >= 12 ? min(6, $th - 9) : 0, 'maxLevel' => 6, 'village' => 'home'],
                ['name' => 'Minion', 'level' => min(12, $th - 2), 'maxLevel' => 12, 'village' => 'home'],
                ['name' => 'Hog Rider', 'level' => min(12, $th - 2), 'maxLevel' => 12, 'village' => 'home'],
                ['name' => 'Valkyrie', 'level' => min(10, $th - 2), 'maxLevel' => 10, 'village' => 'home'],
                ['name' => 'Golem', 'level' => min(13, $th - 2), 'maxLevel' => 13, 'village' => 'home'],
                ['name' => 'Witch', 'level' => min(6, $th - 3), 'maxLevel' => 6, 'village' => 'home'],
                ['name' => 'Lava Hound', 'level' => min(7, $th - 4), 'maxLevel' => 7, 'village' => 'home'],
                ['name' => 'Bowler', 'level' => $th >= 10 ? min(7, $th - 5) : 0, 'maxLevel' => 7, 'village' => 'home'],
            ],
            'spells' => [
                ['name' => 'Lightning Spell', 'level' => min(11, $th - 2), 'maxLevel' => 11, 'village' => 'home'],
                ['name' => 'Healing Spell', 'level' => min(10, $th - 2), 'maxLevel' => 10, 'village' => 'home'],
                ['name' => 'Rage Spell', 'level' => min(6, $th - 3), 'maxLevel' => 6, 'village' => 'home'],
                ['name' => 'Jump Spell', 'level' => min(5, $th - 4), 'maxLevel' => 5, 'village' => 'home'],
                ['name' => 'Freeze Spell', 'level' => min(7, $th - 4), 'maxLevel' => 7, 'village' => 'home'],
                ['name' => 'Poison Spell', 'level' => min(10, $th - 3), 'maxLevel' => 10, 'village' => 'home'],
                ['name' => 'Earthquake Spell', 'level' => min(6, $th - 4), 'maxLevel' => 6, 'village' => 'home'],
                ['name' => 'Haste Spell', 'level' => min(6, $th - 4), 'maxLevel' => 6, 'village' => 'home'],
                ['name' => 'Bat Spell', 'level' => min(6, $th - 5), 'maxLevel' => 6, 'village' => 'home'],
            ],
        ];
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

    /**
     * واکشی اطلاعات و مشخصات کامل کلن از API رسمی سوپرسل
     */
    public function getClanData(string $clanTag): array
    {
        $cleanTag = $this->normalizeTag($clanTag);

        return cache()->remember("coc.clan.{$cleanTag}", now()->addMinutes(10), function () use ($cleanTag) {
            if (! empty($this->apiToken)) {
                try {
                    $clanApiBase = 'https://api.clashofclans.com/v1/clans/%23';
                    $response = Http::timeout(10)->retry(1, 300)
                        ->acceptJson()
                        ->withToken($this->apiToken)
                        ->get($clanApiBase.rawurlencode($cleanTag));

                    if ($response->ok()) {
                        return $response->json();
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Clan API request failed: '.$e->getMessage());
                }
            }

            return [
                'tag' => '#'.$cleanTag,
                'name' => 'Persian Warriors',
                'clanLevel' => 18,
                'members' => 48,
                'warWins' => 380,
                'warWinStreak' => 7,
                'warLeague' => ['name' => 'Master League I'],
                'clanCapital' => ['capitalHallLevel' => 10],
                'badgeUrls' => [
                    'medium' => 'https://api-assets.clashofclans.com/badges/200/4e8o4N6U8.png',
                ],
            ];
        });
    }

    private function normalizeTag(string $tag): string
    {
        // بدون # ذخیره و ارسال می‌شود (سازگار با proxy و رکوردهای موجود).
        // O اشتباه تایپی رایج به‌جای 0 است.
        $tag = strtoupper(str_replace(['#', ' '], '', trim($tag)));

        return str_replace('O', '0', $tag);
    }
}
