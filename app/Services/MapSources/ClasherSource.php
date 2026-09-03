<?php

namespace App\Services\MapSources;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * منبع Clasher.us — API موبایل: /api/mobile/designs/{slug}?sort=new|like&no_mark=ok
 * هر فراخوانی حداکثر ۳۰۰ آیتم برمی‌گرداند (بدون صفحه‌بندی)؛ sort=new جدیدترین‌ها را می‌دهد.
 * دسته‌ها برای TH1–18؛ لیست‌های بیلدر هال خالی است (۲۰۲۶-۰۹).
 */
class ClasherSource implements MapSourceAdapter
{
    public const BASE = 'https://www.clasher.us';

    public const CATEGORIES = ['war', 'trophy', 'farming', 'hybrid', 'progress', 'funny', 'cwl'];

    public const MAX_TH = 18;

    public const MAX_BH = 10;

    /** فاصلهٔ مؤدبانه بین درخواست‌ها (میلی‌ثانیه). */
    public int $delayMs = 350;

    public function key(): string
    {
        return 'clasher';
    }

    public function label(): string
    {
        return 'Clasher.us';
    }

    public function fetch(array $options = []): iterable
    {
        $sort = in_array($options['sort'] ?? 'new', ['new', 'like', 'date'], true) ? ($options['sort'] ?? 'new') : 'new';
        $limit = (int) ($options['limit'] ?? 0);
        $since = $options['since'] ?? null;
        $progress = $options['progress'] ?? null;
        $yielded = 0;

        foreach ($this->lists($options) as [$village, $level, $category]) {
            $slug = ($village === 'builder' ? 'builder-hall-' : 'town-hall-')."{$level}-{$category}-base";
            $url = self::BASE."/api/mobile/designs/{$slug}?sort={$sort}&no_mark=ok";

            $items = $this->request($url);
            if ($progress) {
                $progress($village, $level, $category, count($items));
            }

            foreach ($items as $raw) {
                $item = $this->normalize($raw, $village, $level, $category);
                if ($item === null) {
                    continue;
                }
                if ($since && $item['published_at'] && $item['published_at']->lt($since)) {
                    continue;
                }

                yield $item;

                if ($limit > 0 && ++$yielded >= $limit) {
                    return;
                }
            }

            usleep($this->delayMs * 1000);
        }
    }

    /**
     * فهرست (village, level, category) بر اساس فیلترها.
     *
     * @return array<int, array{0:string,1:int,2:string}>
     */
    public function lists(array $options): array
    {
        $villages = match ($options['village'] ?? null) {
            'home' => ['home'],
            'builder' => ['builder'],
            default => ['home', 'builder'],
        };
        $categories = ! empty($options['category']) ? [strtolower((string) $options['category'])] : self::CATEGORIES;
        $out = [];

        foreach ($villages as $village) {
            $max = $village === 'builder' ? self::MAX_BH : self::MAX_TH;
            $levels = ! empty($options['th']) ? [(int) $options['th']] : range($max, 1);
            foreach ($levels as $level) {
                if ($level < 1 || $level > $max) {
                    continue;
                }
                foreach ($categories as $category) {
                    $out[] = [$village, $level, $category];
                }
            }
        }

        return $out;
    }

    /**
     * @return array<int, array>
     */
    protected function request(string $url): array
    {
        foreach ([1, 2, 3] as $attempt) {
            try {
                $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (cocai maps:update)'])
                    ->timeout(40)->connectTimeout(10)->get($url);

                if ($response->ok()) {
                    $json = $response->json();
                    if (is_array($json) && array_is_list($json)) {
                        return $json;
                    }
                    if (is_array($json) && isset($json['ok']) && $json['ok'] === false) {
                        return [];
                    }

                    return is_array($json) ? (array) ($json['data'] ?? []) : [];
                }

                if ($response->status() === 404) {
                    return [];
                }

                Log::warning("ClasherSource: {$url} → HTTP {$response->status()} (attempt {$attempt})");
            } catch (\Throwable $e) {
                Log::warning("ClasherSource: {$url} → ".$e->getMessage()." (attempt {$attempt})");
            }

            usleep(500000 * $attempt);
        }

        return [];
    }

    protected function normalize(array $raw, string $village, int $level, string $category): ?array
    {
        $id = isset($raw['_id']) ? (string) $raw['_id'] : null;
        $mapLink = $this->absolute($raw['url'] ?? null, self::BASE);
        if (! $id && ! $mapLink) {
            return null;
        }
        if (! $mapLink) {
            $mapLink = self::BASE.'/design/'.$id;
        }

        return [
            'external_id' => $id,
            'name' => trim((string) ($raw['name'] ?? '')) ?: 'Base '.$id,
            'map_link' => $mapLink,
            'copy_link' => isset($raw['copy_link']) ? trim((string) $raw['copy_link']) : null,
            'image_url' => $this->absolute($raw['imgo'] ?? $raw['img'] ?? null, 'https://img.clasher.us'),
            'thumbnail_url' => $this->absolute($raw['imgo_tn'] ?? $raw['img_tn'] ?? null, 'https://img.clasher.us'),
            'hall_type' => $village === 'builder' ? 1 : 0,
            'hall_level' => $level,
            'category' => $category,
            'stats' => [
                'views' => (int) ($raw['viewCount'] ?? 0),
                'downloads' => (int) ($raw['downCount'] ?? 0),
                'likes' => (int) ($raw['likeCount'] ?? 0),
                'reports' => (int) ($raw['reportCount'] ?? 0),
            ],
            'published_at' => self::objectIdTime($id),
        ];
    }

    /** زمان ساخت از ۴ بایت اول ObjectId مونگو. */
    public static function objectIdTime(?string $id): ?Carbon
    {
        if (! $id || ! preg_match('/^[0-9a-f]{24}$/i', $id)) {
            return null;
        }

        return Carbon::createFromTimestampUTC(hexdec(substr($id, 0, 8)));
    }

    protected function absolute(?string $url, string $base): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }
        if (str_starts_with($url, 'http://')) {
            return 'https://'.substr($url, 7);
        }
        if (str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim($base, '/').'/'.ltrim($url, '/');
    }
}
