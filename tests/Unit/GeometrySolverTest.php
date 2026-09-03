<?php

use App\Services\BaseClone\GeometrySolver;

/**
 * صحنهٔ مصنوعی: شبکهٔ ۴۴×۴۴ با محورهای معلوم؛ ساختمان‌ها روی خانه‌های صحیح قرار دارند و
 * جعبهٔ اسپرایت آن‌ها از هندسه ساخته می‌شود. حل‌کننده باید خانه‌ها را با خطای < ۰٫۵ بازیابی کند.
 *
 * @return array{input: array, truth: array<int, array{0:int,1:int}>}
 */
function syntheticScene(float $k = 0.5, bool $withDiamond = true, bool $overhang = true, array $extra = []): array
{
    $imgW = 1200.0;
    $imgH = 800.0;
    $s = 20.0;
    $origin = [600.0, 40.0];
    $a = [$s / 2, $k * $s / 2];
    $b = [-$s / 2, $k * $s / 2];
    $grid = 44;

    $cells = [
        ['town_hall', 4, 20, 20], ['army_camp', 4, 5, 5], ['army_camp', 4, 35, 5], ['army_camp', 4, 5, 35], ['army_camp', 4, 35, 35],
        ['cannon', 3, 12, 20], ['cannon', 3, 29, 20], ['cannon', 3, 20, 12], ['cannon', 3, 20, 29],
        ['archer_tower', 3, 10, 10], ['archer_tower', 3, 30, 10], ['archer_tower', 3, 10, 30], ['archer_tower', 3, 30, 30],
        ['hidden_tesla', 2, 16, 16], ['hidden_tesla', 2, 26, 26], ['hidden_tesla', 2, 16, 26], ['hidden_tesla', 2, 26, 16],
        ['gold_storage', 3, 24, 8], ['elixir_storage', 3, 8, 24], ['wizard_tower', 3, 33, 24], ['mortar', 3, 24, 33],
    ];

    $pctX = fn (float $x): float => $x / $imgW * 100;
    $pctY = fn (float $y): float => $y / $imgH * 100;
    $toPx = fn (float $u, float $v): array => [$origin[0] + $u * $a[0] + $v * $b[0], $origin[1] + $u * $a[1] + $v * $b[1]];

    $buildings = [];
    $truth = [];
    foreach ($cells as $i => [$type, $n, $x0, $y0]) {
        [$gx, $gy] = $toPx($x0 + $n / 2, $y0 + $n / 2);
        $w = $n * $s * ($overhang ? 1.08 : 1.0);
        $footprintHalfHeight = $n * $s * $k / 2;
        $y1 = $gy + $footprintHalfHeight;
        $top = $y1 - ($n * $s * $k + 22);
        $buildings[] = [
            'x' => $pctX($gx), 'y' => $pctY($gy - 10), 'size' => $n,
            'box' => [$pctX($gx - $w / 2), $pctY($top), $pctX($gx + $w / 2), $pctY($y1)],
        ];
        $truth[$i] = [$x0, $y0];
    }

    $walls = [];
    foreach ([[3, 3, 40, 3], [3, 3, 3, 40], [40, 3, 40, 40], [3, 40, 40, 40], [15, 15, 15, 28], [15, 28, 28, 28]] as [$u1, $v1, $u2, $v2]) {
        [$px1, $py1] = $toPx($u1 + 0.5, $v1 + 0.5);
        [$px2, $py2] = $toPx($u2 + 0.5, $v2 + 0.5);
        $walls[] = ['x1' => $pctX($px1), 'y1' => $pctY($py1), 'x2' => $pctX($px2), 'y2' => $pctY($py2)];
    }

    $input = array_merge([
        'image_size' => [$imgW, $imgH],
        'units' => 'pct',
        'grid_size' => $grid,
        'buildings' => $buildings,
        'walls' => $walls,
        'corners' => null,
        'diamond_box' => $withDiamond
            ? [$pctX($origin[0] - $grid * $s / 2), $pctY($origin[1]), $pctX($origin[0] + $grid * $s / 2), $pctY($origin[1] + $grid * $s * $k)]
            : null,
    ], $extra);

    return ['input' => $input, 'truth' => $truth];
}

it('recovers the grid cells of a synthetic 2:1 scene within half a tile', function () {
    ['input' => $input, 'truth' => $truth] = syntheticScene();
    $result = (new GeometrySolver)->solve($input);

    expect($result['ok'])->toBeTrue()
        ->and($result['source'])->toBe('diamond')
        ->and($result['scale_source'])->toBe('boxes')
        ->and($result['axis']['fixed'])->toBeTrue()
        ->and($result['axis']['source'])->toBe('walls')
        ->and(abs($result['tile_px'] - 20.0))->toBeLessThan(1.0)
        ->and($result['lattice']['score'])->toBeGreaterThan(0.7);

    foreach ($truth as $i => [$x0, $y0]) {
        $n = $input['buildings'][$i]['size'];
        $u = $result['buildings'][$i]['u'] - $n / 2;
        $v = $result['buildings'][$i]['v'] - $n / 2;
        expect(abs($u - $x0))->toBeLessThan(0.5, "building $i u: $u vs $x0")
            ->and(abs($v - $y0))->toBeLessThan(0.5, "building $i v: $v vs $y0")
            ->and($result['buildings'][$i]['edge_of_tile'])->toBeLessThan(GeometrySolver::EDGE_THRESHOLD)
            ->and($result['buildings'][$i]['box_size_mismatch'])->toBeFalse();
    }
});

it('fits general axes from wall slopes when the render is not 2:1', function () {
    ['input' => $input, 'truth' => $truth] = syntheticScene(0.8);
    $result = (new GeometrySolver)->solve($input);

    expect($result['axis']['fixed'])->toBeFalse()
        ->and($result['axis']['source'])->toBe('walls')
        ->and(abs($result['axis']['k1'] - 0.8))->toBeLessThan(0.05)
        ->and(abs($result['axis']['k2'] - 0.8))->toBeLessThan(0.05);

    foreach ($truth as $i => [$x0, $y0]) {
        $n = $input['buildings'][$i]['size'];
        expect(abs($result['buildings'][$i]['u'] - $n / 2 - $x0))->toBeLessThan(0.5)
            ->and(abs($result['buildings'][$i]['v'] - $n / 2 - $y0))->toBeLessThan(0.5);
    }
});

it('keeps relative positions when only the centroid anchors the origin', function () {
    ['input' => $input, 'truth' => $truth] = syntheticScene(0.5, false);
    $result = (new GeometrySolver)->solve($input);

    expect($result['source'])->toBe('fitted');

    // انتقال صحیح مشترک مجاز است؛ اختلاف نسبی خانه‌ها باید حفظ شود.
    $n0 = $input['buildings'][0]['size'];
    $du = round($result['buildings'][0]['u'] - $n0 / 2 - $truth[0][0]);
    $dv = round($result['buildings'][0]['v'] - $n0 / 2 - $truth[0][1]);
    foreach ($truth as $i => [$x0, $y0]) {
        $n = $input['buildings'][$i]['size'];
        expect(abs($result['buildings'][$i]['u'] - $n / 2 - $du - $x0))->toBeLessThan(0.5)
            ->and(abs($result['buildings'][$i]['v'] - $n / 2 - $dv - $y0))->toBeLessThan(0.5)
            ->and($result['buildings'][$i]['u'] - $n / 2)->toBeGreaterThanOrEqual(-0.5)
            ->and($result['buildings'][$i]['u'] + $n / 2)->toBeLessThanOrEqual(44.5);
    }
});

it('uses validated corners as the origin and rejects a sheared quadrilateral', function () {
    ['input' => $input] = syntheticScene();
    $imgW = 1200;
    $imgH = 800;
    $good = [
        'top' => ['x' => 600 / $imgW * 100, 'y' => 40 / $imgH * 100],
        'right' => ['x' => 1040 / $imgW * 100, 'y' => 260 / $imgH * 100],
        'bottom' => ['x' => 600 / $imgW * 100, 'y' => 480 / $imgH * 100],
        'left' => ['x' => 160 / $imgW * 100, 'y' => 260 / $imgH * 100],
    ];
    $result = (new GeometrySolver)->solve(array_merge($input, ['corners' => $good, 'diamond_box' => null]));
    expect($result['source'])->toBe('model')
        ->and($result['warnings'])->not->toContain('corners_rejected');

    $bad = $good;
    $bad['right']['y'] = 60 / $imgH * 100; // لوزی هم‌محور نیست
    $result = (new GeometrySolver)->solve(array_merge($input, ['corners' => $bad]));
    expect($result['source'])->toBe('diamond')
        ->and($result['warnings'])->toContain('corners_rejected');
});

it('flags a box whose width does not match the claimed footprint', function () {
    ['input' => $input] = syntheticScene();
    // کنون (۳×۳) با جعبه‌ای به پهنای یک ساختمان ۲×۲
    $b = &$input['buildings'][5];
    $cx = ($b['box'][0] + $b['box'][2]) / 2;
    $halfW = ($b['box'][2] - $b['box'][0]) / 2 * (2 / 3);
    $b['box'][0] = $cx - $halfW;
    $b['box'][2] = $cx + $halfW;
    unset($b);

    $result = (new GeometrySolver)->solve($input);

    expect($result['buildings'][5]['box_size_mismatch'])->toBeTrue()
        ->and($result['buildings'][6]['box_size_mismatch'])->toBeFalse()
        ->and(abs($result['tile_px'] - 20.0))->toBeLessThan(1.5);
});

it('falls back to the town hall box and to the bounding box without sprite boxes', function () {
    $solver = new GeometrySolver;

    $result = $solver->solve([
        'image_size' => [1000, 1000],
        'buildings' => [['x' => 50, 'y' => 50, 'size' => 4], ['x' => 60, 'y' => 50, 'size' => 3]],
        'town_hall_box' => ['x' => 45, 'y' => 45, 'w' => 10, 'h' => 9],
        'th_size' => 4,
    ]);
    expect($result['source'])->toBe('town_hall_scale')
        ->and($result['scale_source'])->toBe('town_hall')
        ->and($result['tile_px'])->toBe(25.0);

    $result = $solver->solve([
        'buildings' => [['x' => 30, 'y' => 30, 'size' => 3], ['x' => 70, 'y' => 60, 'size' => 3]],
    ]);
    expect($result['source'])->toBe('bounding_box')
        ->and($result['ok'])->toBeTrue();

    expect($solver->solve(['buildings' => []])['ok'])->toBeFalse();
});

it('maps pixels back to grid coordinates through the affine it returns', function () {
    ['input' => $input] = syntheticScene();
    $solver = new GeometrySolver;
    $result = $solver->solve($input);

    // مرکز لوزی → مرکز شبکه
    [$u, $v] = $solver->toGrid($result['affine'], 600.0, 40.0 + 22 * 20 * 0.5);
    expect(abs($u - 22))->toBeLessThan(0.6)
        ->and(abs($v - 22))->toBeLessThan(0.6);
});
