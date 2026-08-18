<?php

use App\Models\GameProfile;
use App\Models\User;
use App\Models\WarTargetCall;
use App\Services\WarRoomService;
use Carbon\Carbon;

function createWarUser(string $name = 'Commander', int $th = 15): User
{
    $user = User::factory()->create(['name' => $name]);
    GameProfile::factory()->create([
        'user_id' => $user->id,
        'player_tag' => 'TAG_'.$user->id,
        'game_data' => [
            'name' => $name,
            'tag' => '#TAG_'.$user->id,
            'townHallLevel' => $th,
            'clan' => ['tag' => '#WAR_CLAN', 'name' => 'Persian Elite'],
        ],
    ]);

    return $user;
}

it('initializes war map state with default targets', function () {
    $service = app(WarRoomService::class);
    $state = $service->getWarMapState('WAR_CLAN', 15);

    expect($state['ok'])->toBeTrue()
        ->and($state['clan_tag'])->toBe('WAR_CLAN')
        ->and($state['total_targets'])->toBe(15)
        ->and(count($state['grid']))->toBe(15)
        ->and($state['grid'][0]['status'])->toBe('open');
});

it('allows player to call a target and sets expiration', function () {
    $user = createWarUser('Ali', 15);
    $service = app(WarRoomService::class);

    $result = $service->callTarget($user, [
        'clan_tag' => 'WAR_CLAN',
        'target_number' => 3,
        'target_th_level' => 15,
        'tactical_notes' => 'ورود با بلیمپ از ساعت ۹',
    ]);

    expect($result['ok'])->toBeTrue()
        ->and($result['call'])->not->toBeNull()
        ->and($result['call']->target_number)->toBe(3)
        ->and($result['call']->status)->toBe('called')
        ->and($result['call']->win_probability)->toBeGreaterThanOrEqual(70);

    $state = $service->getWarMapState('WAR_CLAN', 15);
    expect($state['grid'][2]['status'])->toBe('called')
        ->and($state['grid'][2]['call']->caller_name)->toBe('Ali');
});

it('prevents another player from calling the same active target', function () {
    $user1 = createWarUser('Player 1', 15);
    $user2 = createWarUser('Player 2', 15);
    $service = app(WarRoomService::class);

    $service->callTarget($user1, [
        'clan_tag' => 'WAR_CLAN',
        'target_number' => 5,
        'target_th_level' => 15,
    ]);

    $secondCall = $service->callTarget($user2, [
        'clan_tag' => 'WAR_CLAN',
        'target_number' => 5,
        'target_th_level' => 15,
    ]);

    expect($secondCall['ok'])->toBeFalse()
        ->and($secondCall['message'])->toContain('رزرو است');
});

it('records attack results and marks cleared when 3 stars', function () {
    $user = createWarUser('Reza', 16);
    $service = app(WarRoomService::class);

    $callResult = $service->callTarget($user, [
        'clan_tag' => 'WAR_CLAN',
        'target_number' => 1,
        'target_th_level' => 15,
    ]);

    $recordResult = $service->recordAttackResult($user, $callResult['call']->id, 3, 100);

    expect($recordResult['ok'])->toBeTrue()
        ->and($recordResult['call']->status)->toBe('cleared')
        ->and($recordResult['call']->attack_result_stars)->toBe(3)
        ->and($recordResult['call']->attack_destruction_percent)->toBe(100);

    $state = $service->getWarMapState('WAR_CLAN', 15);
    expect($state['cleared_count'])->toBe(1)
        ->and($state['total_stars'])->toBe(3)
        ->and($state['grid'][0]['status'])->toBe('cleared');
});

it('calculates matchup estimation with higher win rate for higher TH', function () {
    $service = app(WarRoomService::class);

    $overkill = $service->calculateMatchupEstimation(16, 14);
    expect($overkill['win_probability'])->toBeGreaterThanOrEqual(95)
        ->and($overkill['recommended_army'])->not->toBeNull();

    $underdog = $service->calculateMatchupEstimation(14, 16);
    expect($underdog['win_probability'])->toBeLessThan(50);
});
