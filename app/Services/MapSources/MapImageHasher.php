<?php

namespace App\Services\MapSources;

use App\Models\Map;
use App\Services\BaseClone\ImageHasher;
use Illuminate\Support\Facades\Http;

/**
 * هش تصویر یک نقشه با ترتیب تلاش: تصویر کامل (/images/full/)، image_url خام، بندانگشتی.
 * (تصویر کامل همان فریم اسکرین‌شات کاربر است؛ بندانگشتی هش متفاوتی می‌دهد.)
 */
class MapImageHasher
{
    public function __construct(protected ImageHasher $hasher)
    {
    }

    public function hashMap(Map $map): bool
    {
        foreach ($this->candidateUrls($map) as $url) {
            try {
                $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (cocai maps:hash)'])
                    ->timeout(25)->connectTimeout(8)->get($url);
                if ($response->ok() && strlen($response->body()) > 2000) {
                    $hash = $this->hasher->hashBinary($response->body());
                    if ($hash !== null) {
                        $map->image_hash = $hash;
                        $map->save();

                        return true;
                    }
                }
            } catch (\Throwable) {
                // تلاش بعدی
            }
        }

        return false;
    }

    /** @return array<int, string> */
    public function candidateUrls(Map $map): array
    {
        $urls = [];
        if ($map->image_url) {
            $urls[] = str_replace('/images/fullo/', '/images/full/', $map->image_url);
            $urls[] = $map->image_url;
        }
        if ($map->thumbnail_url) {
            $urls[] = $map->thumbnail_url;
        }

        return array_values(array_unique(array_filter($urls)));
    }
}
