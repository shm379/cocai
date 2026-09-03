<?php

use App\Models\GameProfile;
use App\Models\User;
use App\Services\AI\NabuGateClient;
use App\Services\ChatbotService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    config()->set('services.nabu.base_url', 'https://gate.test');
    config()->set('services.nabu.api_key', 'token');
    config()->set('services.nabu.model', 'openrouter2/google/gemini-2.5-flash');
    config()->set('services.nabu.fallback_models', 'nabu-smart,nabu-fast');
});

function gateFake(array $handlers): void
{
    Http::fake(function ($request) use ($handlers) {
        $model = $request->data()['model'] ?? '';
        $handler = $handlers[$model] ?? $handlers['*'] ?? null;

        if ($handler === null) {
            return Http::response(['error' => ['message' => "unknown model {$model}"]], 404);
        }

        return is_callable($handler) ? $handler($request) : $handler;
    });
}

function completion(string $content, string $model = 'nabu-smart')
{
    return Http::response(['choices' => [['message' => ['content' => $content]]], 'model' => $model], 200);
}

it('falls back to nabu-smart when the primary model returns 502', function () {
    gateFake([
        'openrouter2/google/gemini-2.5-flash' => Http::response([
            'error' => ['message' => 'all targets failed ... upstream error (status 402): This request requires more credits'],
        ], 502),
        'nabu-smart' => completion('سلام فرمانده!'),
    ]);

    $result = app(NabuGateClient::class)->chat([['role' => 'user', 'content' => 'سلام']]);

    expect($result)->not->toBeNull()
        ->and($result['content'])->toBe('سلام فرمانده!')
        ->and($result['model'])->toBe('nabu-smart');

    // ۱ تلاش روی مدل اصلی (502 «all targets failed» قطعی است، تکرار ندارد) + ۱ تلاش موفق روی nabu-smart
    Http::assertSentCount(2);
});

it('retries once on transient 503 before falling back', function () {
    $calls = 0;
    gateFake([
        'openrouter2/google/gemini-2.5-flash' => function () use (&$calls) {
            $calls++;

            return $calls === 1
                ? Http::response(['error' => ['message' => 'upstream busy']], 503)
                : completion('بعد از تکرار', 'openrouter2/google/gemini-2.5-flash');
        },
    ]);

    $result = app(NabuGateClient::class)->chat([['role' => 'user', 'content' => 'سلام']]);

    expect($result['content'])->toBe('بعد از تکرار')
        ->and($result['model'])->toBe('openrouter2/google/gemini-2.5-flash');

    Http::assertSentCount(2);
});

it('treats an empty completion as failure and falls back', function () {
    gateFake([
        'openrouter2/google/gemini-2.5-flash' => completion(''),
        'nabu-smart' => Http::response(['choices' => [['message' => ['content' => [['type' => 'text', 'text' => 'بخش ۱'], ['type' => 'text', 'text' => 'بخش ۲']]]]]], 200),
    ]);

    $result = app(NabuGateClient::class)->chat([['role' => 'user', 'content' => 'سلام']]);

    expect($result['content'])->toBe("بخش ۱\nبخش ۲")
        ->and($result['model'])->toBe('nabu-smart');

    Http::assertSentCount(2);
});

it('returns null with a reason when every model fails', function () {
    gateFake(['*' => Http::response(['error' => ['message' => 'all targets failed']], 502)]);

    $client = app(NabuGateClient::class);
    $result = $client->chat([['role' => 'user', 'content' => 'سلام']]);

    expect($result)->toBeNull()
        ->and($client->lastError()['reason'])->toBe('server')
        ->and($client->lastError()['status'])->toBe(502)
        ->and($client->lastError()['model'])->toBe('nabu-fast')
        ->and($client->lastErrorMessage())->toContain('502');

    // ۳ مدل × ۱ تلاش (502 قطعی → بدون تکرار)
    Http::assertSentCount(3);
});

it('stops the whole chain on 401 and reports auth', function () {
    gateFake(['*' => Http::response(['error' => ['message' => 'invalid or missing API key']], 401)]);

    $client = app(NabuGateClient::class);

    expect($client->chat([['role' => 'user', 'content' => 'سلام']]))->toBeNull()
        ->and($client->lastError()['reason'])->toBe('auth')
        ->and($client->lastErrorMessage())->toContain('توکن');

    // توکن رد شده → ارسال به مدل‌های fallback بی‌فایده است
    Http::assertSentCount(1);
});

it('keeps trying fallback models on 403 (model-specific access)', function () {
    gateFake([
        'openrouter2/google/gemini-2.5-flash' => Http::response(['error' => ['message' => 'no access to this model']], 403),
        'nabu-smart' => completion('دسترسی به مدل جایگزین'),
    ]);

    $result = app(NabuGateClient::class)->chat([['role' => 'user', 'content' => 'سلام']]);

    expect($result['model'])->toBe('nabu-smart');

    Http::assertSentCount(2);
});

it('stops the whole chain on a read timeout and reports timeout', function () {
    // درخواست‌هایی که با exception شکست می‌خورند ثبت (recorded) نمی‌شوند؛ خودمان می‌شماریم
    $calls = 0;
    Http::fake(function () use (&$calls) {
        $calls++;

        throw new ConnectionException('cURL error 28: Operation timed out after 45001 milliseconds with 0 bytes received');
    });

    $client = app(NabuGateClient::class);

    expect($client->chat([['role' => 'user', 'content' => 'سلام']]))->toBeNull()
        ->and($client->lastError()['reason'])->toBe('timeout')
        ->and($client->lastErrorMessage())->toContain('طول کشید')
        // بودجهٔ زمانی مصرف شده → مدل بعدی امتحان نمی‌شود
        ->and($calls)->toBe(1);
});

it('classifies a connect timeout as connection and moves to the next model', function () {
    $calls = 0;
    Http::fake(function () use (&$calls) {
        $calls++;
        if ($calls === 1) {
            throw new ConnectionException('cURL error 28: Connection timed out after 10001 milliseconds');
        }

        return completion('بعد از خطای اتصال');
    });

    $client = app(NabuGateClient::class);
    $result = $client->chat([['role' => 'user', 'content' => 'سلام']]);

    expect($result['content'])->toBe('بعد از خطای اتصال')
        ->and($result['model'])->toBe('nabu-smart')
        ->and($calls)->toBe(2);

    // فقط پاسخ موفق ثبت می‌شود
    Http::assertSentCount(1);
});

it('shares one gateway instance per request between services', function () {
    expect(app(NabuGateClient::class))->toBe(app(NabuGateClient::class))
        ->and(app(ChatbotService::class)->gateway())->toBe(app(NabuGateClient::class));
});

it('reads choices[0].text when message content is missing', function () {
    gateFake(['*' => Http::response(['choices' => [['text' => 'متن قدیمی']]], 200)]);

    $result = app(NabuGateClient::class)->chat([['role' => 'user', 'content' => 'سلام']]);

    expect($result['content'])->toBe('متن قدیمی')
        ->and($result['model'])->toBe('openrouter2/google/gemini-2.5-flash');
});

it('ChatbotService::ask still returns model text via fallback when the primary 502s', function () {
    gateFake([
        'openrouter2/google/gemini-2.5-flash' => Http::response(['error' => ['message' => 'all targets failed']], 502),
        'nabu-smart' => completion('⚔️ پاسخ ژنرال تایتوس از مدل جایگزین'),
    ]);

    $user = User::factory()->create();
    GameProfile::factory()->create([
        'user_id' => $user->id,
        'player_tag' => 'DEMO',
        'game_data' => ['townHallLevel' => 12, 'trophies' => 3000],
    ]);

    $answer = app(ChatbotService::class)->ask($user, 'بهترین ارتش وار چیه؟', 'war_general');

    expect($answer)->toBe('⚔️ پاسخ ژنرال تایتوس از مدل جایگزین');

    Http::assertSent(fn ($request) => ($request->data()['model'] ?? null) === 'nabu-smart'
        && ($request->data()['max_tokens'] ?? null) === 950);
});
