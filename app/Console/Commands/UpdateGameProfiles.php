<?php

namespace App\Console\Commands;

use App\Models\GameProfile;
use App\Models\TrophyLog;
use Illuminate\Console\Command;
use App\Services\ClashOfClansService;

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
        $profiles = GameProfile::query()->all();

        foreach ($profiles as $profile) {
            TrophyLog::query()->create([
                'game_profile_id' => $profile->id,
                'trophy_count' => $playerData['trophies'] ?? 0,
            ]);
            $data = $service->getPlayerData($profile->player_tag);
            $profile->update(['game_data' => json_encode($data)]);
        }

        $this->info('All game profiles updated.');
    }

}
