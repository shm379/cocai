<?php

namespace App\Services;

use App\Models\Map;
use App\Models\Topic;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * کراولر نقشه‌ها از Clasher.us API.
 *
 * این سرویس نقشه‌های تاون‌هال و بیلدرهال را از API کلشر یواس گرفته و در دیتابیس
 * ذخیره می‌کند. از firstOrCreate برای topicها استفاده می‌شود تا ستون‌های
 * hall_type و hall_level به‌درستی پر شوند.
 */
class MapCrawlerService
{
    /**
     * لیست اندپوینت‌های پایه برای هر تاون‌هال و بیلدرهال.
     */
    protected array $baseTypes = [
        'trophy_base',
        'war_base',
        'farming_base',
        'hybrid_base',
        'progress_base',
        'funny_base',
    ];

    /**
     * کراول نقشه‌ها برای تمام تاون‌هال‌ها و بیلدرهال‌ها.
     *
     * @return array{ok: bool, imported: int, message: string}
     */
    public function crawlAll(): array
    {
        $totalImported = 0;
        $errors = [];

        for ($th = 1; $th <= 17; $th++) {
            foreach ($this->baseTypes as $type) {
                try {
                    $count = $this->crawlTownHall($th, $type);
                    $totalImported += $count;
                } catch (\Throwable $e) {
                    Log::error("MapCrawlerService TH{$th} {$type} failed: "
                        .$e->getMessage());
                    $errors[] = "TH{$th} {$type}: "
                        .mb_strimwidth($e->getMessage(), 0, 100, '...');
                }
            }
        }

        for ($bh = 1; $bh <= 10; $bh++) {
            foreach ($this->baseTypes as $type) {
                try {
                    $count = $this->crawlBuilderHall($bh, $type);
                    $totalImported += $count;
                } catch (\Throwable $e) {
                    Log::error("MapCrawlerService BH{$bh} {$type} failed: "
                        .$e->getMessage());
                    $errors[] = "BH{$bh} {$type}: "
                        .mb_strimwidth($e->getMessage(), 0, 100, '...');
                }
            }
        }

        return [
            'ok' => true,
            'imported' => $totalImported,
            'errors' => $errors,
            'message' => "{$totalImported} نقشه وارد شد."
                .(empty($errors) ? '' : ' ('.count($errors).' خطای جزئی)'),
        ];
    }

    /**
     * کراول نقشه‌های یک تاون‌هال خاص.
     */
    public function crawlTownHall(int $th, string $type = 'trophy_base'): int
    {
        $dashType = str_replace('_', '-', $type);
        $url = "http://www.clasher.us/api/mobile/designs/town-hall-{$th}-{$dashType}?sort=like&no_mark=ok";

        return $this->processDesigns($url, "Town Hall {$th} ".ucwords(str_replace('_', ' ', $type)), 0, $th);
    }

    /**
     * کراول نقشه‌های یک بیلدرهال خاص.
     */
    public function crawlBuilderHall(int $bh, string $type = 'trophy_base'): int
    {
        $dashType = str_replace('_', '-', $type);
        $url = "http://www.clasher.us/api/mobile/designs/builder-hall-{$bh}-{$dashType}?sort=like&no_mark=ok";

        return $this->processDesigns($url, "Builder Hall {$bh} ".ucwords(str_replace('_', ' ', $type)), 1, $bh);
    }

    /**
     * پردازش پاسخ API و ذخیره نقشه‌ها.
     */
    protected function processDesigns(string $url, string $topicName, int $hallType, int $hallLevel): int
    {
        $response = Http::withOptions(['verify' => false])
            ->timeout(120)
            ->get($url);

        if (! $response->successful()) {
            Log::warning("MapCrawlerService failed to fetch: {$url}");

            return 0;
        }

        $data = $response->json();
        if (! is_array($data) || empty($data)) {
            return 0;
        }

        $topic = $this->getOrCreateTopic($topicName, $hallType, $hallLevel);
        $imported = 0;

        foreach ($data as $item) {
            try {
                $createdAt = $this->convertAgoToDate($item['ago'] ?? 'now');

                $map = Map::updateOrCreate(
                    ['map_link' => $item['url'] ?? $item['map_link'] ?? null],
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

                if ($map->wasRecentlyCreated || ! $map->topics()->where('topics.id', $topic->id)->exists()) {
                    $map->topics()->attach($topic->id);
                }

                $imported++;
            } catch (\Throwable $e) {
                Log::error('MapCrawlerService process item failed: '.$e->getMessage());
            }
        }

        return $imported;
    }

    /**
     * دریافت یا ساخت topic با hall_type و hall_level.
     */
    protected function getOrCreateTopic(string $name, int $hallType, int $hallLevel): Topic
    {
        $topic = Topic::firstOrCreate(
            ['name' => $name],
            ['hall_type' => $hallType, 'hall_level' => $hallLevel]
        );

        if (is_null($topic->hall_type) || is_null($topic->hall_level)) {
            $topic->update(['hall_type' => $hallType, 'hall_level' => $hallLevel]);
        }

        return $topic;
    }

    /**
     * تبدیل رشته‌های "X minutes ago" به تاریخ Carbon.
     */
    protected function convertAgoToDate(string $agoString): Carbon
    {
        $time = Carbon::now();

        if (preg_match('/(\d+)\s+(second|minute|hour|day|week|month|year)s?\s+ago/', $agoString, $matches)) {
            $time = Carbon::now()->sub($matches[2], (int) $matches[1]);
        }

        return $time;
    }
}
