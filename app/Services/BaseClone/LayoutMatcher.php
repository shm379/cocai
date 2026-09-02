<?php

namespace App\Services\BaseClone;

use App\Models\Map;

/**
 * یافتن نقشه‌های آرشیو (Clasher.us) که تصویرشان با تصویر آپلودشده یکی یا بسیار مشابه است.
 *
 * تنها راه به‌دست‌آوردن لینک کپی داخل بازی همین است: لینک OpenLayout یک ارجاع
 * ۲۴ بایتی به چیدمانی است که در سرور Supercell ذخیره شده و از روی تصویر قابل ساخت نیست.
 */
class LayoutMatcher
{
    /** فاصلهٔ همینگی که «همان بیس» محسوب می‌شود. */
    public const CONFIDENT_DISTANCE = 10;

    /** حداکثر فاصله برای نمایش به‌عنوان «مشابه احتمالی». */
    public const MAX_DISTANCE = 16;

    /**
     * @return array<int, array{id:int,name:string,copy_link:?string,map_link:?string,image_url:?string,thumbnail_url:?string,distance:int,similarity:int,confident:bool}>
     */
    public function findMatches(string $hash, int $limit = 3, int $maxDistance = self::MAX_DISTANCE): array
    {
        $matches = [];

        Map::query()
            ->whereNotNull('image_hash')
            ->whereNotNull('copy_link')
            ->select(['id', 'name', 'copy_link', 'map_link', 'image_url', 'thumbnail_url', 'image_hash'])
            ->chunk(500, function ($maps) use ($hash, $maxDistance, &$matches) {
                foreach ($maps as $map) {
                    $distance = ImageHasher::distance($hash, $map->image_hash);
                    if ($distance > $maxDistance) {
                        continue;
                    }
                    $matches[] = [
                        'id' => $map->id,
                        'name' => $map->name,
                        'copy_link' => $map->copy_link,
                        'map_link' => $map->map_link,
                        'image_url' => $map->image_url,
                        'thumbnail_url' => $map->thumbnail_url,
                        'distance' => $distance,
                        'similarity' => ImageHasher::similarity($distance),
                        'confident' => $distance <= self::CONFIDENT_DISTANCE,
                    ];
                }
            });

        usort($matches, fn ($a, $b) => $a['distance'] <=> $b['distance'] ?: $a['id'] <=> $b['id']);

        return array_slice($matches, 0, $limit);
    }
}
