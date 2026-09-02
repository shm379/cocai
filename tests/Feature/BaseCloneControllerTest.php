<?php

use App\Models\BaseClone;
use App\Models\Map;
use App\Models\User;
use App\Services\BaseClone\ImageHasher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Http::preventStrayRequests();
    Storage::fake('public');
    $this->withoutVite();
});

function fakeVisionLayout(): array
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
        'town_hall_box' => ['x' => 45, 'y' => 45, 'w' => 10, 'h' => 9],
        'buildings' => [
            ['type' => 'town_hall', 'x' => 50, 'y' => 50, 'level' => 15],
            ['type' => 'eagle_artillery', 'x' => 50, 'y' => 30],
            ['type' => 'cannon', 'x' => 30, 'y' => 50],
            ['type' => 'x_bow_air', 'x' => 70, 'y' => 50],
        ],
        'walls' => [
            ['x1' => 30, 'y1' => 30, 'x2' => 70, 'y2' => 30],
        ],
    ];
}

function fakeVisionResponse(array $layout): void
{
    Http::fake([
        '*/v1/chat/completions' => Http::response([
            'choices' => [
                ['message' => ['content' => "```json\n".json_encode($layout)."\n```"]],
            ],
        ], 200),
    ]);
}

it('clones a base from an uploaded image and returns a share link', function () {
    fakeVisionResponse(fakeVisionLayout());

    $user = User::factory()->create();
    $image = UploadedFile::fake()->image('base.jpg', 400, 300);

    $response = $this->actingAs($user)
        ->postJson('/api/base-clones', ['image' => $image, 'title' => 'بیس تست']);

    $response->assertCreated()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('clone.title', 'بیس تست')
        ->assertJsonPath('clone.th_level', 15)
        ->assertJsonPath('clone.layout.grid_size', 44)
        ->assertJsonPath('clone.layout.buildings.0.type', 'town_hall')
        ->assertJsonPath('clone.layout.buildings.3.type', 'x_bow')
        ->assertJsonPath('clone.layout.stats.placed_count', 4)
        ->assertJsonPath('clone.matched_map', null);

    $shareUrl = $response->json('clone.share_url');
    $slug = $response->json('clone.slug');

    expect($shareUrl)->toBe(route('base-clone.show', $slug))
        ->and($response->json('clone.layout.walls'))->not->toBeEmpty();

    $this->assertDatabaseHas('base_clones', [
        'user_id' => $user->id,
        'slug' => $slug,
        'th_level' => 15,
    ]);

    Storage::disk('public')->assertExists(BaseClone::where('slug', $slug)->first()->image_path);
});

it('serves the public share page to guests', function () {
    fakeVisionResponse(fakeVisionLayout());

    $user = User::factory()->create();
    $image = UploadedFile::fake()->image('base.jpg', 400, 300);

    $slug = $this->actingAs($user)
        ->postJson('/api/base-clones', ['image' => $image])
        ->json('clone.slug');

    auth()->logout();

    $this->get(route('base-clone.show', $slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('BaseClone/Show')
            ->where('clone.slug', $slug)
            ->where('isOwner', false)
        );

    expect(BaseClone::where('slug', $slug)->first()->view_count)->toBe(1);
});

it('returns the in-game copy link when the image matches an archived map', function () {
    fakeVisionResponse(fakeVisionLayout());

    $user = User::factory()->create();
    $image = UploadedFile::fake()->image('base.jpg', 400, 300);
    $hash = app(ImageHasher::class)->hashFile($image->getRealPath());

    $map = Map::factory()->create([
        'image_hash' => $hash,
        'copy_link' => 'https://link.clashofclans.com/en?action=OpenLayout&id=TH15%3AWB%3AAAAAKQAAAAHKhX8bPxJyNAX1FJlbFuFy',
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/base-clones', ['image' => $image]);

    $response->assertCreated()
        ->assertJsonPath('clone.matched_map.id', $map->id)
        ->assertJsonPath('clone.matched_map.copy_link', $map->copy_link)
        ->assertJsonPath('clone.match_similarity', 100)
        ->assertJsonPath('matches.0.confident', true);
});

it('rejects the request when the vision model returns no buildings', function () {
    fakeVisionResponse(['buildings' => []]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/base-clones', ['image' => UploadedFile::fake()->image('base.jpg', 200, 200)])
        ->assertStatus(422)
        ->assertJsonPath('ok', false);

    expect(BaseClone::count())->toBe(0);
});

it('returns 503 when AI vision is not configured', function () {
    config()->set('services.nabu.api_key', null);

    $this->actingAs(User::factory()->create())
        ->postJson('/api/base-clones', ['image' => UploadedFile::fake()->image('base.jpg', 200, 200)])
        ->assertStatus(503);
});

it('lists and deletes only the owner clones', function () {
    fakeVisionResponse(fakeVisionLayout());

    $owner = User::factory()->create();
    $other = User::factory()->create();

    $slug = $this->actingAs($owner)
        ->postJson('/api/base-clones', ['image' => UploadedFile::fake()->image('base.jpg', 300, 300)])
        ->json('clone.slug');

    $this->actingAs($owner)->getJson('/api/base-clones')
        ->assertOk()
        ->assertJsonCount(1, 'clones');

    $this->actingAs($other)->getJson('/api/base-clones')
        ->assertOk()
        ->assertJsonCount(0, 'clones');

    $this->actingAs($other)->deleteJson("/api/base-clones/{$slug}")->assertStatus(403);
    $this->actingAs($owner)->deleteJson("/api/base-clones/{$slug}")->assertOk();

    expect(BaseClone::where('slug', $slug)->exists())->toBeFalse();
});
