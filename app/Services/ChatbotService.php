<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class ChatbotService
{
    protected $apiUrl = "https://app.mu.chat/api/agents/cm5tp91kl007nf7cn0ri331v1/query";
    protected $authToken = "22f1bce6-ae1c-4233-bd9c-5324c8ee4ffd";

    public function sendQuery($query, $id, $chatbotData, $output_json = true, $conversationId = null)
    {
        $postData = [
            'query' => $query,
            'maxTokens' => 16000,
            'conversationId' => $conversationId,
        ];

        if (isset($chatbotData[$id])) {
            $user = $chatbotData[$id];
            if (isset($user['visitorId'])) {
                $postData['visitorId'] = $user['visitorId'];
            }
            if (isset($user['conversationId'])) {
                $postData['conversationId'] = $user['conversationId'];
            }
        }

        $response = Http::timeout(120)           // ← اضافه کنید
            ->connectTimeout(30)                // ← اضافه کنید
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->authToken,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, $postData);

        if ($response->ok()) {
            $responseData = $response->json();
            $conversationId = $responseData['conversationId'] ?? '';
            $visitorId = $responseData['visitorId'] ?? '';
            $lastMessageOutput = $responseData['answer'] ?? "پاسخی یافت نشد";

            // **📌 اگر `output_json = true` باشد، فقط JSON را استخراج کن**
            if ($output_json) {
                $jsonData = $this->extractJsonFromText($lastMessageOutput);
                if ($jsonData) {
                    return json_decode($jsonData, true); // تبدیل به آرایه
                }
            }

            // ذخیره اطلاعات چت‌بات
            $chatbotData[$id] = [
                'conversationId' => $conversationId,
                'visitorId' => $visitorId,
            ];

            $this->updateChatbotOption($chatbotData);

            return $lastMessageOutput; // اگر `output_json = false` باشد، کل متن را برمی‌گردانیم
        }

        return "خطا در ارتباط با چت‌بات: " . $response->body();
    }

    /**
     * **📌 استخراج بخش JSON از متن**
     */
    private function extractJsonFromText($text)
    {
        preg_match('/\{.*\}$/s', $text, $matches);
        return $matches[0] ?? null;
    }

    // ذخیره اطلاعات چت‌بات
    protected function updateChatbotOption($chatbotData)
    {
        if (!is_array($chatbotData)) {
            $chatbotData = [];
        }

        \Cache::put('chatbot', $chatbotData, now()->addHours(24));
    }

    // دریافت اطلاعات چت‌بات
    public function getChatbotData()
    {
        // بازیابی اطلاعات از کش
        $chatbot = \Cache::get('chatbot', []);

        // بررسی اینکه داده ذخیره‌شده یک آرایه است یا نه
        if (is_string($chatbot)) {
            $chatbot = json_decode($chatbot, true);
        }

        // اطمینان از اینکه `chatbot` یک آرایه معتبر است
        return is_array($chatbot) ? $chatbot : [];
    }

    /**
     * Generate a strategy or plan based on user data.
     *
     * @param \App\Models\User $user
     * @param string $type 'daily_plan' | 'war_strategy'
     * @return string|array
     */
    public function generateStrategy($user, $type)
    {
        $gameData = $user->gameProfile->game_data ?? [];
        $playerTag = $user->player_tag;

        if (empty($gameData)) {
            return "اطلاعات بازی شما یافت نشد. لطفاً ابتدا پروفایل خود را به‌روزرسانی کنید.";
        }

        $prompt = "";
        $context = "Player Tag: {$playerTag}. Game Data: " . json_encode($gameData);

        if ($type === 'daily_plan') {
            $prompt = "Based on the following Clash of Clans player data, provide a detailed daily plan. Focus on what upgrades to prioritize next (buildings, troops, heroes) considering their current Town Hall level. Also suggest what resources to farm. \n\nContext: " . $context;
        } elseif ($type === 'war_strategy') {
            $prompt = "Based on the following Clash of Clans player data, suggest the best Clan War attack strategies. Consider their troop and hero levels. Explain the army composition and the attack plan step-by-step. \n\nContext: " . $context;
        } else {
            return "Invalid strategy type.";
        }

        // Reuse the existing sendQuery method
        // We use a static ID 'strategy_bot' or similar to cache context if needed, 
        // or just use user ID to keep it personalized.
        return $this->sendQuery($prompt, $user->id, $this->getChatbotData(), false);
    }

}
