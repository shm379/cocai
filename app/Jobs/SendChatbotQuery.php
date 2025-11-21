<?php

namespace App\Jobs;

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
    protected $query;
    protected $userId;
    protected $chatbotData;
    protected $outputJson;

    // می‌توانید مقادیر timeout یا سایر مشخصه‌های صف را اینجا تعیین کنید
    public $timeout = 1200; // حداکثر مدت زمان اجرای جاب در ثانیه

    /**
     * Create a new job instance.
     *
     * @param string $query
     * @param mixed $userId
     * @param array $chatbotData
     * @param bool $outputJson
     */
    public function __construct($query, $userId, $chatbotData = [], $outputJson = true)
    {
        $this->query = $query;
        $this->userId = $userId;
        $this->chatbotData = $chatbotData;
        $this->outputJson = $outputJson;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ChatbotService $chatbotService)
    {
        set_time_limit(1200);
        $response = $chatbotService->sendQuery(
            $this->query,
            $this->userId,
            $this->chatbotData,
            $this->outputJson,
        );

        $response = $chatbotService->sendQuery(
            'Think deeply and check that everything the calendar gives is equal to the levels in json api. and return calendar in persian',
            $this->userId,
            $this->chatbotData,
            $this->outputJson,
        );

        $gameProfile = GameProfile::query()->where('user_id',$this->userId)->latest()->first();
        // اگر خروجی JSON بود:
        if (is_array($response) && isset($response['days'])) {
            foreach ($response['days'] as $task) {
                \App\Models\Calendar::create([
                    'user_id' => $this->userId,
                    'game_profile_id' => $gameProfile->id,
                    'day' => $task['day'],
                    'task' => $task['task'],
                ]);
            }
        }
    }

}
