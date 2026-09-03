<?php

use App\Services\BaseClone\BuildingCatalog;
use App\Services\BaseClone\LayoutGridMapper;

function squareCorners(): array
{
    return [
        'top' => ['x' => 50, 'y' => 0],
        'right' => ['x' => 100, 'y' => 50],
        'bottom' => ['x' => 50, 'y' => 100],
        'left' => ['x' => 0, 'y' => 50],
    ];
}

function mapper(): LayoutGridMapper
{
    return new LayoutGridMapper(new BuildingCatalog);
}

it('maps the image centre to the centre of the 44x44 grid', function () {
    $layout = mapper()->map([
        'grid_corners' => squareCorners(),
        'buildings' => [
            ['type' => 'town_hall', 'x' => 50, 'y' => 50, 'level' => 16],
        ],
    ]);

    expect($layout['grid_size'])->toBe(44)
        ->and($layout['corners_source'])->toBe('model')
        ->and($layout['th_level'])->toBe(16)
        ->and($layout['buildings'])->toHaveCount(1);

    $th = $layout['buildings'][0];
    expect($th['type'])->toBe('town_hall')
        ->and($th['size'])->toBe(4)
        ->and($th['x'])->toBe(20)
        ->and($th['y'])->toBe(20)
        ->and($th['placed'])->toBeTrue();
});

it('maps the right corner of the diamond to the x axis', function () {
    [$gx, $gy] = mapper()->isoToGrid(100, 50, squareCorners());

    expect(round($gx, 3))->toBe(44.0)
        ->and(round($gy, 3))->toBe(0.0);

    [$gx, $gy] = mapper()->isoToGrid(0, 50, squareCorners());

    expect(round($gx, 3))->toBe(0.0)
        ->and(round($gy, 3))->toBe(44.0);
});

it('resolves overlapping buildings without dropping them', function () {
    $layout = mapper()->map([
        'grid_corners' => squareCorners(),
        'buildings' => [
            ['type' => 'cannon', 'x' => 50, 'y' => 50],
            ['type' => 'cannon', 'x' => 50, 'y' => 50],
            ['type' => 'archer_tower', 'x' => 50.5, 'y' => 50.5],
        ],
    ]);

    expect($layout['buildings'])->toHaveCount(3)
        ->and($layout['stats']['unplaced_count'])->toBe(0);

    $cells = [];
    foreach ($layout['buildings'] as $b) {
        expect($b['placed'])->toBeTrue();
        for ($y = $b['y']; $y < $b['y'] + $b['size']; $y++) {
            for ($x = $b['x']; $x < $b['x'] + $b['size']; $x++) {
                $key = "$x,$y";
                expect(isset($cells[$key]))->toBeFalse("cell $key is used twice");
                $cells[$key] = true;
            }
        }
    }
});

it('rasterizes wall segments and never places walls under buildings', function () {
    $layout = mapper()->map([
        'grid_corners' => squareCorners(),
        'buildings' => [
            ['type' => 'town_hall', 'x' => 50, 'y' => 50],
        ],
        'walls' => [
            // خط افقی روی شبکه که از وسط تاون‌هال عبور می‌کند
            ['x1' => 25, 'y1' => 25, 'x2' => 75, 'y2' => 75],
        ],
    ]);

    expect($layout['walls'])->not->toBeEmpty();

    $th = $layout['buildings'][0];
    foreach ($layout['walls'] as [$x, $y]) {
        $inside = $x >= $th['x'] && $x < $th['x'] + 4 && $y >= $th['y'] && $y < $th['y'] + 4;
        expect($inside)->toBeFalse();
        expect($x)->toBeGreaterThanOrEqual(0)->toBeLessThan(44);
        expect($y)->toBeGreaterThanOrEqual(0)->toBeLessThan(44);
    }
});

it('estimates corners from the town hall scale when the model gives none', function () {
    $layout = mapper()->map([
        'grid_corners' => null,
        'town_hall_box' => ['x' => 45, 'y' => 45, 'w' => 10, 'h' => 9],
        'image_size' => [1000, 1000],
        'buildings' => [
            ['type' => 'town_hall', 'x' => 50, 'y' => 50],
            ['type' => 'cannon', 'x' => 60, 'y' => 50],
        ],
    ]);

    expect($layout['corners_source'])->toBe('town_hall_scale')
        ->and($layout['stats']['placed_count'])->toBe(2);

    foreach ($layout['buildings'] as $b) {
        expect($b['x'])->toBeGreaterThanOrEqual(0)
            ->and($b['x'] + $b['size'])->toBeLessThanOrEqual(44)
            ->and($b['y'])->toBeGreaterThanOrEqual(0)
            ->and($b['y'] + $b['size'])->toBeLessThanOrEqual(44);
    }
});

it('falls back to bounding box estimation and keeps everything inside the grid', function () {
    $buildings = [];
    for ($i = 0; $i < 12; $i++) {
        $buildings[] = ['type' => 'archer_tower', 'x' => 20 + $i * 5, 'y' => 30 + ($i % 3) * 10];
    }

    $layout = mapper()->map([
        'buildings' => $buildings,
    ]);

    expect($layout['corners_source'])->toBe('bounding_box')
        ->and($layout['buildings'])->toHaveCount(12);

    foreach ($layout['buildings'] as $b) {
        expect($b['x'])->toBeGreaterThanOrEqual(0)
            ->and($b['x'] + $b['size'])->toBeLessThanOrEqual(44)
            ->and($b['y'])->toBeGreaterThanOrEqual(0)
            ->and($b['y'] + $b['size'])->toBeLessThanOrEqual(44);
    }
});

it('ignores unknown types and normalizes aliases', function () {
    $layout = mapper()->map([
        'perspective' => 'top_down',
        'buildings' => [
            ['type' => 'X-Bow Air', 'x' => 10, 'y' => 10],
            ['type' => 'inferno_tower_multi', 'x' => 80, 'y' => 80],
            ['type' => 'dragon_statue', 'x' => 50, 'y' => 50],
            ['type' => 'wall', 'x' => 50, 'y' => 50],
        ],
    ]);

    $types = array_column($layout['buildings'], 'type');

    expect($types)->toBe(['x_bow', 'inferno_tower'])
        ->and($layout['corners_source'])->toBe('linear');
});

it('emits version 2 metadata, confidence, flags and empty alternatives', function () {
    $layout = mapper()->map([
        'grid_corners' => squareCorners(),
        'buildings' => [
            ['type' => 'town_hall', 'x' => 50, 'y' => 50],
        ],
    ]);

    expect($layout['version'])->toBe(2)
        ->and($layout['source'])->toBe('ai')
        ->and($layout['geometry'])->toBeArray()
        ->and($layout['stats']['uncertain_count'])->toBe(0)
        ->and($layout['stats']['trimmed_count'])->toBe(0);

    $th = $layout['buildings'][0];
    expect($th['flags'])->toBe([])
        ->and($th['alternatives'])->toBe([])
        ->and($th['uncertain'])->toBeFalse()
        ->and($th['shift'])->toBe(0)
        ->and($th['confidence'])->toBe(0.93)  // بدون جعبه: 0.55 + 0.15 + 0.15 + 0.075
        ->and($th['grid_raw'])->toBe(['u' => 22.0, 'v' => 22.0]);
});

it('marks displaced buildings as moved and large shifts as uncertain', function () {
    $buildings = [];
    // ۹ کنون همه روی یک نقطه: اولی سر جایش می‌ماند، بقیه جابه‌جا می‌شوند.
    for ($i = 0; $i < 9; $i++) {
        $buildings[] = ['type' => 'cannon', 'x' => 50, 'y' => 50];
    }
    $layout = mapper()->map(['grid_corners' => squareCorners(), 'buildings' => $buildings]);

    $first = $layout['buildings'][0];
    expect($first['shift'])->toBe(0)
        ->and($first['flags'])->not->toContain('moved');

    $moved = array_filter($layout['buildings'], fn ($b) => in_array('moved', $b['flags'], true));
    $farMoved = array_filter($layout['buildings'], fn ($b) => $b['shift'] >= 2);
    expect(count($moved))->toBe(8)
        ->and(count($farMoved))->toBeGreaterThan(0);

    foreach ($farMoved as $b) {
        expect($b['uncertain'])->toBeTrue()
            ->and($b['confidence'])->toBeLessThan($first['confidence']);
    }
    expect($layout['stats']['uncertain_count'])->toBe(count($farMoved));
});

it('trims buildings above the town hall cap without deleting them', function () {
    $buildings = [['type' => 'town_hall', 'x' => 50, 'y' => 50, 'level' => 15]];
    for ($i = 0; $i < 9; $i++) {
        $buildings[] = ['type' => 'cannon', 'x' => 20 + $i * 7, 'y' => 20 + ($i % 2) * 8];
    }
    $layout = mapper()->map(['town_hall_level' => 15, 'grid_corners' => squareCorners(), 'buildings' => $buildings]);

    $cannons = array_values(array_filter($layout['buildings'], fn ($b) => $b['type'] === 'cannon'));
    $trimmed = array_values(array_filter($cannons, fn ($b) => in_array('cap_trimmed', $b['flags'], true)));

    expect($cannons)->toHaveCount(9)
        ->and($trimmed)->toHaveCount(2)
        ->and($layout['stats']['building_count'])->toBe(10)
        ->and($layout['stats']['placed_count'])->toBe(8)
        ->and($layout['stats']['trimmed_count'])->toBe(2)
        ->and($layout['stats']['unplaced_count'])->toBe(0)
        ->and($layout['stats']['by_type']['cannon'])->toBe(7)
        ->and($layout['stats']['expected_total'])->toBe(98)
        ->and($layout['stats']['uncertain_count'])->toBeGreaterThanOrEqual(2);

    foreach ($trimmed as $b) {
        expect($b['placed'])->toBeFalse()
            ->and($b['uncertain'])->toBeTrue()
            ->and($b['confidence'])->toBeLessThanOrEqual(0.3);
    }
});

it('enforces the merge-group cap and skips caps for unknown or low town halls', function () {
    $buildings = [['type' => 'town_hall', 'x' => 50, 'y' => 50]];
    for ($i = 0; $i < 7; $i++) {
        $buildings[] = ['type' => 'cannon', 'x' => 15 + $i * 9, 'y' => 20];
    }
    $buildings[] = ['type' => 'ricochet_cannon', 'x' => 30, 'y' => 70];
    $buildings[] = ['type' => 'ricochet_cannon', 'x' => 60, 'y' => 70];

    $layout = mapper()->map(['town_hall_level' => 16, 'grid_corners' => squareCorners(), 'buildings' => $buildings]);
    $kept = array_filter($layout['buildings'], fn ($b) => ! in_array('cap_trimmed', $b['flags'], true));
    $sum = 0;
    foreach ($kept as $b) {
        $sum += match ($b['type']) {
            'cannon' => 1,
            'ricochet_cannon' => 2,
            default => 0,
        };
    }
    expect($sum)->toBeLessThanOrEqual(7)
        ->and($layout['stats']['trimmed_count'])->toBeGreaterThan(0);

    $noCaps = mapper()->map(['town_hall_level' => 8, 'grid_corners' => squareCorners(), 'buildings' => $buildings]);
    expect($noCaps['stats']['trimmed_count'])->toBe(0)
        ->and($noCaps['stats']['expected_total'])->toBeNull();

    $unknownTh = mapper()->map(['grid_corners' => squareCorners(), 'buildings' => $buildings]);
    expect($unknownTh['stats']['trimmed_count'])->toBe(0);
});

it('does not apply caps to the builder base', function () {
    $buildings = [['type' => 'builder_hall', 'x' => 50, 'y' => 50, 'level' => 10]];
    for ($i = 0; $i < 8; $i++) {
        $buildings[] = ['type' => 'cannon', 'x' => 15 + $i * 8, 'y' => 25];
    }
    $layout = (new LayoutGridMapper(new \App\Services\BaseClone\BuilderBaseCatalog))->map(['town_hall_level' => 10, 'grid_corners' => squareCorners(), 'buildings' => $buildings]);

    expect($layout['stats']['trimmed_count'])->toBe(0)
        ->and($layout['stats']['placed_count'])->toBe(9);
});

it('drops off-axis and zero-length wall runs when most runs follow a grid axis', function () {
    $onAxis = [
        ['x1' => 20, 'y1' => 30, 'x2' => 40, 'y2' => 50], // موازی محور u
        ['x1' => 60, 'y1' => 30, 'x2' => 40, 'y2' => 50], // موازی محور v
        ['x1' => 20, 'y1' => 70, 'x2' => 40, 'y2' => 90],
        ['x1' => 80, 'y1' => 70, 'x2' => 60, 'y2' => 90],
    ];
    $layout = mapper()->map([
        'grid_corners' => squareCorners(),
        'buildings' => [['type' => 'town_hall', 'x' => 50, 'y' => 20]],
        'walls' => array_merge($onAxis, [
            ['x1' => 20, 'y1' => 50, 'x2' => 80, 'y2' => 50], // افقی در تصویر = قطری در شبکه → ناممکن
            ['x1' => 50, 'y1' => 50, 'x2' => 50, 'y2' => 50], // طول صفر
        ]),
    ]);

    expect($layout['walls'])->not->toBeEmpty()
        ->and($layout['warnings'])->toContain('walls_dropped')
        ->and($layout['stats']['walls_dropped'])->toBe(2);

    // هر ردیف دیوار باید یک خط راست روی یکی از محورهای شبکه باشد.
    $rows = [];
    foreach ($layout['walls'] as [$x, $y]) {
        $rows["x$x"] = ($rows["x$x"] ?? 0) + 1;
        $rows["y$y"] = ($rows["y$y"] ?? 0) + 1;
    }
    expect(max($rows))->toBeGreaterThanOrEqual(8);

    // بدون اکثریت هم‌محور (مثلاً یک دیوار قطری تنها) رفتار قدیمی حفظ می‌شود.
    $legacy = mapper()->map([
        'grid_corners' => squareCorners(),
        'buildings' => [['type' => 'town_hall', 'x' => 50, 'y' => 20]],
        'walls' => [['x1' => 30, 'y1' => 30, 'x2' => 70, 'y2' => 30]],
    ]);
    expect($legacy['walls'])->not->toBeEmpty()
        ->and($legacy['stats']['walls_dropped'])->toBe(0);
});

it('uses sprite boxes and the diamond box to solve the geometry', function () {
    // تصویر ۱۰۰۰×۵۰۰، لوزی کامل، مقیاس ۲۰px، محور ۲:۱؛ مختصات به درصد.
    $s = 20.0;
    $origin = [500.0, 30.0];
    $toPx = fn (float $u, float $v): array => [$origin[0] + ($u - $v) * $s / 2, $origin[1] + ($u + $v) * $s / 4];
    $cells = [['town_hall', 4, 20, 20], ['cannon', 3, 10, 10], ['cannon', 3, 30, 10], ['cannon', 3, 10, 30], ['cannon', 3, 30, 30],
        ['archer_tower', 3, 20, 8], ['archer_tower', 3, 8, 20], ['mortar', 3, 32, 20], ['wizard_tower', 3, 20, 32], ['hidden_tesla', 2, 16, 16]];
    $buildings = [];
    foreach ($cells as [$type, $n, $x0, $y0]) {
        [$gx, $gy] = $toPx($x0 + $n / 2, $y0 + $n / 2);
        $y1 = $gy + $n * $s / 4;
        $buildings[] = ['type' => $type, 'x' => $gx / 10, 'y' => ($gy - 10) / 5,
            'box' => [($gx - $n * $s / 2) / 10, ($y1 - $n * $s / 2 - 20) / 5, ($gx + $n * $s / 2) / 10, $y1 / 5]];
    }
    $layout = mapper()->map([
        'image_size' => [1000, 500],
        'diamond_box' => [(500 - 440) / 10, 30 / 5, (500 + 440) / 10, (30 + 440) / 5],
        'buildings' => $buildings,
    ]);

    expect($layout['corners_source'])->toBe('diamond')
        ->and($layout['geometry']['scale_source'])->toBe('boxes')
        ->and(abs($layout['geometry']['tile_px'] - 20))->toBeLessThan(1.0);

    foreach ($cells as $i => [$type, $n, $x0, $y0]) {
        $b = $layout['buildings'][$i];
        expect($b['x'])->toBe($x0, "$type x")
            ->and($b['y'])->toBe($y0, "$type y")
            ->and($b['placed'])->toBeTrue()
            ->and($b['raw']['w'])->toBeGreaterThan(0)
            ->and($b['confidence'])->toBe(1.0);
    }
});
