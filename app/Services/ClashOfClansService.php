<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ClashOfClansService
{
    protected $apiBase = 'http://49.12.36.232:8080/get_player/?player_tag=';
    
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
        if (!$user->player_tag) {
            throw new \Exception('Player Tag is not set for this user.');
        }

        // درخواست به API برای گرفتن اطلاعات
        $response = Http::get("{$this->apiBase}/get_player/{$user->player_tag}");

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
