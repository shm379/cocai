<?php

namespace App\Services\BaseClone\Games;

use App\Services\AI\LayoutVisionExtractor;
use App\Services\BaseClone\BuildingCatalog;
use App\Services\BaseClone\LayoutGridMapper;
use App\Services\BaseClone\LayoutMatcher;
use Illuminate\Http\UploadedFile;

/**
 * موتور مشترک کلش آف کلنز: Vision → شبکهٔ ۴۴×۴۴ → تطبیق با آرشیو.
 */
abstract class CocLayoutAdapter implements GameAdapter
{
    public function __construct(
        protected LayoutVisionExtractor $vision,
        protected LayoutMatcher $matcher,
    ) {
    }

    abstract protected function catalog(): BuildingCatalog;

    public function isConfigured(): bool
    {
        return $this->vision->isConfigured();
    }

    public function analyze(UploadedFile $image, ?string $hash): array
    {
        $catalog = $this->catalog();
        $matches = $hash ? $this->matcher->findMatches($hash) : [];

        $extracted = $this->vision->withCatalog($catalog)->extractLayout($image);
        if (! ($extracted['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => $extracted['message'] ?? 'خطا در تحلیل تصویر.',
                'matches' => $matches,
            ];
        }

        $visionData = $extracted['data'];
        $path = $image->getRealPath();
        $info = $path ? @getimagesize($path) : false;
        $visionData['image_size'] = $info ? [$info[0], $info[1]] : null;

        $layout = (new LayoutGridMapper($catalog))->map($visionData, $catalog->gridSize());
        $layout['type'] = 'layout';
        $layout['village'] = $catalog->key();

        $best = $matches[0] ?? null;
        $confident = $best && $best['confident'];

        return [
            'ok' => true,
            'layout' => $layout,
            'copy_link' => $confident ? $best['copy_link'] : null,
            'th_level' => $layout['th_level'],
            'matched_map_id' => $confident ? $best['id'] : null,
            'match_distance' => $best['distance'] ?? null,
            'matches' => $matches,
        ];
    }
}
