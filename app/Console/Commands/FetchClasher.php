<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Map;
use App\Models\Topic;
use App\Models\Unit;
use App\Models\Guide;
use App\Models\GuideCat;
use Carbon\Carbon;

class FetchClasher extends Command
{

    protected $signature = 'fetch:clasher';
    protected $description = 'Fetch data from Clasher.us API and store in the database';

    private $apiEndpoints = [];

    private function extractTopicFromUrl($key)
    {
        // **تبدیل کلید API به نام `Topic` صحیح**
        $topicName = str_replace(['_', 'base', 'designs', 'mobile', 'api'], [' ', '', '', '', ''], $key);
        $topicName = ucwords(trim($topicName));

        return $topicName;
    }

    private function convertAgoToDate($agoString)
    {
        try {
            $time = Carbon::now();

            // بررسی اینکه مقدار `ago` شامل اعداد و واحد زمانی هست
            if (preg_match('/(\d+)\s+(second|minute|hour|day|week|month|year)s?\s+ago/', $agoString, $matches)) {
                $quantity = (int)$matches[1];
                $unit = $matches[2];

                // تبدیل به Carbon date
                $time = Carbon::now()->sub($unit, $quantity);
            }

            return $time;
        } catch (\Exception $e) {
            return Carbon::now(); // در صورت وجود خطا، زمان فعلی را ذخیره کن
        }
    }

    public function __construct()
    {
        parent::__construct();

        for ($i = 1; $i <= 17; $i++) {
            $this->apiEndpoints["town_hall_{$i}_trophy_base"] = "http://www.clasher.us/api/mobile/designs/town-hall-{$i}-trophy-base?sort=like&no_mark=ok";
            $this->apiEndpoints["town_hall_{$i}_war_base"] = "http://www.clasher.us/api/mobile/designs/town-hall-{$i}-war-base?sort=like&no_mark=ok";
            $this->apiEndpoints["town_hall_{$i}_farming_base"] = "http://www.clasher.us/api/mobile/designs/town-hall-{$i}-farming-base?sort=like&no_mark=ok";
            $this->apiEndpoints["town_hall_{$i}_hybrid_base"] = "http://www.clasher.us/api/mobile/designs/town-hall-{$i}-hybrid-base?sort=like&no_mark=ok";
            $this->apiEndpoints["town_hall_{$i}_progress_base"] = "http://www.clasher.us/api/mobile/designs/town-hall-{$i}-progress-base?sort=like&no_mark=ok";
            $this->apiEndpoints["town_hall_{$i}_funny_base"] = "http://www.clasher.us/api/mobile/designs/town-hall-{$i}-funny-base?sort=like&no_mark=ok";
            $this->apiEndpoints["town_hall_{$i}_guides"] = "http://www.clasher.us/api/mobile/guides_all?cat=town-hall-{$i}";
        }

        for ($i = 1; $i <= 10; $i++) {
            $this->apiEndpoints["builder_hall_{$i}_guides"] = "http://www.clasher.us/api/mobile/guides_all?cat=builder-hall-{$i}";
            $this->apiEndpoints["builder_hall_{$i}_funny_base"] = "http://www.clasher.us/api/mobile/designs/builder-hall-{$i}-funny-base?sort=like&no_mark=ok";
            $this->apiEndpoints["builder_hall_{$i}_trophy_base"] = "http://www.clasher.us/api/mobile/designs/builder-hall-{$i}-trophy-base?sort=like&no_mark=ok";
            $this->apiEndpoints["builder_hall_{$i}_war_base"] = "http://www.clasher.us/api/mobile/designs/builder-hall-{$i}-war-base?sort=like&no_mark=ok";
            $this->apiEndpoints["builder_hall_{$i}_farming_base"] = "http://www.clasher.us/api/mobile/designs/builder-hall-{$i}-farming-base?sort=like&no_mark=ok";
            $this->apiEndpoints["builder_hall_{$i}_hybrid_base"] = "http://www.clasher.us/api/mobile/designs/builder-hall-{$i}-hybrid-base?sort=like&no_mark=ok";
            $this->apiEndpoints["builder_hall_{$i}_progress_base"] = "http://www.clasher.us/api/mobile/designs/builder-hall-{$i}-progress-base?sort=like&no_mark=ok";
        }

        $this->apiEndpoints = array_merge($this->apiEndpoints, [
            'units' => 'http://www.clasher.us/api/mobile/units',
            'ads' => 'http://www.clasher.us/app-ads/ads.json',
            'beginner_guides' => 'http://www.clasher.us/api/mobile/guides_all?cat=beginners-guides',
            'free_gems_guides' => 'http://www.clasher.us/api/mobile/guides_all?cat=get-free-gems',
            'army_compositions_guides' => 'http://www.clasher.us/api/mobile/guides_all?cat=amy-compositions',
            'gameplay_guides' => 'http://www.clasher.us/api/mobile/guides_all?cat=gameplay',
            'clan_war_guides' => 'http://www.clasher.us/api/mobile/guides_all?cat=clan-war',
            'base_layouts_guides' => 'http://www.clasher.us/api/mobile/guides_all?cat=base-layouts-design',
            'attack_tactics_guides' => 'http://www.clasher.us/api/mobile/guides_all?cat=tactics-attack-strategies',
        ]);
    }

    public function handle()
    {
        foreach ($this->apiEndpoints as $key => $url) {
            $this->info("Fetching data from $key...");
            $response = Http::withOptions(['verify' => false])->timeout(600000)->get($url);

            if ($response->successful()) {
                $this->processData($key, $response->json());
            } else {
                $this->error("Failed to fetch data from $key");
            }
        }
    }

    private function processData($key, $data)
    {
        if ($key === 'units') {
            foreach ($data as $item) {
                try {
                    Unit::updateOrCreate(
                        ['name' => $item['name']],
                        [
                            'gear_up_cost' => $item['gear_up_cost'] ?? null,
                            'gear_up_time' => $item['gear_up_time'] ?? null,
                            'size_on_board' => $item['size_on_board'] ?? null,
                            'damage_type' => $item['damage_type'] ?? null,
                            'hv_cannon_level_required' => $item['hv_cannon_level_required'] ?? null,
                            'bb_double_cannon_level_required' => $item['bb_double_cannon_level_required'] ?? null,
                            'unit_type_targeted' => $item['unit_type_targeted'] ?? null,
                            'elixir_type' => $item['elixir_type'] ?? null,
                            'range' => $item['range'] ?? null,
                            'has_gear' => $item['has_gear'] ?? false,
                            'game' => $item['game'] ?? null,
                            'img' => $item['img'] ?? null,
                            'summary' => json_encode($item['summary'] ?? null),
                            'trivia' => json_encode($item['trivia'] ?? null),
                            'icon_des' => json_encode($item['icon_des'] ?? null),
                            'levels' => json_encode($item['levels'] ?? null),
                            'levels_gear' => json_encode($item['levels_gear'] ?? null),
                            'offensive_strategy' => json_encode($item['offensive_strategy'] ?? null),
                            'defensive_strategy' => json_encode($item['defensive_strategy'] ?? null),
                        ]
                    );
                } catch (\Exception $e) {
                    $this->error("Failed to process unit: " . $e->getMessage());
                }
            }

            $this->info("Unit data processed successfully.");
            return;
        }

        if (str_contains($key, 'guides')) {
            foreach ($data as $item) {
                try {
                    if (isset($item['url']) && str_contains($item['url'], 'youtube.com')) {
                        Guide::updateOrCreate(
                            ['name' => $item['name']],
                            [
                                'url' => $item['url'],
                                'type' => 'youtube',
                            ]
                        );
                        continue;
                    }

                    if (isset($item['url']) && str_contains($item['url'], 'clasher.us')) {
                        $htmlResponse = Http::withOptions(['verify' => false])->timeout(60000)->get($item['url']);
                        $htmlContent = $htmlResponse->successful() ? $htmlResponse->body() : null;

                        $guide = Guide::updateOrCreate(
                            ['name' => $item['name']],
                            [
                                'url' => $item['url'],
                                'type' => 'html',
                                'content' => $htmlContent,
                            ]
                        );

                        if (isset($item['catIds']) && is_array($item['catIds'])) {
                            foreach ($item['catIds'] as $catId) {
                                $category = GuideCat::firstOrCreate(['name' => $catId]);
                                $guide->categories()->syncWithoutDetaching([$category->id]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to process guide: " . $e->getMessage());
                }
            }

            $this->info("Guide data processed successfully.");
            return;
        }

        if (str_contains($key, 'town_hall') || str_contains($key, 'builder_hall')) {
            foreach ($data as $item) {
                try {
                    $createdAt = $this->convertAgoToDate($item['ago'] ?? 'now');

                    // ذخیره یا آپدیت نقشه
                    $map = Map::updateOrCreate(
                        ['map_link' => $item['url']], // کلید اصلی برای جلوگیری از ذخیره تکراری
                        [
                            'name' => $item['name'],
                            'image_url' => $item['img'] ?? null,
                            'thumbnail_url' => $item['img_tn'] ?? null,
                            'copy_link' => $item['copy_link'] ?? null,
                            'view_count' => $item['viewCount'] ?? 0,
                            'download_count' => $item['downCount'] ?? 0,
                            'like_count' => $item['likeCount'] ?? 0,
                            'report_count' => $item['reportCount'] ?? 0,
                            'created_at' => $createdAt,
                        ]
                    );

                    // **گرفتن `Topic` از لینک API**
                    $topicName = $this->extractTopicFromUrl($key);
                    $topic = Topic::firstOrCreate(['name' => $topicName]);

                    // **اتصال فقط اگر قبلاً متصل نشده باشد**
                    if (!$map->topics()->where('topics.id', $topic->id)->exists()) {
                        $map->topics()->attach($topic->id);
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to process map: " . $e->getMessage());
                }
            }

            $this->info("Map data processed successfully.");
        }
    }
}
