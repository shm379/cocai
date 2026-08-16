<?php

namespace App\Http\Controllers;

use App\Jobs\SendChatbotQuery;
use App\Models\Task;
use App\Services\ChatbotService;
use App\Services\ClashOfClansService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $clashOfClansService;

    protected $chatbotService;

    public function __construct(ClashOfClansService $clashOfClansService, ChatbotService $chatbotService)
    {
        $this->clashOfClansService = $clashOfClansService;
        $this->chatbotService = $chatbotService;
    }

    /**
     * پیش‌نمایش سریع تگ بازیکن قبل از ذخیره.
     */
    public function previewPlayerTag(Request $request)
    {
        $request->validate([
            'player_tag' => ['required', 'string', 'max:15', 'regex:/^#?[0-9A-Za-z]{5,12}$/'],
        ]);

        try {
            $data = $this->clashOfClansService->getPlayerData($request->player_tag);

            return response()->json([
                'name' => $data['name'] ?? null,
                'town_hall' => $data['townHallLevel'] ?? null,
                'trophies' => $data['trophies'] ?? null,
                'exp_level' => $data['expLevel'] ?? null,
                'clan' => $data['clan']['name'] ?? null,
                'tag' => $data['tag'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * ثبت تگ بازیکن: دریافت دادهٔ بازی، ذخیرهٔ پروفایل + لاگ تروفی،
     * و ساخت تقویم قطعی در پس‌زمینه.
     */
    public function savePlayerTag(Request $request)
    {
        $request->validate([
            'player_tag' => ['required', 'string', 'max:15', 'regex:/^#?[0-9A-Za-z]{5,12}$/'],
        ], [
            'player_tag.regex' => 'تگ بازیکن معتبر نیست. مثال: #2PP0J9VL',
        ]);

        $user = auth()->user();

        try {
            $this->clashOfClansService->storeProfile($user, $request->player_tag);
        } catch (\Throwable $e) {
            \Log::warning('savePlayerTag failed: '.$e->getMessage());

            return redirect()->route('dashboard')
                ->with(['errorMessage' => 'دریافت اطلاعات بازیکن ناموفق بود. تگ را بررسی کن و دوباره تلاش کن.']);
        }

        SendChatbotQuery::dispatch($user->id);

        return redirect()->route('dashboard')->with(['successMessage' => 'پردازش با موفقیت انجام شد']);
    }

    /**
     * رفرش دستی پروفایل (دکمهٔ به‌روزرسانی داشبورد).
     */
    public function refreshProfile(Request $request)
    {
        $user = auth()->user();

        try {
            $this->clashOfClansService->refreshGameProfile($user);
        } catch (\Throwable $e) {
            return redirect()->route('dashboard')
                ->with(['errorMessage' => 'به‌روزرسانی ناموفق بود: '.$e->getMessage()]);
        }

        SendChatbotQuery::dispatch($user->id);

        return redirect()->route('dashboard')->with(['successMessage' => 'اطلاعات بازی به‌روز شد']);
    }

    public function completeTask(Request $request)
    {
        $user = auth()->user();

        $task = Task::where('user_id', $user->id)->where('task', $request->task)->first();

        if ($task) {
            $task->update(['completed' => true]);
            $user->recordTaskCompletion();

            $newTask = $this->chatbotService->generateNewTask($user);

            Task::create(['user_id' => $user->id, 'task' => $newTask]);

            return response()->json([
                'message' => 'تسک با موفقیت انجام شد!'.($user->task_streak > 1 ? " — استریک {$user->task_streak} روزه! 🔥" : ''),
                'todayTask' => $newTask,
                'streak' => $user->task_streak,
            ]);
        }

        return response()->json(['message' => 'تسک یافت نشد.'], 400);
    }
}
