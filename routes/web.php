<?php

use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StrategyLabController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// 1) صفحه اصلی داشبورد (مثلاً منوی کلی)

// صفحات فرعی داشبورد — همگی نیازمند احراز هویت
Route::middleware('auth')->group(function () {
    // 2) صفحه اطلاعات بازی
    Route::get('/dashboard/game-info', function () {
        $user = auth()->user();
        $gameProfile = $user->gameProfile?->game_data ?? [];

        return Inertia::render('Dashboard/GameInfoPage', [
            'gameProfile' => $gameProfile,
        ]);
    })->name('dashboard.game-info');

    // 3) صفحه نیروها
    Route::get('/dashboard/troops', function () {
        $user = auth()->user();
        $gameProfile = $user->gameProfile?->game_data ?? [];

        return Inertia::render('Dashboard/TroopsPage', [
            'gameProfile' => $gameProfile,
        ]);
    })->name('dashboard.troops');

    // 4) صفحه تقویم — مسیر خواندن یکسان با داشبورد (تقویمِ پروفایل بازی)
    Route::get('/dashboard/calendar', function () {
        $calendar = auth()->user()->gameProfile?->calendars ?? [];

        return Inertia::render('Dashboard/CalendarPage', [
            'calendar' => $calendar,
        ]);
    })->name('dashboard.calendar');

    // 5) صفحهٔ تحلیل پیشرفت — خروجی موتور قطعی ProgressionService
    Route::get('/dashboard/progress', [\App\Http\Controllers\ProgressController::class, 'show'])
        ->name('dashboard.progress');
    Route::get('/api/progress', [\App\Http\Controllers\ProgressController::class, 'api'])
        ->name('progress.api');

    // سیستم اشتراک و پرداخت (زیبال، پی‌پینگ، زرین‌پال)
    Route::get('/api/subscription/plans', [\App\Http\Controllers\PaymentController::class, 'plans'])
        ->name('subscription.plans');
    Route::post('/subscription/checkout', [\App\Http\Controllers\PaymentController::class, 'checkout'])
        ->name('subscription.checkout');

    // ورود یکپارچه با گیم سیتی (GameCity SSO & CRM Sync)
    Route::get('/auth/gamecity/redirect', [\App\Http\Controllers\GameCityAuthController::class, 'redirect'])
        ->name('auth.gamecity.redirect');
    Route::match(['get', 'post'], '/auth/gamecity/callback', [\App\Http\Controllers\GameCityAuthController::class, 'callback'])
        ->name('auth.gamecity.callback');

    // تشخیص خودکار ساختمان‌ها از روی تصویر با AI Vision
    Route::post('/api/strategy-lab/detect-vision', [StrategyLabController::class, 'detectByVision'])
        ->name('strategy-lab.detect-vision');

    // ای‌پی‌آی ترکیب‌ها و تیرلیست متای برتر (Winning Meta & Tactics)
    Route::get('/api/meta-tier-items', function () {
        $items = \App\Models\MetaTierItem::where('is_featured', true)
            ->orderBy('tier')
            ->get();
        return response()->json(['ok' => true, 'items' => $items]);
    })->name('api.meta-tier-items');

    // شبیه‌ساز حمله و اسکنر دفاعی هوش مصنوعی (AI War Simulator & Defense Scanner & CWL & Live HUD)
    Route::post('/api/ai/simulate-attack', [\App\Http\Controllers\AiTacticalController::class, 'simulateAttack'])
        ->name('ai.simulate-attack');
    Route::get('/api/ai/defense-scan', [\App\Http\Controllers\AiTacticalController::class, 'scanDefense'])
        ->name('ai.defense-scan');
    Route::get('/api/ai/cwl-analysis', [\App\Http\Controllers\AiTacticalController::class, 'analyzeCwl'])
        ->name('ai.cwl-analysis');
    Route::post('/api/ai/live-attack-plan', [\App\Http\Controllers\AiTacticalController::class, 'liveAttackPlan'])
        ->name('ai.live-attack-plan');

    // پلن اتک خودکار و دانلود ایجنت اندروید (Android In-Game Auto-Attack Bot)
    Route::post('/api/android/generate-macro', [\App\Http\Controllers\AndroidCompanionController::class, 'generateMacro'])
        ->name('android.generate-macro');
    Route::get('/api/android/latest-screenshot', [\App\Http\Controllers\AndroidCompanionController::class, 'getLatestScreenshot'])
        ->name('android.latest-screenshot');
    Route::get('/downloads/cocai-android/cocai-android-agent.py', [\App\Http\Controllers\AndroidCompanionController::class, 'downloadAgentScript'])
        ->name('android.download-agent');

    // صفحه آزمایشگاه استراتژی
    Route::get('/dashboard/strategy-lab', [StrategyLabController::class, 'index'])
        ->name('dashboard.strategy-lab');
    Route::post('/api/strategy-lab/sessions', [StrategyLabController::class, 'store'])
        ->name('strategy-lab.store');
    Route::post('/api/strategy-lab/quick-analyze', [StrategyLabController::class, 'quickAnalyze'])
        ->name('strategy-lab.quick-analyze');
    Route::get('/api/strategy-lab/sessions/{session}', [StrategyLabController::class, 'show'])
        ->name('strategy-lab.show');
    Route::post('/api/strategy-lab/sessions/{session}/analyze', [StrategyLabController::class, 'analyze'])
        ->name('strategy-lab.analyze');
    Route::delete('/api/strategy-lab/sessions/{session}', [StrategyLabController::class, 'destroy'])
        ->name('strategy-lab.destroy');

    // 5) صفحه وظیفهٔ امروز
    Route::get('/dashboard/today-task', function () {
        $todayTask = optional(auth()->user()->tasks()->latest()->first())->task
            ?? 'هیچ تسکی تعریف نشده.';

        return Inertia::render('Dashboard/TodayTaskPage', [
            'todayTask' => $todayTask,
        ]);
    })->name('dashboard.today-task');
});
// داشبورد
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // مسیرهای مربوط به تسک — endpointهای متصل به LLM با throttle
    Route::post('/tasks/generate', [TaskController::class, 'generateTask'])
        ->middleware('throttle:10,1')->name('tasks.generate');
    Route::get('/tasks/last', [TaskController::class, 'getLastTask'])->name('tasks.last');
    Route::post('/tasks/complete', [TaskController::class, 'completeTask'])->name('tasks.complete');
    Route::post('/tasks/daily-plan', [TaskController::class, 'getDailyPlan'])
        ->middleware('throttle:10,1')->name('tasks.daily-plan');
    Route::post('/tasks/war-strategy', [TaskController::class, 'getWarStrategy'])
        ->middleware('throttle:10,1')->name('tasks.war-strategy');

    // دستیار هوش مصنوعی (چت آزاد)
    Route::post('/api/chat', [ChatbotController::class, 'query'])
        ->middleware('throttle:20,1')->name('chatbot.query');
});
Route::post('/save-player-tag', [UserController::class, 'savePlayerTag'])->middleware('auth');
Route::post('/profile/refresh', [UserController::class, 'refreshProfile'])
    ->middleware(['auth', 'throttle:6,1'])->name('profile.refresh');

// پیش‌نمایش تگ بازیکن قبل از ثبت (فقط نام/کاپ/تاون‌هال)
Route::get('/api/player-preview', [UserController::class, 'previewPlayerTag'])
    ->middleware(['auth', 'throttle:10,1'])->name('player.preview');

// مقایسهٔ پیشرفت با یک بازیکن دیگر
Route::get('/api/compare-progress', [UserController::class, 'compareProgress'])
    ->middleware(['auth', 'throttle:10,1'])->name('player.compare');

Route::get('/clash/player', [\App\Http\Controllers\ClashOfClansController::class, 'getPlayer'])
    ->middleware(['auth', 'throttle:30,1'])->name('clash.player');

Route::get('/clash/clan', [\App\Http\Controllers\ClashOfClansController::class, 'getClan'])
    ->middleware(['auth', 'throttle:30,1'])->name('clash.clan');

Route::get('/api/supercell/profile', [\App\Http\Controllers\SupercellHubController::class, 'getProfile'])
    ->middleware(['auth', 'throttle:30,1'])->name('supercell.profile');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// کراول نقشه‌ها — عملیات سنگین و حالت‌گردان؛ فقط برای کاربر احراز هویت‌شده.
// TODO: بهتر است به یک کامند Artisan یا میدلور admin محدود شود.
Route::get('/map', [MapController::class, 'crawlMaps'])->middleware('auth')->name('map.crawl');

// علاقه‌مندی‌های نقشه
Route::middleware('auth')->group(function () {
    Route::post('/maps/{map}/favorite', [MapController::class, 'toggleFavorite'])->name('maps.favorite');
    Route::get('/maps/favorites', [MapController::class, 'favorites'])->name('maps.favorites');
});

// کال‌بک پرداخت درگاه‌ها (زیبال، پی‌پینگ، زرین‌پال)
Route::match(['get', 'post'], '/subscription/callback/{gateway}', [\App\Http\Controllers\PaymentController::class, 'callback'])
    ->name('subscription.callback');

require __DIR__.'/auth.php';
