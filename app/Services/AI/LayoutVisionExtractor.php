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
            return [
                'ok' => false,
                'message' => $this->lastErrorMessage(),
                'reason' => $this->lastError()['reason'] ?? 'empty',
            ];
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
        if ($start === false) {
            Log::warning('LayoutVisionExtractor: no JSON object in response.', ['content' => mb_substr($content, 0, 500)]);

            return null;
        }

        // پاسخ قطع‌شده ممکن است اصلاً «}» نداشته باشد؛ در آن صورت مستقیم به بازیابی می‌رویم.
        $data = ($end !== false && $end > $start)
            ? json_decode(substr($content, $start, $end - $start + 1), true)
            : null;
        if (! is_array($data)) {
            // پاسخ ناقص (مثلاً قطع‌شده در سقف توکن) → بازیابی هر چه قابل خواندن است.
            $data = $this->salvage($content);
            Log::warning('LayoutVisionExtractor: invalid JSON, salvaged.', [
                'length' => mb_strlen($content),
                'salvaged_buildings' => count($data['buildings'] ?? []),
                'head' => mb_substr($content, 0, 200),
                'tail' => mb_substr($content, -200),
            ]);

            if ($data === null) {
                return null;
            }
        }

        $data = $this->expandCompact($data);

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

    /**
     * تبدیل اسکیمای فشرده (th/p/c/thb/b/w با آرایه) به اسکیمای کامل؛ اسکیمای کامل دست‌نخورده می‌ماند.
     */
    protected function expandCompact(array $data): array
    {
        if (array_key_exists('th', $data) && ! array_key_exists('town_hall_level', $data)) {
            $data['town_hall_level'] = $data['th'];
        }
        if (isset($data['p']) && ! isset($data['perspective'])) {
            $data['perspective'] = $data['p'] === 'top' ? 'top_down' : 'isometric';
        }
        if (isset($data['c']) && is_array($data['c']) && count($data['c']) >= 8 && ! isset($data['grid_corners'])) {
            $c = array_values($data['c']);
            $data['grid_corners'] = [
                'top' => ['x' => $c[0], 'y' => $c[1]],
                'right' => ['x' => $c[2], 'y' => $c[3]],
                'bottom' => ['x' => $c[4], 'y' => $c[5]],
                'left' => ['x' => $c[6], 'y' => $c[7]],
            ];
        }
        if (isset($data['thb']) && is_array($data['thb']) && count($data['thb']) >= 4 && ! isset($data['town_hall_box'])) {
            $t = array_values($data['thb']);
            $data['town_hall_box'] = ['x' => $t[0], 'y' => $t[1], 'w' => $t[2], 'h' => $t[3]];
        }
        if (isset($data['b']) && is_array($data['b']) && ! isset($data['buildings'])) {
            $data['buildings'] = array_map(fn ($row) => $this->rowToBuilding($row), $data['b']);
        }
        if (isset($data['w']) && is_array($data['w']) && ! isset($data['walls'])) {
            $data['walls'] = array_map(fn ($row) => $this->rowToWall($row), $data['w']);
        }

        return $data;
    }

    /** @param  mixed  $row */
    protected function rowToBuilding($row): array
    {
        if (is_array($row) && array_is_list($row) && count($row) >= 3) {
            $b = ['type' => $row[0], 'x' => $row[1], 'y' => $row[2]];
            if (isset($row[3]) && is_numeric($row[3])) {
                $b['level'] = $row[3];
            }

            return $b;
        }

        return is_array($row) ? $row : [];
    }

    /** @param  mixed  $row */
    protected function rowToWall($row): array
    {
        if (is_array($row) && array_is_list($row) && count($row) >= 4) {
            return ['x1' => $row[0], 'y1' => $row[1], 'x2' => $row[2], 'y2' => $row[3]];
        }

        return is_array($row) ? $row : [];
    }

    /**
     * بازیابی داده از JSON ناقص (قطع‌شده در سقف توکن): فیلدهای اسکالر و هر ساختمان/دیوار کامل با regex.
     * هر دو اسکیما (فشرده و کامل) پشتیبانی می‌شود.
     */
    protected function salvage(string $content): ?array
    {
        $data = [];

        if (preg_match('/"(?:th|town_hall_level)"\s*:\s*(\d+)/', $content, $m)) {
            $data['town_hall_level'] = (int) $m[1];
        }
        if (preg_match('/"p"\s*:\s*"(iso|top)"/', $content, $m)) {
            $data['perspective'] = $m[1] === 'top' ? 'top_down' : 'isometric';
        } elseif (preg_match('/"perspective"\s*:\s*"(isometric|top_down)"/', $content, $m)) {
            $data['perspective'] = $m[1];
        }
        if (preg_match('/"c"\s*:\s*(\[[-\d.,\s]+\])/', $content, $m)) {
            $c = json_decode($m[1], true);
            if (is_array($c) && count($c) >= 8) {
                $data['grid_corners'] = [
                    'top' => ['x' => $c[0], 'y' => $c[1]], 'right' => ['x' => $c[2], 'y' => $c[3]],
                    'bottom' => ['x' => $c[4], 'y' => $c[5]], 'left' => ['x' => $c[6], 'y' => $c[7]],
                ];
            }
        } elseif (preg_match('/"grid_corners"\s*:\s*(\{(?:[^{}]|\{[^{}]*\})*\})/', $content, $m)) {
            $corners = json_decode($m[1], true);
            $data['grid_corners'] = is_array($corners) ? $corners : null;
        }
        if (preg_match('/"thb"\s*:\s*(\[[-\d.,\s]+\])/', $content, $m)) {
            $t = json_decode($m[1], true);
            if (is_array($t) && count($t) >= 4) {
                $data['town_hall_box'] = ['x' => $t[0], 'y' => $t[1], 'w' => $t[2], 'h' => $t[3]];
            }
        } elseif (preg_match('/"town_hall_box"\s*:\s*(\{[^{}]*\})/', $content, $m)) {
            $box = json_decode($m[1], true);
            $data['town_hall_box'] = is_array($box) ? $box : null;
        }

        $buildings = [];
        $walls = [];

        // اسکیمای فشرده: ["type",x,y(,level)] و بعد از "w": [x1,y1,x2,y2]
        if (preg_match_all('/\[\s*"([a-z_]+)"\s*,\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)(?:\s*,\s*(\d+))?\s*\]/', $content, $rows, PREG_SET_ORDER)) {
            foreach ($rows as $r) {
                $b = ['type' => $r[1], 'x' => (float) $r[2], 'y' => (float) $r[3]];
                if (isset($r[4]) && $r[4] !== '') {
                    $b['level'] = (int) $r[4];
                }
                $buildings[] = $b;
            }
        }
        if (preg_match('/"w"\s*:\s*\[(.*)$/s', $content, $wm)
            && preg_match_all('/\[\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*\]/', $wm[1], $rows, PREG_SET_ORDER)) {
            foreach ($rows as $r) {
                $walls[] = ['x1' => (float) $r[1], 'y1' => (float) $r[2], 'x2' => (float) $r[3], 'y2' => (float) $r[4]];
            }
        }

        // اسکیمای کامل: اشیاء {type,x,y} و {x1,y1,x2,y2}
        if ($buildings === [] && preg_match_all('/\{[^{}]*\}/', $content, $objects)) {
            foreach ($objects[0] as $raw) {
                $obj = json_decode($raw, true);
                if (! is_array($obj)) {
                    continue;
                }
                if (isset($obj['type'], $obj['x'], $obj['y'])) {
                    $buildings[] = $obj;
                } elseif (isset($obj['x1'], $obj['y1'], $obj['x2'], $obj['y2'])) {
                    $walls[] = $obj;
                }
            }
        }

        if ($buildings === []) {
            return null;
        }

        $data['buildings'] = $buildings;
        $data['walls'] = $walls;

        return $data;
    }

    protected function systemPrompt(): string
    {
        $types = $this->catalog->promptTypeList();
        $village = strtoupper($this->catalog->villageLabel());
        $grid = $this->catalog->gridSize();

        $prompt = <<<'PROMPT'
You are a Clash of Clans __VILLAGE__ layout digitizer. The user provides a screenshot or picture of a base. Return ONLY a JSON object (no markdown, no code fences, no commentary) with exactly this COMPACT schema. Angle brackets are placeholders you must replace with values measured from THIS image; never copy placeholder text or invent numbers.

{"th":<int or null>,"p":"iso"|"top","c":[<top_x>,<top_y>,<right_x>,<right_y>,<bottom_x>,<bottom_y>,<left_x>,<left_y>]|null,"thb":[<x>,<y>,<w>,<h>]|null,"b":[["<type>",<x>,<y>],["<type>",<x>,<y>,<level>]],"w":[[<x1>,<y1>,<x2>,<y2>]]}

Keys: "th" = Town Hall level. "p" = perspective ("iso" for in-game screenshots, "top" for flat diagrams). "c" = the 4 corners of the whole __GRID__x__GRID__ buildable village diamond in order top, right, bottom, left. "thb" = bounding box of the Town Hall (or Builder Hall) sprite (x,y top-left; w,h size), used as scale reference (4x4 tiles). "b" = one entry per building INSTANCE as [type, x, y] or [type, x, y, level]. "w" = straight wall segments as [x1, y1, x2, y2] between the centres of the first and last wall piece.

Rules:
- All coordinates are PERCENTAGES of the image width (x) and height (y), rounded to integers. The top-left corner of the image is (0,0). Values may be outside 0..100 when a point lies outside the visible image (for example the corners of a cropped screenshot).
- If the screenshot is cropped, extrapolate the corners from the visible edges and building sizes; if you truly cannot, set "c": null. Set "thb": null if the hall is not visible.
- x,y of a building is the centre of its footprint on the ground (base of the sprite, not its top). Allowed types: __TYPES__. Add the level only when clearly readable.
- Merge consecutive wall pieces in a straight line into one segment. Use an empty array if walls are not visible.
- Do NOT include traps, decorations, obstacles, buildings of the other village or anything outside the village grid.
- Be complete: a Town Hall 12+ base usually has 60-80 buildings excluding walls. List every cannon, archer tower, storage, collector and camp separately. Never invent buildings you cannot see.
- Output MINIFIED JSON on a single line, "b" before "w".
PROMPT;

        return str_replace(['__TYPES__', '__VILLAGE__', '__GRID__'], [$types, $village, (string) $grid], $prompt);
    }

    protected function userPrompt(): string
    {
        return 'Digitize this Clash of Clans base layout. Return the full JSON: grid corners, town hall box, every building instance and wall segments.';
    }

    protected function maxTokens(): int
    {
        // provider پشت nabu-vision این عدد را «کل بودجه» (ورودی + تصویر ≈ ۶٬۵۰۰ توکن + خروجی) حساب می‌کند؛
        // با ۸۰۰۰ فقط ~۲۰۰ توکن خروجی می‌ماند. ۲۴۰۰۰ برای ~۸۰ ساختمان + دیوارها کافی است.
        return 24000;
    }

    protected function temperature(): float
    {
        return 0.1;
    }
}
