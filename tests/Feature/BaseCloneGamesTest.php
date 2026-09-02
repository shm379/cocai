<?php

use App\Models\BaseClone;
use App\Models\User;
use App\Services\BaseClone\Games\ClashRoyaleDeckAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Http::preventStrayRequests();
    Storage::fake('public');
    $this->withoutVite();
});

function fakeVision(array $payload): void
{
    Http::fake([
        '*/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => json_encode($payload)]]],
        ], 200),
    ]);
}

it('lists active engines and coming-soon games', function () {
    $this->actingAs(User::factory()->create())
        ->getJson('/api/base-clones/games')
        ->assertOk()
        ->assertJsonPath('games.0.key', 'coc_home')
        ->assertJsonPath('games.0.result_type', 'layout')
        ->assertJsonPath('games.1.key', 'coc_builder')
        ->assertJsonPath('games.2.key', 'clash_royale')
        ->assertJsonPath('games.2.result_type', 'deck')
        ->assertJsonPath('games.3.coming_soon', true);
});

it('reads a Clash Royale deck and builds the official copy link', function () {
    fakeVision([
        'cards' => [
            ['slot' => 1, 'name' => 'Hog Rider', 'level' => 14],
            ['slot' => 2, 'name' => 'Ice Golem'],
            ['slot' => 3, 'name' => 'Musketeer'],
            ['slot' => 4, 'name' => 'Cannon'],
            ['slot' => 5, 'name' => 'Ice Spirit'],
            ['slot' => 6, 'name' => 'Skeletons'],
            ['slot' => 7, 'name' => 'Fireball'],
            ['slot' => 8, 'name' => 'The Log'],
        ],
        'tower_troop' => null,
        'source' => 'in_game',
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/base-clones', [
        'image' => UploadedFile::fake()->image('deck.png', 800, 400),
        'game' => 'clash_royale',
    ]);

    $expected = 'https://link.clashroyale.com/deck/en?deck=26000021;26000038;26000014;27000000;26000030;26000010;28000000;28000011&l=Royals';

    $response->assertCreated()
        ->assertJsonPath('clone.game', 'clash_royale')
        ->assertJsonPath('clone.result_type', 'deck')
        ->assertJsonPath('clone.copy_link', $expected)
        ->assertJsonPath('clone.layout.complete', true)
        ->assertJsonPath('clone.layout.avg_elixir', 2.6)
        ->assertJsonPath('clone.layout.cards.0.name', 'Hog Rider')
        ->assertJsonPath('clone.layout.cards.0.level', 14)
        ->assertJsonPath('clone.layout.copy_link_evo', null);

    $this->assertDatabaseHas('base_clones', [
        'user_id' => $user->id,
        'game' => 'clash_royale',
        'copy_link' => $expected,
    ]);
});

it('adds the evolution link when an evolved card is detected', function () {
    fakeVision([
        'cards' => [
            ['slot' => 1, 'name' => 'Evolved Knight', 'evolution' => true],
            ['slot' => 2, 'name' => 'Archers'],
            ['slot' => 3, 'name' => 'Goblins'],
            ['slot' => 4, 'name' => 'Giant'],
            ['slot' => 5, 'name' => 'Minions'],
            ['slot' => 6, 'name' => 'Arrows'],
            ['slot' => 7, 'name' => 'Zap'],
            ['slot' => 8, 'name' => 'Tesla'],
        ],
        'tower_troop' => 'Cannoneer',
    ]);

    $response = $this->actingAs(User::factory()->create())->postJson('/api/base-clones', [
        'image' => UploadedFile::fake()->image('deck.png', 800, 400),
        'game' => 'clash_royale',
    ]);

    $response->assertCreated()
        ->assertJsonPath('clone.layout.cards.0.evolution', true)
        ->assertJsonPath('clone.layout.stats.evolution_count', 1)
        ->assertJsonPath('clone.layout.tower_troop.name', 'Cannoneer');

    $evo = $response->json('clone.layout.copy_link_evo');
    expect($evo)->toStartWith('https://link.clashroyale.com/en/?clashroyale://copyDeck?deck=26000000;26000001;')
        ->and($evo)->toContain('&slots=1;0;0;0;0;0;0;0')
        ->and($evo)->toContain('&tt=159000001');
});

it('keeps an incomplete deck without a copy link and lists unresolved cards', function () {
    fakeVision([
        'cards' => [
            ['slot' => 1, 'name' => 'Hog Rider'],
            ['slot' => 2, 'name' => 'Dragon Statue'],
            ['slot' => 3, 'name' => 'Musketeer'],
        ],
    ]);

    $this->actingAs(User::factory()->create())->postJson('/api/base-clones', [
        'image' => UploadedFile::fake()->image('deck.png', 800, 400),
        'game' => 'clash_royale',
    ])->assertCreated()
        ->assertJsonPath('clone.copy_link', null)
        ->assertJsonPath('clone.layout.complete', false)
        ->assertJsonPath('clone.layout.unresolved.0', 'Dragon Statue')
        ->assertJsonPath('clone.layout.stats.card_count', 2);
});

it('reconstructs a Builder Base layout with the builder catalog', function () {
    fakeVision([
        'town_hall_level' => 10,
        'perspective' => 'top_down',
        'buildings' => [
            ['type' => 'builder_hall', 'x' => 50, 'y' => 50, 'level' => 10],
            ['type' => 'crusher', 'x' => 30, 'y' => 30],
            ['type' => 'mega_tesla', 'x' => 70, 'y' => 70],
            ['type' => 'eagle_artillery', 'x' => 20, 'y' => 80],
        ],
    ]);

    $response = $this->actingAs(User::factory()->create())->postJson('/api/base-clones', [
        'image' => UploadedFile::fake()->image('bb.png', 400, 400),
        'game' => 'coc_builder',
    ]);

    $types = array_column($response->json('clone.layout.buildings'), 'type');

    $response->assertCreated()
        ->assertJsonPath('clone.game', 'coc_builder')
        ->assertJsonPath('clone.layout.village', 'builder')
        ->assertJsonPath('clone.th_level', 10);

    expect($types)->toBe(['builder_hall', 'crusher', 'mega_tesla']);
});

it('rejects unknown games', function () {
    $this->actingAs(User::factory()->create())->postJson('/api/base-clones', [
        'image' => UploadedFile::fake()->image('x.png', 100, 100),
        'game' => 'hay_day',
    ])->assertStatus(422);

    expect(BaseClone::count())->toBe(0);
});

it('builds deck links deterministically', function () {
    expect(ClashRoyaleDeckAdapter::classicLink([1, 2]))->toBe('https://link.clashroyale.com/deck/en?deck=1;2&l=Royals')
        ->and(ClashRoyaleDeckAdapter::copyDeckLink([1, 2], [1, 0], 159000000))
        ->toBe('https://link.clashroyale.com/en/?clashroyale://copyDeck?deck=1;2&slots=1;0&tt=159000000&l=Royals');
});
