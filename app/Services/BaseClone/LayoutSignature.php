<?php

namespace App\Services\BaseClone;

/**
 * امضای فشرده و قطعی یک چیدمان ۴۴×۴۴ برای تطبیق ساختاری با آرشیو نقشه‌ها.
 *
 * برخلاف dHash (که به زوم، برش، اسکین و پوشش UI حساس است) امضا از خودِ چیدمان ساخته می‌شود:
 *   - th / village / grid
 *   - cells: نوع ساختمان → فهرست مرتب [x, y] (گوشهٔ بالا-چپ footprint) فقط برای ساختمان‌های جای‌گرفته
 *   - wall_mask: بیت‌ست grid×grid دیوارها به‌صورت هگز (بیت y·grid+x، از MSB هر نیبل)
 *   - counts: شمارش هر نوع (+ wall)
 *
 * امتیاز (۰ تا ۱):
 *   - سطح هال باید بخواند: برای th ≥ ۱۲ دقیقاً برابر، در غیر این صورت ±۱ (تشخیص TH پایین‌تر
 *     برای مدل سخت‌تر است). اگر یکی از دو طرف TH ندارد، امتیاز حداکثر {@see UNKNOWN_TH_CAP} می‌شود.
 *   - نوع دهکده باید یکی باشد، وگرنه ۰.
 *   - ساختمان‌ها: در هر نوع، تطبیق حریصانهٔ نزدیک‌ترین جفت‌ها در شعاع چبیشف ±{@see POSITION_TOLERANCE}؛
 *     امتیاز = Σ تطبیق‌ها / Σ max(nA, nB) (کم‌شماری و بیش‌شماری هر دو جریمه می‌شوند).
 *   - دیوارها: IoU نرم با تحمل ±۱ خانه: (|A∩dilate(B)| + |B∩dilate(A)|) / (|A|+|B|).
 *     هر دو خالی → ۱؛ فقط یکی خالی → ۰.
 *   - ترکیب: {@see BUILDING_WEIGHT}·ساختمان + {@see WALL_WEIGHT}·دیوار.
 *   چون جابه‌جایی یک‌خانه‌ای کل شبکه خطای رایج Vision است، تطبیق با چند آفست جهانی (۰، اختلاف
 *   جای تاون‌هال دو طرف) انجام می‌شود و بیشترین امتیاز برمی‌گردد.
 *
 * آستانه‌ها: {@see CONFIDENT} (≥ ۰٫۸ = «همان بیس»، لینک بازی برگردانده می‌شود) و
 * {@see SIMILAR} (≥ ۰٫۶ = «مشابه احتمالی»، فقط به‌عنوان پیشنهاد نمایش داده می‌شود).
 */
class LayoutSignature
{
    public const VERSION = 1;

    /** امتیازی که «همان بیس» محسوب می‌شود. */
    public const CONFIDENT = 0.8;

    /** حداقل امتیاز برای نمایش به‌عنوان «مشابه احتمالی». */
    public const SIMILAR = 0.6;

    /** حداکثر امتیاز وقتی سطح هال یکی از دو طرف نامعلوم است (هرگز «مطمئن» نمی‌شود). */
    public const UNKNOWN_TH_CAP = 0.7;

    /** شعاع چبیشف تطبیق موقعیت ساختمان‌ها (خانه). */
    public const POSITION_TOLERANCE = 2;

    public const BUILDING_WEIGHT = 0.75;

    public const WALL_WEIGHT = 0.25;

    /** از این سطح به بالا TH باید دقیقاً برابر باشد. */
    public const STRICT_TH_FROM = 12;

    /**
     * ساخت امضا از خروجی LayoutGridMapper (یا چیدمان ویرایش‌شدهٔ کاربر با همان ساختار).
     *
     * @param  array  $layout  {grid_size, th_level, village, buildings:[{type,x,y,placed?,flags?}], walls:[[x,y],...]}
     * @return array{v:int,th:?int,village:string,grid:int,cells:array<string,array<int,array{0:int,1:int}>>,wall_mask:string,counts:array<string,int>}
     */
    public static function fromLayout(array $layout): array
    {
        $grid = max(8, (int) ($layout['grid_size'] ?? LayoutGridMapper::GRID_SIZE));
        $th = isset($layout['th_level']) && is_numeric($layout['th_level']) ? (int) $layout['th_level'] : null;
        $village = ($layout['village'] ?? 'home') === 'builder' ? 'builder' : 'home';

        $cells = [];
        foreach ($layout['buildings'] ?? [] as $b) {
            if (! is_array($b) || ! isset($b['type'], $b['x'], $b['y'])) {
                continue;
            }
            if (array_key_exists('placed', $b) && ! $b['placed']) {
                continue;
            }
            if (in_array('cap_trimmed', $b['flags'] ?? [], true)) {
                continue;
            }
            $type = (string) $b['type'];
            if ($type === '' || $type === BuildingCatalog::WALL) {
                continue;
            }
            $x = (int) $b['x'];
            $y = (int) $b['y'];
            if ($x < 0 || $y < 0 || $x >= $grid || $y >= $grid) {
                continue;
            }
            $cells[$type][] = [$x, $y];
        }

        ksort($cells, SORT_STRING);
        foreach ($cells as &$positions) {
            usort($positions, fn ($a, $b) => $a[0] <=> $b[0] ?: $a[1] <=> $b[1]);
        }
        unset($positions);

        $bits = array_fill(0, $grid * $grid, 0);
        $wallCount = 0;
        foreach ($layout['walls'] ?? [] as $w) {
            if (! is_array($w) || count($w) < 2) {
                continue;
            }
            $x = (int) ($w[0] ?? $w['x'] ?? -1);
            $y = (int) ($w[1] ?? $w['y'] ?? -1);
            if ($x < 0 || $y < 0 || $x >= $grid || $y >= $grid) {
                continue;
            }
            $idx = $y * $grid + $x;
            if ($bits[$idx] === 0) {
                $bits[$idx] = 1;
                $wallCount++;
            }
        }

        $counts = array_map('count', $cells);
        $counts['wall'] = $wallCount;

        return [
            'v' => self::VERSION,
            'th' => $th,
            'village' => $village,
            'grid' => $grid,
            'cells' => $cells,
            'wall_mask' => self::packMask($bits),
            'counts' => $counts,
        ];
    }

    /**
     * امتیاز شباهت دو امضا (۰ تا ۱).
     */
    public static function score(array $a, array $b): float
    {
        return self::compare($a, $b)['score'];
    }

    /**
     * مقایسهٔ تفصیلی دو امضا.
     *
     * @return array{score:float,buildings:float,walls:float,th_ok:bool,village_ok:bool,offset:array{0:int,1:int},capped:bool}
     */
    public static function compare(array $a, array $b): array
    {
        $result = [
            'score' => 0.0,
            'buildings' => 0.0,
            'walls' => 0.0,
            'th_ok' => false,
            'village_ok' => false,
            'offset' => [0, 0],
            'capped' => false,
        ];

        $villageA = $a['village'] ?? 'home';
        $villageB = $b['village'] ?? 'home';
        if ($villageA !== $villageB) {
            return $result;
        }
        $result['village_ok'] = true;

        $thA = isset($a['th']) ? (int) $a['th'] : null;
        $thB = isset($b['th']) ? (int) $b['th'] : null;
        $thKnown = $thA !== null && $thB !== null;
        if ($thKnown && ! self::townHallCompatible($thA, $thB)) {
            return $result;
        }
        $result['th_ok'] = $thKnown;
        $result['capped'] = ! $thKnown;

        $gridA = (int) ($a['grid'] ?? LayoutGridMapper::GRID_SIZE);
        $gridB = (int) ($b['grid'] ?? LayoutGridMapper::GRID_SIZE);
        if ($gridA !== $gridB) {
            return $result;
        }

        // چیدمان بدون ساختمان با هیچ چیز «مشابه» نیست (وگرنه دو امضای خالی امتیاز ۱ می‌گرفتند).
        if (self::buildingCount($a) === 0 || self::buildingCount($b) === 0) {
            return $result;
        }

        $best = null;
        foreach (self::candidateOffsets($a, $b) as $offset) {
            $buildings = self::buildingScore($a['cells'] ?? [], $b['cells'] ?? [], $offset);
            $walls = self::wallScore($a, $b, $offset);
            $score = self::BUILDING_WEIGHT * $buildings + self::WALL_WEIGHT * $walls;
            if ($best === null || $score > $best['score']) {
                $best = ['score' => $score, 'buildings' => $buildings, 'walls' => $walls, 'offset' => $offset];
            }
        }

        if ($best !== null) {
            $result = array_merge($result, $best);
        }

        if (! $thKnown) {
            $result['score'] = min($result['score'], self::UNKNOWN_TH_CAP);
        }

        $result['score'] = round(max(0.0, min(1.0, $result['score'])), 4);
        $result['buildings'] = round($result['buildings'], 4);
        $result['walls'] = round($result['walls'], 4);

        return $result;
    }

    /**
     * آیا دو سطح هال قابل تطبیق‌اند؟ (≥ ۱۲ دقیقاً برابر، در غیر این صورت ±۱)
     */
    public static function townHallCompatible(int $thA, int $thB): bool
    {
        if ($thA === $thB) {
            return true;
        }
        if (max($thA, $thB) >= self::STRICT_TH_FROM) {
            return false;
        }

        return abs($thA - $thB) <= 1;
    }

    /**
     * تعداد ساختمان‌های (غیر دیوار) یک امضا.
     */
    public static function buildingCount(array $signature): int
    {
        $n = 0;
        foreach ($signature['cells'] ?? [] as $positions) {
            $n += is_array($positions) ? count($positions) : 0;
        }

        return $n;
    }

    public static function isConfident(float $score): bool
    {
        return $score >= self::CONFIDENT;
    }

    public static function isSimilar(float $score): bool
    {
        return $score >= self::SIMILAR;
    }

    /**
     * آفست‌های جهانی که امتحان می‌شوند: بدون جابه‌جایی و (در صورت وجود یک تاون‌هال/بیلدرهال در هر
     * دو طرف) اختلاف موقعیت آن‌ها.
     *
     * @return array<int, array{0:int,1:int}>
     */
    protected static function candidateOffsets(array $a, array $b): array
    {
        $offsets = [[0, 0]];
        foreach (['town_hall', 'builder_hall'] as $anchor) {
            $pa = $a['cells'][$anchor] ?? [];
            $pb = $b['cells'][$anchor] ?? [];
            if (count($pa) === 1 && count($pb) === 1) {
                $dx = (int) $pa[0][0] - (int) $pb[0][0];
                $dy = (int) $pa[0][1] - (int) $pb[0][1];
                if (($dx !== 0 || $dy !== 0) && abs($dx) <= 6 && abs($dy) <= 6) {
                    $offsets[] = [$dx, $dy];
                }
            }
        }

        return $offsets;
    }

    /**
     * تطبیق حریصانهٔ موقعیت‌ها در هر نوع (نزدیک‌ترین جفت‌ها اول) در شعاع POSITION_TOLERANCE.
     *
     * @param  array{0:int,1:int}  $offset  به موقعیت‌های B اضافه می‌شود
     */
    protected static function buildingScore(array $cellsA, array $cellsB, array $offset): float
    {
        $types = array_unique(array_merge(array_keys($cellsA), array_keys($cellsB)));
        $matched = 0;
        $total = 0;

        foreach ($types as $type) {
            $pa = $cellsA[$type] ?? [];
            $pb = $cellsB[$type] ?? [];
            $total += max(count($pa), count($pb));
            if ($pa === [] || $pb === []) {
                continue;
            }

            $pairs = [];
            foreach ($pa as $i => $p) {
                foreach ($pb as $j => $q) {
                    $d = max(abs((int) $p[0] - ((int) $q[0] + $offset[0])), abs((int) $p[1] - ((int) $q[1] + $offset[1])));
                    if ($d <= self::POSITION_TOLERANCE) {
                        $pairs[] = [$d, $i, $j];
                    }
                }
            }
            usort($pairs, fn ($x, $y) => $x[0] <=> $y[0] ?: $x[1] <=> $y[1] ?: $x[2] <=> $y[2]);

            $usedA = [];
            $usedB = [];
            foreach ($pairs as [$d, $i, $j]) {
                if (isset($usedA[$i]) || isset($usedB[$j])) {
                    continue;
                }
                $usedA[$i] = true;
                $usedB[$j] = true;
                $matched++;
            }
        }

        return $total === 0 ? 1.0 : $matched / $total;
    }

    /**
     * IoU نرم دیوارها با تحمل ±۱ خانه.
     */
    protected static function wallScore(array $a, array $b, array $offset): float
    {
        $grid = (int) ($a['grid'] ?? LayoutGridMapper::GRID_SIZE);
        $setA = self::unpackMask((string) ($a['wall_mask'] ?? ''), $grid);
        $setB = self::unpackMask((string) ($b['wall_mask'] ?? ''), $grid);

        if ($offset !== [0, 0]) {
            $shifted = [];
            foreach ($setB as $key => $_) {
                [$x, $y] = explode(',', $key);
                $nx = (int) $x + $offset[0];
                $ny = (int) $y + $offset[1];
                if ($nx >= 0 && $ny >= 0 && $nx < $grid && $ny < $grid) {
                    $shifted[$nx.','.$ny] = true;
                }
            }
            $setB = $shifted;
        }

        $nA = count($setA);
        $nB = count($setB);
        if ($nA === 0 && $nB === 0) {
            return 1.0;
        }
        if ($nA === 0 || $nB === 0) {
            return 0.0;
        }

        $hit = 0;
        foreach ($setA as $key => $_) {
            if (self::nearWall($key, $setB)) {
                $hit++;
            }
        }
        foreach ($setB as $key => $_) {
            if (self::nearWall($key, $setA)) {
                $hit++;
            }
        }

        return $hit / ($nA + $nB);
    }

    /**
     * @param  array<string, bool>  $set
     */
    protected static function nearWall(string $key, array $set): bool
    {
        [$x, $y] = array_map('intval', explode(',', $key));
        for ($dy = -1; $dy <= 1; $dy++) {
            for ($dx = -1; $dx <= 1; $dx++) {
                if (isset($set[($x + $dx).','.($y + $dy)])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<int, int>  $bits
     */
    public static function packMask(array $bits): string
    {
        $hex = '';
        $n = count($bits);
        for ($i = 0; $i < $n; $i += 4) {
            $nibble = 0;
            for ($k = 0; $k < 4; $k++) {
                $nibble = ($nibble << 1) | (($bits[$i + $k] ?? 0) ? 1 : 0);
            }
            $hex .= dechex($nibble);
        }

        return $hex;
    }

    /**
     * @return array<string, bool>  کلید "x,y" برای هر خانهٔ دیوار
     */
    public static function unpackMask(string $hex, int $grid): array
    {
        $set = [];
        $len = strlen($hex);
        for ($i = 0; $i < $len; $i++) {
            $nibble = hexdec($hex[$i]);
            if ($nibble === 0) {
                continue;
            }
            for ($k = 0; $k < 4; $k++) {
                if ($nibble & (8 >> $k)) {
                    $idx = $i * 4 + $k;
                    $x = $idx % $grid;
                    $y = intdiv($idx, $grid);
                    if ($y < $grid) {
                        $set[$x.','.$y] = true;
                    }
                }
            }
        }

        return $set;
    }
}
