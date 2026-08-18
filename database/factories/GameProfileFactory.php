<?php

namespace Database\Factories;

use App\Models\GameProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameProfile>
 */
class GameProfileFactory extends Factory
{
    protected $model = GameProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'player_tag' => 'DEMO',
            'game_data' => [
                'townHallLevel' => 12,
                'trophies' => 3000,
                'name' => 'Demo Chief',
                'tag' => '#DEMO',
                'heroes' => [],
                'troops' => [],
                'spells' => [],
            ],
        ];
    }
}
