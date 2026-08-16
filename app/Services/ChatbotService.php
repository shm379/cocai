<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Talks to our own AI gateway (NabuGate), an OpenAI-compatible endpoint that
 * routes the `nabu-smart` alias to Claude/GPT with fallback. We never call a
 * vendor (or mu.chat) directly — only NabuGate.
 *
 * منطق توصیه دیگر «prompt خام» نیست: هر پیام با «بلوک واقعیت» تولیدشده توسط
 * ProgressionService زمین‌گیر (ground) می‌شود و مدل حق ندارد عددی خارج از آن بسازد.
 */
class ChatbotService
{
    protected $baseUrl;

    protected $apiKey;

    protected $model;

    public function __construct(protected ProgressionService $progression)
    {
        $this->baseUrl = config('services.nabu.base_url');
        $this->apiKey = config('services.nabu.api_key');
        $this->model = config('services.nabu.model', 'nabu-smart');
    }

    protected function systemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert Clash of Clans strategist and coach.
You know Town Hall progression, optimal upgrade order (lab, heroes, buildings, walls),
army compositions and attack strategies per Town Hall, war attacks, farming, and base building.

Rules:
- Always answer in fluent, natural Persian (فارسی).
- Be concrete and actionable: name specific troops, levels, and upgrade priorities.
- A FACTS block computed from the player's real API data may be included in the message.
  Treat it as the single source of truth. Every level, cap, percentage, and priority you
  mention MUST come from that block. NEVER invent upgrade costs, times, or level caps —
  if a number is not in the FACTS block, do not state it.
- If no FACTS block is present, give strategy advice without inventing specific numbers.
- Keep answers tight and practical — no filler, no disclaimers.
PROMPT;
    }

    /**
     * بلوک واقعیت: خلاصهٔ قطعی وضعیت بازیکن از موتور تحلیل.
     * هر prompt توصیه‌ای باید این را حمل کند.
     */
    public function buildFactBlock(User $user): ?string
    {
        $gameData = $user->gameProfile->game_data ?? [];
        if (empty($gameData)) {
            return null;
        }

        $analysis = $this->progression->analyze($gameData);
        if (! ($analysis['ok'] ?? false)) {
            return null;
        }

        $armies = $analysis['armies'];
        $warNames = implode('، ', array_map(fn ($a) => $a['name_fa'], $armies['war'] ?? []));

        return "=== FACTS (computed from live game data — the only allowed source of numbers) ===\n"
            .$analysis['summary_fa']
            ."\nارتش‌های متای جنگی برای این تاون‌هال: {$warNames}"
            ."\n=== END FACTS ===";
    }

    /**
     * Core call to the NabuGate chat-completions endpoint.
     * یک بار retry برای خطاهای گذرا؛ '' فقط وقتی هر دو تلاش شکست بخورد.
     */
    protected function chat(array $messages, float $temperature = 0.4, int $maxTokens = 500): string
    {
        if (empty($this->baseUrl)) {
            Log::error('NabuGate base_url is not configured (services.nabu.base_url).');

            return '';
        }

        $cleanMessages = array_map(function ($msg) {
            return [
                'role' => $msg['role'] ?? 'user',
                'content' => mb_convert_encoding((string) ($msg['content'] ?? ''), 'UTF-8', 'UTF-8'),
            ];
        }, $messages);

        foreach ([1, 2] as $attempt) {
            try {
                $response = Http::timeout(120)
                    ->connectTimeout(30)
                    ->withToken($this->apiKey)
                    ->acceptJson()
                    ->post(rtrim($this->baseUrl, '/').'/v1/chat/completions', [
                        'model' => $this->model,
                        'messages' => $cleanMessages,
                        'temperature' => $temperature,
                        'max_tokens' => $maxTokens,
                    ]);

                if ($response->ok()) {
                    return (string) data_get($response->json(), 'choices.0.message.content', '');
                }

                Log::error("NabuGate error (attempt {$attempt}) ".$response->status().': '.$response->body());

                // خطای کلاینت (۴xx) با تکرار درست نمی‌شود
                if ($response->clientError()) {
                    return '';
                }
            } catch (\Throwable $e) {
                Log::error("NabuGate request failed (attempt {$attempt}): ".$e->getMessage());
            }
        }

        return '';
    }

    /**
     * Send a free-form query. Returns text, or a decoded array when $output_json is true.
     *
     * Signature kept backward-compatible with existing callers; the $id /
     * $chatbotData / $conversationId params are accepted but no longer used.
     *
     * @return string|array
     */
    public function sendQuery($query, $id = null, $chatbotData = [], $output_json = true, $conversationId = null)
    {
        $system = $this->systemPrompt();
        if ($output_json) {
            $system .= "\n\nReturn ONLY a single valid JSON object, with no extra text before or after it.";
        }

        $content = $this->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => (string) $query],
        ], $output_json ? 0.2 : 0.4);

        if ($content === '') {
            return $output_json ? [] : 'خطا در ارتباط با سرویس هوش مصنوعی. لطفاً بعداً تلاش کنید.';
        }

        if ($output_json) {
            $json = $this->extractJsonFromText($content);

            return $json ? (json_decode($json, true) ?? []) : [];
        }

        return $content;
    }

    /**
     * چت آزاد کاربر، grounded با بلوک واقعیت.
     */
    public function answerUserQuestion(User $user, string $question): string
    {
        $facts = $this->buildFactBlock($user);
        $content = $facts ? $facts."\n\nسؤال بازیکن: ".$question : $question;

        return $this->sendQuery($content, $user->id, [], false);
    }

    /**
     * Generate a strategy or plan based on the user's real game data.
     *
     * @param  \App\Models\User  $user
     * @param  string  $type  'daily_plan' | 'war_strategy'
     * @return string
     */
    public function generateStrategy($user, $type)
    {
        $facts = $this->buildFactBlock($user);

        if ($facts === null) {
            return 'اطلاعات بازی شما یافت نشد. لطفاً ابتدا پروفایل خود را به‌روزرسانی کنید.';
        }

        if ($type === 'daily_plan') {
            $prompt = 'بر اساس بلوک FACTS زیر یک برنامهٔ روزانهٔ دقیق بده: کدام آپگریدها را شروع کند '
                .'(به همان ترتیب اولویتِ داده‌شده)، و با کدام ارتش فارم کند. فقط از اطلاعات FACTS استفاده کن.'
                ."\n\n".$facts;
        } elseif ($type === 'war_strategy') {
            $prompt = 'بر اساس بلوک FACTS زیر، از بین ارتش‌های متای ذکرشده بهترین را برای سطح فعلی نیروها و هیروهای '
                .'این بازیکن انتخاب کن و نقشهٔ حمله را قدم‌به‌قدم توضیح بده. فقط از اطلاعات FACTS استفاده کن.'
                ."\n\n".$facts;
        } else {
            return 'نوع استراتژی نامعتبر است.';
        }

        return $this->sendQuery($prompt, $user->id, [], false);
    }

    /**
     * تسک امروز: انتخاب قطعی از صف آپگرید موتور؛ LLM فقط لحن را طبیعی می‌کند.
     * اگر LLM در دسترس نبود، متن قطعی خودمان برگردانده می‌شود — هیچ‌وقت خالی نیست.
     *
     * @param  \App\Models\User  $user
     * @return string
     */
    public function generateNewTask($user)
    {
        $gameData = $user->gameProfile->game_data ?? [];
        if (empty($gameData)) {
            return 'اطلاعات بازی شما یافت نشد. لطفاً ابتدا تگ بازیکن خود را ثبت کنید.';
        }

        $analysis = $this->progression->analyze($gameData);
        if (! ($analysis['ok'] ?? false)) {
            return 'دادهٔ بازی قابل تحلیل نیست. پروفایل را به‌روزرسانی کنید.';
        }

        $top = $analysis['upgrade_queue'][0] ?? null;
        if ($top === null) {
            return 'همه‌چیز مکس است! وقت بردن تاون‌هال '.($analysis['town_hall'] + 1).' است.';
        }

        $deterministic = "مهم‌ترین کار امروز: {$top['name']} را از لِوِل {$top['current']} به سمت {$top['target']} ببر. "
            .$top['reason_fa'];

        $farm = $analysis['armies']['farm'][0]['name_fa'] ?? null;
        if ($farm) {
            $deterministic .= " برای هزینه‌اش با «{$farm}» فارم کن.";
        }

        $polished = $this->sendQuery(
            "این توصیهٔ محاسبه‌شده را در حداکثر ۳ خط، دوستانه و انگیزشی بازنویسی کن. عدد یا توصیهٔ جدید اضافه نکن:\n\n".$deterministic,
            $user->id,
            [],
            false
        );

        // شکست LLM نباید تسک را از بین ببرد
        return str_starts_with($polished, 'خطا در ارتباط') ? $deterministic : $polished;
    }

    /**
     * تقویم چندروزه — کاملاً قطعی از صف آپگرید ساخته می‌شود؛ بدون LLM،
     * بدون زمان‌های ساختگی. هر روز: یک هدف آپگرید + فارم مرتبط.
     *
     * @param  \App\Models\User  $user
     * @return array shape: ['days' => [['day' => int, 'task' => string], ...]]
     */
    public function generateCalendar($user): array
    {
        $gameData = $user->gameProfile->game_data ?? [];
        if (empty($gameData)) {
            return [];
        }

        $analysis = $this->progression->analyze($gameData);
        if (! ($analysis['ok'] ?? false)) {
            return [];
        }

        $queue = $analysis['upgrade_queue'];
        if (empty($queue)) {
            return ['days' => [['day' => 1, 'task' => 'همه‌چیز مکس است — آمادهٔ تاون‌هال بعدی شو.']]];
        }

        $farm = $analysis['armies']['farm'][0]['name_fa'] ?? 'ارتش فارم';
        $days = [];
        $dayNo = 1;

        foreach (array_slice($queue, 0, 10) as $i => $item) {
            $verb = $item['type'] === 'hero' ? 'آپگرید هیرو' : ($item['type'] === 'spell' ? 'آپگرید طلسم در لَب' : 'آپگرید نیرو در لَب');
            $task = "{$verb}: {$item['name']} ({$item['current']} → {$item['target']}). {$item['reason_fa']}";

            if ($i % 2 === 1) {
                $task .= " + فارم منابع با {$farm}.";
            }

            $days[] = ['day' => $dayNo++, 'task' => $task];
        }

        return ['days' => $days];
    }

    /**
     * Extract the first JSON object found in a text blob.
     */
    private function extractJsonFromText($text)
    {
        if (preg_match('/\{.*\}/s', (string) $text, $matches)) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Backward-compat stub. NabuGate is stateless, so there is no per-user
     * conversation cache anymore; callers that still ask for it get an empty array.
     */
    public function getChatbotData()
    {
        return [];
    }
}
