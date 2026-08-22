<?php

namespace Database\Factories;

use App\Models\Map;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Map>
 */
class MapFactory extends Factory
{
    protected $model = Map::class;

    public function definition(): array
    {
        return [
            'name' => 'Base '.$this->faker->unique()->numberBetween(1, 9999),
            'image_url' => 'https://example.com/base.png',
            'thumbnail_url' => 'https://example.com/base-thumb.png',
            'map_link' => 'https://clasher.us/base/'.$this->faker->uuid(),
            'copy_link' => 'https://link.clashofclans.com/en?action=OpenLayout&id='.$this->faker->uuid(),
            'view_count' => $this->faker->numberBetween(0, 10000),
            'download_count' => $this->faker->numberBetween(0, 5000),
            'like_count' => $this->faker->numberBetween(0, 2000),
            'report_count' => 0,
            'created_at' => now(),
        ];
    }
}
