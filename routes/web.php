<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\MapController;
use Illuminate\Http\Request;
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// 1) صفحه اصلی داشبورد (مثلاً منوی کلی)

// 2) صفحه اطلاعات بازی
Route::get('/dashboard/game-info', function () {
    // با دیتابیس یا API چیزی بخوانید
    $user = auth()->user();
    $gameProfile = $user->gameProfile->game_data ?? [];
    // ...
    return Inertia::render('Dashboard/GameInfoPage', [
        'gameProfile' => $gameProfile
    ]);
})->name('dashboard.game-info');

// 3) صفحه نیروها
Route::get('/dashboard/troops', function () {
    $user = auth()->user();
    $gameProfile = $user->gameProfile->game_data ?? [];
    // ...
    return Inertia::render('Dashboard/TroopsPage', [
        'gameProfile' => $gameProfile
        // + اگر لازم است نقشه‌ها یا داده‌های دیگر
    ]);
})->name('dashboard.troops');

// 4) صفحه تقویم
Route::get('/dashboard/calendar', function () {
    $calendar = auth()->user()->calendars ?? [];
    return Inertia::render('Dashboard/CalendarPage', [
        'calendar' => $calendar
    ]);
})->name('dashboard.calendar');

// 5) صفحه وظیفهٔ امروز
Route::get('/dashboard/today-task', function () {
    $todayTask = auth()->user()->tasks()->latest()->first()->task ?? 'هیچ تسکی تعریف نشده.';
    return Inertia::render('Dashboard/TodayTaskPage', [
        'todayTask' => $todayTask
    ]);
})->name('dashboard.today-task');
Route::get('/test-pagination', function (Request $request) {
    // صفحه فعلی را از query بگیرید یا 1 باشد
    $page = $request->query('page', 1);

    // به کامپوننت Vue می‌فرستیم
    return Inertia::render('MinimalPaginationTest', [
        'page' => (int) $page,
    ]);
})->name('test.pagination');
// داشبورد
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // مسیرهای مربوط به تسک
    Route::post('/tasks/generate', [TaskController::class, 'generateTask'])->name('tasks.generate');
    Route::get('/tasks/last', [TaskController::class, 'getLastTask'])->name('tasks.last');
    Route::post('/tasks/complete', [TaskController::class, 'completeTask'])->name('tasks.complete');
    Route::post('/tasks/daily-plan', [TaskController::class, 'getDailyPlan'])->name('tasks.daily-plan');
    Route::post('/tasks/war-strategy', [TaskController::class, 'getWarStrategy'])->name('tasks.war-strategy');
    // Route::get('/clash/player', [ClashOfClansController::class, 'getPlayer']);

});
Route::post('/save-player-tag', [UserController::class, 'savePlayerTag'])->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::get('/map', [MapController::class, 'crawlMaps']);
require __DIR__ . '/auth.php';
