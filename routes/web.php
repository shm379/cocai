<?php

use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ProfileController;
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

require __DIR__.'/auth.php';
