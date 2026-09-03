<?php

use App\Models\Map;
use App\Models\Topic;
use App\Services\MapSources\ClasherSource;
use App\Services\MapSources\MapImporter;
use Illuminate\Support\Facades\Http;

const VALID_LINK_A = 'https://link.clashofclans.com/en?action=OpenLayout&id=TH16%3AWB%3AAAAAKQAAAAHKhX8bPxJyNAX1FJlbFuFy';
const VALID_LINK_B = 'https://link.clashofclans.com/en?action=OpenLayout&id=TH16%3AWB%3ABBBBKQAAAAHKhX8bPxJyNAX1FJlbFuFy';

function clasherItem(string $id, string $name, ?string $copyLink, array $extra = []): array
{
    return array_merge([
        '_id' => $id,
        'name' => $name,
        'url' => "https://www.clasher.us/design/th16-war-base/{$id}",
        'copy_link' => $copyLink,
        'img' => "https://img.clasher.us/images/fullo/v2-{$id}.jpg",
        'img_tn' => "https://img.clasher.us/images/thumbo/v2-{$id}.jpg",
        'viewCount' => 10, 'downCount' => 5, 'likeCount' => 2, 'reportCount' => 0,
    ], $extra);
}

beforeEach(function () {
    Http::preventStrayRequests();
    app(ClasherSource::class)->delayMs = 0;
});

it('imports new Clasher maps, links topics, and is idempotent', function () {
    Http::fake([
        'www.clasher.us/api/mobile/designs/town-hall-16-war-base*' => Http::response([
            clasherItem('6a993398f7c2b9a1c4d5e6f1', 'TH16 War base #1', VALID_LINK_A),
            clasherItem('6a993398f7c2b9a1c4d5e6f2', 'TH16 War base #2', 'https://link.clashofclans.com/en?action=OpenLayout&id=TH16_FAKE'),
        ], 200),
    ]);

    $this->artisan('maps:update', ['--source' => 'clasher', '--th' => 16, '--village' => 'home', '--category' => 'war'])
        ->assertSuccessful();

    expect(Map::count())->toBe(2);
    $a = Map::where('external_id', '6a993398f7c2b9a1c4d5e6f1')->first();
    expect($a->copy_link)->toBe(VALID_LINK_A)
        ->and($a->source)->toBe('clasher')
        ->and($a->category)->toBe('war')
        ->and($a->published_at)->not->toBeNull()
        ->and($a->image_url)->toBe('https://img.clasher.us/images/fullo/v2-6a993398f7c2b9a1c4d5e6f1.jpg');

    $b = Map::where('external_id', '6a993398f7c2b9a1c4d5e6f2')->first();
    expect($b->copy_link)->toBeNull(); // لینک با فرمت جعلی ذخیره نمی‌شود

    $topic = Topic::where('hall_type', 0)->where('hall_level', 16)->first();
    expect($topic)->not->toBeNull()
        ->and(trim($topic->name))->toBe('Town Hall 16 War')
        ->and($a->topics()->count())->toBe(1);

    // اجرای دوباره: هیچ ردیف جدیدی نه
    $this->artisan('maps:update', ['--source' => 'clasher', '--th' => 16, '--village' => 'home', '--category' => 'war'])->assertSuccessful();
    expect(Map::count())->toBe(2)->and($a->fresh()->topics()->count())->toBe(1);
});

it('never replaces a valid copy link with an invalid one, but upgrades an invalid one', function () {
    $existing = Map::factory()->create([
        'map_link' => 'https://www.clasher.us/design/th16-war-base/6a993398f7c2b9a1c4d5e6f1',
        'copy_link' => VALID_LINK_A,
        'external_id' => null,
    ]);
    $noLink = Map::factory()->create([
        'map_link' => 'https://www.clasher.us/design/th16-war-base/6a993398f7c2b9a1c4d5e6f2',
        'copy_link' => null,
    ]);

    Http::fake([
        'www.clasher.us/api/mobile/designs/town-hall-16-war-base*' => Http::response([
            clasherItem('6a993398f7c2b9a1c4d5e6f1', 'renamed', null),
            clasherItem('6a993398f7c2b9a1c4d5e6f2', 'now has link', VALID_LINK_B),
        ], 200),
    ]);

    $this->artisan('maps:update', ['--source' => 'clasher', '--th' => 16, '--village' => 'home', '--category' => 'war'])->assertSuccessful();

    expect(Map::count())->toBe(2)
        ->and($existing->fresh()->copy_link)->toBe(VALID_LINK_A)
        ->and($existing->fresh()->name)->toBe('renamed')
        ->and($noLink->fresh()->copy_link)->toBe(VALID_LINK_B);
});

it('supports dry-run and since filtering', function () {
    Http::fake([
        'www.clasher.us/api/mobile/designs/town-hall-16-war-base*' => Http::response([
            clasherItem('6a993398f7c2b9a1c4d5e6f1', 'new', VALID_LINK_A),   // 2026
            clasherItem('5eb94bbe8baa2d043d326e80', 'old (2020)', VALID_LINK_B),
        ], 200),
    ]);

    $this->artisan('maps:update', ['--source' => 'clasher', '--th' => 16, '--village' => 'home', '--category' => 'war', '--dry-run' => true])->assertSuccessful();
    expect(Map::count())->toBe(0);

    $this->artisan('maps:update', ['--source' => 'clasher', '--th' => 16, '--village' => 'home', '--category' => 'war', '--since' => '2025-01-01'])->assertSuccessful();
    expect(Map::count())->toBe(1)->and(Map::first()->name)->toBe('new');
});

it('rejects an unknown source and decodes ObjectId timestamps', function () {
    $this->artisan('maps:update', ['--source' => 'nope'])->assertFailed();

    expect(ClasherSource::objectIdTime('5eb94bbe8baa2d043d326e80')->year)->toBe(2020)
        ->and(ClasherSource::objectIdTime('not-an-id'))->toBeNull()
        ->and(MapImporter::normalizeLink('http://www.clasher.us/design/x/'))->toBe('https://www.clasher.us/design/x');
});

it('hashes newly imported maps with --hash using the full image', function () {
    $png = (function () { $i = imagecreatetruecolor(160, 120); for ($y = 0; $y < 120; $y++) { for ($x = 0; $x < 160; $x++) { imagesetpixel($i, $x, $y, imagecolorallocate($i, ($x * 7) % 256, ($y * 11) % 256, ($x * $y) % 256)); } } ob_start(); imagepng($i); return ob_get_clean(); })();
    Http::fake([
        'www.clasher.us/api/mobile/designs/town-hall-16-war-base*' => Http::response([clasherItem('6a993398f7c2b9a1c4d5e6f1', 'x', VALID_LINK_A)], 200),
        'img.clasher.us/images/full/*' => Http::response(str_repeat($png, 1), 200),
        'img.clasher.us/*' => Http::response('', 404),
    ]);

    $this->artisan('maps:update', ['--source' => 'clasher', '--th' => 16, '--village' => 'home', '--category' => 'war', '--hash' => true])->assertSuccessful();

    expect(Map::first()->image_hash)->toMatch('/^[0-9a-f]{16}$/');
});
