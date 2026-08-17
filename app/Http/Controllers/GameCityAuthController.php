<?php

namespace App\Http\Controllers;

use App\Services\Auth\WordPressAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameCityAuthController extends Controller
{
    public function __construct(protected WordPressAuthService $wpAuth)
    {
    }

    /**
     * هدایت کاربر به صفحه لاگین وردپرس (OAuth2 یا لینک ورود اختصاصی)
     */
    public function redirect(Request $request)
    {
        $wpUrl = rtrim(config('services.gamecity.url', 'https://gamecity.ir'), '/');
        $callbackUrl = route('auth.gamecity.callback');

        $ssoUrl = "{$wpUrl}/?gamecity_sso_action=auth&redirect_to=" . urlencode($callbackUrl);
        return redirect()->away($ssoUrl);
    }

    /**
     * کال‌بک بازگشت از وردپرس با توکن یا رمز عبور اپلیکیشن
     */
    public function callback(Request $request)
    {
        $token = $request->query('token') ?? $request->input('token');

        if ($token) {
            $result = $this->wpAuth->authenticateWithToken($token);

            if ($result['ok']) {
                Auth::login($result['user'], true);
                return redirect()->route('dashboard')->with('success', 'با موفقیت از طریق حساب گیم سیتی وارد شدید!');
            }
        }

        // بررسی پارامترهای پایه‌ای اگر مستقیماً کاربر با basic auth لاگین کند
        $username = $request->input('username');
        $password = $request->input('password');

        if ($username && $password) {
            $result = $this->wpAuth->authenticateWithCredentials($username, $password);
            if ($result['ok']) {
                Auth::login($result['user'], true);
                return redirect()->route('dashboard')->with('success', 'با موفقیت وارد شدید!');
            }
            return back()->withErrors(['email' => $result['message'] ?? 'خطا در احراز هویت وردپرس']);
        }

        return redirect()->route('login')->with('error', 'احراز هویت با گیم سیتی لغو شد یا پاسخ معتبر دریافت نشد.');
    }
}
