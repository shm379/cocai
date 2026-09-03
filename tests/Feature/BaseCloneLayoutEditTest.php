<?php

use App\Models\BaseClone;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Http::preventStrayRequests();
    Storage::fake('public');
    $this->withoutVite();
});

/**
 * یک بیس چیدمان‌محور با سه ساختمان و دو دیوار (شکل خروجی LayoutGridMapper).
 */
function makeLayoutClone(User $user, array $layoutOverrides = [], array $attributes = []): BaseClone
{
    $layout = array_merge([
        'type' => 'layout',
        'village' => 'home',
        'grid_size' => 44,
        'th_level' => 15,
        'perspective' => 'isometric',
        'corners_source' => 'model',
        'buildings' => [
            ['id' => 1, 'type' => 'town_hall', 'label' => 'تاون‌هال', 'category' => 'core', 'color' => '#f59e0b', 'icon' => '🏰', 'size' => 4, 'x' => 20, 'y' => 20, 'placed' => true, 'level' => 15],
            ['id' => 2, 'type' => 'cannon', 'label' => 'کنون', 'category' => 'defense', 'color' => '#6b7280', 'icon' => '💣', 'size' => 3, 'x' => 10, 'y' => 10, 'placed' => true, 'uncertain' => true, 'shift' => 2],
            ['id' => 3, 'type' => 'hidden_tesla', 'label' => 'تسلا', 'category' => 'defense', 'color' => '#facc15', 'icon' => '⚡', 'size' => 2, 'x' => 30, 'y' => 30, 'placed' => true],
        ],
        'walls' => [[5, 5], [6, 5]],
        'stats' => ['building_count' => 3, 'placed_count' => 3, 'unplaced_count' => 0, 'wall_count' => 2, 'by_category' => ['core' => 1, 'defense' => 2], 'by_type' => ['town_hall' => 1, 'cannon' => 1, 'hidden_tesla' => 1]],
        'version' => 1,
        'source' => 'ai',
        'quality' => ['trimmed' => true, 'score' => 0.7],
        'raw' => ['model_output' => '...'],
    ], $layoutOverrides);

    return BaseClone::create(array_merge([
        'user_id' => $user->id,
        'slug' => Str::lower(Str::random(12)),
        'game' => 'coc_home',
        'title' => 'بیس تست',
        'image_path' => 'base-clones/test.jpg',
        'th_level' => 15,
        'layout' => $layout,
    ], $attributes));
}

function validEdit(array $overrides = []): array
{
    return array_merge([
        'version' => 1,
        'buildings' => [
            ['id' => 1, 'type' => 'town_hall', 'x' => 20, 'y' => 20, 'level' => 15],
            ['id' => 2, 'type' => 'cannon', 'x' => 10, 'y' => 12, 'user_fixed' => true],
            ['id' => 3, 'type' => 'hidden_tesla', 'x' => 30, 'y' => 30],
        ],
        'walls' => [[5, 5], [6, 5]],
    ], $overrides);
}

// ---------------------------------------------------------------- catalog

it('returns the home village catalog with grid size and catalog-authoritative metadata', function () {
    $this->actingAs(User::factory()->create())
        ->getJson('/api/base-clones/catalog?game=coc_home')
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('game', 'coc_home')
        ->assertJsonPath('grid_size', 44)
        ->assertJsonPath('types.town_hall.size', 4)
        ->assertJsonPath('types.town_hall.label', 'تاون‌هال')
        ->assertJsonPath('types.hidden_tesla.size', 2)
        ->assertJsonPath('types.cannon.category', 'defense')
        ->assertJsonPath('limits.buildings', 300)
        ->assertJsonPath('limits.walls', 400)
        ->assertJsonMissingPath('types.wall');
});

it('returns the builder base catalog', function () {
    $this->actingAs(User::factory()->create())
        ->getJson('/api/base-clones/catalog?game=coc_builder')
        ->assertOk()
        ->assertJsonPath('game', 'coc_builder')
        ->assertJsonPath('village', 'builder')
        ->assertJsonPath('grid_size', 44)
        ->assertJsonPath('types.builder_hall.size', 4)
        ->assertJsonMissingPath('types.town_hall');
});

it('rejects the catalog for deck games and unknown games', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/base-clones/catalog?game=clash_royale')
        ->assertStatus(422)
        ->assertJsonPath('ok', false);

    $this->actingAs($user)->getJson('/api/base-clones/catalog?game=nope')
        ->assertStatus(422)
        ->assertJsonPath('ok', false);

    auth()->logout();
    $this->getJson('/api/base-clones/catalog?game=coc_home')->assertUnauthorized();
});

// ---------------------------------------------------------------- update

it('lets the owner save an edited layout, bumps the version and recomputes stats', function () {
    $owner = User::factory()->create();
    $clone = makeLayoutClone($owner);

    $edit = validEdit();
    $edit['buildings'][] = ['id' => 4, 'type' => 'archer', 'x' => 0, 'y' => 0, 'level' => 3];
    $edit['walls'] = [[5, 5], [7, 7], [7, 7]];

    $response = $this->actingAs($owner)
        ->putJson("/api/base-clones/{$clone->slug}/layout", $edit)
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('clone.layout.version', 2)
        ->assertJsonPath('clone.layout_version', 2)
        ->assertJsonPath('clone.layout.source', 'user')
        ->assertJsonPath('clone.can_edit', true)
        ->assertJsonPath('clone.layout.stats.building_count', 4)
        ->assertJsonPath('clone.layout.stats.placed_count', 4)
        ->assertJsonPath('clone.layout.stats.wall_count', 2)
        ->assertJsonPath('clone.layout.stats.uncertain_count', 0)
        ->assertJsonPath('clone.layout.stats.by_type.archer_tower', 1)
        ->assertJsonPath('clone.layout.quality.trimmed', true);

    $buildings = collect($response->json('clone.layout.buildings'))->keyBy('id');

    // کنون: جابه‌جا و تأیید شد → uncertain پاک، منبع کاربر، ابعاد از کاتالوگ
    expect($buildings[2]['x'])->toBe(10)
        ->and($buildings[2]['y'])->toBe(12)
        ->and($buildings[2]['uncertain'])->toBeFalse()
        ->and($buildings[2]['user_fixed'])->toBeTrue()
        ->and($buildings[2]['source'])->toBe('user')
        ->and($buildings[2]['size'])->toBe(3)
        ->and($buildings[2]['shift'])->toBe(0);

    // ساختمان جدید با alias: نوع نرمال شده و متادیتا از کاتالوگ
    expect($buildings[4]['type'])->toBe('archer_tower')
        ->and($buildings[4]['label'])->toBe('آرچر تاور')
        ->and($buildings[4]['size'])->toBe(3)
        ->and($buildings[4]['level'])->toBe(3)
        ->and($buildings[4]['source'])->toBe('user');

    // تاون‌هال دست‌نخورده: منبع AI باقی می‌ماند و سطح حفظ می‌شود
    expect($buildings[1]['source'])->toBe('ai')
        ->and($buildings[1]['level'])->toBe(15);

    $stored = $clone->fresh()->layout;
    expect($stored['version'])->toBe(2)
        ->and($stored['source'])->toBe('user')
        ->and($stored['walls'])->toBe([[5, 5], [7, 7]])
        ->and($stored['edited_at'])->not->toBeEmpty()
        ->and($stored['quality']['trimmed'])->toBeTrue();
});

it('ignores client-supplied size/color/label and takes them from the catalog', function () {
    $owner = User::factory()->create();
    $clone = makeLayoutClone($owner);

    $edit = validEdit();
    $edit['buildings'][0] = ['id' => 1, 'type' => 'town_hall', 'x' => 20, 'y' => 20, 'size' => 1, 'color' => '#000', 'label' => 'hack'];

    $response = $this->actingAs($owner)
        ->putJson("/api/base-clones/{$clone->slug}/layout", $edit)
        ->assertOk();

    $th = collect($response->json('clone.layout.buildings'))->firstWhere('id', 1);
    expect($th['size'])->toBe(4)
        ->and($th['color'])->toBe('#f59e0b')
        ->and($th['label'])->toBe('تاون‌هال');
});

it('forbids non-owners and guests from editing the layout', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $clone = makeLayoutClone($owner);

    $this->actingAs($other)
        ->putJson("/api/base-clones/{$clone->slug}/layout", validEdit())
        ->assertStatus(403)
        ->assertJsonPath('ok', false);

    auth()->logout();
    $this->putJson("/api/base-clones/{$clone->slug}/layout", validEdit())->assertUnauthorized();

    expect($clone->fresh()->layout['version'])->toBe(1);
});

it('rejects overlapping buildings with a per-item error and never auto-shifts', function () {
    $owner = User::factory()->create();
    $clone = makeLayoutClone($owner);

    $edit = validEdit();
    // کنون ۳×۳ در (21,21) روی تاون‌هال ۴×۴ در (20,20) می‌افتد
    $edit['buildings'][1] = ['id' => 2, 'type' => 'cannon', 'x' => 21, 'y' => 21];

    $errors = $this->actingAs($owner)
        ->putJson("/api/base-clones/{$clone->slug}/layout", $edit)
        ->assertStatus(422)
        ->assertJsonPath('ok', false)
        ->assertJsonPath('reason', 'layout')
        ->json('errors');

    expect($errors)->toBe(['buildings.1' => 'هم‌پوشانی با ساختمان #1']);

    $stored = $clone->fresh()->layout;
    expect($stored['version'])->toBe(1)
        ->and($stored['buildings'][1]['x'])->toBe(10);
});

it('rejects walls placed under a building', function () {
    $owner = User::factory()->create();
    $clone = makeLayoutClone($owner);

    $edit = validEdit(['walls' => [[5, 5], [21, 21]]]);

    $errors = $this->actingAs($owner)
        ->putJson("/api/base-clones/{$clone->slug}/layout", $edit)
        ->assertStatus(422)
        ->json('errors');

    expect($errors)->toBe(['walls.1' => 'دیوار روی ساختمان #1 قرار دارد.']);
});

it('rejects unknown types, wall as a building type, and out-of-grid coordinates', function () {
    $owner = User::factory()->create();
    $clone = makeLayoutClone($owner);

    $edit = validEdit();
    $edit['buildings'][] = ['id' => 4, 'type' => 'dragon_lair', 'x' => 0, 'y' => 0];
    $edit['buildings'][] = ['id' => 5, 'type' => 'wall', 'x' => 1, 'y' => 1];
    $edit['buildings'][] = ['id' => 6, 'type' => 'cannon', 'x' => 42, 'y' => 0]; // ۳×۳ در x=42 از شبکهٔ ۴۴ بیرون می‌زند
    $edit['walls'][] = [44, 0];

    $errors = $this->actingAs($owner)
        ->putJson("/api/base-clones/{$clone->slug}/layout", $edit)
        ->assertStatus(422)
        ->json('errors');

    expect($errors)->toBe([
        'buildings.3.type' => 'نوع ساختمان نامعتبر است.',
        'buildings.4.type' => 'نوع ساختمان نامعتبر است.',
        'buildings.5' => 'ساختمان خارج از نقشه است.',
        'walls.2' => 'دیوار خارج از نقشه است.',
    ]);
});

it('returns 409 with the current layout when the version is stale', function () {
    $owner = User::factory()->create();
    $clone = makeLayoutClone($owner, ['version' => 3]);

    $this->actingAs($owner)
        ->putJson("/api/base-clones/{$clone->slug}/layout", validEdit(['version' => 2]))
        ->assertStatus(409)
        ->assertJsonPath('ok', false)
        ->assertJsonPath('reason', 'version')
        ->assertJsonPath('current_version', 3)
        ->assertJsonPath('clone.layout.version', 3)
        ->assertJsonPath('clone.layout.buildings.1.x', 10);

    expect($clone->fresh()->layout['version'])->toBe(3);
});

it('enforces the building and wall caps and basic shape rules', function () {
    $owner = User::factory()->create();
    $clone = makeLayoutClone($owner);

    $tooManyWalls = [];
    for ($i = 0; $i < 401; $i++) {
        $tooManyWalls[] = [$i % 44, intdiv($i, 44)];
    }

    $this->actingAs($owner)
        ->putJson("/api/base-clones/{$clone->slug}/layout", validEdit(['walls' => $tooManyWalls]))
        ->assertStatus(422)
        ->assertJsonPath('ok', false);

    $this->actingAs($owner)
        ->putJson("/api/base-clones/{$clone->slug}/layout", ['version' => 1, 'buildings' => [['id' => 1, 'type' => 'town_hall', 'x' => 'a', 'y' => 0]]])
        ->assertStatus(422)
        ->assertJsonPath('ok', false);
});

it('rejects layout edits on deck clones', function () {
    $owner = User::factory()->create();
    $clone = makeLayoutClone($owner, [
        'type' => 'deck',
        'buildings' => [],
        'walls' => [],
        'cards' => [],
    ], ['game' => 'clash_royale']);

    $this->actingAs($owner)
        ->putJson("/api/base-clones/{$clone->slug}/layout", validEdit())
        ->assertStatus(422)
        ->assertJsonPath('ok', false);
});

// ---------------------------------------------------------------- read

it('serves the owner JSON with private keys and hides them from non-owners', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $clone = makeLayoutClone($owner);

    $this->actingAs($owner)
        ->getJson("/api/base-clones/{$clone->slug}")
        ->assertOk()
        ->assertJsonPath('clone.slug', $clone->slug)
        ->assertJsonPath('clone.layout.version', 1)
        ->assertJsonPath('clone.layout.source', 'ai')
        ->assertJsonPath('clone.can_edit', true)
        ->assertJsonPath('clone.layout.quality.trimmed', true)
        ->assertJsonPath('clone.layout.raw.model_output', '...');

    $this->actingAs($other)
        ->getJson("/api/base-clones/{$clone->slug}")
        ->assertStatus(403);

    auth()->logout();

    $this->get(route('base-clone.show', $clone->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('BaseClone/Show')
            ->where('isOwner', false)
            ->where('clone.can_edit', false)
            ->where('clone.layout.version', 1)
            ->where('clone.layout.buildings.0.type', 'town_hall')
            ->missing('clone.layout.quality')
            ->missing('clone.layout.raw')
        );

    $public = $clone->fresh()->toPublicArray();
    expect($public['layout'])->not->toHaveKey('quality')
        ->and($public['layout'])->not->toHaveKey('raw')
        ->and($public['layout']['version'])->toBe(1);
});
