<?php

namespace App\Services\MapSources;

/**
 * قرارداد هر منبع نقشه (Clasher.us و منابع دیگر).
 *
 * fetch() آیتم‌های نرمال‌شده برمی‌گرداند:
 * ['external_id','name','map_link','copy_link','image_url','thumbnail_url','hall_type'(0=TH,1=BH),'hall_level','category','stats'=>[views,downloads,likes,reports],'published_at'=>?Carbon]
 */
interface MapSourceAdapter
{
    public function key(): string;

    public function label(): string;

    /**
     * @param  array{th?: ?int, village?: ?string, category?: ?string, sort?: string, limit?: int, since?: ?\Carbon\CarbonInterface, progress?: callable}  $options
     * @return iterable<int, array>
     */
    public function fetch(array $options = []): iterable;
}
