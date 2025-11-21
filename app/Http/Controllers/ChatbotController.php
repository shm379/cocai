<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ChatbotService;

class ChatbotController extends Controller
{
    protected $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    public function query(Request $request)
    {
        // دریافت اطلاعات ورودی
        $query = $request->input('query');
        $id = $request->input('id');

        // بازیابی داده‌های چت‌بات
        $chatbotData = $this->chatbotService->getChatbotData();

        // ارسال درخواست به چت‌بات
        $response = $this->chatbotService->sendQuery($query, $id, $chatbotData);

        // پاسخ را به کاربر بازگردانید
        return response()->json(['answer' => $response]);
    }
}
