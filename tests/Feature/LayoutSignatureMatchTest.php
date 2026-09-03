<?php

use App\Models\Map;
use App\Models\User;
use App\Services\AI\LayoutVisionExtractor;
use App\Services\BaseClone\BuildingCatalog;
use App\Services\BaseClone\ImageHasher;
use App\Services\BaseClone\LayoutGridMapper;
use App\Services\BaseClone\LayoutMatcher;
use App\Services\BaseClone\LayoutSignature;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Http::preventStrayRequests();
    Storage::fake('public');
    $this->withoutVite();
});

const SIG_VALID_LINK = 'https://link.clashofclans.com/en?action=OpenLayout&id=TH15%3AWB%3AAAAAKQAAAAHKhX8bPxJyNAX1FJlbFuFy';
const SIG_VALID_LINK_TH12 = 'https://link.clashofclans.com/en?action=OpenLayout&id=TH12:HV:AAAALQAAAAJ4SVSjyCtjnrScICZyyMlY';
const SIG_FAKE_LINK = 'https://link.clashofclans.com/en?action=OpenLayout&id=TH18_WAR_META_01';

/**
 * چیدمان مصنوعی با ساختار خروجی LayoutGridMapper (فقط کلیدهای لازم برای امضا).
 */
function sigSyntheticLayout(int $th = 15, int $dx = 0, int $dy = 0, string $village = 'home'): array
{
    $spec = [
        ['town_hall', 20, 20, 4],
        ['clan_castle', 25, 20, 3],
        ['cannon', 10, 10, 3],
        ['cannon', 30, 10, 3],
        ['cannon', 10, 30, 3],
        ['archer_tower', 30, 30, 3],
        ['archer_tower', 20, 8, 3],
        ['wizard_tower', 20, 32, 3],
        ['air_defense', 8, 20, 3],
        ['air_defense', 33, 20, 3],
        ['gold_storage', 15, 15, 3],
        ['elixir_storage', 26, 26, 3],
    ];

    $buildings = [];
    foreach ($spec as $i => [$type, $x, $y, $size]) {
        $buildings[] = [
            'id' => $i + 1,
            'type' => $type,
            'size' => $size,
            'x' => $x + $dx,
            'y' => $y + $dy,
            'placed' => true,
            'flags' => [],
        ];
    }

    // حلقهٔ دیوار مربعی از (14,14) تا (31,31)
    $walls = [];
    for ($i = 14; $i <= 31; $i++) {
        $walls[] = [$i + $dx, 14 + $dy];
        $walls[] = [$i + $dx, 31 + $dy];
        $walls[] = [14 + $dx, $i + $dy];
        $walls[] = [31 + $dx, $i + $dy];
    }

    return [
        'grid_size' => 44,
        'th_level' => $th,
        'village' => $village,
        'buildings' => $buildings,
        'walls' => $walls,
    ];
}

/**
 * خروجی Vision با ۸ ساختمان (بیش از حداقل امضا) برای تست انتها-به-انتها.
 */
function sigFakeVision(): array
{
    return [
        'town_hall_level' => 15,
        'perspective' => 'isometric',
        'grid_corners' => [
            'top' => ['x' => 50, 'y' => 0],
            'right' => ['x' => 100, 'y' => 50],
            'bottom' => ['x' => 50, 'y' => 100],
            'left' => ['x' => 0, 'y' => 50],
        ],
        'buildings' => [
            ['type' => 'town_hall', 'x' => 50, 'y' => 50, 'level' => 15],
            ['type' => 'eagle_artillery', 'x' => 50, 'y' => 30],
            ['type' => 'cannon', 'x' => 30, 'y' => 50],
            ['type' => 'cannon', 'x' => 70, 'y' => 50],
            ['type' => 'archer_tower', 'x' => 50, 'y' => 70],
            ['type' => 'wizard_tower', 'x' => 35, 'y' => 35],
            ['type' => 'air_defense', 'x' => 65, 'y' => 65],
            ['type' => 'clan_castle', 'x' => 40, 'y' => 60],
        ],
        'walls' => [
            ['x1' => 30, 'y1' => 30, 'x2' => 70, 'y2' => 30],
        ],
    ];
}

function sigFakeGateway(array $layout): void
{
    Http::fake([
        '*/v1/chat/completions' => Http::response([
            'choices' => [
                ['message' => ['content' => "```json\n".json_encode($layout)."\n```"]],
            ],
        ], 200),
    ]);
}

/**
 * همان خط لولهٔ آداپتور (parse → grid) روی دادهٔ Vision تا امضای «آرشیو» با امضای آپلود یکی باشد.
 */
function sigFromVision(array $vision, array $imageSize): array
{
    $catalog = new BuildingCatalog;
    $data = app(LayoutVisionExtractor::class)->parseLayoutJson(json_encode($vision), $imageSize);
    $data['image_size'] = $imageSize;
    $layout = (new LayoutGridMapper($catalog))->map($data, $catalog->gridSize());
    $layout['village'] = $catalog->key();

    return LayoutSignature::fromLayout($layout);
}

// ---------------------------------------------------------------- LayoutSignature

it('scores identical layouts as 1.0', function () {
    $a = LayoutSignature::fromLayout(sigSyntheticLayout());
    $b = LayoutSignature::fromLayout(sigSyntheticLayout());

    expect(LayoutSignature::score($a, $b))->toBe(1.0)
        ->and($a['th'])->toBe(15)
        ->and($a['village'])->toBe('home')
        ->and($a['grid'])->toBe(44)
        ->and($a['counts']['cannon'])->toBe(3)
        ->and($a['counts']['wall'])->toBe(4 * 18 - 4)
        ->and(strlen($a['wall_mask']))->toBe(44 * 44 / 4);
});

it('scores a layout shifted by one tile at least 0.8', function () {
    $a = LayoutSignature::fromLayout(sigSyntheticLayout());
    $b = LayoutSignature::fromLayout(sigSyntheticLayout(15, 1, 1));

    $detail = LayoutSignature::compare($a, $b);

    expect($detail['score'])->toBeGreaterThanOrEqual(0.8)
        ->and($detail['buildings'])->toBe(1.0)
        ->and($detail['walls'])->toBeGreaterThanOrEqual(0.9);
});

it('scores zero when the town hall level differs at TH12+ but tolerates ±1 below', function () {
    $th15 = LayoutSignature::fromLayout(sigSyntheticLayout(15));
    $th14 = LayoutSignature::fromLayout(sigSyntheticLayout(14));
    $th9 = LayoutSignature::fromLayout(sigSyntheticLayout(9));
    $th10 = LayoutSignature::fromLayout(sigSyntheticLayout(10));
    $th11 = LayoutSignature::fromLayout(sigSyntheticLayout(11));

    expect(LayoutSignature::score($th15, $th14))->toBe(0.0)
        ->and(LayoutSignature::score($th9, $th10))->toBe(1.0)
        ->and(LayoutSignature::score($th9, $th11))->toBe(0.0);
});

it('scores zero across villages and caps the score when a TH level is unknown', function () {
    $home = LayoutSignature::fromLayout(sigSyntheticLayout());
    $builder = LayoutSignature::fromLayout(sigSyntheticLayout(15, 0, 0, 'builder'));
    $unknown = LayoutSignature::fromLayout(sigSyntheticLayout(15) + ['th_level' => null]);
    $unknown['th'] = null;

    expect(LayoutSignature::score($home, $builder))->toBe(0.0)
        ->and(LayoutSignature::score($home, $unknown))->toBe(LayoutSignature::UNKNOWN_TH_CAP)
        ->and(LayoutSignature::isConfident(LayoutSignature::UNKNOWN_TH_CAP))->toBeFalse();
});

it('penalises missing and extra buildings', function () {
    $full = sigSyntheticLayout();
    $partial = $full;
    $partial['buildings'] = array_slice($partial['buildings'], 0, 6); // ۶ از ۱۲ ساختمان

    $a = LayoutSignature::fromLayout($full);
    $b = LayoutSignature::fromLayout($partial);
    $detail = LayoutSignature::compare($a, $b);

    expect($detail['buildings'])->toBe(0.5)
        ->and($detail['score'])->toBeLessThan(LayoutSignature::CONFIDENT);
});

it('produces a deterministic signature regardless of building order and ignores unplaced ones', function () {
    $layout = sigSyntheticLayout();
    $shuffled = $layout;
    $shuffled['buildings'] = array_reverse($shuffled['buildings']);
    $shuffled['walls'] = array_reverse($shuffled['walls']);
    $shuffled['buildings'][] = ['type' => 'cannon', 'x' => 40, 'y' => 40, 'placed' => false, 'flags' => ['unplaced']];
    $shuffled['buildings'][] = ['type' => 'cannon', 'x' => 2, 'y' => 2, 'placed' => true, 'flags' => ['cap_trimmed']];

    expect(json_encode(LayoutSignature::fromLayout($shuffled)))->toBe(json_encode(LayoutSignature::fromLayout($layout)));
});

it('round-trips the wall mask', function () {
    $sig = LayoutSignature::fromLayout(sigSyntheticLayout());
    $set = LayoutSignature::unpackMask($sig['wall_mask'], 44);

    expect(count($set))->toBe($sig['counts']['wall'])
        ->and(isset($set['14,14']))->toBeTrue()
        ->and(isset($set['31,31']))->toBeTrue()
        ->and(isset($set['20,20']))->toBeFalse();
});

// ---------------------------------------------------------------- Map::hasValidCopyLink

it('recognises only real OpenLayout links', function () {
    expect(Map::isValidCopyLink(SIG_VALID_LINK))->toBeTrue()
        ->and(Map::isValidCopyLink(SIG_VALID_LINK_TH12))->toBeTrue()
        ->and(Map::isValidCopyLink('https://link.clashofclans.com/en?action=OpenLayout&id=TH9:BB2:AAAALQAAAAJ4SVSjyCtjnrScICZyyMlY'))->toBeTrue()
        ->and(Map::isValidCopyLink(SIG_FAKE_LINK))->toBeFalse()
        ->and(Map::isValidCopyLink('https://link.clashofclans.com/en?action=OpenLayout&id=TH15:WB:AAAAKQAAAAHKhX8bPxJyNAX1FJlbFuF'))->toBeFalse() // ۳۱ کاراکتر
        ->and(Map::isValidCopyLink('https://link.clashofclans.com/en?action=OpenLayout&id='.fake()->uuid()))->toBeFalse()
        ->and(Map::isValidCopyLink('http://link.clashofclans.com/en?action=OpenLayout&id=TH15:WB:AAAAKQAAAAHKhX8bPxJyNAX1FJlbFuFy'))->toBeFalse()
        ->and(Map::isValidCopyLink(null))->toBeFalse();

    expect(Map::parseCopyLink(SIG_VALID_LINK))->toBe(['th' => 15, 'village' => 'home', 'kind' => 'WB'])
        ->and(Map::parseCopyLink('https://link.clashofclans.com/en?action=OpenLayout&id=TH9%3ABB%3AAAAALQAAAAJ4SVSjyCtjnrScICZyyMlY')['village'])->toBe('builder');

    $map = Map::factory()->make(['copy_link' => SIG_FAKE_LINK]);
    expect($map->hasValidCopyLink())->toBeFalse();
});

// ---------------------------------------------------------------- LayoutMatcher

it('never returns archive maps whose copy link is not a real in-game link', function () {
    $hash = 'a1b2c3d4e5f60718';

    // لینک پیش‌فرض کارخانه (uuid) و لینک جای‌گذار seeder هر دو نامعتبرند.
    Map::factory()->create(['image_hash' => $hash]);
    Map::factory()->create(['image_hash' => $hash, 'copy_link' => SIG_FAKE_LINK]);
    Map::factory()->create(['image_hash' => $hash, 'copy_link' => null]);
    $real = Map::factory()->create(['image_hash' => $hash, 'copy_link' => SIG_VALID_LINK]);

    $matches = app(LayoutMatcher::class)->findMatches($hash);

    expect($matches)->toHaveCount(1)
        ->and($matches[0]['id'])->toBe($real->id)
        ->and($matches[0]['method'])->toBe('hash')
        ->and($matches[0]['distance'])->toBe(0)
        ->and($matches[0]['similarity'])->toBe(100)
        ->and($matches[0]['confident'])->toBeTrue();
});

it('prefers a layout-signature match over a weak image-hash match', function () {
    $layout = sigSyntheticLayout();
    $hash = '0000000000000000';
    $weakHash = '0000000000000fff'; // فاصلهٔ همینگ ۱۲: مشابه ولی نه مطمئن

    $bySignature = Map::factory()->create([
        'image_hash' => null,
        'copy_link' => SIG_VALID_LINK,
        'layout_signature' => LayoutSignature::fromLayout($layout),
    ]);
    $byHash = Map::factory()->create([
        'image_hash' => $weakHash,
        'copy_link' => 'https://link.clashofclans.com/en?action=OpenLayout&id=TH15:WB:BBBBKQAAAAHKhX8bPxJyNAX1FJlbFuFy',
    ]);

    $matches = app(LayoutMatcher::class)->findMatches($hash, $layout);

    expect($matches)->toHaveCount(2)
        ->and($matches[0]['id'])->toBe($bySignature->id)
        ->and($matches[0]['method'])->toBe('signature')
        ->and($matches[0]['signature_score'])->toBe(1.0)
        ->and($matches[0]['similarity'])->toBe(100)
        ->and($matches[0]['confident'])->toBeTrue()
        ->and($matches[0]['distance'])->toBeNull()
        ->and($matches[1]['id'])->toBe($byHash->id)
        ->and($matches[1]['method'])->toBe('hash')
        ->and($matches[1]['confident'])->toBeFalse();
});

it('prefilters archive maps by town hall level and village', function () {
    $layout = sigSyntheticLayout(15);

    // امضای یکسان ولی لینک TH12 → خارج از TH±۱
    Map::factory()->create([
        'image_hash' => null,
        'copy_link' => SIG_VALID_LINK_TH12,
        'layout_signature' => LayoutSignature::fromLayout($layout) + ['th' => 12],
    ]);
    // امضای یکسان ولی بیلدر بیس
    Map::factory()->create([
        'image_hash' => null,
        'copy_link' => 'https://link.clashofclans.com/en?action=OpenLayout&id=TH15:BB2:CCCCKQAAAAHKhX8bPxJyNAX1FJlbFuFy',
        'layout_signature' => LayoutSignature::fromLayout(sigSyntheticLayout(15, 0, 0, 'builder')),
    ]);

    expect(app(LayoutMatcher::class)->findMatches(null, $layout))->toBe([]);

    // امضای تقریباً خالی به کار نمی‌رود.
    $tiny = $layout;
    $tiny['buildings'] = array_slice($tiny['buildings'], 0, 3);
    Map::factory()->create(['image_hash' => null, 'copy_link' => SIG_VALID_LINK, 'layout_signature' => LayoutSignature::fromLayout($tiny)]);

    expect(app(LayoutMatcher::class)->findMatches(null, $tiny))->toBe([]);
});

// ---------------------------------------------------------------- انتها به انتها

it('returns the archive copy link when the reconstructed layout matches a signed archive map', function () {
    sigFakeGateway(sigFakeVision());

    $map = Map::factory()->create([
        'image_hash' => null,
        'copy_link' => SIG_VALID_LINK,
        'layout_signature' => sigFromVision(sigFakeVision(), [400, 300]),
    ]);

    $user = User::factory()->create();
    $response = $this->actingAs($user)
        ->postJson('/api/base-clones', ['image' => UploadedFile::fake()->image('base.jpg', 400, 300)]);

    $response->assertCreated()
        ->assertJsonPath('clone.copy_link', SIG_VALID_LINK)
        ->assertJsonPath('clone.matched_map.id', $map->id)
        ->assertJsonPath('clone.match_similarity', 100)
        ->assertJsonPath('clone.match_method', 'signature')
        ->assertJsonPath('clone.match_distance', null)
        ->assertJsonPath('clone.layout.match.method', 'signature')
        ->assertJsonPath('matches.0.id', $map->id)
        ->assertJsonPath('matches.0.confident', true);
});

it('computes and stores archive signatures with maps:signature', function () {
    config()->set('services.nabu.base_url', 'https://gate.test');
    config()->set('services.nabu.api_key', 'token');

    $fakeImage = UploadedFile::fake()->image('archive.jpg', 400, 300); // نگه داشته می‌شود تا فایل موقت پاک نشود
    $imageBytes = file_get_contents($fakeImage->getRealPath());
    Http::fake([
        'img.cocmap.com/*' => Http::response($imageBytes, 200, ['Content-Type' => 'image/jpeg']),
        '*/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => json_encode(sigFakeVision())]]],
        ], 200),
    ]);

    // سطح هال مدل (۱۵) با لینک (TH12) فرق دارد: لینک برنده است.
    $signed = Map::factory()->create([
        'image_hash' => null,
        'copy_link' => SIG_VALID_LINK_TH12,
        'image_url' => 'https://img.cocmap.com/images/fullo/v2-a.jpg',
        'like_count' => 500,
    ]);
    $skippedFakeLink = Map::factory()->create(['copy_link' => SIG_FAKE_LINK, 'image_url' => 'https://img.cocmap.com/images/fullo/v2-b.jpg']);
    $skippedOtherTh = Map::factory()->create(['copy_link' => SIG_VALID_LINK, 'image_url' => 'https://img.cocmap.com/images/fullo/v2-c.jpg']);

    $this->artisan('maps:signature', ['--limit' => 5, '--th' => 12])
        ->expectsOutputToContain('امضا شد: 1، ناموفق: 0')
        ->assertSuccessful();

    $signed->refresh();
    $expected = sigFromVision(sigFakeVision(), [400, 300]);

    expect($signed->layout_signature)->not->toBeNull()
        ->and($signed->layout_signature['th'])->toBe(12)
        ->and($signed->layout_signature['village'])->toBe('home')
        ->and($signed->layout_signature['cells'])->toEqual($expected['cells']) // MySQL ترتیب کلیدهای JSON را عوض می‌کند
        ->and(LayoutSignature::score(array_merge($signed->layout_signature, ['th' => 15]), $expected))->toBe(1.0)
        ->and($signed->layout_signature['wall_mask'])->toBe($expected['wall_mask'])
        ->and($signed->signature_computed_at)->not->toBeNull()
        ->and($signed->image_hash)->not->toBeNull()
        ->and($skippedFakeLink->refresh()->layout_signature)->toBeNull()
        ->and($skippedOtherTh->refresh()->layout_signature)->toBeNull();

    Http::assertSentCount(2); // یک دانلود تصویر + یک فراخوانی Vision

    // بدون --force دوباره پردازش نمی‌شود.
    $this->artisan('maps:signature', ['--th' => 12])
        ->expectsOutputToContain('نقشه‌ای برای محاسبهٔ امضا وجود ندارد')
        ->assertSuccessful();
});

it('hides placeholder links from the public clone payload', function () {
    $fake = Map::factory()->create(['copy_link' => SIG_FAKE_LINK]);
    $user = User::factory()->create();

    $clone = $user->baseClones()->create([
        'slug' => 'sigtest12345',
        'game' => 'coc_home',
        'title' => 'تست',
        'image_path' => 'base-clones/x.jpg',
        'th_level' => 15,
        'image_hash' => 'a1b2c3d4e5f60718',
        'layout' => sigSyntheticLayout() + ['type' => 'layout'],
        'copy_link' => SIG_FAKE_LINK,
        'matched_map_id' => $fake->id,
        'match_distance' => 0,
    ]);

    $public = $clone->load('matchedMap')->toPublicArray();

    expect($public['copy_link'])->toBeNull()
        ->and($public['matched_map'])->toBeNull();

    // لینک دک کلش رویال (ساختار متفاوت) دست‌نخورده می‌ماند.
    $deck = $user->baseClones()->create([
        'slug' => 'sigtest12346',
        'game' => 'clash_royale',
        'title' => 'دک',
        'image_path' => 'base-clones/y.jpg',
        'layout' => ['type' => 'deck', 'cards' => []],
        'copy_link' => 'https://link.clashroyale.com/deck/en?deck=26000000;26000001',
    ]);

    expect($deck->toPublicArray()['copy_link'])->toBe('https://link.clashroyale.com/deck/en?deck=26000000;26000001');
});
