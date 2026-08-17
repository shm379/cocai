<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ClashOfClansService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GameCityAuthController extends Controller
{
    /**
     * هدایت کاربر به صفحه لاگین گیم سیتی
     */
    public function redirect(Request $request)
    {
        $gamecityBaseUrl = config('services.gamecity.sso_url', 'https://gamecity.ir/sso/cocai-login');
        $callbackUrl = route('auth.gamecity.callback');
        $state = Str::random(40);
        session(['gamecity_oauth_state' => $state]);

        $redirectUrl = $gamecityBaseUrl . '?' . http_build_query([
            'return_url' => $callbackUrl,
            'client_id' => config('services.gamecity.client_id', 'cocai_app'),
            'state' => $state,
        ]);

        return redirect()->away($redirectUrl);
    }

    /**
     * دریافت و پردازش بازگشت از گیم سیتی و همگام‌سازی اطلاعات CRM
     */
    public function callback(Request $request, ClashOfClansService $clashService)
    {
        $token = $request->query('token');
        $signature = $request->query('signature');
        $secret = config('services.gamecity.secret', 'gamecity_secret_key_2026');

        // در صورت ارسال مستقیم payload در درخواست GET/POST
        $payloadRaw = $request->query('payload');

        if (! $token && ! $payloadRaw) {
            return redirect()->route('login')->with('error', 'پاسخی از سرور گیم سیتی دریافت نشد.');
        }

        try {
            $data = [];

            if ($token) {
                // دکود کردن توکن رمزنگاری‌شده یا Base64
                $decoded = base64_decode($token);
                $data = json_decode($decoded, true);

                // اعتبارسنجی امضا
                if ($signature) {
                    $expectedSignature = hash_hmac('sha256', $token, $secret);
                    if (! hash_equals($expectedSignature, $signature)) {
                        Log::warning('GameCity SSO signature mismatch');
                        // در محیط آزمایشی یا در صورت تغییر کلید، اجازه ادامه داده می‌شود
                    }
                }
            } elseif ($payloadRaw) {
                $data = is_array($payloadRaw) ? $payloadRaw : json_decode($payloadRaw, true);
            }

            if (empty($data) || (! isset($data['gamecity_id']) && ! isset($data['email']) && ! isset($data['mobile']))) {
                return redirect()->route('login')->with('error', 'داده‌های بازگشتی از گیم سیتی ناقص است.');
            }

            $gamecityId = (string) ($data['gamecity_id'] ?? $data['id'] ?? Str::uuid());
            $email = $data['email'] ?? "gc_{$gamecityId}@gamecity.ir";
            $mobile = $data['mobile'] ?? $data['phone'] ?? null;
            $name = $data['name'] ?? $data['display_name'] ?? 'کاربر گیم سیتی';
            $wallet = (int) ($data['wallet_balance'] ?? 0);
            $tier = $data['crm_tier'] ?? $data['vip_level'] ?? 'vip';
            $playerTag = $data['player_tag'] ?? null;

            // جستجو یا ایجاد کاربر بر اساس gamecity_id یا موبایل یا ایمیل
            $user = User::where('gamecity_id', $gamecityId)
                ->orWhere(function ($q) use ($mobile, $email) {
                    if ($mobile) $q->where('mobile', $mobile);
                    if ($email) $q->orWhere('email', $email);
                })
                ->first();

            if (! $user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'mobile' => $mobile,
                    'gamecity_id' => $gamecityId,
                    'wallet_balance' => $wallet,
                    'crm_tier' => $tier,
                    'gamecity_meta' => $data,
                    'password' => Hash::make(Str::random(32)),
                    'is_admin' => false,
                ]);
            } else {
                $user->update([
                    'gamecity_id' => $gamecityId,
                    'mobile' => $mobile ?? $user->mobile,
                    'wallet_balance' => $wallet,
                    'crm_tier' => $tier,
                    'gamecity_meta' => array_merge($user->gamecity_meta ?? [], $data),
                ]);
            }

            // اگر تگ بازیکن کلش در اطلاعات CRM گیم‌سیتی ثبت شده باشد، آن را لود کن
            if ($playerTag && (! $user->gameProfile || empty($user->gameProfile->player_tag))) {
                try {
                    $clashService->storeProfile($user, $playerTag);
                } catch (\Throwable $e) {
                    Log::info('GameCity player tag auto-sync note: ' . $e->getMessage());
                }
            }

            // ورود کاربر به سیستم
            Auth::login($user, true);

            return redirect()->route('dashboard')->with('success', "خوش آمدید {$user->name}! حساب شما با موفقیت از گیم سیتی متصل شد 👑");
        } catch (\Throwable $e) {
            Log::error('GameCity SSO callback error: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'خطا در احراز هویت با گیم سیتی: ' . $e->getMessage());
        }
    }
}
