<?php

namespace App\Services\AI;

use App\Services\BaseClone\BuildingCatalog;
use Illuminate\Support\Facades\Log;

/**
 * استخراج چیدمان کامل بیس از روی تصویر با مدل Vision.
 *
 * برخلاف BaseVisionAnalyzer که فقط ساختمان‌های کلیدی را برای تحلیل نقاط ضعف
 * می‌گیرد، این کلاس همهٔ ساختمان‌ها، قطعات دیوار، گوشه‌های لوزی نقشه و مقیاس
 * تاون‌هال را می‌خواهد تا LayoutGridMapper بتواند چیدمان ۴۴×۴۴ را بازسازی کند.
 */
class LayoutVisionExtractor extends BaseVisionAnalyzer
{
    public function __construct(protected BuildingCatalog $catalog)
    {
        parent::__construct();
    }

    /**
     * نسخه‌ای از استخراج‌کننده با کاتالوگ دیگر (مثلاً بیلدر بیس).
     */
    public function withCatalog(BuildingCatalog $catalog): static
    {
        $clone = clone $this;
        $clone->catalog = $catalog;

        return $clone;
    }

    /**
     * @param  \Illuminate\Http\UploadedFile|string  $image
     * @return array{ok: bool, data?: array, message?: string, raw_content?: string, model?: string}
     */
    public function extractLayout($image): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'تنظیمات AI Vision انجام نشده است.'];
        }

        $base64 = $this->imageToBase64($image);
        if ($base64 === null) {
            return ['ok' => false, 'message' => 'خطا در خواندن تصویر.'];
        }

        $response = $this->callVisionModel($base64);
        if ($response === null || empty($response['content'])) {
            return ['ok' => false, 'message' => 'پاسخی از مدل Vision دریافت نشد.'];
        }

        $data = $this->parseLayoutJson($response['content']);
        if ($data === null) {
            return [
                'ok' => false,
                'message' => 'خروجی مدل Vision قابل تفسیر نبود. لطفاً تصویر واضح‌تری از کل بیس آپلود کنید.',
                'raw_content' => $response['content'],
            ];
        }

        if ($data['buildings'] === []) {
            return [
                'ok' => false,
                'message' => 'هیچ ساختمانی در تصویر تشخیص داده نشد. مطمئن شوید تصویر، نمای کامل یک بیس کلش است.',
                'raw_content' => $response['content'],
            ];
        }

        return [
            'ok' => true,
            'data' => $data,
            'raw_content' => $response['content'],
            'model' => $response['model'] ?? $this->model,
        ];
    }

    /**
     * تفسیر تحمل‌پذیر JSON مدل (حذف code fence، برش تا آخرین آکولاد).
     */
    protected function parseLayoutJson(string $content): ?array
    {
        $content = trim($content);

        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*|\s*```$/s', '', $content) ?? $content;
            $content = trim($content);
        }

        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        if ($start === false || $end === false || $end <= $start) {
            Log::warning('LayoutVisionExtractor: no JSON object in response.', ['content' => mb_substr($content, 0, 500)]);

            return null;
        }

        $data = json_decode(substr($content, $start, $end - $start + 1), true);
        if (! is_array($data)) {
            Log::warning('LayoutVisionExtractor: invalid JSON.', ['content' => mb_substr($content, 0, 500)]);

            return null;
        }

        $buildings = [];
        foreach ((array) ($data['buildings'] ?? []) as $b) {
            if (! is_array($b)) {
                continue;
            }
            $type = $this->catalog->normalizeType(is_string($b['type'] ?? null) ? $b['type'] : null);
            if ($type === null || $type === BuildingCatalog::WALL) {
                continue;
            }
            if (! isset($b['x'], $b['y']) || ! is_numeric($b['x']) || ! is_numeric($b['y'])) {
                continue;
            }

            $entry = [
                'type' => $type,
                'x' => round((float) $b['x'], 2),
                'y' => round((float) $b['y'], 2),
            ];
            if (isset($b['level']) && is_numeric($b['level'])) {
                $entry['level'] = (int) $b['level'];
            }
            $buildings[] = $entry;
        }

        $walls = [];
        foreach ((array) ($data['walls'] ?? []) as $w) {
            if (! is_array($w)) {
                continue;
            }
            $ok = true;
            foreach (['x1', 'y1', 'x2', 'y2'] as $k) {
                if (! isset($w[$k]) || ! is_numeric($w[$k])) {
                    $ok = false;
                    break;
                }
            }
            if (! $ok) {
                continue;
            }
            $walls[] = [
                'x1' => round((float) $w['x1'], 2),
                'y1' => round((float) $w['y1'], 2),
                'x2' => round((float) $w['x2'], 2),
                'y2' => round((float) $w['y2'], 2),
            ];
        }

        return [
            'town_hall_level' => is_numeric($data['town_hall_level'] ?? null) ? (int) $data['town_hall_level'] : null,
            'perspective' => ($data['perspective'] ?? 'isometric') === 'top_down' ? 'top_down' : 'isometric',
            'grid_corners' => is_array($data['grid_corners'] ?? null) ? $data['grid_corners'] : null,
            'town_hall_box' => is_array($data['town_hall_box'] ?? null) ? $data['town_hall_box'] : null,
            'buildings' => $buildings,
            'walls' => $walls,
        ];
    }

    protected function systemPrompt(): string
    {
        $types = $this->catalog->promptTypeList();
        $village = strtoupper($this->catalog->villageLabel());
        $grid = $this->catalog->gridSize();

        $prompt = <<<'PROMPT'
You are a Clash of Clans __VILLAGE__ layout digitizer. The user provides a screenshot or picture of a base. Return ONLY a JSON object (no markdown, no code fences, no commentary) with exactly this structure:

{
  "town_hall_level": 16,
  "perspective": "isometric",
  "grid_corners": {
    "top": {"x": 50.0, "y": -8.0},
    "right": {"x": 118.0, "y": 48.0},
    "bottom": {"x": 50.0, "y": 104.0},
    "left": {"x": -18.0, "y": 48.0}
  },
  "town_hall_box": {"x": 46.0, "y": 44.0, "w": 9.0, "h": 8.0},
  "buildings": [
    {"type": "town_hall", "x": 50.5, "y": 48.0, "level": 16},
    {"type": "cannon", "x": 31.0, "y": 22.5}
  ],
  "walls": [
    {"x1": 20.0, "y1": 30.0, "x2": 40.0, "y2": 20.0}
  ]
}

Rules:
- All coordinates are PERCENTAGES of the image width (x) and height (y). The top-left corner of the image is (0,0). Values may be outside 0..100 when a point lies outside the visible image (for example the grid corners of a cropped screenshot).
- "grid_corners": the four corners of the whole __GRID__x__GRID__ buildable village diamond (the green grass area, excluding the outer forest/border). "top" is the corner at the top of the screen, "right" on the right, and so on. If the screenshot is cropped, extrapolate them from the visible edges and the size of the buildings; if you truly cannot, set "grid_corners": null.
- "town_hall_box": bounding box (x,y = top-left, w,h = size, all percentages) of the Town Hall (or Builder Hall) sprite. It is used as a scale reference (its footprint is 4x4 tiles). Set null if it is not visible.
- "perspective": "isometric" for in-game screenshots (default) or "top_down" for flat diagrams.
- "buildings": one entry per building INSTANCE (list every cannon, archer tower, storage, collector, camp separately). x,y is the centre of the building's footprint on the ground (the base of the sprite, not its top). Allowed "type" values: __TYPES__. Use "level" only when it is clearly readable, otherwise omit it.
- "walls": straight wall segments as line segments between the centres of their first and last wall piece. Merge consecutive wall pieces in a straight line into one segment. Return an empty array if walls are not visible.
- Do NOT include traps, decorations, obstacles, buildings of the other village or anything outside the village grid.
- Be complete: a Town Hall 12+ base usually has 60-80 buildings excluding walls. Never invent buildings you cannot see.
PROMPT;

        return str_replace(['__TYPES__', '__VILLAGE__', '__GRID__'], [$types, $village, (string) $grid], $prompt);
    }

    protected function userPrompt(): string
    {
        return 'Digitize this Clash of Clans base layout. Return the full JSON: grid corners, town hall box, every building instance and wall segments.';
    }

    protected function maxTokens(): int
    {
        return 6000;
    }

    protected function temperature(): float
    {
        return 0.1;
    }
}
