<?php

use App\Models\Map;
use App\Models\Topic;
use App\Services\MapCrawlerService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
});

it('imports maps from clasher town hall api', function () {
    Http::fake([
        'www.clasher.us/api/mobile/designs/town-hall-12-trophy-base*' => Http::response([
            [
                'url' => 'https://clasher.us/map/1',
                'name' => 'TH12 Trophy Base',
                'img' => 'https://clasher.us/img/1.jpg',
                'img_tn' => 'https://clasher.us/img/1_tn.jpg',
                'copy_link' => 'https://link.clasher.us/1',
                'viewCount' => 100,
                'downCount' => 50,
                'likeCount' => 20,
                'reportCount' => 0,
                'ago' => '2 hours ago',
            ],
        ], 200),
    ]);

    $crawler = app(MapCrawlerService::class);
    $imported = $crawler->crawlTownHall(12, 'trophy_base');

    expect($imported)->toBe(1)
        ->and(Map::where('map_link', 'https://clasher.us/map/1')->exists())->toBeTrue()
        ->and(Topic::where('name', 'Town Hall 12 Trophy Base')->exists())->toBeTrue();
});

it('imports builder hall maps and sets hall type to builder', function () {
    Http::fake([
        'www.clasher.us/api/mobile/designs/builder-hall-9-war-base*' => Http::response([
            [
                'url' => 'https://clasher.us/map/bh9',
                'name' => 'BH9 War Base',
                'img' => 'https://clasher.us/img/bh9.jpg',
                'viewCount' => 10,
                'downCount' => 5,
                'likeCount' => 2,
                'reportCount' => 0,
                'ago' => '1 day ago',
            ],
        ], 200),
    ]);

    $crawler = app(MapCrawlerService::class);
    $imported = $crawler->crawlBuilderHall(9, 'war_base');

    $topic = Topic::where('name', 'Builder Hall 9 War Base')->first();

    expect($imported)->toBe(1)
        ->and($topic)->not->toBeNull()
        ->and($topic->hall_type)->toBe(1)
        ->and($topic->hall_level)->toBe(9);
});

it('returns zero when api fails', function () {
    Http::fake([
        'www.clasher.us/api/mobile/designs/town-hall-5-trophy-base*' => Http::response([], 500),
    ]);

    $crawler = app(MapCrawlerService::class);
    $imported = $crawler->crawlTownHall(5, 'trophy_base');

    expect($imported)->toBe(0);
});
