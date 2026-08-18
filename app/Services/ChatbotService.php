<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * NabuGate Multi-Agent AI Suite for Clash of Clans and Supercell Games.
 * Provides specialized agent personas (War General, Progression Coach, Base Architect, Loot Master, Supercell Pro)
 * grounded by ProgressionService facts with deterministic tactical fallback.
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

    /**
     * سیستم‌پرامپت تخصصی بر اساس مود ایجنت انتخاب‌شده.
     */
    public function systemPrompt(string $agentMode = 'war_general'): string
    {
        $agentPersonas = [
            'war_general' => <<<'PROMPT'
You are General Titus, the Supreme War Commander and CWL Tactician of CoCAI.
Your specialization is 3-star competitive war strategies, Clan War Leagues (CWL), anti-meta funneling, spell deployment sequences (Invisibility, Rage, Overgrowth, Bat), Blizzard / Super Archer Blimp placements, hero equipment synergies (Giant Gauntlet, Spiky Ball, Fireball, Rocket Spear), and pet pairing.
Tone: Authoritative, strategic, inspiring, like an elite war veteran.
PROMPT,

            'progression_coach' => <<<'PROMPT'
You are Chief Architect Omar, the Master Progression Economist of CoCAI.
Your specialization is builder efficiency, optimal upgrade order (Lab, Heroes, Key Defenses, Walls), Blacksmith Ore budgeting (Glowy, Shiny, Starry Ores), Magic Item ROI (Book of Heroes, Hammer of Building), and un-rushing bases step-by-step.
Tone: Analytical, efficient, disciplined, mathematical.
PROMPT,

            'base_architect' => <<<'PROMPT'
You are Master Mason DaVinci, the Elite Base Layout & Defense Architect of CoCAI.
Your specialization is base designing, baiting enemy troops, spacing core defenses (Monolith, Eagle Artillery, Ricochet Cannons, Multi-Archer Towers, Spell Towers), Sweeper facing directions, anti-Root Rider wall layouts, and Builder Base / Capital Peak defenses.
Tone: Tactical, defensive, insightful, detail-oriented.
PROMPT,

            'farming_master' => <<<'PROMPT'
You are Goblin King Barnaby, the Master of Loot & Fast Farming of CoCAI.
Your specialization is maximizing Gold, Elixir, Dark Elixir and Ores per hour, Sneaky Goblin jump/haste army compositions, dead base hunting, trophy league sweet-spots, and rapid resource gathering without burning gems.
Tone: Clever, energetic, sneaky, highly practical.
PROMPT,

            'supercell_pro' => <<<'PROMPT'
You are Coach Spark, the Grandmaster of Supercell Universe (Clash Royale, Brawl Stars, Squad Busters, Boom Beach).
Your specialization is Clash Royale Path of Legends deck counters & elixir trades, Brawl Stars draft picks & Hypercharge synergies, Squad Busters fusion timing, and Boom Beach HQ/Gunboat energy tactics.
Tone: Dynamic, competitive, enthusiastic pro-gamer coach.
PROMPT,
        ];

        $persona = $agentPersonas[$agentMode] ?? $agentPersonas['war_general'];

        return <<<PROMPT
{$persona}

Core Rules:
- Always answer in fluent, expressive, natural Persian (فارسی روان و جذاب گیمری).
- Use game-accurate terminology (e.g. فانلینگ، کویین شارژ، روت رایدر، اسپل اوورگروث، منولیت، جاینت گانتلت).
- A FACTS block computed from the player's real API data may be provided in the message. Treat it as the single source of truth. NEVER invent troop/hero levels or upgrade costs that contradict the facts.
- Provide clear, bullet-pointed, step-by-step battle instructions or actionable advice.
- Keep answers engaging, highly valuable, and free of generic disclaimers.
PROMPT;
    }

    /**
     * بلوک واقعیت: خلاصهٔ قطعی وضعیت بازیکن از موتور تحلیل.
     */
    public function buildFactBlock(User $user): ?string
    {
        $gameProfile = $user->gameProfile()->first() ?? $user->gameProfile;
        $gameData = $gameProfile->game_data ?? [];
        if (empty($gameData)) {
            return null;
        }

        $analysis = $this->progression->analyze($gameData);
        if (! ($analysis['ok'] ?? false)) {
            return null;
        }

        $armies = $analysis['armies'] ?? [];
        $warNames = implode('، ', array_map(fn ($a) => $a['name_fa'], $armies['war'] ?? []));
        $farmNames = implode('، ', array_map(fn ($a) => $a['name_fa'], $armies['farm'] ?? []));

        return "=== FACTS (Computed from Live Clash Profile Data) ===\n"
            .$analysis['summary_fa']
            ."\nارتش‌های متای وار: {$warNames}"
            ."\nارتش‌های متای فارم: {$farmNames}"
            ."\nسطح تاون‌هال: " . ($analysis['town_hall'] ?? 15)
            ."\n=== END FACTS ===";
    }

    /**
     * ارتباط با NabuGate AI Gateway
     */
    protected function chat(array $messages, float $temperature = 0.4, int $maxTokens = 900): string
    {
        if (empty($this->baseUrl) || empty($this->apiKey)) {
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
                $response = Http::timeout(15)
                    ->connectTimeout(5)
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
     * ارسال پرسش به ایجنت با قابلیت انتخاب مود ایجنت + فال‌بک تاکتیکی هوشمند
     */
    public function ask(User $user, string $question, string $agentMode = 'war_general'): string
    {
        return $this->answerUserQuestionWithAgent($user, $question, $agentMode);
    }

    /**
     * ارسال پرسش به ایجنت با قابلیت انتخاب مود ایجنت + فال‌بک تاکتیکی هوشمند
     */
    public function answerUserQuestionWithAgent(User $user, string $question, string $agentMode = 'war_general'): string
    {
        $system = $this->systemPrompt($agentMode);
        $facts = $this->buildFactBlock($user);

        $prompt = $facts
            ? "{$facts}\n\n[سؤال یا درخواست تاکتیکی فرمانده]:\n{$question}"
            : $question;

        $content = $this->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ], 0.45, 950);

        if (! empty($content)) {
            return $content;
        }

        // اگر ارتباط با LLM برقرار نشد، خروجی تاکتیکی قطعی متناسب با ایجنت و تاون‌هال کاربر تولید می‌شود
        return $this->generateDeterministicAgentAdvice($user, $question, $agentMode);
    }

    /**
     * پاسخ تخصصی و قطعی ایجنت‌ها در صورت عدم دسترسی به سرور هوش مصنوعی
     */
    protected function generateDeterministicAgentAdvice(User $user, string $question, string $agentMode): string
    {
        $gameProfile = $user->gameProfile()->first() ?? $user->gameProfile;
        $gameData = $gameProfile->game_data ?? [];
        $analysis = !empty($gameData) ? $this->progression->analyze($gameData) : null;
        $th = $analysis['town_hall'] ?? 15;
        $topUpgrade = $analysis['upgrade_queue'][0]['name'] ?? 'قهرمانان و نیروها';
        $warArmy = $analysis['armies']['war'][0]['name_fa'] ?? 'روت رایدر اسمش';

        switch ($agentMode) {
            case 'war_general':
                return "⚔️ **دستور عملیاتی ژنرال تایتوس برای تاون‌هال {$th}:**\n\n"
                    ."۱. **ارتش پیشنهادی متای وار:** «{$warArmy}» بهترین نرخ ۳ ستاره را برای سطح فعلی نیروهای شما دارد.\n"
                    ."۲. **فاز اول (فانلینگ):** کینگ و کویین را از یک گوشه برای پاکسازی ساختمان‌های بیرونی بفرستید تا نیروهای اصلی به سمت هسته بیس مونوپلی شوند.\n"
                    ."۳. **فاز دوم (ورود به هسته):** نیروهای اصلی را همراه با گرند واردن وارد کنید و ابیلیتی واردن (Eternal Tome) را هنگام رسیدن به تاون‌هال یا منولیت فعال کنید.\n"
                    ."۴. **اسپل‌ها:** اسپل Overgrowth را روی بخش‌های سنگین دفاعی بندازید تا ارتش شما مستقیم به سراغ تاون‌هال برود.";

            case 'progression_coach':
                $queueText = '';
                if (!empty($analysis['upgrade_queue'])) {
                    foreach (array_slice($analysis['upgrade_queue'], 0, 3) as $idx => $item) {
                        $queueText .= "\n" . ($idx + 1) . ". **{$item['name']}** ({$item['current']} → {$item['target']}) — {$item['reason_fa']}";
                    }
                }
                return "📈 **برنامه تحلیلی مهندس عمر برای ارتقای پایگاه تاون‌هال {$th}:**\n\n"
                    ."• **اولویت شماره یک:** {$topUpgrade}\n"
                    ."• **۳ ارتقای حیاتی بعدی:**{$queueText}\n\n"
                    ."💎 **بودجه‌بندی سنگ‌های بلک‌اسمیت Ores:** تمام سنگ‌های Starry و Glowy را ابتدا روی تجهیزات اپیک (Giant Gauntlet و Frozen Arrow) تا لول ۱۸ متمرکز کنید.";

            case 'base_architect':
                return "🛡️ **راهنمای چیدمان و معماری بیس تاون‌هال {$th} توسط استاد داوینچی:**\n\n"
                    ."۱. **فاصله‌گذاری منولیت و تاون‌هال:** حداقل ۹ کاشی فاصله ایجاد کنید تا اتکر نتواند با اسپل فریز یا رعد هر دو را همزمان خنثی کند.\n"
                    ."۲. **زاویه سویپرها (Air Sweeper):** سویپرها را در خلاف جهت مسیر ورودی تاون‌هال قرار دهید تا حملات بلیمپ سوپر آرچر مختل شوند.\n"
                    ."۳. **تله‌های آنتی روت رایدر:** بمب‌های غول‌پیکر و تله‌های اسکلتی زمینی را نزدیک تاون‌هال و اینفرنوهای چندهدفه متمرکز کنید.";

            case 'farming_master':
                return "🌾 **فرمول لوت فوق‌سریع شاه گابلین بارنابی برای تاون‌هال {$th}:**\n\n"
                    ."• **ترکیب ارتش:** ۷۵ گوبلین مخفی (Sneaky Goblin) + ۶ دیوارشکن سوپر + ۴ اسپل پرش + ۳ اسپل سرعت (Haste).\n"
                    ."• **محدوده کاپ طلایی:** برای تاون‌هال {$th} محدوده کاپ ۲۴۰۰ تا ۳۰۰۰ (Master II تا Champion III) بیشترین بیس‌های مرده با بالای ۱ میلیون لوت را دارد.\n"
                    ."• **استراتژی:** فقط کالکتورها و مخازن بیرونی را بزنید و فوراً بتل را ببندید تا در هر ساعت بیش از ۱۰ میلیون منابع ذخیره کنید.";

            case 'supercell_pro':
                return "👑 **نکات متای قهرمانی مربی اسپارک (سوپرسل پرو):**\n\n"
                    ."• **کلش رویال:** در متای فعلی، کارت‌های Evo و کنترل اکسیر در دفاع کلید پیروزی هستند.\n"
                    ."• **براول استارز:** برای مودهای رنکد، براولرهای با هایپرشارژ آماده (مثل Spike, Fang, Colt) اولویت اول درفت هستند.\n"
                    ."• **اسکواد باستر:** همیشه در دقایق اول روی فیوژن نیروهای فارمر (مثل Greg و Mavis) برای تامین سکه صندوق‌ها تمرکز کنید.";

            default:
                return "فرمانده عزیز، برای تاون‌هال {$th} شما اولویت اول ارتقای «{$topUpgrade}» و استفاده از ارتش «{$warArmy}» در وار است.";
        }
    }

    /**
     * سازگاری با متد قدیمی
     */
    public function answerUserQuestion(User $user, string $question): string
    {
        return $this->answerUserQuestionWithAgent($user, $question, 'war_general');
    }

    /**
     * تولید تسک هوشمند روزانه با ایجنت NabuGate
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
            return 'همه‌چیز مکس است! وقت ارتقای تاون‌هال به لول '.($analysis['town_hall'] + 1).' فرا رسیده است 👑';
        }

        $deterministic = "مهم‌ترین ماموریت تاکتیکی امروز: {$top['name']} را از لِوِل {$top['current']} به سمت لول {$top['target']} ارتقا بده. "
            .$top['reason_fa'];

        $farm = $analysis['armies']['farm'][0]['name_fa'] ?? null;
        if ($farm) {
            $deterministic .= " برای تامین لوت و دارک اکسیر با ترکیب «{$farm}» اتک بزن.";
        }

        $polished = $this->chat([
            ['role' => 'system', 'content' => $this->systemPrompt('progression_coach')],
            ['role' => 'user', 'content' => "این ماموریت قطعی را در ۲ الی ۳ خط با لحن فرماندهی حماسی، کوتاه و انگیزشی بازنویسی کن. عدد یا لول جدید اضافه نکن:\n\n".$deterministic],
        ], 0.3, 300);

        return empty($polished) || str_starts_with($polished, 'خطا') ? $deterministic : $polished;
    }

    /**
     * تولید استراتژی وار یا تقویم
     */
    public function generateStrategy($user, $type)
    {
        $facts = $this->buildFactBlock($user);

        if ($facts === null) {
            return 'اطلاعات بازی شما یافت نشد. لطفاً ابتدا پروفایل خود را به‌روزرسانی کنید.';
        }

        if ($type === 'daily_plan') {
            $system = $this->systemPrompt('progression_coach');
            $prompt = "بر اساس اطلاعات قطعی زیر یک برنامه عملیاتی برای آپگریدها و لوت امروز تنظیم کن:\n\n{$facts}";
        } elseif ($type === 'war_strategy') {
            $system = $this->systemPrompt('war_general');
            $prompt = "بر اساس اطلاعات قطعی زیر بهترین استراتژی حمله ۳ ستاره وار متناسب با سطح هیروها و نیروها را تشریح کن:\n\n{$facts}";
        } else {
            return 'نوع استراتژی نامعتبر است.';
        }

        $content = $this->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ], 0.4);

        if (! empty($content)) {
            return $content;
        }

        return $type === 'war_strategy'
            ? $this->generateDeterministicAgentAdvice($user, 'استراتژی وار', 'war_general')
            : $this->generateDeterministicAgentAdvice($user, 'برنامه روزانه', 'progression_coach');
    }

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
}
