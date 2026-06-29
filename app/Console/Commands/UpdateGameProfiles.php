<?php

namespace App\Console\Commands;

use App\Models\GameProfile;
use App\Models\TrophyLog;
use App\Services\ClashOfClansService;
use Illuminate\Console\Command;

class UpdateGameProfiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:game_profiles';

    protected $description = 'Update GameProfiles';

    public function handle(ClashOfClansService $service)
    {
        $profiles = GameProfile::all();

        foreach ($profiles as $profile) {
            if (! $profile->player_tag) {
                continue;
            }

            try {
                // ابتدا داده‌ی تازه را بگیر، سپس پروفایل و لاگ تروفی را به‌روزرسانی کن
                $data = $service->getPlayerData($profile->player_tag);

                $profile->update(['game_data' => $data]);

                TrophyLog::create([
                    'game_profile_id' => $profile->id,
                    'trophy_count' => $data['trophies'] ?? 0,
                ]);
            } catch (\Exception $e) {
                \Log::error("Failed to update game profile {$profile->id}: ".$e->getMessage());
                $this->warn("Skipped profile {$profile->id}: {$e->getMessage()}");
            }
        }

        $this->info('All game profiles updated.');
    }
}
