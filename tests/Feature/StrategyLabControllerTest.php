<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('renders the strategy lab page for authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard.strategy-lab'));

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/StrategyLabPage')
            ->has('sessions')
        );
});

it('redirects guests from the strategy lab page', function () {
    $response = $this->get(route('dashboard.strategy-lab'));

    $response->assertRedirect('/login');
});

it('stores a strategy lab session with image and buildings', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('strategy-lab.store'), [
        'title' => 'تست بیس',
        'image' => UploadedFile::fake()->image('base.jpg', 100, 100),
        'buildings' => [
            ['id' => 1, 'type' => 'town_hall', 'x' => 50, 'y' => 50],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('title', 'تست بیس');

    $this->assertDatabaseHas('strategy_lab_sessions', [
        'user_id' => $user->id,
        'title' => 'تست بیس',
    ]);

    $session = $user->strategyLabSessions()->latest()->first();
    Storage::disk('public')->assertExists($session->image_path);
});

it('analyzes a stored session', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $session = $user->strategyLabSessions()->create([
        'title' => 'تست',
        'image_path' => 'strategy-lab/base.jpg',
        'buildings' => [
            ['id' => 1, 'type' => 'town_hall', 'x' => 50, 'y' => 50],
            ['id' => 2, 'type' => 'air_defense', 'x' => 20, 'y' => 20],
        ],
    ]);

    $response = $this->actingAs($user)->postJson(route('strategy-lab.analyze', $session));

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('building_count', 2);

    $session->refresh();
    expect($session->analysis)->not->toBeNull();
});

it('prevents analyzing another users session', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $session = $other->strategyLabSessions()->create([
        'title' => 'خصوصی',
        'image_path' => 'strategy-lab/private.jpg',
        'buildings' => [],
    ]);

    $response = $this->actingAs($user)->postJson(route('strategy-lab.analyze', $session));

    $response->assertUnprocessable();
});

it('runs quick analysis without storing', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('strategy-lab.quick-analyze'), [
        'buildings' => [
            ['id' => 1, 'type' => 'town_hall', 'x' => 50, 'y' => 50],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('ok', true);

    $this->assertDatabaseCount('strategy_lab_sessions', 0);
});
