<?php

namespace App\Http\Controllers;

use App\Jobs\SendChatbotQuery;
use App\Models\TrophyLog;
use Illuminate\Http\Request;
use App\Services\ClashOfClansService;
use App\Services\ChatbotService;
use App\Models\GameProfile;
use App\Models\Task;
use Inertia\Inertia;

class UserController extends Controller
{

    protected $clashOfClansService;
    protected $chatbotService;

    public function __construct(ClashOfClansService $clashOfClansService, ChatbotService $chatbotService)
    {
        $this->clashOfClansService = $clashOfClansService;
        $this->chatbotService = $chatbotService;
    }

    public function generateTask($user)
    {
        $query = $user->player_tag . " لطفا فقط بگو چه کاری برای ارتقا مهمترینه امروز و من الان تقویم نیاز ندارم فقط در ۳ خط بگو چیکار کنم امروز چون باید تاون هال ببرم بالا و نیاز به کمک دارم";

        $chatbotData = \Cache::get('chatbot', []);

        try {
            $response = $this->chatbotService->sendQuery($query, $user->id, $chatbotData, false); // کل متن را برگردان

            // ذخیره تسک جدید در دیتابیس
            $task = Task::create([
                'user_id' => $user->id,
                'task' => $response,
            ]);

            return $task;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function generateCalendar($user)
    {

        $query = $user->player_tag . " یک تقویم بده";

        try {
            $chatbotData = \Cache::get('chatbot', []);
            SendChatbotQuery::dispatch($query, $user->id, $chatbotData, true);

            return true;
        } catch (\Exception $e) {
            \Log::error('Error generating calendar: ' . $e->getMessage());
            return false;
        }
    }

    public function savePlayerTag(Request $request)
    {
        set_time_limit(1200); // زمان اجرا را به 120 ثانیه افزایش می‌دهد

        $request->validate([
            'player_tag' => 'required|string|max:255',
        ]);
        $user = auth()->user();


        // به‌روزرسانی یا ایجاد رکورد در GameProfile
//        try {
            // حذف # از player_tag
            $playerTag = str_replace('#', '', $request->player_tag);

            // دریافت اطلاعات بازی از ClashOfClansService
            $playerData = $this->clashOfClansService->getPlayerData($playerTag);
            // ذخیره یا به‌روزرسانی اطلاعات در GameProfile
            $gameProfile = GameProfile::updateOrCreate(
                ['user_id' => $user->id,
                ], // شرط برای به‌روزرسانی یا ایجاد
                [
                    'player_tag' => $playerTag,
                    'game_data' => $playerData, // ذخیره اطلاعات بازی
                ]
            );
            if($gameProfile) {
                TrophyLog::query()->create([
                    'game_profile_id' => $gameProfile->id,
                    'trophy_count' => $playerData['trophies'] ?? 0,
                ]);
            }
            $this->generateCalendar($user);

            // تولید تسک جدید با استفاده از generateTask
//            $this->generateTask($user);

            return redirect()->route('dashboard')->with(['successMessage'=>'پردازش با موفقیت انجام شد']);
//        } catch (\Exception $e) {
//            return redirect()->route('dashboard')->with(['errorMessage'=>'پردازش با شکست مواجه شد']);
//        }
    }

    public function completeTask(Request $request)
    {
        $user = auth()->user();

        // تلاش برای یافتن تسک فعلی کاربر
        $task = Task::where('user_id', $user->id)->where('task', $request->task)->first();

        if ($task) {
            // به‌روزرسانی وضعیت تسک به انجام شده
            $task->update(['status' => 'completed']);

            // تولید تسک جدید
            $newTask = $this->chatbotService->generateNewTask($user);

            // بروزرسانی اطلاعات تسک جدید
            return response()->json([
                'message' => 'تسک با موفقیت انجام شد!',
                'todayTask' => $newTask,  // تسک جدید
            ]);
        }

        return response()->json(['message' => 'تسک یافت نشد.'], 400);
    }


}
