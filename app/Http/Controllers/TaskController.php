<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Services\ChatbotService;
use Inertia\Inertia;

class TaskController extends Controller
{
    protected $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * تولید تسک جدید از چت‌بات
     */
    public function generateTask($user)
    {
        $query = $user->player_tag." "; // پیام موردنظر برای چت‌بات
        if($user->todayTask){
            $query.=$user->todayTask;
        }
        // گرفتن اطلاعات چت‌بات (می‌توانید از کش یا دیتابیس ذخیره کنید)
        $chatbotData = \Cache::get('chatbot', []);

        try {
            $chatbotData = $this->chatbotService->getChatbotData(); // دریافت اطلاعات از کش
            
            // ارسال درخواست به چت‌بات
            $response = $this->chatbotService->sendQuery($query, $user->id, $chatbotData);
    

            // ذخیره تسک جدید در دیتابیس
            $task = Task::create([
                'user_id' => $user->id,
                'task' => $response,
            ]);

            // ذخیره اطلاعات به‌روزرسانی شده چت‌بات
            // \Cache::put('chatbot', $response['chatbotData'], now()->addHours(24));

            return $task;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * دریافت آخرین تسک
     */
    public function getLastTask()
    {
        $user = auth()->user();
        $lastTask = $user->tasks()->latest()->first();

        return response()->json([
            'task' => $lastTask,
        ]);
    }

    public function completeTask(Request $request)
    {
        $user = auth()->user();
        
        // فرض بر این است که تسک‌ها در جدول Task ذخیره می‌شوند
        // ما باید آخرین تسک را پیدا کنیم و وضعیت آن را تغییر دهیم
        $task = $user->tasks()->latest()->first();
    
        // اگر تسکی وجود دارد، آن را به حالت کامل تغییر می‌دهیم
        if ($task) {
            $task->update(['completed' => true]);  // یا هر فیلد دیگری که برای نشان دادن تکمیل استفاده می‌کنید
        }
        // $newTask = $this->generateTask($user);

        // بازگشت پاسخ به کلاینت
        
        return Inertia::replace('Dashboard', [
            'user' => $user,
            'successMessage' => 'تسک انجام شد',
        ]);
    }
    
}
