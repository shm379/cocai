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

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.nabu.base_url') ?? '', '/');
        $this->apiKey = config('services.nabu.api_key');
        $this->model = config('services.nabu.model');
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
                'message' => 'پاسخی از مدل Vision دریافت نشد.',
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
     */
    protected function callVisionModel(string $base64Image): ?array
    {
        $systemPrompt = $this->systemPrompt();

        $payload = [
            'model' => $this->model,
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
                $response = Http::timeout(60)
                    ->connectTimeout(10)
                    ->withToken($this->apiKey)
                    ->acceptJson()
                    ->post($this->baseUrl.'/v1/chat/completions', $payload);

                if ($response->ok()) {
                    $json = $response->json();

                    return [
                        'content' => (string) data_get($json, 'choices.0.message.content', ''),
                        'model' => data_get($json, 'model', $this->model),
                    ];
                }

                Log::error("BaseVisionAnalyzer attempt {$attempt} failed: "
                    .$response->status().' '.$response->body());

                if ($response->clientError()) {
                    return null;
                }
            } catch (\Throwable $e) {
                Log::error("BaseVisionAnalyzer attempt {$attempt} exception: "
                    .$e->getMessage());
            }
        }

        return null;
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
