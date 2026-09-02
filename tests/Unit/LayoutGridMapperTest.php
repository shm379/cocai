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
