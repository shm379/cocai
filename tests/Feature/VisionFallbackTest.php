<?php

use App\Models\User;
use App\Services\AI\LayoutVisionExtractor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Http::preventStrayRequests();
    Storage::fake('public');
    config()->set('services.nabu.base_url', 'https://gate.test');
    config()->set('services.nabu.api_key', 'token');
    config()->set('services.nabu.model', 'nabu-smart');
    config()->set('services.nabu.vision_model', 'nabu-vision');
});

function layoutJson(): string
{
    return json_encode([
        'town_hall_level' => 12,
        'perspective' => 'top_down',
        'buildings' => [['type' => 'town_hall', 'x' => 50, 'y' => 50]],
    ]);
}

it('falls back to the general model when the vision alias is rejected', function () {
    Http::fake(function ($request) {
        $model = $request->data()['model'] ?? null;

        if ($model === 'nabu-vision') {
            return Http::response(['error' => ['message' => 'all targets failed']], 502);
        }

        return Http::response(['choices' => [['message' => ['content' => layoutJson()]]], 'model' => $model], 200);
    });

    $result = app(LayoutVisionExtractor::class)->extractLayout(UploadedFile::fake()->image('b.jpg', 200, 200));

    expect($result['ok'])->toBeTrue()
        ->and($result['model'])->toBe('nabu-smart')
        ->and($result['data']['buildings'][0]['type'])->toBe('town_hall');

    // ۲ تلاش روی alias تصویری (5xx) + ۱ تلاش موفق روی مدل عمومی
    Http::assertSentCount(3);
});

it('skips a model that the token may not use and reports auth when all fail', function () {
    Http::fake(['*' => Http::response(['error' => ['message' => 'invalid or missing API key']], 401)]);

    $extractor = app(LayoutVisionExtractor::class);
    $result = $extractor->extractLayout(UploadedFile::fake()->image('b.jpg', 200, 200));

    expect($result['ok'])->toBeFalse()
        ->and($result['reason'])->toBe('auth')
        ->and($result['message'])->toContain('توکن');

    Http::assertSentCount(2); // یک بار برای هر مدل، بدون تکرار
});

it('treats an empty completion as a failure and moves on', function () {
    Http::fake(function ($request) {
        $model = $request->data()['model'] ?? null;

        if ($model === 'nabu-vision') {
            return Http::response(['choices' => [['message' => ['content' => '']]]], 200);
        }

        return Http::response(['choices' => [['message' => ['content' => [['type' => 'text', 'text' => layoutJson()]]]]]], 200);
    });

    $result = app(LayoutVisionExtractor::class)->extractLayout(UploadedFile::fake()->image('b.jpg', 200, 200));

    expect($result['ok'])->toBeTrue()
        ->and($result['data']['town_hall_level'])->toBe(12);
});

it('returns 503 with a connection reason when the gateway is unreachable', function () {
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('cURL error 7: Failed to connect'));

    $this->actingAs(User::factory()->create())
        ->postJson('/api/base-clones', ['image' => UploadedFile::fake()->image('b.jpg', 200, 200), 'game' => 'coc_home'])
        ->assertStatus(503)
        ->assertJsonPath('reason', 'connection');
});

it('salvages buildings from a truncated JSON response', function () {
    $truncated = '{"town_hall_level":14,"perspective":"isometric","grid_corners":{"top":{"x":50,"y":0},"right":{"x":100,"y":50},"bottom":{"x":50,"y":100},"left":{"x":0,"y":50}},"town_hall_box":null,"buildings":[{"type":"town_hall","x":50,"y":50},{"type":"cannon","x":30,"y":30},{"type":"archer_tower","x":70,"y":7';

    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => $truncated], 'finish_reason' => 'length']]], 200)]);

    $result = app(LayoutVisionExtractor::class)->extractLayout(UploadedFile::fake()->image('b.jpg', 200, 200));

    expect($result['ok'])->toBeTrue()
        ->and($result['data']['town_hall_level'])->toBe(14)
        ->and($result['data']['grid_corners']['right']['x'])->toBe(100)
        ->and(array_column($result['data']['buildings'], 'type'))->toBe(['town_hall', 'cannon']);
});

it('parses the compact layout schema', function () {
    $compact = '{"th":15,"p":"iso","c":[50,0,100,50,50,100,0,50],"thb":[45,45,10,9],"b":[["town_hall",50,50,15],["cannon",30,30],["x_bow_air",70,30]],"w":[[30,30,70,30]]}';
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => $compact], 'finish_reason' => 'stop']]], 200)]);

    $result = app(LayoutVisionExtractor::class)->extractLayout(UploadedFile::fake()->image('b.jpg', 200, 200));

    expect($result['ok'])->toBeTrue()
        ->and($result['data']['town_hall_level'])->toBe(15)
        ->and($result['data']['perspective'])->toBe('isometric')
        ->and($result['data']['grid_corners']['left'])->toBe(['x' => 0, 'y' => 50])
        ->and($result['data']['town_hall_box']['w'])->toBe(10)
        ->and(array_column($result['data']['buildings'], 'type'))->toBe(['town_hall', 'cannon', 'x_bow'])
        ->and($result['data']['buildings'][0]['level'])->toBe(15)
        ->and($result['data']['walls'][0])->toBe(['x1' => 30.0, 'y1' => 30.0, 'x2' => 70.0, 'y2' => 30.0]);
});

it('salvages a truncated compact response including walls', function () {
    $truncated = '{"th":13,"p":"iso","c":[50,0,100,50,50,100,0,50],"thb":null,"b":[["town_hall",50,50],["mortar",40,40,12],["archer_tower",60,4';
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => $truncated], 'finish_reason' => 'length']]], 200)]);

    $result = app(LayoutVisionExtractor::class)->extractLayout(UploadedFile::fake()->image('b.jpg', 200, 200));

    expect($result['ok'])->toBeTrue()
        ->and($result['data']['town_hall_level'])->toBe(13)
        ->and($result['data']['grid_corners']['right']['x'])->toBe(100)
        ->and(array_column($result['data']['buildings'], 'type'))->toBe(['town_hall', 'mortar'])
        ->and($result['data']['buildings'][1]['level'])->toBe(12);
});

it('parses the bbox schema with 0-1000 integer coordinates', function () {
    $compact = '{"th":15,"p":"iso","d":[10,10,990,990],"c":null,"b":[["town_hall",463,404,538,480,15],["cannon",300,300,350,350],["x_bow_air",700,300,750,350]],"w":[[490,390,460,360]]}';
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => $compact], 'finish_reason' => 'stop']]], 200)]);

    $result = app(LayoutVisionExtractor::class)->extractLayout(UploadedFile::fake()->image('b.jpg', 400, 300));
    $data = $result['data'];

    expect($result['ok'])->toBeTrue()
        ->and($data['schema_version'])->toBe(2)
        ->and($data['town_hall_level'])->toBe(15)
        ->and($data['grid_corners'])->toBeNull()
        ->and($data['diamond_box'])->toBe([1.0, 1.0, 99.0, 99.0])
        ->and($data['image_size'])->toBe([400, 300])
        ->and($data['axes_swapped'])->toBeFalse()
        ->and(array_column($data['buildings'], 'type'))->toBe(['town_hall', 'cannon', 'x_bow'])
        ->and($data['buildings'][0]['box'])->toBe([46.3, 40.4, 53.8, 48.0])
        ->and($data['buildings'][0]['x'])->toBe(50.05)
        ->and($data['buildings'][0]['y'])->toBe(44.2)
        ->and($data['buildings'][0]['level'])->toBe(15)
        ->and($data['buildings'][1])->not->toHaveKey('level')
        ->and($data['walls'][0])->toBe(['x1' => 49.0, 'y1' => 39.0, 'x2' => 46.0, 'y2' => 36.0]);
});

it('salvages truncated bbox rows and keeps the diamond box', function () {
    $truncated = '{"th":14,"p":"iso","d":[20,0,980,900],"c":null,"b":[["town_hall",463,404,538,480,14],["cannon",300,300,350,350],["archer_tower",600,6';
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => $truncated], 'finish_reason' => 'length']]], 200)]);

    $result = app(LayoutVisionExtractor::class)->extractLayout(UploadedFile::fake()->image('b.jpg', 200, 200));
    $data = $result['data'];

    expect($result['ok'])->toBeTrue()
        ->and($data['schema_version'])->toBe(2)
        ->and($data['town_hall_level'])->toBe(14)
        ->and($data['diamond_box'])->toBe([2.0, 0.0, 98.0, 90.0])
        ->and(array_column($data['buildings'], 'type'))->toBe(['town_hall', 'cannon'])
        ->and($data['buildings'][0]['level'])->toBe(14)
        ->and($data['buildings'][1]['box'])->toBe([30.0, 30.0, 35.0, 35.0])
        ->and($data['walls'])->toBe([]);
});

it('detects and repairs yxyx coordinate order', function () {
    // لوزی بلندتر از پهن و شیب دیوارها > ۱ ⇒ محورها جابه‌جا شده‌اند.
    // لوزی واقعی: x از ۱۰۰ تا ۹۰۰ و y از ۲۰۰ تا ۶۰۰؛ دیوارها با شیب واقعی ۰٫۵ — همه به ترتیب [y,x] نوشته شده‌اند.
    $swapped = '{"th":13,"p":"iso","d":[200,100,600,900],"c":null,"b":[["town_hall",400,450,480,550],["cannon",300,300,350,350],["cannon",600,300,650,350]],'
        .'"w":[[300,100,350,200],[350,200,400,300],[400,300,450,400],[450,400,500,500],[500,500,550,600],[550,600,600,700]]}';
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => $swapped], 'finish_reason' => 'stop']]], 200)]);

    $result = app(LayoutVisionExtractor::class)->extractLayout(UploadedFile::fake()->image('b.jpg', 200, 200));
    $data = $result['data'];

    expect($data['axes_swapped'])->toBeTrue()
        ->and($data['diamond_box'])->toBe([10.0, 20.0, 90.0, 60.0])
        ->and($data['buildings'][0]['box'])->toBe([45.0, 40.0, 55.0, 48.0])
        ->and($data['buildings'][0]['x'])->toBe(50.0)
        ->and($data['walls'][0])->toBe(['x1' => 10.0, 'y1' => 30.0, 'x2' => 20.0, 'y2' => 35.0]);
});

it('reports a service problem, not an image problem, when the model leaks reasoning instead of JSON', function () {
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => '[Preamble: This is an internal thinking process for a large language model. The user is asking to create an ARC...']]]], 200)]);

    $this->actingAs(User::factory()->create())
        ->postJson('/api/base-clones', ['image' => UploadedFile::fake()->image('b.jpg', 200, 200), 'game' => 'coc_home'])
        ->assertStatus(503)
        ->assertJsonPath('reason', 'server');
});
