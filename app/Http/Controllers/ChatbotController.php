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

        // افزودن تگ بازیکن به‌عنوان زمینه تا پاسخ شخصی‌سازی شود
        $query = $validated['question'];
        if ($user && $user->player_tag) {
            $query = "Player Tag: {$user->player_tag}. ".$query;
        }

        // شناسه‌ی پایدار برای حفظ مکالمه در کش
        $id = $user?->id ?? 'guest';

        // بازیابی داده‌های چت‌بات
        $chatbotData = $this->chatbotService->getChatbotData();

        // ارسال درخواست به چت‌بات و دریافت متن کامل پاسخ
        $response = $this->chatbotService->sendQuery($query, $id, $chatbotData, false);

        // پاسخ را به کاربر بازگردانید
        return response()->json(['answer' => $response]);
    }
}
