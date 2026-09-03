<?php

namespace App\Services\MapSources;

use App\Models\Map;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;

/**
 * درج/به‌روزرسانی نقشه‌های یک منبع در آرشیو.
 *
 * کلید یکتا map_link است (با fallback روی external_id). لینک کپی فقط با فرمت واقعی
 * بازی ذخیره می‌شود و هرگز لینک معتبر قبلی با مقدار نامعتبر/خالی جایگزین نمی‌شود.
 */
class MapImporter
{
    /** @var array<string, int> کش topic بر اساس "hall_type:hall_level:category" */
    protected array $topicCache = [];

    /**
     * @param  iterable<int, array>  $items
     * @return array{fetched:int, inserted:int, updated:int, skipped_invalid:int, errors:int, new_ids:array<int,int>, updated_ids:array<int,int>}
     */
    public function import(iterable $items, string $source, bool $dryRun = false): array
    {
        $stats = ['fetched' => 0, 'inserted' => 0, 'updated' => 0, 'skipped_invalid' => 0, 'errors' => 0, 'new_ids' => [], 'updated_ids' => []];

        foreach ($items as $item) {
            $stats['fetched']++;

            try {
                $result = $this->importOne($item, $source, $dryRun);
            } catch (\Throwable $e) {
                $stats['errors']++;
                report($e);

                continue;
            }

            if ($result['status'] === 'inserted') {
                $stats['inserted']++;
                if ($result['id']) {
                    $stats['new_ids'][] = $result['id'];
                }
            } elseif ($result['status'] === 'updated') {
                $stats['updated']++;
                if ($result['id']) {
                    $stats['updated_ids'][] = $result['id'];
                }
            }
            if ($result['invalid_link']) {
                $stats['skipped_invalid']++;
            }
        }

        return $stats;
    }

    /**
     * @return array{status:string, id:?int, invalid_link:bool}
     */
    public function importOne(array $item, string $source, bool $dryRun = false): array
    {
        $mapLink = self::normalizeLink($item['map_link'] ?? null);
        if ($mapLink === null) {
            return ['status' => 'skipped', 'id' => null, 'invalid_link' => false];
        }

        $copyLink = trim((string) ($item['copy_link'] ?? ''));
        $validLink = $copyLink !== '' && Map::isValidCopyLink($copyLink);

        $map = Map::where('map_link', $mapLink)->first();
        if (! $map && ! empty($item['external_id'])) {
            $map = Map::where('external_id', $item['external_id'])->where('source', $source)->first();
        }

        $now = now();
        $attributes = [
            'name' => mb_substr((string) ($item['name'] ?? ''), 0, 255),
            'image_url' => $item['image_url'] ?? null,
            'thumbnail_url' => $item['thumbnail_url'] ?? null,
            'view_count' => (int) ($item['stats']['views'] ?? 0),
            'download_count' => (int) ($item['stats']['downloads'] ?? 0),
            'like_count' => (int) ($item['stats']['likes'] ?? 0),
            'report_count' => (int) ($item['stats']['reports'] ?? 0),
            'source' => $source,
            'external_id' => $item['external_id'] ?? null,
            'category' => $item['category'] ?? null,
            'published_at' => $item['published_at'] ?? null,
            'fetched_at' => $now,
        ];

        if ($dryRun) {
            return ['status' => $map ? 'updated' : 'inserted', 'id' => $map?->id, 'invalid_link' => ! $validLink];
        }

        return DB::transaction(function () use ($map, $mapLink, $attributes, $copyLink, $validLink, $item, $now) {
            if ($map) {
                // لینک معتبر جدید جایگزین می‌شود؛ لینک معتبر قبلی هرگز با نامعتبر/خالی پاک نمی‌شود.
                if ($validLink) {
                    $attributes['copy_link'] = $copyLink;
                } elseif (! $map->hasValidCopyLink()) {
                    $attributes['copy_link'] = null;
                }
                // تصویر قدیمی را فقط وقتی جایگزین کن که تصویر جدید داریم.
                if (empty($attributes['image_url'])) {
                    unset($attributes['image_url']);
                }
                if (empty($attributes['thumbnail_url'])) {
                    unset($attributes['thumbnail_url']);
                }
                $map->fill($attributes);
                $changed = $map->isDirty(array_diff(array_keys($attributes), ['fetched_at']));
                $map->save();
                $this->attachTopic($map, $item);

                return ['status' => $changed ? 'updated' : 'unchanged', 'id' => $map->id, 'invalid_link' => ! $validLink];
            }

            $map = Map::create($attributes + [
                'map_link' => $mapLink,
                'copy_link' => $validLink ? $copyLink : null,
                'created_at' => $item['published_at'] ?? $now,
            ]);
            $this->attachTopic($map, $item);

            return ['status' => 'inserted', 'id' => $map->id, 'invalid_link' => ! $validLink];
        });
    }

    /**
     * اتصال به topic (hall_type, hall_level, category) با همان نام‌گذاری آرشیو: «Town Hall 15 War».
     */
    protected function attachTopic(Map $map, array $item): void
    {
        $hallType = isset($item['hall_type']) ? (int) $item['hall_type'] : null;
        $hallLevel = isset($item['hall_level']) ? (int) $item['hall_level'] : null;
        if ($hallType === null || $hallLevel === null) {
            return;
        }
        $category = ucfirst(strtolower((string) ($item['category'] ?? 'other')));
        $key = "{$hallType}:{$hallLevel}:{$category}";

        if (! isset($this->topicCache[$key])) {
            $name = ($hallType === 1 ? 'Builder Hall ' : 'Town Hall ')."{$hallLevel} {$category}";
            $topic = Topic::where('hall_type', $hallType)->where('hall_level', $hallLevel)
                ->whereRaw('TRIM(name) = ?', [$name])->first()
                ?? Topic::create(['name' => $name, 'hall_type' => $hallType, 'hall_level' => $hallLevel]);
            $this->topicCache[$key] = $topic->id;
        }

        $map->topics()->syncWithoutDetaching([$this->topicCache[$key]]);
    }

    public static function normalizeLink(?string $link): ?string
    {
        $link = trim((string) $link);
        if ($link === '') {
            return null;
        }
        if (str_starts_with($link, 'http://')) {
            $link = 'https://'.substr($link, 7);
        }

        return rtrim($link, '/');
    }
}
