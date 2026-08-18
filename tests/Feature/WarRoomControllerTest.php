<?php

use App\Models\GameProfile;
use App\Models\User;
use App\Models\WarTargetCall;

function createFeatureWarUser(string $name = 'FeatureUser'): User
{
    $user = User::factory()->create(['name' => $name]);
    GameProfile::factory()->create([
        'user_id' => $user->id,
        'player_tag' => 'F_TAG_'.$user->id,
        'game_data' => [
            'name' => $name,
            'tag' => '#F_TAG_'.$user->id,
            'townHallLevel' => 15,
            'clan' => ['tag' => '#FEATURE_CLAN', 'name' => 'Persian Feature Clan'],
        ],
    ]);

    return $user;
}

it('returns war room state for authenticated user', function () {
    $user = createFeatureWarUser();

    $response = $this->actingAs($user)
        ->getJson('/api/war-room/state?clan_tag=FEATURE_CLAN&total_targets=15');

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('total_targets', 15)
        ->assertJsonCount(15, 'grid');
});

it('allows calling a target via API', function () {
    $user = createFeatureWarUser();

    $response = $this->actingAs($user)
        ->postJson('/api/war-room/call', [
            'clan_tag' => 'FEATURE_CLAN',
            'target_number' => 7,
            'target_th_level' => 14,
            'tactical_notes' => 'حمله با سوپر دراگون',
        ]);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('call.target_number', 7)
        ->assertJsonPath('call.status', 'called');

    expect(WarTargetCall::where('clan_tag', 'FEATURE_CLAN')->where('target_number', 7)->exists())->toBeTrue();
});

it('records battle result via API', function () {
    $user = createFeatureWarUser();

    $call = WarTargetCall::create([
        'user_id' => $user->id,
        'clan_tag' => 'FEATURE_CLAN',
        'target_number' => 2,
        'target_th_level' => 15,
        'caller_name' => $user->name,
        'caller_tag' => 'TAG',
        'caller_th_level' => 15,
        'status' => 'called',
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/war-room/record-result', [
            'call_id' => $call->id,
            'stars' => 3,
            'percent' => 100,
        ]);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('call.status', 'cleared')
        ->assertJsonPath('call.attack_result_stars', 3);
});

it('cancels active call via API', function () {
    $user = createFeatureWarUser();

    $call = WarTargetCall::create([
        'user_id' => $user->id,
        'clan_tag' => 'FEATURE_CLAN',
        'target_number' => 4,
        'target_th_level' => 15,
        'caller_name' => $user->name,
        'caller_tag' => 'TAG',
        'caller_th_level' => 15,
        'status' => 'called',
    ]);

    $response = $this->actingAs($user)
        ->deleteJson("/api/war-room/calls/{$call->id}");

    $response->assertOk()
        ->assertJsonPath('ok', true);

    expect($call->fresh()->status)->toBe('canceled');
});

it('returns AI estimation for TH matchup', function () {
    $user = createFeatureWarUser();

    $response = $this->actingAs($user)
        ->postJson('/api/war-room/estimate', [
            'attacker_th' => 16,
            'defender_th' => 15,
        ]);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('estimation.win_probability', 92);
});
