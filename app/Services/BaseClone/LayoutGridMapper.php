<?php

namespace App\Services\BaseClone;

/**
 * نگاشت قطعی خروجی Vision (درصد تصویر) به شبکهٔ ۴۴×۴۴ دهکده.
 *
 * ورودی: گوشه‌های لوزی نقشه، ساختمان‌ها (مرکز) و قطعات دیوار (پاره‌خط) به درصد.
 * خروجی: چیدمان با مختصات صحیح خانه‌ها، بدون هم‌پوشانی (با جابه‌جایی کوچک در صورت برخورد).
 * هیچ عددی حدس زده نمی‌شود؛ تنها تبدیل هندسی و برف‌گیری (snap) روی شبکه انجام می‌شود.
 */
class LayoutGridMapper
{
    public const GRID_SIZE = 44;

    public const MAX_WALLS = 400;

    protected const SEARCH_RADIUS = 3;

    protected const CORNER_RANGE = [-250.0, 350.0];

    public function __construct(protected BuildingCatalog $catalog)
    {
    }

    /**
     * @param  array  $vision  خروجی نرمال‌شدهٔ مدل Vision
     * @return array{grid_size:int,th_level:?int,perspective:string,corners_source:string,buildings:array,walls:array,stats:array}
     */
    public function map(array $vision, int $gridSize = self::GRID_SIZE): array
    {
        $perspective = ($vision['perspective'] ?? 'isometric') === 'top_down' ? 'top_down' : 'isometric';
        $imageSize = $this->sanitizeImageSize($vision['image_size'] ?? null);

        $rawBuildings = $this->sanitizeBuildings($vision['buildings'] ?? []);
        $rawWalls = $this->sanitizeWalls($vision['walls'] ?? []);

        $cornersSource = 'model';
        $corners = $perspective === 'isometric' ? $this->sanitizeCorners($vision['grid_corners'] ?? null) : null;

        if ($corners === null && $perspective === 'isometric') {
            $thBox = $this->sanitizeBox($vision['town_hall_box'] ?? null);
            $corners = $this->estimateCorners($rawBuildings, $rawWalls, $thBox, $imageSize, $gridSize);
            $cornersSource = $thBox ? 'town_hall_scale' : 'bounding_box';
        }

        if ($corners === null) {
            $cornersSource = 'linear';
        }

        $toGrid = fn (float $x, float $y): array => $corners
            ? $this->isoToGrid($x, $y, $corners, $gridSize)
            : [$x / 100 * $gridSize, $y / 100 * $gridSize];

        // ساختمان‌های بزرگ‌تر اول جای‌گذاری می‌شوند تا کمتر جابه‌جا شوند.
        $ordered = $rawBuildings;
        usort($ordered, function (array $a, array $b) {
            $sa = $this->catalog->size($a['type']);
            $sb = $this->catalog->size($b['type']);

            return $sb <=> $sa ?: $a['_index'] <=> $b['_index'];
        });

        $occupancy = array_fill(0, $gridSize, array_fill(0, $gridSize, false));
        $buildings = [];
        $unplaced = 0;

        foreach ($ordered as $raw) {
            $type = $raw['type'];
            $meta = $this->catalog->get($type);
            $size = $meta['size'];

            [$gx, $gy] = $toGrid($raw['x'], $raw['y']);

            $x0 = (int) round($gx - $size / 2);
            $y0 = (int) round($gy - $size / 2);
            $x0 = max(0, min($gridSize - $size, $x0));
            $y0 = max(0, min($gridSize - $size, $y0));

            $spot = $this->findFreeSpot($occupancy, $x0, $y0, $size, $gridSize);
            $placed = $spot !== null;

            if ($placed) {
                [$x0, $y0] = $spot;
                $this->occupy($occupancy, $x0, $y0, $size);
            } else {
                $unplaced++;
            }

            $entry = [
                'id' => $raw['_index'] + 1,
                'type' => $type,
                'label' => $meta['label'],
                'category' => $meta['category'],
                'color' => $meta['color'],
                'icon' => $meta['icon'],
                'size' => $size,
                'x' => $x0,
                'y' => $y0,
                'placed' => $placed,
            ];

            if ($raw['level'] !== null) {
                $entry['level'] = $raw['level'];
            }

            $buildings[] = $entry;
        }

        usort($buildings, fn ($a, $b) => $a['id'] <=> $b['id']);

        $walls = [];
        foreach ($rawWalls as $segment) {
            [$ax, $ay] = $toGrid($segment['x1'], $segment['y1']);
            [$bx, $by] = $toGrid($segment['x2'], $segment['y2']);

            foreach ($this->lineCells((int) floor($ax), (int) floor($ay), (int) floor($bx), (int) floor($by)) as [$cx, $cy]) {
                if ($cx < 0 || $cy < 0 || $cx >= $gridSize || $cy >= $gridSize) {
                    continue;
                }
                if ($occupancy[$cy][$cx]) {
                    continue;
                }
                $key = $cx.','.$cy;
                if (isset($walls[$key])) {
                    continue;
                }
                $walls[$key] = [$cx, $cy];
                if (count($walls) >= self::MAX_WALLS) {
                    break 2;
                }
            }
        }

        $thLevel = $this->sanitizeTownHallLevel($vision['town_hall_level'] ?? null, $rawBuildings);

        return [
            'grid_size' => $gridSize,
            'th_level' => $thLevel,
            'perspective' => $perspective,
            'corners_source' => $cornersSource,
            'buildings' => $buildings,
            'walls' => array_values($walls),
            'stats' => $this->buildStats($buildings, count($walls), $unplaced),
        ];
    }

    /**
     * تبدیل نقطهٔ تصویر (درصد) به مختصات پیوستهٔ شبکه با حل دستگاه دو مجهولی
     * P = T + u·(R−T) + v·(L−T)  →  (u·N, v·N)
     *
     * @param  array{top:array{x:float,y:float},right:array{x:float,y:float},left:array{x:float,y:float}}  $corners
     * @return array{0: float, 1: float}
     */
    public function isoToGrid(float $x, float $y, array $corners, int $gridSize = self::GRID_SIZE): array
    {
        $t = $corners['top'];
        $ax = $corners['right']['x'] - $t['x'];
        $ay = $corners['right']['y'] - $t['y'];
        $bx = $corners['left']['x'] - $t['x'];
        $by = $corners['left']['y'] - $t['y'];
        $dx = $x - $t['x'];
        $dy = $y - $t['y'];

        $det = $ax * $by - $ay * $bx;
        if (abs($det) < 1e-6) {
            return [$x / 100 * $gridSize, $y / 100 * $gridSize];
        }

        $u = ($dx * $by - $dy * $bx) / $det;
        $v = ($ax * $dy - $ay * $dx) / $det;

        return [$u * $gridSize, $v * $gridSize];
    }

    /**
     * تخمین گوشه‌های لوزی وقتی مدل آن‌ها را نداده است.
     *
     * اولویت با مقیاس تاون‌هال است (عرض اسپرایت TH = ۴ خانه)؛ در غیر این صورت
     * جعبهٔ محیطی همهٔ نقاط به‌عنوان کل نقشه در نظر گرفته می‌شود.
     */
    protected function estimateCorners(array $buildings, array $walls, ?array $thBox, ?array $imageSize, int $gridSize): ?array
    {
        $points = [];
        foreach ($buildings as $b) {
            $points[] = [$b['x'], $b['y']];
        }
        foreach ($walls as $w) {
            $points[] = [$w['x1'], $w['y1']];
            $points[] = [$w['x2'], $w['y2']];
        }

        if ($points === []) {
            return null;
        }

        [$imgW, $imgH] = $imageSize ?? [100.0, 100.0];
        $sx = $imgW / 100;
        $sy = $imgH / 100;

        $minX = $minY = PHP_FLOAT_MAX;
        $maxX = $maxY = -PHP_FLOAT_MAX;
        foreach ($points as [$px, $py]) {
            $minX = min($minX, $px * $sx);
            $maxX = max($maxX, $px * $sx);
            $minY = min($minY, $py * $sy);
            $maxY = max($maxY, $py * $sy);
        }

        $cx = ($minX + $maxX) / 2;
        $cy = ($minY + $maxY) / 2;

        if ($thBox !== null && $thBox['w'] > 0) {
            $thSize = $this->catalog->has('town_hall') ? $this->catalog->size('town_hall') : $this->catalog->size('builder_hall');
            $tileWidth = ($thBox['w'] * $sx) / $thSize;
            $halfWidth = $tileWidth * $gridSize / 2;
        } else {
            $w = max(1.0, $maxX - $minX);
            $h = max(1.0, $maxY - $minY);
            $halfWidth = ($w / 2 + $h) * 1.15;
        }

        $halfHeight = $halfWidth / 2;

        $pct = fn (float $px, float $py): array => ['x' => $px / $sx, 'y' => $py / $sy];

        return [
            'top' => $pct($cx, $cy - $halfHeight),
            'right' => $pct($cx + $halfWidth, $cy),
            'bottom' => $pct($cx, $cy + $halfHeight),
            'left' => $pct($cx - $halfWidth, $cy),
        ];
    }

    /**
     * جست‌وجوی مارپیچی برای نزدیک‌ترین جای خالی.
     *
     * @return array{0:int,1:int}|null
     */
    protected function findFreeSpot(array $occupancy, int $x0, int $y0, int $size, int $gridSize): ?array
    {
        if ($this->isFree($occupancy, $x0, $y0, $size, $gridSize)) {
            return [$x0, $y0];
        }

        for ($r = 1; $r <= self::SEARCH_RADIUS; $r++) {
            for ($dy = -$r; $dy <= $r; $dy++) {
                for ($dx = -$r; $dx <= $r; $dx++) {
                    if (max(abs($dx), abs($dy)) !== $r) {
                        continue;
                    }
                    $nx = $x0 + $dx;
                    $ny = $y0 + $dy;
                    if ($this->isFree($occupancy, $nx, $ny, $size, $gridSize)) {
                        return [$nx, $ny];
                    }
                }
            }
        }

        return null;
    }

    protected function isFree(array $occupancy, int $x0, int $y0, int $size, int $gridSize): bool
    {
        if ($x0 < 0 || $y0 < 0 || $x0 + $size > $gridSize || $y0 + $size > $gridSize) {
            return false;
        }

        for ($y = $y0; $y < $y0 + $size; $y++) {
            for ($x = $x0; $x < $x0 + $size; $x++) {
                if ($occupancy[$y][$x]) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function occupy(array &$occupancy, int $x0, int $y0, int $size): void
    {
        for ($y = $y0; $y < $y0 + $size; $y++) {
            for ($x = $x0; $x < $x0 + $size; $x++) {
                $occupancy[$y][$x] = true;
            }
        }
    }

    /**
     * الگوریتم Bresenham برای خانه‌های روی یک پاره‌خط.
     *
     * @return array<int, array{0:int,1:int}>
     */
    public function lineCells(int $x0, int $y0, int $x1, int $y1): array
    {
        $cells = [];
        $dx = abs($x1 - $x0);
        $dy = -abs($y1 - $y0);
        $sx = $x0 < $x1 ? 1 : -1;
        $sy = $y0 < $y1 ? 1 : -1;
        $err = $dx + $dy;

        $guard = 0;
        while ($guard++ < 500) {
            $cells[] = [$x0, $y0];
            if ($x0 === $x1 && $y0 === $y1) {
                break;
            }
            $e2 = 2 * $err;
            if ($e2 >= $dy) {
                $err += $dy;
                $x0 += $sx;
            }
            if ($e2 <= $dx) {
                $err += $dx;
                $y0 += $sy;
            }
        }

        return $cells;
    }

    protected function sanitizeBuildings(array $items): array
    {
        $out = [];
        $index = 0;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $type = $this->catalog->normalizeType($item['type'] ?? null);
            if ($type === null || $type === BuildingCatalog::WALL) {
                continue;
            }
            if (! isset($item['x'], $item['y']) || ! is_numeric($item['x']) || ! is_numeric($item['y'])) {
                continue;
            }

            $level = isset($item['level']) && is_numeric($item['level']) ? (int) $item['level'] : null;
            if ($level !== null && ($level < 1 || $level > 30)) {
                $level = null;
            }

            $out[] = [
                '_index' => $index++,
                'type' => $type,
                'x' => (float) $item['x'],
                'y' => (float) $item['y'],
                'level' => $level,
            ];
        }

        return $out;
    }

    protected function sanitizeWalls(array $items): array
    {
        $out = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            foreach (['x1', 'y1', 'x2', 'y2'] as $k) {
                if (! isset($item[$k]) || ! is_numeric($item[$k])) {
                    continue 2;
                }
            }
            $out[] = [
                'x1' => (float) $item['x1'],
                'y1' => (float) $item['y1'],
                'x2' => (float) $item['x2'],
                'y2' => (float) $item['y2'],
            ];
        }

        return $out;
    }

    protected function sanitizeCorners($corners): ?array
    {
        if (! is_array($corners)) {
            return null;
        }

        $out = [];
        foreach (['top', 'right', 'bottom', 'left'] as $name) {
            $c = $corners[$name] ?? null;
            if (! is_array($c) || ! isset($c['x'], $c['y']) || ! is_numeric($c['x']) || ! is_numeric($c['y'])) {
                return null;
            }
            $x = (float) $c['x'];
            $y = (float) $c['y'];
            [$lo, $hi] = self::CORNER_RANGE;
            if ($x < $lo || $x > $hi || $y < $lo || $y > $hi) {
                return null;
            }
            $out[$name] = ['x' => $x, 'y' => $y];
        }

        // لوزی باید مساحت معنادار داشته باشد.
        $ax = $out['right']['x'] - $out['top']['x'];
        $ay = $out['right']['y'] - $out['top']['y'];
        $bx = $out['left']['x'] - $out['top']['x'];
        $by = $out['left']['y'] - $out['top']['y'];
        if (abs($ax * $by - $ay * $bx) < 1.0) {
            return null;
        }

        return $out;
    }

    protected function sanitizeBox($box): ?array
    {
        if (! is_array($box)) {
            return null;
        }
        foreach (['x', 'y', 'w', 'h'] as $k) {
            if (! isset($box[$k]) || ! is_numeric($box[$k])) {
                return null;
            }
        }
        $w = (float) $box['w'];
        $h = (float) $box['h'];
        if ($w <= 0.5 || $h <= 0.5 || $w > 100 || $h > 100) {
            return null;
        }

        return ['x' => (float) $box['x'], 'y' => (float) $box['y'], 'w' => $w, 'h' => $h];
    }

    protected function sanitizeImageSize($size): ?array
    {
        if (! is_array($size) || count($size) < 2 || ! is_numeric($size[0]) || ! is_numeric($size[1])) {
            return null;
        }
        $w = (float) $size[0];
        $h = (float) $size[1];

        return ($w > 0 && $h > 0) ? [$w, $h] : null;
    }

    protected function sanitizeTownHallLevel($level, array $buildings): ?int
    {
        if (is_numeric($level)) {
            $level = (int) $level;
            if ($level >= 1 && $level <= 20) {
                return $level;
            }
        }

        foreach ($buildings as $b) {
            if (in_array($b['type'], ['town_hall', 'builder_hall'], true) && $b['level'] !== null) {
                return $b['level'];
            }
        }

        return null;
    }

    protected function buildStats(array $buildings, int $wallCount, int $unplaced): array
    {
        $byCategory = [];
        $byType = [];
        foreach ($buildings as $b) {
            if (! $b['placed']) {
                continue;
            }
            $byCategory[$b['category']] = ($byCategory[$b['category']] ?? 0) + 1;
            $byType[$b['type']] = ($byType[$b['type']] ?? 0) + 1;
        }

        return [
            'building_count' => count($buildings),
            'placed_count' => count($buildings) - $unplaced,
            'unplaced_count' => $unplaced,
            'wall_count' => $wallCount,
            'by_category' => $byCategory,
            'by_type' => $byType,
        ];
    }
}
