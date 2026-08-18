<?php

use App\Models\GameProfile;
use App\Models\User;
use App\Services\AI\AiAgentService;

function makeUserWithProfile(): User
{
    $user = User::factory()->create();
    GameProfile::factory()->create([
        'user_id' => $user->id,
        'player_tag' => 'DEMO',
        'game_data' => ['townHallLevel' => 12, 'trophies' => 3000],
    ]);

    return $user;
}

it('detects refresh profile intent', function () {
    $service = app(AiAgentService::class);

    expect($service->detectIntent('پروفایل من را بروزرسانی کن'))->toBe('refresh_profile')
        ->and($service->detectIntent('refresh profile'))->toBe('refresh_profile');
});

it('detects generate task intent', function () {
    $service = app(AiAgentService::class);

    expect($service->detectIntent('یک تسک جدید بساز'))->toBe('generate_task')
        ->and($service->detectIntent('new task'))->toBe('generate_task');
});

it('detects daily plan intent', function () {
    $service = app(AiAgentService::class);

    expect($service->detectIntent('برنامه روزانه بده'))->toBe('daily_plan')
        ->and($service->detectIntent('daily plan'))->toBe('daily_plan');
});

it('detects war strategy intent', function () {
    $service = app(AiAgentService::class);

    expect($service->detectIntent('استراتژی وار بگو'))->toBe('war_strategy')
        ->and($service->detectIntent('war strategy'))->toBe('war_strategy');
});

it('detects crawl maps intent', function () {
    $service = app(AiAgentService::class);

    expect($service->detectIntent('نقشه‌ها را کراول کن'))->toBe('crawl_maps')
        ->and($service->detectIntent('crawl maps'))->toBe('crawl_maps');
});

it('falls back to chat intent for unknown messages', function () {
    $service = app(AiAgentService::class);

    expect($service->detectIntent('سلام چطوری؟'))->toBe('chat')
        ->and($service->detectIntent('tell me about dragons'))->toBe('chat');
});

it('executes generate task action and stores a task', function () {
    $user = makeUserWithProfile();
    $service = app(AiAgentService::class);
    $result = $service->handle($user, 'تسک جدید بساز', 'progression_coach');

    expect($result['ok'])->toBeTrue()
        ->and($result['action'])->toBe('generate_task')
        ->and($result['action_result']['ok'])->toBeTrue()
        ->and($user->tasks()->count())->toBe(1);
});

it('executes daily plan action', function () {
    $user = makeUserWithProfile();
    $service = app(AiAgentService::class);
    $result = $service->handle($user, 'برنامه روزانه بده', 'progression_coach');

    expect($result['ok'])->toBeTrue()
        ->and($result['action'])->toBe('daily_plan')
        ->and($result['action_result']['ok'])->toBeTrue();
});
