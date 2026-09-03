<?php

use App\Models\BaseClone;
use App\Models\Map;
use App\Models\User;
use App\Services\BaseClone\ImageHasher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

const MF_LINK = 'https://link.clashofclans.com/en?action=OpenLayout&id=TH15%3AWB%3AAAAAKQAAAAHKhX8bPxJyNAX1FJlbFuFy';

beforeEach(function () {
    Http::preventStrayRequests();
    Storage::fake('public');
    $this->withoutVite();
});

function mfVisionPayload(): array
{
    return [
        'th' => 15, 'p' => 'top', 'd' => null, 'c' => null,
        'b' => [['town_hall', 480, 480, 520, 520], ['cannon', 280, 280, 320, 320]],
        'w' => [],
    ];
}

it('returns the archive link immediately without calling vision when the image matches', function () {
    $image = UploadedFile::fake()->image('base.jpg', 400, 300);
    $hash = app(ImageHasher::class)->hashFile($image->getRealPath());
    $map = Map::factory()->create(['image_hash' => $hash, 'copy_link' => MF_LINK, 'image_url' => 'https://img.clasher.us/images/fullo/x.jpg']);

    Http::fake(); // هر تماس با gateway ثبت می‌شود

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/base-clones', ['image' => $image, 'game' => 'coc_home']);

    $response->assertCreated()
        ->assertJsonPath('matched_first', true)
        ->assertJsonPath('clone.copy_link', MF_LINK)
        ->assertJsonPath('clone.matched_map.id', $map->id)
        ->assertJsonPath('clone.pending', true)
        ->assertJsonPath('clone.th_level', 15)
        ->assertJsonPath('clone.layout.source', 'archive')
        ->assertJsonPath('clone.can_edit', false);

    Http::assertNothingSent();
});

it('reconstructs a pending clone with AI on demand and keeps the archive link', function () {
    $image = UploadedFile::fake()->image('base.jpg', 400, 300);
    $hash = app(ImageHasher::class)->hashFile($image->getRealPath());
    Map::factory()->create(['image_hash' => $hash, 'copy_link' => MF_LINK]);

    Http::fake(['*/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => json_encode(mfVisionPayload())]]]], 200)]);

    $owner = User::factory()->create();
    $slug = $this->actingAs($owner)->postJson('/api/base-clones', ['image' => $image, 'game' => 'coc_home'])->json('clone.slug');

    $this->actingAs(User::factory()->create())->postJson("/api/base-clones/{$slug}/reconstruct")->assertStatus(403);

    $response = $this->actingAs($owner)->postJson("/api/base-clones/{$slug}/reconstruct");

    $response->assertOk()
        ->assertJsonPath('clone.pending', false)
        ->assertJsonPath('clone.copy_link', MF_LINK)
        ->assertJsonPath('clone.layout.source', 'ai')
        ->assertJsonPath('clone.layout.buildings.0.type', 'town_hall')
        ->assertJsonPath('clone.can_edit', true);

    Http::assertSentCount(1);
    expect(BaseClone::where('slug', $slug)->first()->matched_map_id)->not->toBeNull();
});

it('falls back to the AI flow when nothing in the archive matches', function () {
    Http::fake(['*/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => json_encode(mfVisionPayload())]]]], 200)]);

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/base-clones', ['image' => UploadedFile::fake()->image('base.jpg', 400, 300), 'game' => 'coc_home']);

    $response->assertCreated()
        ->assertJsonPath('matched_first', false)
        ->assertJsonPath('clone.pending', false)
        ->assertJsonPath('clone.layout.buildings.0.type', 'town_hall');

    Http::assertSentCount(1);
});
