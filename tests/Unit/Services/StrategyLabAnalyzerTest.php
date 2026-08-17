<?php

use App\Services\StrategyLabAnalyzer;

it('returns an error when no buildings are provided', function () {
    $analyzer = new StrategyLabAnalyzer;

    $result = $analyzer->analyze([]);

    expect($result['ok'])->toBeFalse()
        ->and($result['message'])->toContain('هیچ ساختمانی');
});

it('detects low air defense count', function () {
    $analyzer = new StrategyLabAnalyzer;

    $buildings = [
        ['id' => 1, 'type' => 'town_hall', 'x' => 50, 'y' => 50],
        ['id' => 2, 'type' => 'air_defense', 'x' => 30, 'y' => 30],
    ];

    $result = $analyzer->analyze($buildings);

    expect($result['ok'])->toBeTrue();

    $titles = array_column($result['weak_points'], 'title');
    expect($titles)->toContain('شکاف دفاع هوایی');
});

it('flags exposed town hall near edge', function () {
    $analyzer = new StrategyLabAnalyzer;

    $buildings = [
        ['id' => 1, 'type' => 'town_hall', 'x' => 5, 'y' => 50],
        ['id' => 2, 'type' => 'cannon', 'x' => 90, 'y' => 90],
    ];

    $result = $analyzer->analyze($buildings);

    $titles = array_column($result['weak_points'], 'title');
    expect($titles)->toContain('تانک هال در معرض');
});

it('suggests entry sides', function () {
    $analyzer = new StrategyLabAnalyzer;

    $buildings = [
        ['id' => 1, 'type' => 'town_hall', 'x' => 50, 'y' => 50],
        ['id' => 2, 'type' => 'cannon', 'x' => 80, 'y' => 80],
        ['id' => 3, 'type' => 'archer_tower', 'x' => 85, 'y' => 85],
        ['id' => 4, 'type' => 'air_defense', 'x' => 20, 'y' => 20],
        ['id' => 5, 'type' => 'air_defense', 'x' => 25, 'y' => 25],
        ['id' => 6, 'type' => 'air_defense', 'x' => 30, 'y' => 30],
    ];

    $result = $analyzer->analyze($buildings);

    expect($result['entry_suggestions'])->toHaveCount(2)
        ->and($result['entry_suggestions'][0])->toHaveKeys(['side', 'score', 'reason']);
});
