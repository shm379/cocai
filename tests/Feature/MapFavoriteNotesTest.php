<?php

use App\Models\Map;
use App\Models\User;

it('stores notes and tags when adding a favorite', function () {
    $user = User::factory()->create();
    $map = Map::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('maps.favorite', $map), [
            'notes' => 'Best war base for TH16',
            'tags' => ['war', 'TH16'],
        ]);

    $response->assertOk()
        ->assertJsonPath('is_favorite', true);

    $this->assertDatabaseHas('map_favorites', [
        'user_id' => $user->id,
        'map_id' => $map->id,
        'notes' => 'Best war base for TH16',
    ]);

    $favorite = $user->favoriteMaps()->where('maps.id', $map->id)->first();
    expect($favorite->pivot->tags)->toBe(['war', 'TH16']);
});

it('updates notes and tags on an existing favorite', function () {
    $user = User::factory()->create();
    $map = Map::factory()->create();

    $user->favoriteMaps()->attach($map->id, ['notes' => 'Old note', 'tags' => ['old']]);

    $response = $this->actingAs($user)
        ->putJson(route('maps.favorite.update', $map), [
            'notes' => 'Updated note',
            'tags' => ['farm', 'loot'],
        ]);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('notes', 'Updated note')
        ->assertJsonPath('tags', ['farm', 'loot']);

    $favorite = $user->favoriteMaps()->where('maps.id', $map->id)->first();
    expect($favorite->pivot->notes)->toBe('Updated note');
    expect($favorite->pivot->tags)->toBe(['farm', 'loot']);
});

it('returns 404 when updating a non-favorited map', function () {
    $user = User::factory()->create();
    $map = Map::factory()->create();

    $response = $this->actingAs($user)
        ->putJson(route('maps.favorite.update', $map), [
            'notes' => 'Note',
            'tags' => ['tag'],
        ]);

    $response->assertNotFound()
        ->assertJsonPath('ok', false);
});

it('filters favorites by tag', function () {
    $user = User::factory()->create();
    $warMap = Map::factory()->create();
    $farmMap = Map::factory()->create();

    $user->favoriteMaps()->attach($warMap->id, ['tags' => ['war']]);
    $user->favoriteMaps()->attach($farmMap->id, ['tags' => ['farm']]);

    $response = $this->actingAs($user)
        ->getJson(route('maps.favorites', ['tag' => 'war']));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($warMap->id);
});

it('lists favorites with pivot notes and tags', function () {
    $user = User::factory()->create();
    $map = Map::factory()->create();

    $user->favoriteMaps()->attach($map->id, ['notes' => 'My note', 'tags' => ['cwl']]);

    $response = $this->actingAs($user)
        ->getJson(route('maps.favorites'));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.pivot.notes'))->toBe('My note')
        ->and($response->json('data.0.pivot.tags'))->toBe(['cwl']);
});
