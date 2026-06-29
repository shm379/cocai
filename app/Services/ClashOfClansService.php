<?php

namespace App\Services;

use App\Models\GameProfile;
use Illuminate\Support\Facades\Http;

class ClashOfClansService
{
    protected $apiBase;

    public function __construct()
    {
        $this->apiBase = config('services.clash.api_base');
    }

    public function getPlayerData($playerTag)
    {
        // حذف # از تگ
        $cleanTag = str_replace('#', '', $playerTag);

        // ارسال درخواست به API
        $response = Http::get("{$this->apiBase}{$cleanTag}");
        // بررسی موفقیت پاسخd
        if ($response->ok()) {
            return $response->json();
        }

        // مدیریت خطا
        throw new \Exception('Unable to fetch player data.');
    }

    public function updateGameProfile($user)
    {
        if (! $user->player_tag) {
            throw new \Exception('Player Tag is not set for this user.');
        }

        // درخواست به API برای گرفتن اطلاعات (هم‌الگو با getPlayerData)
        $cleanTag = str_replace('#', '', $user->player_tag);
        $response = Http::get("{$this->apiBase}{$cleanTag}");

        if ($response->ok()) {
            $playerData = $response->json();

            // ذخیره اطلاعات در GameProfile
            GameProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'player_tag' => $user->player_tag,
                    'game_data' => $playerData,
                ]
            );

            return true;
        }

        throw new \Exception('Failed to fetch player data from API.');
    }
}
