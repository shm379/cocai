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
abstract class CocLayoutAdapter implements LayoutGameAdapter
{
    public function __construct(
        protected LayoutVisionExtractor $vision,
        protected LayoutMatcher $matcher,
    ) {
    }

    abstract public function catalog(): BuildingCatalog;

    public function gridSize(): int
    {
        return $this->catalog()->gridSize();
    }

    public function isConfigured(): bool
    {
        return $this->vision->isConfigured();
    }

    public function analyze(UploadedFile $image, ?string $hash): array
    {
        $catalog = $this->catalog();

        $extracted = $this->vision->withCatalog($catalog)->extractLayout($image);
        if (! ($extracted['ok'] ?? false)) {
            // بدون چیدمان فقط تطبیق تصویری ممکن است (برای پیشنهاد «شاید این بیس» در خطا).
            return [
                'ok' => false,
                'message' => $extracted['message'] ?? 'خطا در تحلیل تصویر.',
                'reason' => $extracted['reason'] ?? 'parse',
                'matches' => $hash ? $this->matcher->findMatches($hash) : [],
            ];
        }

        // دادهٔ Vision (به درصد تصویر؛ شامل جعبهٔ اسپرایت‌ها و لوزی) + ابعاد اصلی تصویر برای حل هندسه.
        $visionData = $extracted['data'];
        $path = $image->getRealPath();
        $info = $path ? @getimagesize($path) : false;
        $visionData['image_size'] = $info ? [$info[0], $info[1]] : ($visionData['image_size'] ?? null);

        $layout = (new LayoutGridMapper($catalog))->map($visionData, $catalog->gridSize());
        $layout['type'] = 'layout';
        $layout['village'] = $catalog->key();
        $layout['vision'] = [
            'model' => $extracted['model'] ?? null,
            'schema_version' => $visionData['schema_version'] ?? 1,
            'axes_swapped' => (bool) ($visionData['axes_swapped'] ?? false),
            'image_size' => $visionData['image_size'],
        ];

        // تطبیق با آرشیو: هش تصویر + امضای چیدمان (فقط نقشه‌هایی با لینک واقعی بازی برمی‌گردند).
        $matches = $this->matcher->findMatches($hash, $layout);

        $best = $matches[0] ?? null;
        $confident = $best && $best['confident'];

        if ($best) {
            $layout['match'] = [
                'map_id' => $best['id'],
                'similarity' => $best['similarity'],
                'method' => $best['method'],
                'signature_score' => $best['signature_score'],
                'distance' => $best['distance'],
                'confident' => $confident,
            ];
        }

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
