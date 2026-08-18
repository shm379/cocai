<?php

use App\Models\GameProfile;
use App\Models\User;

function createUserWithProfile(string $tag = 'DEMO'): User
{
    $user = User::factory()->create();
    GameProfile::factory()->create([
        'user_id' => $user->id,
        'player_tag' => $tag,
        'game_data' => ['townHallLevel' => 12, 'trophies' => 3000],
    ]);

    return $user;
}

it('returns action result for generate task intent', function () {
    $user = createUserWithProfile();

    $response = $this->actingAs($user)
        ->postJson('/api/chat', [
            'question' => 'یک تسک جدید بساز',
            'agent_mode' => 'progression_coach',
        ]);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('action', 'generate_task')
        ->assertJsonPath('action_result.ok', true);

    expect($user->tasks()->count())->toBe(1);
});

it('returns action result for refresh profile intent', function () {
    $user = createUserWithProfile();

    $response = $this->actingAs($user)
        ->postJson('/api/chat', [
            'question' => 'پروفایل من را بروزرسانی کن',
            'agent_mode' => 'progression_coach',
        ]);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('action', 'refresh_profile')
        ->assertJsonPath('action_result.ok', true);
});

it('falls back to chat when agent actions are disabled', function () {
    $user = createUserWithProfile();

    $response = $this->actingAs($user)
        ->postJson('/api/chat', [
            'question' => 'یک تسک جدید بساز',
            'agent_mode' => 'progression_coach',
            'agent_actions' => false,
        ]);

    $response->assertOk()
        ->assertJsonPath('action', 'chat');
});
