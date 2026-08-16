<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\ChatbotService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    protected $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * تولید تسک جدید — از موتور تحلیل پیشرفت (قطعی) با بیان LLM.
     */
    public function generateTask(Request $request)
    {
        $user = auth()->user();

        $text = $this->chatbotService->generateNewTask($user);

        $task = Task::create([
            'user_id' => $user->id,
            'task' => $text,
        ]);

        return response()->json(['task' => $task]);
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

        // اگر id مشخص شده همان تسک؛ وگرنه آخرین تسکِ باز
        $query = $user->tasks()->where('completed', false);
        $task = $request->filled('task_id')
            ? $query->where('id', $request->integer('task_id'))->first()
            : $query->latest()->first();

        if ($task) {
            $task->update(['completed' => true]);
            $user->recordTaskCompletion();
        }

        return to_route('dashboard')->with('successMessage', 'تسک انجام شد'.($user->task_streak > 1 ? " — استریک {$user->task_streak} روزه! 🔥" : ''));
    }

    public function getDailyPlan(Request $request)
    {
        $user = auth()->user();
        $plan = $this->chatbotService->generateStrategy($user, 'daily_plan');

        return response()->json(['plan' => $plan]);
    }

    public function getWarStrategy(Request $request)
    {
        $user = auth()->user();
        $strategy = $this->chatbotService->generateStrategy($user, 'war_strategy');

        return response()->json(['strategy' => $strategy]);
    }
}
