<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Talks to our own AI gateway (NabuGate), an OpenAI-compatible endpoint that
 * routes the `nabu-smart` alias to Claude/GPT with fallback. We never call a
 * vendor (or mu.chat) directly — only NabuGate.
 */
class ChatbotService
{
    protected $baseUrl;

    protected $apiKey;

    protected $model;

    public function __construct()
    {
        $this->baseUrl = config('services.nabu.base_url');
        $this->apiKey = config('services.nabu.api_key');
        $this->model = config('services.nabu.model', 'nabu-smart');
    }

    /**
     * System persona: an expert Clash of Clans strategist that answers in Persian.
     * This is the "grounding" — the model's CoC knowledge is steered by the player's
     * real game_data, which callers inject into the user message.
     */
    protected function systemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert Clash of Clans strategist and coach.
You know Town Hall progression, optimal upgrade order (lab, heroes, buildings, walls),
army compositions and attack strategies per Town Hall, war attacks, farming, and base building.

Rules:
- Always answer in fluent, natural Persian (فارسی).
- Be concrete and actionable: name specific troops, levels, and upgrade priorities.
- Base every recommendation on the player's actual data given in the message
  (Town Hall level, hero levels, troop levels, resources). Never give generic advice
  that ignores their data.
- Keep answers tight and practical — no filler, no disclaimers.
PROMPT;
    }

    /**
     * Core call to the NabuGate chat-completions endpoint.
     *
     * @param  array  $messages  OpenAI-style [{role, content}, ...]
     * @return string assistant content ('' on failure)
     */
    protected function chat(array $messages): string
    {
        if (empty($this->baseUrl)) {
            Log::error('NabuGate base_url is not configured (services.nabu.base_url).');

            return '';
        }

        try {
            $response = Http::timeout(120)
                ->connectTimeout(30)
                ->withToken($this->apiKey)
                ->acceptJson()
                ->post(rtrim($this->baseUrl, '/').'/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => $messages,
                    'temperature' => 0.4,
                ]);
        } catch (\Throwable $e) {
            Log::error('NabuGate request failed: '.$e->getMessage());

            return '';
        }

        if (! $response->ok()) {
            Log::error('NabuGate error '.$response->status().': '.$response->body());

            return '';
        }

        return (string) data_get($response->json(), 'choices.0.message.content', '');
    }

    /**
     * Send a free-form query. Returns text, or a decoded array when $output_json is true.
     *
     * Signature kept backward-compatible with existing callers; the $id /
     * $chatbotData / $conversationId params are accepted but no longer used
     * (NabuGate is stateless — context is carried in the message itself).
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
        ]);

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
     * Generate a strategy or plan based on the user's real game data.
     *
     * @param  \App\Models\User  $user
     * @param  string  $type  'daily_plan' | 'war_strategy'
     * @return string
     */
    public function generateStrategy($user, $type)
    {
        $gameData = $user->gameProfile->game_data ?? [];
        $playerTag = $user->player_tag;

        if (empty($gameData)) {
            return 'اطلاعات بازی شما یافت نشد. لطفاً ابتدا پروفایل خود را به‌روزرسانی کنید.';
        }

        $context = "Player Tag: {$playerTag}. Game Data: ".json_encode($gameData, JSON_UNESCAPED_UNICODE);

        if ($type === 'daily_plan') {
            $prompt = 'بر اساس دادهٔ بازیکن زیر، یک برنامهٔ روزانهٔ دقیق بده: چه ارتقاهایی را اولویت بده '
                ."(ساختمان‌ها، نیروها، قهرمان‌ها) با توجه به سطح تاون‌هال فعلی، و چه منابعی را فارم کند.\n\n".$context;
        } elseif ($type === 'war_strategy') {
            $prompt = 'بر اساس دادهٔ بازیکن زیر، بهترین استراتژی‌های حملهٔ Clan War را پیشنهاد بده. '
                ."سطح نیروها و قهرمان‌ها را در نظر بگیر. ترکیب ارتش و نقشهٔ حمله را قدم‌به‌قدم توضیح بده.\n\n".$context;
        } else {
            return 'نوع استراتژی نامعتبر است.';
        }

        return $this->sendQuery($prompt, $user->id, [], false);
    }

    /**
     * Generate a single fresh "today task" for the user.
     *
     * @param  \App\Models\User  $user
     * @return string
     */
    public function generateNewTask($user)
    {
        $gameData = $user->gameProfile->game_data ?? [];
        $context = ! empty($gameData)
            ? "\n\nPlayer Data: ".json_encode($gameData, JSON_UNESCAPED_UNICODE)
            : '';

        $query = 'فقط مهم‌ترین کاری که امروز باید برای پیشرفت و بردن تاون‌هال انجام بدهم را در حداکثر ۳ خط بگو. '
            .'به تقویم نیازی ندارم، فقط اقدام امروز.'.$context;

        return $this->sendQuery($query, $user->id, [], false);
    }

    /**
     * Generate a multi-day upgrade calendar as a structured array.
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

        $prompt = 'بر اساس دادهٔ بازیکن زیر، یک برنامهٔ ارتقای چندروزه برای Clash of Clans بساز. '
            .'خروجی را فقط به صورت JSON معتبر با این ساختار برگردان: '
            .'{"days":[{"day":1,"task":"..."}]} . '
            .'هر task یک جملهٔ کوتاه، فارسی و عملی باشد. بین ۷ تا ۱۴ روز.'."\n\n"
            .'Player Data: '.json_encode($gameData, JSON_UNESCAPED_UNICODE);

        $content = $this->chat([
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ['role' => 'user', 'content' => $prompt],
        ]);

        $json = $this->extractJsonFromText($content);
        $data = $json ? json_decode($json, true) : null;

        return is_array($data) ? $data : [];
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
