<?php

namespace App\Services\AI;

use App\Jobs\CrawlClasherMaps;
use App\Models\Task;
use App\Models\User;
use App\Services\ChatbotService;
use App\Services\ClashOfClansService;
use Illuminate\Support\Facades\Log;

/**
 * AI Agent با قابلیت اجرای actionهای سرور.
 *
 * این سرویس پیام کاربر را تحلیل می‌کند، نیت (intent) را تشخیص می‌دهد و در صورت
 * نیاز یکی از actionهای زیر را اجرا می‌کند:
 * - refresh_profile: به‌روزرسانی پروفایل کلش از API
 * - generate_task: تولید تسک روزانه جدید
 * - daily_plan: دریافت برنامه روزانه
 * - war_strategy: دریافت استراتژی وار
 * - crawl_maps: شروع کراول نقشه‌ها از Clasher.us
 * - chat: پاسخ معمولی چت‌بات
 */
class AiAgentService
{
    protected NabuGateClient $gate;

    public function __construct(
        protected ChatbotService $chatbot,
        protected ClashOfClansService $clashService,
        ?NabuGateClient $gate = null,
    ) {
        $this->gate = $gate ?? app(NabuGateClient::class);
    }

    /**
     * پردازش پیام کاربر و اجرای action مناسب.
     */
    public function handle(User $user, string $message, string $agentMode = 'war_general'): array
    {
        $intent = $this->detectIntent($message);

        $actionResult = match ($intent) {
            'refresh_profile' => $this->actionRefreshProfile($user),
            'generate_task' => $this->actionGenerateTask($user),
            'daily_plan' => $this->actionDailyPlan($user),
            'war_strategy' => $this->actionWarStrategy($user),
            'crawl_maps' => $this->actionCrawlMaps(),
            default => null,
        };

        if ($intent !== 'chat' && $actionResult !== null) {
            $reply = $this->generateActionReply($user, $intent, $actionResult, $message, $agentMode);

            return [
                'ok' => true,
                'agent_mode' => $agentMode,
                'action' => $intent,
                'action_result' => $actionResult,
                'answer' => $reply,
            ];
        }

        return [
            'ok' => true,
            'agent_mode' => $agentMode,
            'action' => 'chat',
            'answer' => $this->chatbot->answerUserQuestionWithAgent($user, $message, $agentMode),
        ];
    }

    /**
     * تشخیص نیت کاربر از روی متن.
     */
    public function detectIntent(string $message): string
    {
        $normalized = $this->normalize($message);

        // 1. Refresh Profile
        if (
            (str_contains($normalized, 'پروفایل') || str_contains($normalized, 'profile') || str_contains($normalized, 'اکانت') || str_contains($normalized, 'account'))
            && (str_contains($normalized, 'بروز') || str_contains($normalized, 'به‌روز') || str_contains($normalized, 'آپدیت') || str_contains($normalized, 'اپدیت') || str_contains($normalized, 'رفرش') || str_contains($normalized, 'refresh') || str_contains($normalized, 'update') || str_contains($normalized, 'سینک') || str_contains($normalized, 'sync'))
        ) {
            return 'refresh_profile';
        }

        if (str_starts_with($normalized, 'رفرش') || str_starts_with($normalized, 'refresh')) {
            return 'refresh_profile';
        }

        // 2. Generate Task
        if (
            (str_contains($normalized, 'تسک') || str_contains($normalized, 'task') || str_contains($normalized, 'وظیفه') || str_contains($normalized, 'ماموریت'))
            && (str_contains($normalized, 'جدید') || str_contains($normalized, 'امروز') || str_contains($normalized, 'بساز') || str_contains($normalized, 'بده') || str_contains($normalized, 'بگذار') || str_contains($normalized, 'new') || str_contains($normalized, 'today') || str_contains($normalized, 'create'))
        ) {
            return 'generate_task';
        }

        if (str_contains($normalized, 'چیکار کنم') || str_contains($normalized, 'کار امروز')) {
            return 'generate_task';
        }

        // 3. Daily Plan
        if (
            (str_contains($normalized, 'برنامه') || str_contains($normalized, 'پلن') || str_contains($normalized, 'plan'))
            && (str_contains($normalized, 'روزانه') || str_contains($normalized, 'امروز') || str_contains($normalized, 'daily') || str_contains($normalized, 'today') || str_contains($normalized, 'آپگرید'))
        ) {
            return 'daily_plan';
        }

        if (str_contains($normalized, 'چه کارهایی انجام بدم')) {
            return 'daily_plan';
        }

        // 4. War Strategy
        if (
            (str_contains($normalized, 'وار') || str_contains($normalized, 'war') || str_contains($normalized, 'cwl') || str_contains($normalized, 'کلن وار'))
            && (str_contains($normalized, 'استراتژی') || str_contains($normalized, 'strategy') || str_contains($normalized, 'پلن') || str_contains($normalized, 'plan') || str_contains($normalized, 'حمله') || str_contains($normalized, 'اتک') || str_contains($normalized, 'بزنم'))
        ) {
            return 'war_strategy';
        }

        // 5. Crawl Maps
        if (
            (str_contains($normalized, 'نقشه') || str_contains($normalized, 'مپ') || str_contains($normalized, 'map') || str_contains($normalized, 'base') || str_contains($normalized, 'بیس'))
            && (str_contains($normalized, 'کراول') || str_contains($normalized, 'crawl') || str_contains($normalized, 'دانلود') || str_contains($normalized, 'fetch') || str_contains($normalized, 'آپدیت') || str_contains($normalized, 'بروز') || str_contains($normalized, 'بگیر'))
        ) {
            return 'crawl_maps';
        }

        return 'chat';
    }

    /**
     * Action: به‌روزرسانی پروفایل بازی.
     */
    protected function actionRefreshProfile(User $user): array
    {
        try {
            $profile = $this->clashService->refreshGameProfile($user);

            return [
                'ok' => true,
                'message' => 'پروفایل بازی با موفقیت به‌روز شد.',
                'player_tag' => $profile->player_tag,
                'town_hall' => $profile->game_data['townHallLevel'] ?? null,
                'trophies' => $profile->game_data['trophies'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('AiAgent refresh_profile failed: '.$e->getMessage());

            return [
                'ok' => false,
                'message' => 'خطا در به‌روزرسانی پروفایل: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Action: تولید تسک جدید.
     */
    protected function actionGenerateTask(User $user): array
    {
        try {
            $text = $this->chatbot->generateNewTask($user);
            $task = Task::create([
                'user_id' => $user->id,
                'task' => $text,
            ]);

            return [
                'ok' => true,
                'message' => 'تسک جدید ساخته شد.',
                'task' => $task->task,
                'task_id' => $task->id,
            ];
        } catch (\Throwable $e) {
            Log::error('AiAgent generate_task failed: '.$e->getMessage());

            return [
                'ok' => false,
                'message' => 'خطا در ساخت تسک: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Action: دریافت برنامه روزانه.
     */
    protected function actionDailyPlan(User $user): array
    {
        try {
            $plan = $this->chatbot->generateStrategy($user, 'daily_plan');

            return [
                'ok' => true,
                'message' => 'برنامه روزانه آماده است.',
                'plan' => $plan,
            ];
        } catch (\Throwable $e) {
            Log::error('AiAgent daily_plan failed: '.$e->getMessage());

            return [
                'ok' => false,
                'message' => 'خطا در ساخت برنامه روزانه: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Action: دریافت استراتژی وار.
     */
    protected function actionWarStrategy(User $user): array
    {
        try {
            $strategy = $this->chatbot->generateStrategy($user, 'war_strategy');

            return [
                'ok' => true,
                'message' => 'استراتژی وار آماده است.',
                'strategy' => $strategy,
            ];
        } catch (\Throwable $e) {
            Log::error('AiAgent war_strategy failed: '.$e->getMessage());

            return [
                'ok' => false,
                'message' => 'خطا در ساخت استراتژی وار: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Action: شروع کراول نقشه‌ها.
     */
    protected function actionCrawlMaps(): array
    {
        try {
            CrawlClasherMaps::dispatch();

            return [
                'ok' => true,
                'message' => 'کراول نقشه‌ها در صف پردازش قرار گرفت. پس از چند دقیقه نقشه‌ها به‌روز می‌شوند.',
            ];
        } catch (\Throwable $e) {
            Log::error('AiAgent crawl_maps failed: '.$e->getMessage());

            return [
                'ok' => false,
                'message' => 'خطا در شروع کراول نقشه‌ها: '.$e->getMessage(),
            ];
        }
    }

    /**
     * تولید پاسخ نهایی با توجه به نتیجه action.
     */
    protected function generateActionReply(
        User $user,
        string $intent,
        array $actionResult,
        string $originalMessage,
        string $agentMode
    ): string {
        $context = $this->chatbot->buildFactBlock($user);
        $resultSummary = json_encode($actionResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $system = $this->chatbot->systemPrompt($agentMode);

        $prompt = "تو یک دستیار هوشمند CoCAI هستی. کاربر این درخواست را داشت:\n{$originalMessage}\n\n"
            ."یک action سرور اجرا شد: {$intent}\n"
            ."نتیجه action:\n{$resultSummary}\n\n"
            ."حالا یک پاسخ کوتاه، مفید و به زبان فارسی روان بده که کاربر را از نتیجه action مطلع کند. "
            ."اگر action موفق نبود، دلیل خطا را توضیح بده. "
            ."اگر اطلاعات بازیکن موجود است، آن را در پاسخ استفاده کن.";

        if ($context) {
            $prompt = "{$context}\n\n{$prompt}";
        }

        $reply = $this->callModel($system, $prompt, 0.4, 400);

        if (empty($reply)) {
            return $actionResult['message'] ?? 'action اجرا شد.';
        }

        return $reply;
    }

    /**
     * فراخوانی مستقیم مدل زبانی (با زنجیرهٔ fallback مدل‌ها).
     */
    protected function callModel(string $system, string $prompt, float $temperature = 0.4, int $maxTokens = 400): string
    {
        $result = $this->gate->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ], [
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ]);

        if ($result === null) {
            Log::error('AiAgent callModel failed: all NabuGate models failed', $this->gate->lastError() ?? []);

            return '';
        }

        return $result['content'];
    }

    /**
     * نرمال‌سازی متن برای تشخیص نیت.
     */
    protected function normalize(string $text): string
    {
        $text = strtolower($text);
        $text = str_replace(['‌', 'ـ', 'ً', 'ٌ', 'ٍ', 'َ', 'ُ', 'ِ', 'ّ', 'ْ', 'ء'], '', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}
