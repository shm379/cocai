<?php

namespace App\Jobs;

use App\Models\Calendar;
use App\Models\GameProfile;
use App\Models\User;
use App\Services\ChatbotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendChatbotQuery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;

    public $timeout = 1200; // حداکثر مدت زمان اجرای جاب در ثانیه

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Build a multi-day upgrade calendar for the user via the AI gateway and persist it.
     */
    public function handle(ChatbotService $chatbotService)
    {
        set_time_limit(1200);

        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $calendar = $chatbotService->generateCalendar($user);
        if (empty($calendar['days']) || ! is_array($calendar['days'])) {
            return;
        }

        $gameProfile = GameProfile::where('user_id', $this->userId)->latest()->first();
        if (! $gameProfile) {
            return;
        }

        // تقویم قبلی این پروفایل را پاک کن تا برنامهٔ تازه جایگزین شود
        Calendar::where('game_profile_id', $gameProfile->id)->delete();

        foreach ($calendar['days'] as $task) {
            if (! isset($task['day'], $task['task'])) {
                continue;
            }

            Calendar::create([
                'user_id' => $this->userId,
                'game_profile_id' => $gameProfile->id,
                'day' => $task['day'],
                'task' => $task['task'],
            ]);
        }
    }
}
