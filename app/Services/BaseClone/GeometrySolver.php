<?php

namespace App\Services\BaseClone;

/**
 * حل هندسهٔ ایزومتریک بیس به صورت قطعی (بدون مدل زبانی).
 *
 * ورودی: جعبه‌های ساختمان‌ها (اختیاری)، اندازهٔ footprint هر ساختمان از کاتالوگ، پاره‌خط‌های
 * دیوار، گوشه‌های لوزی (اختیاری)، جعبهٔ محیطی لوزی قابل‌مشاهده (اختیاری) و ابعاد تصویر.
 *
 * خروجی: تبدیل آفین پیکسل → مختصات پیوستهٔ شبکه  P = O + u·A + v·B  به همراه مختصات (u,v)
 * مرکز زمینیِ هر ساختمان، فاصلهٔ آن تا مرکز خانه (edge_of_tile) و پرچم ناسازگاری اندازهٔ جعبه.
 *
 * مراحل:
 *  1. مقیاس خانه s = میانهٔ مقاوم (x1−x0)/N روی جعبه‌ها (پرت‌ها حذف و box_size_mismatch علامت می‌خورند).
 *  2. جهت محورها از شیب پاره‌خط‌های دیوار: اگر میانهٔ |dy/dx| در ±۱۰٪ از ۰٫۵ باشد محورهای ثابت ۲:۱،
 *     وگرنه شیب هر خانواده با کمترین مربعات (اسکرین‌شات کشیده/رندر با زاویهٔ دیگر).
 *  3. مبدأ: گوشه‌های معتبرشده (بررسی لوزی بودن، هم‌خوانی مقیاس و نسبت) → جعبهٔ لوزی «d» → مرکز نقاط.
 *  4. مرکز زمینی هر ساختمان = (cx, y1 − N·s·(k1+k2)/4)؛ برای ۲:۱ همان y1 − N·s/4.
 *  5. قفل فاز: انتخاب آفست زیرخانه‌ای (و در صورت سیگنال قوی، مقیاس در ±۱۵٪) که میانگین فاصلهٔ
 *     ساختمان‌ها تا مرکز خانه‌ها را کمینه کند.
 */
class GeometrySolver
{
    public const DEFAULT_SLOPE = 0.5;

    public const EDGE_THRESHOLD = 0.35;

    /** پرت‌های مقیاس (نسبت به میانه) که در برآورد مقیاس نادیده گرفته می‌شوند. */
    protected const SCALE_OUTLIER = [0.6, 1.6];

    /** خارج از این بازه، اندازهٔ جعبه با footprint نوع ادعاشده نمی‌خواند. */
    protected const SIZE_MISMATCH = [0.72, 1.4];

    protected const MIN_LATTICE_POINTS = 8;

    protected const LATTICE_MIN_SCORE = 0.3;

    protected const LATTICE_SCALE_GAIN = 0.05;

    protected const CORNER_TOLERANCE = 0.04;

    /** بیشینهٔ اختلاف نسبی مقیاس جعبه‌ها با مقیاس لوزی/گوشه‌ها پیش از آن‌که جعبه‌ها کنار گذاشته شوند. */
    protected const SCALE_AGREEMENT = 0.12;

    /**
     * @param  array{
     *     image_size?: array{0: float, 1: float}|null,
     *     units?: string,
     *     grid_size?: int,
     *     buildings: array<int, array{x: float, y: float, size: int, box?: array<int, float>|null}>,
     *     walls?: array<int, array{x1: float, y1: float, x2: float, y2: float}>,
     *     corners?: array<string, array{x: float, y: float}>|null,
     *     diamond_box?: array<int, float>|null,
     *     town_hall_box?: array{x: float, y: float, w: float, h: float}|null,
     *     th_size?: int,
     *     refine_scale?: bool,
     * }  $input
     * @return array{
     *     ok: bool,
     *     source: ?string,
     *     affine: ?array{origin: array{0: float, 1: float}, a: array{0: float, 1: float}, b: array{0: float, 1: float}},
     *     tile_px: ?float,
     *     scale_source: ?string,
     *     scale_candidates: array<string, float>,
     *     axis: array{k1: float, k2: float, source: string, fixed: bool},
     *     aspect: ?float,
     *     lattice: array{score_before: ?float, score: ?float, du: float, dv: float, scale_factor: float, applied: bool},
     *     edge_flags: bool,
     *     buildings: array<int, array{u: float, v: float, edge_of_tile: float, edge_flag: bool, box_size_mismatch: bool, box_ratio: ?float}>,
     *     corners: ?array<string, array{x: float, y: float}>,
     *     warnings: array<int, string>
     * }
     */
    public function solve(array $input): array
    {
        $gridSize = max(1, (int) ($input['grid_size'] ?? LayoutGridMapper::GRID_SIZE));
        [$imgW, $imgH] = $this->imageSize($input['image_size'] ?? null);
        $units = $input['units'] ?? 'pct';
        $px = $this->unitConverter($units, $imgW, $imgH);
        $warnings = [];

        $buildings = [];
        foreach ($input['buildings'] ?? [] as $i => $b) {
            $size = max(1, (int) ($b['size'] ?? 1));
            $box = null;
            if (isset($b['box']) && is_array($b['box']) && count($b['box']) >= 4) {
                $bx = array_values($b['box']);
                $box = [$px($bx[0], 'x'), $px($bx[1], 'y'), $px($bx[2], 'x'), $px($bx[3], 'y')];
                if ($box[2] <= $box[0] || $box[3] <= $box[1]) {
                    $box = null;
                }
            }
            $buildings[$i] = [
                'x' => $px($b['x'], 'x'),
                'y' => $px($b['y'], 'y'),
                'size' => $size,
                'box' => $box,
            ];
        }

        $walls = [];
        foreach ($input['walls'] ?? [] as $w) {
            $walls[] = [$px($w['x1'], 'x'), $px($w['y1'], 'y'), $px($w['x2'], 'x'), $px($w['y2'], 'y')];
        }

        $points = [];
        foreach ($buildings as $b) {
            $points[] = [$b['x'], $b['y']];
        }
        foreach ($walls as $w) {
            $points[] = [$w[0], $w[1]];
            $points[] = [$w[2], $w[3]];
        }

        $empty = [
            'ok' => false, 'source' => null, 'affine' => null, 'tile_px' => null, 'scale_source' => null,
            'scale_candidates' => [], 'axis' => ['k1' => self::DEFAULT_SLOPE, 'k2' => self::DEFAULT_SLOPE, 'source' => 'default', 'fixed' => true],
            'aspect' => null, 'lattice' => ['score_before' => null, 'score' => null, 'du' => 0.0, 'dv' => 0.0, 'scale_factor' => 1.0, 'applied' => false],
            'edge_flags' => false, 'buildings' => [], 'corners' => null, 'warnings' => $warnings,
        ];

        if ($points === []) {
            return $empty;
        }

        $corners = $this->cornersToPx($input['corners'] ?? null, $px);
        $diamond = $this->diamondToPx($input['diamond_box'] ?? null, $px, $imgW);
        if (($input['diamond_box'] ?? null) !== null && $diamond === null) {
            $warnings[] = 'diamond_rejected';
        }
        $thBox = $this->thBoxToPx($input['town_hall_box'] ?? null, $px);
        $thSize = max(1, (int) ($input['th_size'] ?? 4));

        // ۱) مقیاس از جعبه‌ها
        $scaleCandidates = [];
        [$boxScale, $boxInfo] = $this->scaleFromBoxes($buildings);
        if ($boxScale !== null) {
            $scaleCandidates['boxes'] = $boxScale;
        }

        // ۲) محورها از دیوارها
        $axis = $this->axisFromWalls($walls, $boxScale);

        // اعتماد به جعبه‌ها پیش از اعتبارسنجی گوشه‌ها: مقیاس مرجع (گوشه‌های هم‌محور، وگرنه لوزی «d»)
        // اول محاسبه می‌شود؛ اگر میانهٔ جعبه‌ها بیش از SCALE_AGREEMENT با آن اختلاف داشته باشد (مدل‌هایی
        // که جعبهٔ ثابت و کوچک برای همه می‌دهند)، جعبه‌ها کنار گذاشته می‌شوند و گوشه‌ها با آن‌ها سنجیده نمی‌شوند.
        $cornerShapeOk = $corners !== null && $this->validateCorners($corners, $gridSize, null, $axis);
        $referenceScale = null;
        if ($cornerShapeOk) {
            $referenceScale = ($corners['right'][0] - $corners['left'][0]) / $gridSize;
        } elseif ($diamond !== null) {
            $referenceScale = ($diamond[2] - $diamond[0]) / $gridSize;
        }
        $boxesTrusted = true;
        if ($boxScale !== null && $referenceScale !== null
            && abs($boxScale - $referenceScale) / $referenceScale > self::SCALE_AGREEMENT) {
            $boxesTrusted = false;
            $warnings[] = 'box_scale_inconsistent';
        }

        // اعتبارسنجی گوشه‌ها و لوزی (مقیاس جعبه‌ها فقط وقتی قابل اعتماد است)
        if ($corners !== null && (! $cornerShapeOk || ! $this->validateCorners($corners, $gridSize, $boxesTrusted ? $boxScale : null, $axis))) {
            $warnings[] = 'corners_rejected';
            $corners = null;
        }
        if ($corners !== null) {
            $scaleCandidates['corners'] = ($corners['right'][0] - $corners['left'][0]) / $gridSize;
        }
        if ($diamond !== null) {
            $scaleCandidates['diamond'] = ($diamond[2] - $diamond[0]) / $gridSize;
        }
        if ($thBox !== null) {
            $scaleCandidates['town_hall'] = $thBox['w'] / $thSize;
        }

        // جعبهٔ محیطی همهٔ نقاط (برای مرکز و مقیاس جایگزین)
        $minX = $minY = PHP_FLOAT_MAX;
        $maxX = $maxY = -PHP_FLOAT_MAX;
        foreach ($points as [$qx, $qy]) {
            $minX = min($minX, $qx);
            $maxX = max($maxX, $qx);
            $minY = min($minY, $qy);
            $maxY = max($maxY, $qy);
        }
        $spreadW = max(1.0, $maxX - $minX);
        $spreadH = max(1.0, $maxY - $minY);
        $scaleCandidates['bounding_box'] = 2 * (($spreadW / 2 + $spreadH) * 1.15) / $gridSize;

        // انتخاب مقیاس و منبع. میانهٔ جعبه‌ها اولویت دارد، مگر آن‌که پیش‌تر نامعتبر تشخیص داده شده باشد
        // (اختلاف بیش از ۱۲٪ با گوشه‌ها/لوزی)؛ در آن صورت مقیاس مرجع به کار می‌رود.
        $reference = $corners !== null ? 'corners' : ($diamond !== null ? 'diamond' : null);
        if ($boxScale !== null && ! $boxesTrusted && $reference !== null) {
            $tile = $scaleCandidates[$reference];
            $scaleSource = $reference;
        } elseif ($boxScale !== null) {
            $tile = $boxScale;
            $scaleSource = 'boxes';
        } elseif ($corners !== null) {
            $tile = $scaleCandidates['corners'];
            $scaleSource = 'corners';
        } elseif ($diamond !== null) {
            $tile = $scaleCandidates['diamond'];
            $scaleSource = 'diamond';
        } elseif ($thBox !== null) {
            $tile = $scaleCandidates['town_hall'];
            $scaleSource = 'town_hall';
        } else {
            $tile = $scaleCandidates['bounding_box'];
            $scaleSource = 'bounding_box';
        }
        $tile = max(1e-6, $tile);

        // محورها وقتی دیوار نداریم: از گوشه‌ها، لوزی یا پیش‌فرض ۲:۱
        if ($axis['source'] === 'default') {
            if ($corners !== null) {
                $t = $corners['top'];
                $r = $corners['right'];
                $l = $corners['left'];
                $k1 = ($r[1] - $t[1]) / max(1e-6, $r[0] - $t[0]);
                $k2 = ($l[1] - $t[1]) / max(1e-6, $t[0] - $l[0]);
                $axis = ['k1' => $k1, 'k2' => $k2, 'source' => 'corners', 'fixed' => false];
            } elseif ($diamond !== null) {
                $k = ($diamond[3] - $diamond[1]) / max(1e-6, $diamond[2] - $diamond[0]);
                $axis = ['k1' => $k, 'k2' => $k, 'source' => 'diamond', 'fixed' => false];
            }
        }

        // ۳) مبدأ / مرکز مرجع
        if ($corners !== null) {
            $source = 'model';
            $centre = [($corners['top'][0] + $corners['bottom'][0]) / 2, ($corners['top'][1] + $corners['bottom'][1]) / 2];
        } elseif ($diamond !== null) {
            $source = 'diamond';
            $centre = [($diamond[0] + $diamond[2]) / 2, ($diamond[1] + $diamond[3]) / 2];
        } else {
            $source = match ($scaleSource) {
                'boxes' => 'fitted',
                'town_hall' => 'town_hall_scale',
                default => 'bounding_box',
            };
            $centre = [($minX + $maxX) / 2, ($minY + $maxY) / 2];
        }

        // محورهای واحد (یک خانه): گوشه‌های معتبر مستقیماً، وگرنه از مقیاس + شیب
        $axesFor = function (float $s) use ($corners, $gridSize, $axis, $source): array {
            if ($source === 'model' && $corners !== null) {
                $t = $corners['top'];
                $a = [($corners['right'][0] - $t[0]) / $gridSize, ($corners['right'][1] - $t[1]) / $gridSize];
                $b = [($corners['left'][0] - $t[0]) / $gridSize, ($corners['left'][1] - $t[1]) / $gridSize];
                $base = ($corners['right'][0] - $corners['left'][0]) / $gridSize;
                $f = $s / max(1e-6, $base);

                return [[$a[0] * $f, $a[1] * $f], [$b[0] * $f, $b[1] * $f]];
            }

            return [[$s / 2, $axis['k1'] * $s / 2], [-$s / 2, $axis['k2'] * $s / 2]];
        };

        // ۴) مراکز زمینی. اگر جعبه‌ها با مقیاس لوزی نمی‌خوانند (جعبهٔ هم‌اندازه و کوچک برای همه)،
        // کف جعبه کف اسپرایت نیست و مرکز جعبه برآورد بهتری از مرکز زمینی است.
        $half = $gridSize / 2;
        $ground = [];
        foreach ($buildings as $i => $b) {
            $ground[$i] = $this->groundCentre($b, $tile, $axis, $boxesTrusted);
        }

        $project = function (float $s) use ($axesFor, $centre, $half, $ground, $buildings): array {
            [$a, $b] = $axesFor($s);
            $out = [];
            foreach ($ground as $i => [$gx, $gy]) {
                [$u, $v] = $this->invert($a, $b, $gx - $centre[0], $gy - $centre[1]);
                $out[$i] = [$u + $half - $buildings[$i]['size'] / 2, $v + $half - $buildings[$i]['size'] / 2];
            }

            return $out;
        };

        // ۵) قفل فاز (و مقیاس)
        $lattice = ['score_before' => null, 'score' => null, 'du' => 0.0, 'dv' => 0.0, 'scale_factor' => 1.0, 'applied' => false];
        $scaleFactor = 1.0;
        if (count($buildings) >= self::MIN_LATTICE_POINTS) {
            $base = $this->latticeFit($project(1.0 * $tile));
            $lattice['score_before'] = round($base['score_raw'], 3);
            $best = $base;
            $bestFactor = 1.0;

            if (($input['refine_scale'] ?? true) && $scaleSource !== 'bounding_box') {
                for ($f = 0.85; $f <= 1.1501; $f += 0.005) {
                    $fit = $this->latticeFit($project($tile * $f));
                    if ($fit['score'] > $best['score'] + 1e-9) {
                        $best = $fit;
                        $bestFactor = $f;
                    }
                }
                if ($bestFactor !== 1.0 && ($best['score'] < self::LATTICE_MIN_SCORE || $best['score'] - $base['score'] < self::LATTICE_SCALE_GAIN)) {
                    $best = $base;
                    $bestFactor = 1.0;
                }
            }

            $scaleFactor = $bestFactor;
            $lattice['score'] = round($best['score'], 3);
            $lattice['du'] = $best['du'];
            $lattice['dv'] = $best['dv'];
            $lattice['scale_factor'] = round($bestFactor, 3);
            $lattice['applied'] = true;
        }

        $tile *= $scaleFactor;
        [$a, $b] = $axesFor($tile);
        $du = $lattice['du'];
        $dv = $lattice['dv'];

        // مبدأ: مرکز مرجع ↔ مرکز شبکه، سپس آفست فاز
        $origin = [
            $centre[0] - $half * ($a[0] + $b[0]) - $du * $a[0] - $dv * $b[0],
            $centre[1] - $half * ($a[1] + $b[1]) - $du * $a[1] - $dv * $b[1],
        ];
        $affine = ['origin' => $origin, 'a' => $a, 'b' => $b];

        // مختصات نهایی + مرکزسازی صحیح برای منابع بدون لنگر مطلق
        $out = [];
        $uMin = $vMin = PHP_FLOAT_MAX;
        $uMax = $vMax = -PHP_FLOAT_MAX;
        foreach ($buildings as $i => $bld) {
            [$u, $v] = $this->toGrid($affine, $ground[$i][0], $ground[$i][1]);
            $out[$i] = ['u' => $u, 'v' => $v];
            $n = $bld['size'];
            $uMin = min($uMin, $u - $n / 2);
            $uMax = max($uMax, $u + $n / 2);
            $vMin = min($vMin, $v - $n / 2);
            $vMax = max($vMax, $v + $n / 2);
        }

        if (in_array($source, ['fitted', 'bounding_box', 'town_hall_scale'], true) && $out !== []) {
            $ku = (int) round(($gridSize - ($uMin + $uMax)) / 2);
            $kv = (int) round(($gridSize - ($vMin + $vMax)) / 2);
            if ($ku !== 0 || $kv !== 0) {
                $affine['origin'] = [
                    $affine['origin'][0] - $ku * $a[0] - $kv * $b[0],
                    $affine['origin'][1] - $ku * $a[1] - $kv * $b[1],
                ];
                foreach ($out as $i => $uv) {
                    $out[$i] = ['u' => $uv['u'] + $ku, 'v' => $uv['v'] + $kv];
                }
            }
        }

        // پرچم «لبهٔ خانه» فقط وقتی معنا دارد که فاز شبکه واقعاً قفل شده باشد (وگرنه فاصله تا مرکز خانه تصادفی است).
        $edgeFlags = ($lattice['score'] ?? 0) >= self::LATTICE_MIN_SCORE;
        foreach ($buildings as $i => $bld) {
            $n = $bld['size'];
            $eu = $this->distToInteger($out[$i]['u'] - $n / 2);
            $ev = $this->distToInteger($out[$i]['v'] - $n / 2);
            $out[$i]['edge_of_tile'] = round(max($eu, $ev), 3);
            $out[$i]['edge_flag'] = $edgeFlags && max($eu, $ev) >= self::EDGE_THRESHOLD;
            $out[$i]['box_size_mismatch'] = $boxInfo[$i]['mismatch'] ?? false;
            $out[$i]['box_ratio'] = isset($boxInfo[$i]['ratio']) ? round($boxInfo[$i]['ratio'], 3) : null;
        }

        $o = $affine['origin'];
        $cornersOut = [
            'top' => $o,
            'right' => [$o[0] + $gridSize * $a[0], $o[1] + $gridSize * $a[1]],
            'bottom' => [$o[0] + $gridSize * ($a[0] + $b[0]), $o[1] + $gridSize * ($a[1] + $b[1])],
            'left' => [$o[0] + $gridSize * $b[0], $o[1] + $gridSize * $b[1]],
        ];
        $toPct = fn (array $p): array => ['x' => round($p[0] / $imgW * 100, 3), 'y' => round($p[1] / $imgH * 100, 3)];

        return [
            'ok' => true,
            'source' => $source,
            'affine' => $affine,
            'tile_px' => round($tile, 3),
            'scale_source' => $scaleSource,
            'scale_candidates' => array_map(fn ($v) => round($v, 3), $scaleCandidates),
            'axis' => ['k1' => round($axis['k1'], 4), 'k2' => round($axis['k2'], 4), 'source' => $axis['source'], 'fixed' => $axis['fixed']],
            'aspect' => round(($axis['k1'] + $axis['k2']) / 2, 4),
            'lattice' => $lattice,
            'edge_flags' => $edgeFlags,
            'buildings' => $out,
            'corners' => array_map($toPct, $cornersOut),
            'warnings' => $warnings,
        ];
    }

    /**
     * پیکسل → مختصات پیوستهٔ شبکه با حل دستگاه P = O + u·A + v·B.
     *
     * @param  array{origin: array{0: float, 1: float}, a: array{0: float, 1: float}, b: array{0: float, 1: float}}  $affine
     * @return array{0: float, 1: float}
     */
    public function toGrid(array $affine, float $x, float $y): array
    {
        return $this->invert($affine['a'], $affine['b'], $x - $affine['origin'][0], $y - $affine['origin'][1]);
    }

    /**
     * @return array{0: float, 1: float}
     */
    protected function invert(array $a, array $b, float $dx, float $dy): array
    {
        $det = $a[0] * $b[1] - $a[1] * $b[0];
        if (abs($det) < 1e-9) {
            return [0.0, 0.0];
        }

        return [
            ($dx * $b[1] - $dy * $b[0]) / $det,
            ($a[0] * $dy - $a[1] * $dx) / $det,
        ];
    }

    /**
     * مرکز زمینی: وسط جعبه در x و «کف» جعبه منهای نصف ارتفاع لوزی footprint در y.
     *
     * @return array{0: float, 1: float}
     */
    protected function groundCentre(array $b, float $tile, array $axis, bool $boxTrusted = true): array
    {
        if ($b['box'] === null) {
            return [$b['x'], $b['y']];
        }
        [$x0, $y0, $x1, $y1] = $b['box'];
        if (! $boxTrusted) {
            return [($x0 + $x1) / 2, ($y0 + $y1) / 2];
        }
        $footprintHeight = $b['size'] * $tile * ($axis['k1'] + $axis['k2']) / 2;

        return [($x0 + $x1) / 2, $y1 - $footprintHeight / 2];
    }

    /**
     * میانهٔ مقاوم (x1−x0)/N روی جعبه‌ها؛ پرت‌ها حذف و ناسازگاری اندازه علامت می‌خورد.
     *
     * @return array{0: ?float, 1: array<int, array{ratio: float, mismatch: bool}>}
     */
    protected function scaleFromBoxes(array $buildings): array
    {
        $samples = [];
        foreach ($buildings as $i => $b) {
            if ($b['box'] === null) {
                continue;
            }
            $samples[$i] = ($b['box'][2] - $b['box'][0]) / $b['size'];
        }
        if (count($samples) < 3) {
            return [null, []];
        }

        $median = $this->median(array_values($samples));
        $kept = [];
        foreach ($samples as $i => $s) {
            $r = $s / $median;
            if ($r >= self::SCALE_OUTLIER[0] && $r <= self::SCALE_OUTLIER[1]) {
                $kept[] = $s;
            }
        }
        $scale = $kept !== [] ? $this->median($kept) : $median;

        $info = [];
        foreach ($samples as $i => $s) {
            $r = $s / $scale;
            $info[$i] = ['ratio' => $r, 'mismatch' => $r < self::SIZE_MISMATCH[0] || $r > self::SIZE_MISMATCH[1]];
        }

        return [$scale, $info];
    }

    /**
     * شیب محورها از پاره‌خط‌های دیوار (کمترین مربعات از مبدأ، به تفکیک خانوادهٔ مثبت/منفی).
     *
     * @return array{k1: float, k2: float, source: string, fixed: bool}
     */
    protected function axisFromWalls(array $walls, ?float $tile): array
    {
        $default = ['k1' => self::DEFAULT_SLOPE, 'k2' => self::DEFAULT_SLOPE, 'source' => 'default', 'fixed' => true];
        $minDx = max(2.0, ($tile ?? 0.0) * 0.5);

        $acc = [
            'pos' => ['xy' => 0.0, 'xx' => 0.0, 'n' => 0, 'slopes' => []],
            'neg' => ['xy' => 0.0, 'xx' => 0.0, 'n' => 0, 'slopes' => []],
        ];
        foreach ($walls as [$x1, $y1, $x2, $y2]) {
            $dx = $x2 - $x1;
            $dy = $y2 - $y1;
            if (abs($dx) < $minDx) {
                continue;
            }
            $slope = $dy / $dx;
            if (abs($slope) < 0.15 || abs($slope) > 2.5) {
                continue;
            }
            $family = $slope > 0 ? 'pos' : 'neg';
            $acc[$family]['xy'] += $dx * $dy;
            $acc[$family]['xx'] += $dx * $dx;
            $acc[$family]['n']++;
            $acc[$family]['slopes'][] = abs($slope);
        }
        $pos = $acc['pos'];
        $neg = $acc['neg'];

        if ($pos['n'] + $neg['n'] < 3) {
            return $default;
        }

        $k1 = $pos['n'] >= 2 ? $pos['xy'] / $pos['xx'] : null;
        $k2 = $neg['n'] >= 2 ? -$neg['xy'] / $neg['xx'] : null;
        $k1 = $k1 ?? $k2;
        $k2 = $k2 ?? $k1;
        if ($k1 === null || $k2 === null || $k1 < 0.3 || $k1 > 1.5 || $k2 < 0.3 || $k2 > 1.5) {
            return $default;
        }

        $median = $this->median(array_merge($pos['slopes'], $neg['slopes']));
        if (abs($median - self::DEFAULT_SLOPE) <= 0.1 * self::DEFAULT_SLOPE) {
            return ['k1' => self::DEFAULT_SLOPE, 'k2' => self::DEFAULT_SLOPE, 'source' => 'walls', 'fixed' => true];
        }

        return ['k1' => $k1, 'k2' => $k2, 'source' => 'walls', 'fixed' => false];
    }

    /**
     * بررسی هندسی گوشه‌ها: لوزی هم‌محور، هم‌خوانی مقیاس با جعبه‌ها و نسبت با شیب دیوارها.
     */
    protected function validateCorners(array $c, int $gridSize, ?float $boxScale, array $axis): bool
    {
        $w = $c['right'][0] - $c['left'][0];
        $h = $c['bottom'][1] - $c['top'][1];
        if ($w <= 1e-6 || $h <= 1e-6) {
            return false;
        }
        $tol = self::CORNER_TOLERANCE * $w;

        if (abs($c['top'][0] - $c['bottom'][0]) > $tol || abs($c['right'][1] - $c['left'][1]) > $tol) {
            return false;
        }
        $m1 = [($c['top'][0] + $c['bottom'][0]) / 2, ($c['top'][1] + $c['bottom'][1]) / 2];
        $m2 = [($c['right'][0] + $c['left'][0]) / 2, ($c['right'][1] + $c['left'][1]) / 2];
        if (abs($m1[0] - $m2[0]) > $tol || abs($m1[1] - $m2[1]) > $tol) {
            return false;
        }

        $aspect = $h / $w;
        if ($aspect < 0.35 || $aspect > 1.05) {
            return false;
        }

        if ($boxScale !== null) {
            $s = $w / $gridSize;
            if (abs($s - $boxScale) / $boxScale > 0.25) {
                return false;
            }
        }

        if ($axis['source'] === 'walls') {
            $expected = ($axis['k1'] + $axis['k2']) / 2;
            if (abs($aspect - $expected) / $expected > 0.2) {
                return false;
            }
        }

        return true;
    }

    /**
     * بهترین آفست زیرخانه‌ای برای هر محور (جداپذیر) و امتیاز شبکه‌ای‌بودن (۰ = تصادفی، ۱ = کامل).
     *
     * @param  array<int, array{0: float, 1: float}>  $points  مختصات (u − N/2, v − N/2)
     * @return array{score: float, score_raw: float, du: float, dv: float}
     */
    protected function latticeFit(array $points): array
    {
        $bestAxis = function (int $axis) use ($points): array {
            $best = [0.0, PHP_FLOAT_MAX];
            for ($k = 0; $k < 20; $k++) {
                $d = $k / 20;
                $sum = 0.0;
                foreach ($points as $p) {
                    $sum += $this->distToInteger($p[$axis] + $d);
                }
                $mean = $sum / count($points);
                if ($mean < $best[1] - 1e-12) {
                    $best = [$d, $mean];
                }
            }
            if ($best[0] > 0.5) {
                $best[0] -= 1.0;
            }

            return $best;
        };

        [$du, $mu] = $bestAxis(0);
        [$dv, $mv] = $bestAxis(1);
        $meanBefore = 0.0;
        foreach ($points as $p) {
            $meanBefore += ($this->distToInteger($p[0]) + $this->distToInteger($p[1])) / 2;
        }
        $meanBefore /= max(1, count($points));

        return [
            'score' => 1 - 4 * (($mu + $mv) / 2),
            'score_raw' => 1 - 4 * $meanBefore,
            'du' => $du,
            'dv' => $dv,
        ];
    }

    protected function distToInteger(float $t): float
    {
        $f = $t - floor($t);

        return min($f, 1 - $f);
    }

    protected function median(array $values): float
    {
        sort($values);
        $n = count($values);
        if ($n === 0) {
            return 0.0;
        }

        return $n % 2 ? (float) $values[intdiv($n, 2)] : ($values[$n / 2 - 1] + $values[$n / 2]) / 2;
    }

    /**
     * @return array{0: float, 1: float}
     */
    protected function imageSize($size): array
    {
        if (is_array($size) && count($size) >= 2 && is_numeric($size[0]) && is_numeric($size[1]) && $size[0] > 0 && $size[1] > 0) {
            return [(float) $size[0], (float) $size[1]];
        }

        return [1000.0, 1000.0];
    }

    protected function unitConverter(string $units, float $imgW, float $imgH): \Closure
    {
        $div = match ($units) {
            'px' => 0.0,
            'permille' => 1000.0,
            default => 100.0,
        };

        return function ($value, string $axis) use ($div, $imgW, $imgH): float {
            $v = (float) $value;
            if ($div === 0.0) {
                return $v;
            }

            return $v / $div * ($axis === 'x' ? $imgW : $imgH);
        };
    }

    /**
     * @return array<string, array{0: float, 1: float}>|null
     */
    protected function cornersToPx($corners, \Closure $px): ?array
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
            $out[$name] = [$px($c['x'], 'x'), $px($c['y'], 'y')];
        }

        return $out;
    }

    /**
     * @return array{0: float, 1: float, 2: float, 3: float}|null
     */
    protected function diamondToPx($box, \Closure $px, float $imgW): ?array
    {
        if (! is_array($box) || count($box) < 4) {
            return null;
        }
        $b = array_values($box);
        foreach ($b as $v) {
            if (! is_numeric($v)) {
                return null;
            }
        }
        $d = [$px($b[0], 'x'), $px($b[1], 'y'), $px($b[2], 'x'), $px($b[3], 'y')];
        $w = $d[2] - $d[0];
        $h = $d[3] - $d[1];
        if ($w <= 0 || $h <= 0 || $w < 0.25 * $imgW) {
            return null;
        }
        $aspect = $h / $w;
        if ($aspect < 0.35 || $aspect > 1.05) {
            return null;
        }

        return $d;
    }

    /**
     * @return array{x: float, y: float, w: float, h: float}|null
     */
    protected function thBoxToPx($box, \Closure $px): ?array
    {
        if (! is_array($box)) {
            return null;
        }
        foreach (['x', 'y', 'w', 'h'] as $k) {
            if (! isset($box[$k]) || ! is_numeric($box[$k])) {
                return null;
            }
        }
        $w = $px($box['w'], 'x');
        $h = $px($box['h'], 'y');
        if ($w <= 0 || $h <= 0) {
            return null;
        }

        return ['x' => $px($box['x'], 'x'), 'y' => $px($box['y'], 'y'), 'w' => $w, 'h' => $h];
    }
}
