<?php

namespace App\Jobs;

use App\Models\Calendar;
use App\Models\User;
use App\Services\ChatbotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SendChatbotQuery implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;

    public $timeout = 300;

    public $tries = 2;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * جلوگیری از صف شدن دو جاب هم‌زمان برای یک کاربر.
     */
    public function uniqueId(): string
    {
        return (string) $this->userId;
    }

    /**
     * ساخت تقویم ارتقا — از موتور قطعی ProgressionService (بدون LLM).
     */
    public function handle(ChatbotService $chatbotService)
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $gameProfile = $user->gameProfile;
        if (! $gameProfile) {
            return;
        }

        $calendar = $chatbotService->generateCalendar($user);
        if (empty($calendar['days']) || ! is_array($calendar['days'])) {
            return;
        }

        // جایگزینی اتمیک تقویم قبلی
        DB::transaction(function () use ($calendar, $gameProfile) {
            Calendar::where('game_profile_id', $gameProfile->id)->delete();

            foreach ($calendar['days'] as $task) {
                if (! isset($task['day'], $task['task']) || ! is_int($task['day']) || $task['day'] < 1) {
                    continue;
                }

                Calendar::create([
                    'user_id' => $this->userId,
                    'game_profile_id' => $gameProfile->id,
                    'day' => $task['day'],
                    'task' => (string) $task['task'],
                ]);
            }
        });
    }
}
