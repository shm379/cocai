<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WordPressAuthService
{
    protected string $wpBaseUrl;
    protected string $appUser;
    protected string $appPassword;

    public function __construct()
    {
        $this->wpBaseUrl = rtrim(config('services.gamecity.url', env('GAMECITY_WP_URL', 'https://gamecity.ir')), '/');
        $this->appUser = config('services.gamecity.app_user', env('GAMECITY_APP_USER', ''));
        $this->appPassword = config('services.gamecity.app_password', env('GAMECITY_APP_PASSWORD', ''));
    }

    /**
     * اعتبارسنجی مستقیم با نام‌کاربری/ایمیل و Application Password وردپرس
     */
    public function authenticateWithCredentials(string $usernameOrEmail, string $password): array
    {
        try {
            $url = "{$this->wpBaseUrl}/wp-json/wp/v2/users/me?context=edit";

            // استفاده از احراز هویت Basic با Application Password وردپرس
            $response = Http::timeout(10)
                ->withBasicAuth($usernameOrEmail, $password)
                ->acceptJson()
                ->get($url);

            if ($response->successful()) {
                $wpUser = $response->json();
                $user = $this->findOrCreateLocalUser($wpUser);
                return ['ok' => true, 'user' => $user];
            }

            return [
                'ok' => false,
                'message' => 'نام کاربری یا کلمه عبور وردپرس صحیح نیست.',
            ];
        } catch (\Throwable $e) {
            Log::error('WordPress auth error: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'خطا در ارتباط با سرور وردپرس: ' . $e->getMessage()];
        }
    }

    /**
     * اعتبارسنجی با توکن OAuth2 / JWT بازگشتی از وردپرس
     */
    public function authenticateWithToken(string $token): array
    {
        try {
            $url = "{$this->wpBaseUrl}/wp-json/wp/v2/users/me?context=edit";

            $response = Http::timeout(10)
                ->withToken($token)
                ->acceptJson()
                ->get($url);

            if ($response->successful()) {
                $wpUser = $response->json();
                $user = $this->findOrCreateLocalUser($wpUser);
                return ['ok' => true, 'user' => $user];
            }

            return ['ok' => false, 'message' => 'توکن احراز هویت وردپرس نامعتبر یا منقضی شده است.'];
        } catch (\Throwable $e) {
            Log::error('WordPress token auth error: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'خطا در ارتباط با وردپرس: ' . $e->getMessage()];
        }
    }

    /**
     * ایجاد یا اتصال کاربر در جدول محلی با حداقل فیلدها و ساختار کاملاً استاندارد
     */
    public function findOrCreateLocalUser(array $wpUser): User
    {
        $email = $wpUser['email'] ?? "wp_{$wpUser['id']}@gamecity.ir";
        $name = $wpUser['name'] ?? $wpUser['slug'] ?? 'کاربر وردپرس';
        $mobile = $wpUser['meta']['billing_phone'] ?? $wpUser['meta']['mobile'] ?? null;

        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'mobile' => $mobile,
                'password' => Hash::make(Str::random(32)),
                'is_admin' => false,
            ]);
        } else {
            $user->update([
                'name' => $name,
                'mobile' => $mobile ?? $user->mobile,
            ]);
        }

        return $user;
    }
}
