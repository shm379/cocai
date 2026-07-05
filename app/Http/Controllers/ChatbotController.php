<?php

namespace App\Http\Controllers;

use App\Services\ChatbotService;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    protected $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    public function query(Request $request)
    {
        // اعتبارسنجی ورودی (فرانت‌اند فیلد `question` را می‌فرستد)
        $validated = $request->validate([
            'question' => 'required|string|max:2000',
        ]);

        $user = auth()->user();

        // پاسخ grounded با بلوک واقعیتِ محاسبه‌شده از دادهٔ واقعی بازیکن
        $response = $this->chatbotService->answerUserQuestion($user, $validated['question']);

        // پاسخ را به کاربر بازگردانید
        return response()->json(['answer' => $response]);
    }
}
