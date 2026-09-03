<?php

namespace App\Services\BaseClone;

/**
 * نگاشت قطعی خروجی Vision (درصد تصویر) به شبکهٔ ۴۴×۴۴ دهکده.
 *
 * ورودی: ساختمان‌ها (مرکز و در صورت وجود جعبهٔ اسپرایت)، قطعات دیوار، گوشه‌های لوزی و جعبهٔ
 * لوزی قابل‌مشاهده — همه به درصد تصویر — و ابعاد تصویر اصلی.
 * هندسه توسط GeometrySolver حل می‌شود (مقیاس از جعبه‌ها، محورها از دیوارها، مبدأ از
 * گوشه‌ها/لوزی/مرکز نقاط و قفل فاز روی شبکه). این کلاس فقط جای‌گذاری روی شبکه، رفع
 * هم‌پوشانی، سقف تعداد به ازای تاون‌هال (BuildingCaps)، اطمینان و پرچم‌ها را انجام می‌دهد.
 *
 * فرمول اطمینان هر ساختمان (قطعی، ۰ تا ۱):
 *   confidence = 0.55
 *              + 0.15 · (1 − min(1, shift / 2))            جابه‌جایی چبیشف از خانهٔ ایده‌آل
 *              + 0.15 · (1 − min(1, edge_of_tile / 0.5))   فاصلهٔ مختصات پیوسته تا مرکز خانه
 *              + 0.15 · box_plausibility                    ۱ = اندازهٔ جعبه با footprint می‌خواند، ۰٫۵ = جعبه ندارد، ۰ = ناسازگار
 *   cap_trimmed → حداکثر ۰٫۳؛ جانشده → حداکثر ۰٫۲.
 * سقف‌ها با «اطمینان پیش از جای‌گذاری» (shift = 0) اعمال می‌شوند تا ساختمان‌های اضافی جای
 * ساختمان‌های واقعی را نگیرند.
 *
 * uncertain = !placed || shift >= 2 || edge_of_tile || box_size_mismatch || cap_trimmed || altar_with_hero_hall
 */
class LayoutGridMapper
{
    public const GRID_SIZE = 44;

    public const MAX_WALLS = 400;

    public const VERSION = 2;

    /** پرچم‌های ممکن برای هر ساختمان. */
    public const FLAGS = ['moved', 'edge_of_tile', 'box_size_mismatch', 'cap_trimmed', 'unplaced', 'altar_with_hero_hall'];

    protected const SEARCH_RADIUS = 3;

    protected const HERO_ALTARS = ['barbarian_king', 'archer_queen', 'grand_warden', 'royal_champion', 'minion_prince'];

    public function __construct(protected BuildingCatalog $catalog) {}

    /**
     * @param  array  $vision  خروجی نرمال‌شدهٔ مدل Vision
     * @return array{grid_size:int,th_level:?int,perspective:string,corners_source:string,version:int,source:string,geometry:?array,warnings:array,buildings:array,walls:array,stats:array}
     */
    public function map(array $vision, int $gridSize = self::GRID_SIZE): array
    {
        $perspective = ($vision['perspective'] ?? 'isometric') === 'top_down' ? 'top_down' : 'isometric';
        $imageSize = $this->sanitizeImageSize($vision['image_size'] ?? null);
        [$imgW, $imgH] = $imageSize ?? [1000.0, 1000.0];

        $rawBuildings = $this->sanitizeBuildings($vision['buildings'] ?? []);
        $rawWalls = $this->sanitizeWalls($vision['walls'] ?? []);
        $thLevel = $this->sanitizeTownHallLevel($vision['town_hall_level'] ?? null, $rawBuildings);
        $village = $this->catalog->key();
        $warnings = [];

        // ۱) هندسه
        $solver = new GeometrySolver;
        $affine = null;
        $solved = [];
        $geometry = null;
        $cornersSource = 'linear';

        if ($perspective === 'isometric' && $rawBuildings !== []) {
            $thType = $this->catalog->has('town_hall') ? 'town_hall' : 'builder_hall';
            $geo = $solver->solve([
                'image_size' => $imageSize,
                'units' => 'pct',
                'grid_size' => $gridSize,
                'buildings' => array_map(fn ($b) => [
                    'x' => $b['x'], 'y' => $b['y'], 'size' => $this->catalog->size($b['type']), 'box' => $b['box'],
                ], $rawBuildings),
                'walls' => $rawWalls,
                'corners' => $vision['grid_corners'] ?? null,
                'diamond_box' => $vision['diamond_box'] ?? null,
                'town_hall_box' => $vision['town_hall_box'] ?? null,
                'th_size' => $this->catalog->size($thType),
            ]);

            if ($geo['ok']) {
                $affine = $geo['affine'];
                $solved = $geo['buildings'];
                $cornersSource = $geo['source'];
                $warnings = array_merge($warnings, $geo['warnings']);
                $geometry = [
                    'tile_px' => $geo['tile_px'],
                    'scale_source' => $geo['scale_source'],
                    'scale_candidates' => $geo['scale_candidates'],
                    'aspect' => $geo['aspect'],
                    'axis_slope' => [$geo['axis']['k1'], $geo['axis']['k2']],
                    'axis_source' => $geo['axis']['source'],
                    'axis_fixed' => $geo['axis']['fixed'],
                    'lattice_score' => $geo['lattice']['score'],
                    'lattice_scale_factor' => $geo['lattice']['scale_factor'],
                    'edge_flags' => $geo['edge_flags'],
                    'corners' => $geo['corners'],
                ];
            }
        }

        $toGrid = fn (float $x, float $y): array => $affine
            ? $solver->toGrid($affine, $x / 100 * $imgW, $y / 100 * $imgH)
            : [$x / 100 * $gridSize, $y / 100 * $gridSize];

        // ۲) مختصات پیوسته + اطمینان پیش از جای‌گذاری
        $entries = [];
        foreach ($rawBuildings as $raw) {
            $i = $raw['_index'];
            $size = $this->catalog->size($raw['type']);
            if (isset($solved[$i])) {
                $gu = $solved[$i]['u'];
                $gv = $solved[$i]['v'];
                $edge = $solved[$i]['edge_of_tile'];
                $edgeFlag = $solved[$i]['edge_flag'];
                $mismatch = $solved[$i]['box_size_mismatch'];
            } else {
                [$gu, $gv] = $toGrid($raw['x'], $raw['y']);
                $edge = max($this->distToInteger($gu - $size / 2), $this->distToInteger($gv - $size / 2));
                $edgeFlag = $edge >= GeometrySolver::EDGE_THRESHOLD;
                $mismatch = false;
            }
            $entries[$i] = [
                'raw' => $raw,
                'size' => $size,
                'u' => $gu,
                'v' => $gv,
                'edge' => $edge,
                'edge_flag' => $edgeFlag,
                'mismatch' => $mismatch,
                'pre_conf' => $this->confidence(0, $edge, $raw['box'] !== null, $mismatch),
                'trimmed' => false,
            ];
        }

        // ۳) سقف تعداد به ازای تاون‌هال (فقط دهکدهٔ اصلی، TH9+)
        // دروازهٔ اطمینان: اگر سطح ردیف تاون‌هال با «th» مدل نخواند، بیشینهٔ دو عدد مبنا می‌شود؛ و اگر
        // انواعی که در این TH سقف صفر دارند ≥ ۳ ساختمان واقعی را حذف کنند، TH پایین‌تر از واقعی خوانده شده
        // و سقف‌ها اعمال نمی‌شوند (th_unreliable تا رابط از مالک تأیید بگیرد).
        $capsTh = $thLevel;
        $thUnreliable = false;
        foreach ($rawBuildings as $raw) {
            if ($raw['type'] === 'town_hall' && $raw['level'] !== null && $thLevel !== null && $raw['level'] !== $thLevel) {
                $capsTh = max($thLevel, $raw['level']);
                $thUnreliable = true;
                break;
            }
        }
        $capsApplied = BuildingCaps::applies($capsTh, $village);
        if ($capsApplied) {
            $zeroCapHits = 0;
            foreach ($entries as $e) {
                if (BuildingCaps::max($e['raw']['type'], $capsTh) === 0) {
                    $zeroCapHits++;
                }
            }
            if ($zeroCapHits >= 3) {
                $capsApplied = false;
                $thUnreliable = true;
            }
        }
        if ($thUnreliable) {
            $warnings[] = 'th_unreliable';
        }
        if ($capsApplied) {
            $thLevel = $capsTh;
            $this->applyCaps($entries, $thLevel);
            $expected = BuildingCaps::total($thLevel);
            if ($expected !== null) {
                if (count($entries) > $expected + 5) {
                    $warnings[] = 'over_detection';
                } elseif (count($entries) < $expected - 15) {
                    $warnings[] = 'under_detection';
                }
            }
        }

        $hasHeroHall = false;
        foreach ($entries as $e) {
            if (! $e['trimmed'] && $e['raw']['type'] === 'hero_hall') {
                $hasHeroHall = true;
                break;
            }
        }

        // ۴) جای‌گذاری: بزرگ‌ترها اول تا کمتر جابه‌جا شوند
        $order = array_keys($entries);
        usort($order, function (int $a, int $b) use ($entries) {
            return $entries[$b]['size'] <=> $entries[$a]['size'] ?: $a <=> $b;
        });

        $occupancy = array_fill(0, $gridSize, array_fill(0, $gridSize, false));
        $buildings = [];

        foreach ($order as $i) {
            $e = $entries[$i];
            $raw = $e['raw'];
            $type = $raw['type'];
            $meta = $this->catalog->get($type);
            $size = $e['size'];

            $ix = (int) round($e['u'] - $size / 2);
            $iy = (int) round($e['v'] - $size / 2);
            $ix = max(0, min($gridSize - $size, $ix));
            $iy = max(0, min($gridSize - $size, $iy));

            $placed = false;
            $shift = 0;
            $x0 = $ix;
            $y0 = $iy;
            if (! $e['trimmed']) {
                $spot = $this->findFreeSpot($occupancy, $ix, $iy, $size, $gridSize);
                if ($spot !== null) {
                    [$x0, $y0] = $spot;
                    $this->occupy($occupancy, $x0, $y0, $size);
                    $placed = true;
                    $shift = max(abs($x0 - $ix), abs($y0 - $iy));
                }
            }

            $flags = [];
            if ($e['trimmed']) {
                $flags[] = 'cap_trimmed';
            } elseif (! $placed) {
                $flags[] = 'unplaced';
            }
            if ($shift >= 1) {
                $flags[] = 'moved';
            }
            if ($e['edge_flag']) {
                $flags[] = 'edge_of_tile';
            }
            if ($e['mismatch']) {
                $flags[] = 'box_size_mismatch';
            }
            if ($hasHeroHall && in_array($type, self::HERO_ALTARS, true)) {
                $flags[] = 'altar_with_hero_hall';
            }

            $confidence = $this->confidence($shift, $e['edge'], $raw['box'] !== null, $e['mismatch']);
            if ($e['trimmed']) {
                $confidence = min($confidence, 0.3);
            } elseif (! $placed) {
                $confidence = min($confidence, 0.2);
            }

            $uncertain = ! $placed
                || $shift >= 2
                || in_array('edge_of_tile', $flags, true)
                || $e['mismatch']
                || $e['trimmed']
                || in_array('altar_with_hero_hall', $flags, true);

            $entry = [
                'id' => $i + 1,
                'type' => $type,
                'label' => $meta['label'],
                'category' => $meta['category'],
                'color' => $meta['color'],
                'icon' => $meta['icon'],
                'size' => $size,
                'x' => $x0,
                'y' => $y0,
                'placed' => $placed,
                'confidence' => round($confidence, 2),
                'uncertain' => $uncertain,
                'flags' => $flags,
                'alternatives' => [],
                'shift' => $shift,
                'edge_of_tile' => round($e['edge'], 3),
                'grid_raw' => ['u' => round($e['u'], 2), 'v' => round($e['v'], 2)],
            ];
            if (array_key_exists('sprite', $meta)) {
                $entry['sprite'] = $meta['sprite'];
            }
            if ($raw['level'] !== null) {
                $entry['level'] = $raw['level'];
            }
            if ($raw['box'] !== null) {
                [$bx0, $by0, $bx1, $by1] = $raw['box'];
                $entry['raw'] = ['x' => round($bx0, 2), 'y' => round($by0, 2), 'w' => round($bx1 - $bx0, 2), 'h' => round($by1 - $by0, 2)];
            }

            $buildings[] = $entry;
        }

        usort($buildings, fn ($a, $b) => $a['id'] <=> $b['id']);

        // ۵) دیوارها. در بازی دیوار فقط در امتداد محورهای شبکه است؛ اگر اکثر پاره‌خط‌های مدل هم‌محور
        // باشند، بقیه (افقی/عمودی در تصویر یا طول صفر) نویز مدل‌اند و حذف می‌شوند و پاره‌خط‌های هم‌محور
        // دقیقاً روی محور می‌نشینند. در غیر این صورت (اسکیمای قدیمی/مدل متفاوت) رفتار قبلی حفظ می‌شود.
        $wallCap = self::MAX_WALLS;
        if ($capsApplied && BuildingCaps::wallCap($thLevel) !== null) {
            $wallCap = min($wallCap, BuildingCaps::wallCap($thLevel));
        }

        $segments = [];
        $onAxis = 0;
        $offAxis = 0;
        foreach ($rawWalls as $segment) {
            [$ax, $ay] = $toGrid($segment['x1'], $segment['y1']);
            [$bx, $by] = $toGrid($segment['x2'], $segment['y2']);
            $class = 'keep';
            if ($affine !== null) {
                $du = abs($bx - $ax);
                $dv = abs($by - $ay);
                $len = max($du, $dv);
                if ($len < 0.5) {
                    $class = 'degenerate';
                } elseif (min($du, $dv) > 1.5 && min($du, $dv) / $len > 0.25) {
                    $class = 'off_axis';
                    $offAxis++;
                } else {
                    $class = $du >= $dv ? 'u' : 'v';
                    $onAxis++;
                }
            }
            $segments[] = [$ax, $ay, $bx, $by, $class];
        }
        $filterWalls = $affine !== null && $onAxis >= 3 && $onAxis >= $offAxis;

        $walls = [];
        $droppedWalls = 0;
        foreach ($segments as [$ax, $ay, $bx, $by, $class]) {
            if ($filterWalls) {
                if ($class === 'degenerate' || $class === 'off_axis') {
                    $droppedWalls++;

                    continue;
                }
                // هم‌راستا کردن با محور: مختصات فرعی برای هر دو سر یکسان می‌شود.
                if ($class === 'u') {
                    $ay = $by = ($ay + $by) / 2;
                } else {
                    $ax = $bx = ($ax + $bx) / 2;
                }
            }

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
                if (count($walls) >= $wallCap) {
                    break 2;
                }
            }
        }
        if ($droppedWalls > 0) {
            $warnings[] = 'walls_dropped';
        }

        $extra = [
            'expected_total' => $capsApplied ? BuildingCaps::total($thLevel) : null,
            'walls_dropped' => $droppedWalls,
        ];

        return [
            'grid_size' => $gridSize,
            'th_level' => $thLevel,
            'perspective' => $perspective,
            'corners_source' => $cornersSource,
            'version' => self::VERSION,
            'source' => 'ai',
            'geometry' => $geometry,
            'warnings' => array_values(array_unique($warnings)),
            'buildings' => $buildings,
            'walls' => array_values($walls),
            'stats' => LayoutStats::build($buildings, count($walls), $extra),
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
     * اطمینان قطعی (فرمول در docblock کلاس).
     */
    public function confidence(int $shift, float $edge, bool $hasBox, bool $mismatch): float
    {
        $boxPlausibility = ! $hasBox ? 0.5 : ($mismatch ? 0.0 : 1.0);

        $c = 0.55
            + 0.15 * (1 - min(1.0, $shift / 2))
            + 0.15 * (1 - min(1.0, $edge / 0.5))
            + 0.15 * $boxPlausibility;

        return max(0.0, min(1.0, $c));
    }

    /**
     * اعمال سقف‌های BuildingCaps: در هر نوع، مطمئن‌ترین‌ها می‌مانند و بقیه trimmed می‌شوند
     * (داده حذف نمی‌شود؛ placed=false و پرچم cap_trimmed). سپس سقف گروهی ادغام‌ها: کم‌اطمینان‌ترین
     * عضو گروه (در تساوی، وزن بیشتر و اندیس بالاتر) حذف می‌شود تا مجموع وزنی به سقف برسد.
     *
     * @param  array<int, array>  $entries  با کلید _index؛ فیلد trimmed به‌روزرسانی می‌شود
     */
    protected function applyCaps(array &$entries, int $th): void
    {
        $byType = [];
        foreach ($entries as $i => $e) {
            $byType[$e['raw']['type']][] = $i;
        }

        $rank = fn (int $a, int $b) => $entries[$b]['pre_conf'] <=> $entries[$a]['pre_conf'] ?: $a <=> $b;

        foreach ($byType as $type => $indices) {
            $cap = BuildingCaps::max($type, $th);
            if ($cap === null || count($indices) <= $cap) {
                continue;
            }
            usort($indices, $rank);
            foreach (array_slice($indices, $cap) as $i) {
                $entries[$i]['trimmed'] = true;
            }
        }

        foreach (BuildingCaps::groups($th) as $group) {
            $guard = 0;
            while ($guard++ < 100) {
                $sum = 0;
                $members = [];
                foreach ($entries as $i => $e) {
                    $w = $group['weights'][$e['raw']['type']] ?? null;
                    if ($w === null || $e['trimmed']) {
                        continue;
                    }
                    $sum += $w;
                    $members[] = $i;
                }
                if ($sum <= $group['cap'] || $members === []) {
                    break;
                }
                usort($members, function (int $a, int $b) use ($entries, $group) {
                    $wa = $group['weights'][$entries[$a]['raw']['type']];
                    $wb = $group['weights'][$entries[$b]['raw']['type']];

                    return $entries[$a]['pre_conf'] <=> $entries[$b]['pre_conf'] ?: $wb <=> $wa ?: $b <=> $a;
                });
                $entries[$members[0]]['trimmed'] = true;
            }
        }
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

    protected function distToInteger(float $t): float
    {
        $f = $t - floor($t);

        return min($f, 1 - $f);
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

            $box = null;
            if (isset($item['box']) && is_array($item['box']) && count($item['box']) >= 4) {
                $b = array_values($item['box']);
                if (is_numeric($b[0]) && is_numeric($b[1]) && is_numeric($b[2]) && is_numeric($b[3])
                    && (float) $b[2] > (float) $b[0] && (float) $b[3] > (float) $b[1]) {
                    $box = [(float) $b[0], (float) $b[1], (float) $b[2], (float) $b[3]];
                }
            }

            $out[] = [
                '_index' => $index++,
                'type' => $type,
                'x' => (float) $item['x'],
                'y' => (float) $item['y'],
                'level' => $level,
                'box' => $box,
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
}
