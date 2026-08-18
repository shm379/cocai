<?php

use App\Services\AI\BaseVisionAnalyzer;

it('parses valid building json from model response', function () {
    $analyzer = new BaseVisionAnalyzer();

    $response = <<<'JSON'
```json
{
  "buildings": [
    {"type": "town_hall", "x": 45.2, "y": 48.5},
    {"type": "air_defense", "x": 30.0, "y": 25.5}
  ]
}
```
JSON;

    $method = new ReflectionMethod($analyzer, 'parseBuildingsFromResponse');
    $method->setAccessible(true);

    $buildings = $method->invoke($analyzer, $response);

    expect($buildings)->toHaveCount(2)
        ->and($buildings[0]['type'])->toBe('town_hall')
        ->and($buildings[0]['x'])->toBe(45.2)
        ->and($buildings[1]['type'])->toBe('air_defense');
});

it('skips invalid building types and out of bounds coordinates', function () {
    $analyzer = new BaseVisionAnalyzer();

    $response = <<<'JSON'
{
  "buildings": [
    {"type": "town_hall", "x": 50, "y": 50},
    {"type": "invalid_type", "x": 10, "y": 10},
    {"type": "air_defense", "x": 120, "y": 10},
    {"type": "cannon", "x": 10, "y": -5}
  ]
}
JSON;

    $method = new ReflectionMethod($analyzer, 'parseBuildingsFromResponse');
    $method->setAccessible(true);

    $buildings = $method->invoke($analyzer, $response);

    expect($buildings)->toHaveCount(1)
        ->and($buildings[0]['type'])->toBe('town_hall');
});

it('returns empty array when json has no buildings key', function () {
    $analyzer = new BaseVisionAnalyzer();

    $method = new ReflectionMethod($analyzer, 'parseBuildingsFromResponse');
    $method->setAccessible(true);

    $buildings = $method->invoke($analyzer, '{"foo": "bar"}');

    expect($buildings)->toBe([]);
});
