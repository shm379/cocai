<?php

namespace App\Services\AI;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * تحلیل تصویری بیس کلش با استفاده از مدل Vision.
 *
 * این سرویس تصویر آپلودشده را به gateway هوش مصنوعی می‌فرستد و مختصات
 * ساختمان‌های کلیدی (تاون‌هال، دفاع‌ها، هیروها و ...) را به صورت JSON
 * استخراج می‌کند تا در Strategy Lab تحلیل قطعی انجام شود.
 */
class BaseVisionAnalyzer
{
    protected ?string $baseUrl;
    protected ?string $apiKey;
    protected ?string $model;
    protected ?string $visionModel;
    protected int $timeout;

    /** آخرین خطای فراخوانی مدل (برای پیام دقیق به کاربر و لاگ). */
    protected ?array $lastError = null;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.nabu.base_url') ?? '', '/');
        $this->apiKey = config('services.nabu.api_key');
        $this->model = config('services.nabu.model');
        $this->visionModel = config('services.nabu.vision_model');
        $this->timeout = max(20, (int) config('services.nabu.vision_timeout', 180));
    }

    /**
     * مدل‌هایی که به ترتیب امتحان می‌شوند: ابتدا alias تصویری، سپس مدل عمومی.
     *
     * @return array<int, string>
     */
    protected function models(): array
    {
        $list = array_map('trim', explode(',', (string) $this->visionModel));
        $list[] = $this->model;

        return array_values(array_unique(array_filter($list)));
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
            'connection' => 'اتصال به سرویس هوش مصنوعی برقرار نشد (gateway در دسترس نیست). تنظیم NABU_BASE_URL را بررسی کنید.',
            'timeout' => 'تحلیل تصویر بیش از حد طول کشید. دوباره تلاش کنید.',
            'auth' => 'توکن سرویس هوش مصنوعی نامعتبر است یا به مدل Vision دسترسی ندارد (NABU_API_KEY / NABU_VISION_MODEL).',
            'model' => "مدل Vision در دسترس نیست (کد {$status}). تنظیم NABU_VISION_MODEL را بررسی کنید.",
            'server' => "سرویس هوش مصنوعی موقتاً پاسخ نمی‌دهد (کد {$status}). چند لحظه بعد دوباره تلاش کنید.",
            'empty' => 'مدل Vision پاسخ خالی برگرداند. دوباره تلاش کنید یا تصویر واضح‌تری بفرستید.',
            default => 'پاسخی از مدل Vision دریافت نشد.',
        };
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

    /**
     * تحلیل تصویر و استخراج ساختمان‌ها.
     *
     * @param  UploadedFile|string  $image  فایل آپلودشده یا مسیر تصویر
     * @return array{ok: bool, buildings: array, message?: string}
     */
    public function detectBuildings($image): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'buildings' => [],
                'message' => 'تنظیمات AI Vision انجام نشده است.',
            ];
        }

        $base64 = $this->imageToBase64($image);
        if ($base64 === null) {
            return [
                'ok' => false,
                'buildings' => [],
                'message' => 'خطا در خواندن تصویر.',
            ];
        }

        $response = $this->callVisionModel($base64);

        if ($response === null || empty($response['content'])) {
            return [
                'ok' => false,
                'buildings' => [],
                'message' => $this->lastErrorMessage(),
                'reason' => $this->lastError()['reason'] ?? 'empty',
            ];
        }

        $buildings = $this->parseBuildingsFromResponse($response['content']);

        return [
            'ok' => true,
            'buildings' => $buildings,
            'raw_content' => $response['content'],
            'model' => $response['model'] ?? $this->model,
        ];
    }

    public function isConfigured(): bool
    {
        return ! empty($this->baseUrl) && ! empty($this->apiKey);
    }

    /**
     * تبدیل تصویر به base64 با resize هوشمند برای کاهش حجم.
     */
    protected function imageToBase64($image): ?string
    {
        $path = $image instanceof UploadedFile ? $image->getRealPath() : $image;

        if (! file_exists($path) || ! is_readable($path)) {
            return null;
        }

        $resizedPath = $this->resizeImage($path, 1024);
        if ($resizedPath === null) {
            return null;
        }

        $mime = mime_content_type($resizedPath) ?: 'image/jpeg';
        $data = base64_encode(file_get_contents($resizedPath));

        if ($resizedPath !== $path) {
            @unlink($resizedPath);
        }

        return "data:{$mime};base64,{$data}";
    }

    /**
     * Resize تصویر با حفظ نسبت ابعاد.
     */
    protected function resizeImage(string $path, int $maxDimension): ?string
    {
        $info = @getimagesize($path);
        if ($info === false) {
            return null;
        }

        [$width, $height, $type] = $info;

        if ($width <= $maxDimension && $height <= $maxDimension) {
            return $path;
        }

        $ratio = min($maxDimension / $width, $maxDimension / $height);
        $newWidth = (int) round($width * $ratio);
        $newHeight = (int) round($height * $ratio);

        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default => null,
        };

        if ($src === false || $src === null) {
            return null;
        }

        $dst = imagecreatetruecolor($newWidth, $newHeight);
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $tmpPath = sys_get_temp_dir().'/cocai_vision_'.uniqid().'.jpg';
        imagejpeg($dst, $tmpPath, 85);

        imagedestroy($src);
        imagedestroy($dst);

        return $tmpPath;
    }

    /**
     * پرامپت سیستم؛ زیرکلاس‌ها می‌توانند آن را بازنویسی کنند.
     */
    protected function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a Clash of Clans base layout analyzer. Look at the provided base screenshot and return ONLY a JSON object with no markdown formatting, no explanation, and no code blocks.

The JSON must have this exact structure:
{
  "buildings": [
    {"type": "town_hall", "x": 45.2, "y": 48.5},
    {"type": "air_defense", "x": 30.0, "y": 25.5}
  ]
}

Coordinates x and y must be percentages (0.0 to 100.0) relative to the image width/height, with the top-left corner being (0,0).

Detect these building types when visible. Use only these exact keys:
town_hall, clan_castle, barbarian_king, archer_queen, grand_warden, royal_champion,
cannon, archer_tower, mortar, air_defense, wizard_tower, air_sweeper, hidden_tesla,
bomb_tower, x_bow, x_bow_air, inferno_tower_single, inferno_tower_multi,
eagle_artillery, scattershot, monolith, builder_hut.

Only include buildings you are reasonably confident about. It is better to return fewer accurate buildings than many incorrect ones.
PROMPT;
    }

    /**
     * متن درخواست کاربر که همراه تصویر ارسال می‌شود.
     */
    protected function userPrompt(): string
    {
        return 'Analyze this Clash of Clans base layout and return the building coordinates as JSON.';
    }

    protected function maxTokens(): int
    {
        return 1500;
    }

    protected function temperature(): float
    {
        return 0.2;
    }

    /**
     * فراخوانی مدل Vision از طریق NabuGate (OpenAI-compatible).
     *
     * ترتیب تلاش: هر مدل از models() (ابتدا alias تصویری)، هر کدام تا دو بار در خطای
     * موقت سرور. خطای 4xx یعنی این مدل برای این توکن در دسترس نیست → مدل بعدی.
     *
     * @return array{content: string, model: string}|null
     */
    protected function callVisionModel(string $base64Image): ?array
    {
        $this->lastError = null;
        $systemPrompt = $this->systemPrompt();

        // فراخوانی Vision ممکن است ۳۰ تا ۹۰ ثانیه طول بکشد؛ سقف پیش‌فرض PHP (۳۰ ثانیه) کافی نیست.
        if (function_exists('set_time_limit')) {
            @set_time_limit(max(($this->timeout + 30) * 2, 180));
        }

        foreach ($this->models() as $model) {
            $payload = [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $this->userPrompt(),
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $base64Image,
                                ],
                            ],
                        ],
                    ],
                ],
                'temperature' => $this->temperature(),
                'max_tokens' => $this->maxTokens(),
            ];

            foreach ([1, 2] as $attempt) {
                try {
                    $response = Http::timeout($this->timeout)
                        ->connectTimeout(10)
                        ->withToken($this->apiKey)
                        ->acceptJson()
                        ->post($this->baseUrl.'/v1/chat/completions', $payload);
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    $isTimeout = str_contains(strtolower($e->getMessage()), 'timed out')
                        || str_contains(strtolower($e->getMessage()), 'timeout');
                    $this->setError($isTimeout ? 'timeout' : 'connection', null, $e->getMessage(), $model);
                    Log::error(static::class." [{$model}] attempt {$attempt} connection error: ".$e->getMessage());

                    // timeout: تکرار فقط زمان کاربر را دو برابر می‌کند؛ بدون اتصال هم مدل دیگر جواب نمی‌دهد.
                    return null;
                } catch (\Throwable $e) {
                    $this->setError('connection', null, $e->getMessage(), $model);
                    Log::error(static::class." [{$model}] attempt {$attempt} exception: ".$e->getMessage());

                    return null;
                }

                if ($response->ok()) {
                    $json = $response->json();
                    $content = $this->extractContent(is_array($json) ? $json : []);

                    if ($content !== '') {
                        $finish = data_get($json, 'choices.0.finish_reason');
                        if ($finish === 'length') {
                            Log::warning(static::class." [{$model}] output truncated by max_tokens.", [
                                'chars' => mb_strlen($content),
                                'usage' => data_get($json, 'usage'),
                            ]);
                        }

                        return [
                            'content' => $content,
                            'model' => data_get($json, 'model', $model),
                            'finish_reason' => $finish,
                        ];
                    }

                    // پاسخ خالی = خطا؛ یک بار دیگر و بعد مدل بعدی.
                    $this->setError('empty', 200, mb_substr($response->body(), 0, 300), $model);
                    Log::warning(static::class." [{$model}] attempt {$attempt} returned empty content.");

                    continue;
                }

                $status = $response->status();
                $detail = mb_substr($response->body(), 0, 300);
                Log::error(static::class." [{$model}] attempt {$attempt} failed: {$status} {$detail}");

                if (in_array($status, [401, 403], true)) {
                    $this->setError('auth', $status, $detail, $model);
                    break; // این مدل مجاز نیست؛ مدل بعدی
                }

                if ($status >= 400 && $status < 500) {
                    $this->setError('model', $status, $detail, $model);
                    break; // مدل/پارامتر نامعتبر؛ مدل بعدی
                }

                $this->setError('server', $status, $detail, $model);
                // 5xx: یک بار دیگر همین مدل
            }
        }

        return null;
    }

    /**
     * استخراج متن از پاسخ OpenAI-compatible؛ content می‌تواند رشته یا آرایه‌ای از بخش‌ها باشد.
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
     * Parse کردن JSON ساختمان‌ها از پاسخ مدل.
     *
     * @return array<int, array{id: int, type: string, x: float, y: float}>
     */
    protected function parseBuildingsFromResponse(string $content): array
    {
        $content = trim($content);

        // حذف مارک‌داون کد بلاک اگر وجود داشت
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*|\s*```$/s', '', $content);
            $content = trim($content);
        }

        $data = json_decode($content, true);
        if (! is_array($data) || ! isset($data['buildings']) || ! is_array($data['buildings'])) {
            Log::warning('BaseVisionAnalyzer: could not parse buildings JSON.', ['content' => $content]);

            return [];
        }

        $buildings = [];
        $id = 1;
        $validTypes = $this->validBuildingTypes();

        foreach ($data['buildings'] as $b) {
            $type = strtolower(trim($b['type'] ?? ''));
            $x = (float) ($b['x'] ?? 0);
            $y = (float) ($b['y'] ?? 0);

            if (! in_array($type, $validTypes, true)) {
                continue;
            }

            if ($x < 0 || $x > 100 || $y < 0 || $y > 100) {
                continue;
            }

            $buildings[] = [
                'id' => $id++,
                'type' => $type,
                'x' => round($x, 2),
                'y' => round($y, 2),
            ];
        }

        return $buildings;
    }

    /**
     * لیست typeهای معتبر ساختمان.
     *
     * @return array<int, string>
     */
    protected function validBuildingTypes(): array
    {
        return [
            'town_hall', 'clan_castle', 'barbarian_king', 'archer_queen',
            'grand_warden', 'royal_champion', 'cannon', 'archer_tower',
            'mortar', 'air_defense', 'wizard_tower', 'air_sweeper',
            'hidden_tesla', 'bomb_tower', 'x_bow', 'x_bow_air',
            'inferno_tower_single', 'inferno_tower_multi', 'eagle_artillery',
            'scattershot', 'monolith', 'builder_hut',
        ];
    }
}
