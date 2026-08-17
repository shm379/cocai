<?php

namespace App\Http\Controllers;

use App\Services\AI\AndroidCompanionService;
use Illuminate\Http\Request;

class AndroidCompanionController extends Controller
{
    public function __construct(protected AndroidCompanionService $androidService)
    {
    }

    /**
     * تولید ماکروی هوشمند تاچ خودکار بر اساس اسکرین‌شات یا ابعاد گوشی
     */
    public function generateMacro(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['ok' => false, 'message' => 'کاربر احراز هویت نشده است.'], 401);
        }

        $options = $request->all();
        
        // ذخیره اسکرین‌شات زنده در کش برای نمایش در داشبورد
        if (!empty($options['image_base64'])) {
            \Illuminate\Support\Facades\Cache::put('latest_android_screen_' . $user->id, $options['image_base64'], 60);
        }

        $macro = $this->androidService->generateTouchMacro($user, $options);

        return response()->json($macro);
    }

    /**
     * دریافت آخرین اسکرین‌شات لایو از بازی برای نمایش در HUD
     */
    public function getLatestScreenshot()
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['ok' => false]);
        }
        
        $base64 = \Illuminate\Support\Facades\Cache::get('latest_android_screen_' . $user->id);
        return response()->json([
            'ok' => true,
            'image' => $base64 ? 'data:image/png;base64,' . $base64 : null
        ]);
    }

    /**
     * دانلود اسکریپت ایجنت اندروید برای اجرا روی گوشی یا سرور شبیه‌ساز
     */
    public function downloadAgentScript()
    {
        $filePath = public_path('downloads/cocai-android/cocai-android-agent.py');
        if (! file_exists($filePath)) {
            return response()->json(['ok' => false, 'message' => 'فایل یافت نشد.'], 404);
        }

        return response()->download($filePath, 'cocai-android-agent.py', [
            'Content-Type' => 'text/x-python',
        ]);
    }
}
