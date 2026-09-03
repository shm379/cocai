<?php

namespace App\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * کلاینت واحد برای chat completions روی NabuGate (OpenAI-compatible).
 *
 * زنجیرهٔ fallback: ابتدا `services.nabu.model` و سپس `services.nabu.fallback_models`
 * (پیش‌فرض `nabu-smart,nabu-fast`) به ترتیب امتحان می‌شوند. برای هر مدل:
 * - خطای اتصال → مدل بعدی
 * - timeout → توقف کل زنجیره (بودجهٔ زمانی تمام شده)
 * - 503/504 (گذرا) → یک بار تکرار، سپس مدل بعدی
 * - سایر 5xx (مثل 502 «all targets failed») → مدل بعدی
 * - 401 → توقف کل زنجیره (توکن نامعتبر است، نه مدل)
 * - سایر 4xx → مدل بعدی
 * - 200 با محتوای خالی → مدل بعدی (پاسخ خالی یعنی شکست)
 *
 * `services.nabu.chat_timeout` بودجهٔ کل یک فراخوانی `chat()` است (نه هر تلاش)؛
 * هر تلاش فقط تا پایان این مهلت فرصت دارد.
 */
class NabuGateClient
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $model;

    /** @var string[] */
    protected array $fallbackModels;

    protected int $timeout;

    /** @var array{reason: string, status: ?int, detail: string, model: ?string}|null */
    protected ?array $lastError = null;

    /** خطایی که ادامهٔ زنجیره را بی‌فایده می‌کند (401 یا اتمام بودجهٔ زمانی). */
    protected bool $fatal = false;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.nabu.base_url', ''), '/');
        $this->apiKey = (string) config('services.nabu.api_key', '');
        $this->model = trim((string) config('services.nabu.model', 'nabu-smart'));
        $this->fallbackModels = $this->parseList(config('services.nabu.fallback_models', 'nabu-smart,nabu-fast'));
        $this->timeout = (int) config('services.nabu.chat_timeout', 45);
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->apiKey !== '';
    }

    /**
     * فهرست مدل‌ها به ترتیب تلاش (بدون تکرار).
     *
     * @return string[]
     */
    public function models(): array
    {
        $list = array_merge([$this->model], $this->fallbackModels);
        $list = array_values(array_unique(array_filter(array_map('trim', $list))));

        return $list ?: ['nabu-smart'];
    }

    /**
     * فراخوانی chat completions با زنجیرهٔ fallback.
     *
     * @param  array<int, array{role: string, content: mixed}>  $messages
     * @param  array{temperature?: float, max_tokens?: int, timeout?: int, model?: string, models?: string[], extra?: array}  $options
     * @return array{content: string, model: string}|null
     */
    public function chat(array $messages, array $options = []): ?array
    {
        $this->lastError = null;
        $this->fatal = false;

        if (! $this->isConfigured()) {
            $this->setError('connection', null, 'NabuGate is not configured (NABU_BASE_URL / NABU_API_KEY).');

            return null;
        }

        $models = $this->models();
        if (! empty($options['models']) && is_array($options['models'])) {
            $models = array_values(array_unique(array_filter(array_map('trim', $options['models']))));
        } elseif (! empty($options['model'])) {
            $models = array_values(array_unique(array_merge([trim((string) $options['model'])], $this->fallbackModels)));
        }

        // بودجهٔ کل زنجیره (همهٔ مدل‌ها و تکرارها روی هم)
        $timeout = max(3, (int) ($options['timeout'] ?? $this->timeout));
        $deadline = microtime(true) + $timeout;
        $cleanMessages = $this->sanitizeMessages($messages);

        foreach ($models as $model) {
            $payload = array_merge($options['extra'] ?? [], [
                'model' => $model,
                'messages' => $cleanMessages,
            ]);
            if (array_key_exists('temperature', $options)) {
                $payload['temperature'] = (float) $options['temperature'];
            }
            if (array_key_exists('max_tokens', $options)) {
                $payload['max_tokens'] = (int) $options['max_tokens'];
            }

            $result = $this->tryModel($model, $payload, $deadline);
            if ($result !== null) {
                return $result;
            }
            if ($this->fatal) {
                break;
            }
        }

        return null;
    }

    /**
     * میان‌بر: فقط متن پاسخ (یا رشتهٔ خالی در صورت شکست).
     */
    public function complete(array $messages, array $options = []): string
    {
        return $this->chat($messages, $options)['content'] ?? '';
    }

    /**
     * @return array{reason: string, status: ?int, detail: string, model: ?string}|null
     */
    public function lastError(): ?array
    {
        return $this->lastError;
    }

    /**
     * پیام فارسی متناسب با آخرین خطا.
     */
    public function lastErrorMessage(): string
    {
        $e = $this->lastError;
        $status = $e['status'] ?? null;

        return match ($e['reason'] ?? 'empty') {
            'connection' => 'اتصال به مرکز فرماندهی هوش مصنوعی برقرار نشد (gateway در دسترس نیست). تنظیم NABU_BASE_URL را بررسی کنید.',
            'timeout' => 'پاسخ هوش مصنوعی بیش از حد طول کشید. دوباره تلاش کنید.',
            'auth' => 'توکن سرویس هوش مصنوعی نامعتبر است یا به این مدل دسترسی ندارد (NABU_API_KEY / NABU_MODEL).',
            'model' => "هیچ‌کدام از مدل‌های هوش مصنوعی در دسترس نبود (کد {$status}). تنظیم NABU_MODEL / NABU_FALLBACK_MODELS را بررسی کنید.",
            'server' => "مرکز فرماندهی هوش مصنوعی موقتاً پاسخ نمی‌دهد (کد {$status}). چند لحظه بعد دوباره تلاش کنید.",
            'empty' => 'هوش مصنوعی پاسخ خالی برگرداند. دوباره تلاش کنید.',
            default => 'پاسخی از هوش مصنوعی دریافت نشد.',
        };
    }

    /**
     * یک مدل را امتحان می‌کند؛ روی 503/504 یک بار تکرار می‌شود.
     * هر تلاش فقط تا `$deadline` (زمان مطلق) فرصت دارد.
     *
     * @return array{content: string, model: string}|null
     */
    protected function tryModel(string $model, array $payload, float $deadline): ?array
    {
        foreach ([1, 2] as $attempt) {
            $remaining = (int) ceil($deadline - microtime(true));
            if ($remaining < 3) {
                $this->setError('timeout', null, 'chat budget exhausted before trying model', $model);
                $this->fatal = true;
                Log::warning("NabuGate [{$model}] skipped: chat timeout budget exhausted.");

                return null;
            }

            try {
                $response = Http::timeout($remaining)
                    ->connectTimeout(min(10, $remaining))
                    ->withToken($this->apiKey)
                    ->acceptJson()
                    ->post($this->baseUrl.'/v1/chat/completions', $payload);
            } catch (ConnectionException $e) {
                // فقط read timeout («Operation timed out») یعنی timeout؛ connect timeout یعنی gateway در دسترس نیست
                $isTimeout = str_contains(strtolower($e->getMessage()), 'operation timed out');
                $this->setError($isTimeout ? 'timeout' : 'connection', null, $e->getMessage(), $model);
                Log::warning("NabuGate [{$model}] connection error: ".$e->getMessage());

                // timeout یعنی بودجهٔ زمانی مصرف شده → توقف زنجیره؛ بدون اتصال → مدل بعدی
                if ($isTimeout) {
                    $this->fatal = true;
                }

                return null;
            } catch (\Throwable $e) {
                $this->setError('connection', null, $e->getMessage(), $model);
                Log::warning("NabuGate [{$model}] exception: ".$e->getMessage());

                return null;
            }

            $status = $response->status();

            if ($response->ok()) {
                $json = $response->json();
                $content = $this->extractContent(is_array($json) ? $json : []);

                if ($content !== '') {
                    if ($this->lastError !== null) {
                        Log::info("NabuGate fallback succeeded on [{$model}] after: ".($this->lastError['detail'] ?? ''));
                    }
                    $this->lastError = null;

                    return [
                        'content' => $content,
                        'model' => $model,
                    ];
                }

                $this->setError('empty', $status, 'empty completion', $model);
                Log::warning("NabuGate [{$model}] returned empty content; trying next model.");

                return null;
            }

            $body = mb_substr((string) $response->body(), 0, 500);

            if ($response->serverError()) {
                $this->setError('server', $status, $body, $model);
                Log::warning("NabuGate [{$model}] attempt {$attempt} HTTP {$status}: {$body}");

                // فقط 503/504 گذرا هستند؛ «all targets failed» / «requires more credits» برای این مدل قطعی است
                $lowerBody = strtolower($body);
                $deterministic = str_contains($lowerBody, 'all targets failed') || str_contains($lowerBody, 'requires more credits');
                if ($attempt === 1 && in_array($status, [503, 504], true) && ! $deterministic) {
                    continue;
                }

                return null;
            }

            // 4xx: تکرار فایده ندارد
            $reason = in_array($status, [401, 403], true) ? 'auth' : 'model';
            $this->setError($reason, $status, $body, $model);
            Log::warning("NabuGate [{$model}] HTTP {$status}: {$body}");

            // 401 یعنی توکن رد شده (نه این مدل) → ادامهٔ زنجیره بی‌فایده است؛ 403 می‌تواند مخصوص همین مدل باشد
            if ($status === 401) {
                $this->fatal = true;
            }

            return null;
        }

        return null;
    }

    /**
     * استخراج متن پاسخ: string، آرایهٔ parts، یا choices[0].text.
     */
    protected function extractContent(array $json): string
    {
        $content = data_get($json, 'choices.0.message.content');

        if (is_array($content)) {
            $parts = [];
            foreach ($content as $part) {
                if (is_string($part)) {
                    $parts[] = $part;
                } elseif (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                    $parts[] = $part['text'];
                }
            }
            $content = implode("\n", $parts);
        }

        if (! is_string($content) || trim($content) === '') {
            $content = data_get($json, 'choices.0.text');
        }

        return is_string($content) ? trim($content) : '';
    }

    /**
     * @param  array<int, array>  $messages
     * @return array<int, array{role: string, content: mixed}>
     */
    protected function sanitizeMessages(array $messages): array
    {
        return array_values(array_map(function ($msg) {
            $content = $msg['content'] ?? '';
            if (is_string($content)) {
                $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
            }

            return [
                'role' => (string) ($msg['role'] ?? 'user'),
                'content' => $content,
            ];
        }, $messages));
    }

    /**
     * @return string[]
     */
    protected function parseList(mixed $value): array
    {
        if (is_array($value)) {
            $items = $value;
        } else {
            $items = explode(',', (string) $value);
        }

        return array_values(array_filter(array_map('trim', $items)));
    }

    protected function setError(string $reason, ?int $status, string $detail, ?string $model = null): void
    {
        $this->lastError = [
            'reason' => $reason,
            'status' => $status,
            'detail' => mb_substr($detail, 0, 500),
            'model' => $model,
        ];
    }
}
