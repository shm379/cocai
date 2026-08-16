<?php

use App\Models\User;

it('starts streak on first task completion', function () {
    $user = User::factory()->create();

    $user->recordTaskCompletion();

    expect($user->fresh()->task_streak)->toBe(1);
    expect($user->fresh()->task_last_completed_at)->not->toBeNull();
});

it('increments streak when completing on consecutive days', function () {
    $user = User::factory()->create([
        'task_streak' => 3,
        'task_last_completed_at' => now()->subDay(),
    ]);

    $user->recordTaskCompletion();

    expect($user->fresh()->task_streak)->toBe(4);
});

it('resets streak when a day is skipped', function () {
    $user = User::factory()->create([
        'task_streak' => 5,
        'task_last_completed_at' => now()->subDays(2),
    ]);

    $user->recordTaskCompletion();

    expect($user->fresh()->task_streak)->toBe(1);
});

it('does not increment streak twice on the same day', function () {
    $user = User::factory()->create([
        'task_streak' => 2,
        'task_last_completed_at' => now(),
    ]);

    $user->recordTaskCompletion();

    expect($user->fresh()->task_streak)->toBe(2);
});
