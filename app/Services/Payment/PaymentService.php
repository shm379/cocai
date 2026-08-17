<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * درخواست پرداخت و اتصال به درگاه (زیبال، پی‌پینگ، زرین‌پال)
     */
    public function requestPayment(User $user, Plan $plan, string $gateway): array
    {
        $gateway = strtolower($gateway);
        $amount = $plan->price; // مبلغ به تومان
        $appUrl = rtrim(config('app.url', url('/')), '/');

        // ایجاد رکورد اولیه پرداخت
        $payment = Payment::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'gateway' => $gateway,
            'amount' => $amount,
            'status' => 'pending',
            'ip_address' => request()->ip(),
        ]);

        $callbackUrl = "{$appUrl}/subscription/callback/{$gateway}?payment_id={$payment->id}";

        try {
            if ($gateway === 'zibal') {
                return $this->requestZibal($payment, $plan, $callbackUrl);
            } elseif ($gateway === 'payping') {
                return $this->requestPayPing($payment, $plan, $callbackUrl, $user);
            } elseif ($gateway === 'zarinpal') {
                return $this->requestZarinpal($payment, $plan, $callbackUrl, $user);
            } else {
                // پیش‌فرض زیبال
                return $this->requestZibal($payment, $plan, $callbackUrl);
            }
        } catch (\Throwable $e) {
            Log::error("Payment request error for gateway {$gateway}: " . $e->getMessage());
            $payment->update([
                'status' => 'failed',
                'gateway_response' => ['error' => $e->getMessage()],
            ]);

            return [
                'ok' => false,
                'message' => 'خطا در ارتباط با درگاه پرداخت: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * درگاه زیبال (Zibal)
     */
    private function requestZibal(Payment $payment, Plan $plan, string $callbackUrl): array
    {
        $merchant = config('payment.gateways.zibal.merchant', 'zibal');
        $url = config('payment.gateways.zibal.request_url', 'https://gateway.zibal.ir/v1/request');
        $startPay = config('payment.gateways.zibal.start_pay_url', 'https://gateway.zibal.ir/start/');

        $amountRial = $payment->amount * 10; // زیبال مبالغ را به ریال دریافت می‌کند

        $response = Http::timeout(15)->post($url, [
            'merchant' => $merchant,
            'amount' => $amountRial,
            'callbackUrl' => $callbackUrl,
            'description' => "خرید اشتراک {$plan->name} در سامانه CoCAI",
            'orderId' => (string) $payment->id,
        ]);

        $data = $response->json();

        if ($response->successful() && isset($data['result']) && $data['result'] == 100) {
            $trackId = (string) $data['trackId'];
            $payment->update([
                'track_id' => $trackId,
                'gateway_response' => $data,
            ]);

            return [
                'ok' => true,
                'payment' => $payment,
                'action_url' => "{$startPay}{$trackId}",
            ];
        }

        $msg = $data['message'] ?? 'خطای ناشناخته زیبال';
        return ['ok' => false, 'message' => "خطا در زیبال (کد {$data['result']}): {$msg}"];
    }

    /**
     * درگاه پی‌پینگ (PayPing)
     */
    private function requestPayPing(Payment $payment, Plan $plan, string $callbackUrl, User $user): array
    {
        $token = config('payment.gateways.payping.token');
        $url = config('payment.gateways.payping.request_url', 'https://api.payping.net/v2/pay');
        $startPay = config('payment.gateways.payping.start_pay_url', 'https://api.payping.net/v2/pay/gotoipg/');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
        ])->timeout(15)->post($url, [
            'amount' => $payment->amount, // پی‌پینگ به تومان است
            'returnUrl' => $callbackUrl,
            'payerIdentity' => $user->email ?? $user->mobile ?? 'user_' . $user->id,
            'payerName' => $user->name,
            'description' => "خرید اشتراک {$plan->name} در سامانه CoCAI",
            'clientRefId' => (string) $payment->id,
        ]);

        $data = $response->json();

        if ($response->successful() && isset($data['code'])) {
            $code = (string) $data['code'];
            $payment->update([
                'track_id' => $code,
                'gateway_response' => $data,
            ]);

            return [
                'ok' => true,
                'payment' => $payment,
                'action_url' => "{$startPay}{$code}",
            ];
        }

        return ['ok' => false, 'message' => 'خطا در ارتباط با پی‌پینگ: ' . json_encode($data)];
    }

    /**
     * درگاه زرین‌پال (Zarinpal)
     */
    private function requestZarinpal(Payment $payment, Plan $plan, string $callbackUrl, User $user): array
    {
        $merchantId = config('payment.gateways.zarinpal.merchant_id');
        $url = config('payment.gateways.zarinpal.request_url', 'https://payment.zarinpal.com/pg/v4/payment/request.json');
        $startPay = config('payment.gateways.zarinpal.start_pay_url', 'https://payment.zarinpal.com/pg/StartPay/');

        $amountRials = $payment->amount * 10;

        $response = Http::timeout(15)->post($url, [
            'merchant_id' => $merchantId,
            'amount' => $amountRials,
            'callback_url' => $callbackUrl,
            'description' => "خرید اشتراک {$plan->name} در CoCAI",
            'metadata' => [
                'email' => $user->email ?? '',
                'mobile' => $user->mobile ?? '',
            ],
        ]);

        $data = $response->json();

        if (isset($data['data']['code']) && $data['data']['code'] == 100) {
            $authority = $data['data']['authority'];
            $payment->update([
                'track_id' => $authority,
                'gateway_response' => $data,
            ]);

            return [
                'ok' => true,
                'payment' => $payment,
                'action_url' => "{$startPay}{$authority}",
            ];
        }

        return ['ok' => false, 'message' => 'خطا در زرین‌پال: ' . json_encode($data)];
    }

    /**
     * اعتبارسنجی و وریفای تراکنش پس از بازگشت از درگاه
     */
    public function verifyPayment(string $gateway, array $params): array
    {
        $gateway = strtolower($gateway);
        $paymentId = $params['payment_id'] ?? null;

        $payment = Payment::with(['user', 'plan'])->find($paymentId);

        if (! $payment) {
            return ['ok' => false, 'message' => 'تراکنش پرداخت یافت نشد.'];
        }

        if ($payment->status === 'paid') {
            return ['ok' => true, 'payment' => $payment, 'message' => 'این تراکنش قبلاً تأیید شده است.'];
        }

        try {
            if ($gateway === 'zibal') {
                return $this->verifyZibal($payment, $params);
            } elseif ($gateway === 'payping') {
                return $this->verifyPayPing($payment, $params);
            } elseif ($gateway === 'zarinpal') {
                return $this->verifyZarinpal($payment, $params);
            } else {
                return $this->verifyZibal($payment, $params);
            }
        } catch (\Throwable $e) {
            Log::error("Payment verify error: " . $e->getMessage());
            $payment->update(['status' => 'failed', 'gateway_response' => ['verify_error' => $e->getMessage()]]);
            return ['ok' => false, 'message' => 'خطا در تایید تراکنش: ' . $e->getMessage()];
        }
    }

    private function verifyZibal(Payment $payment, array $params): array
    {
        $trackId = $params['trackId'] ?? $payment->track_id;
        $success = $params['success'] ?? 0;

        if ($success != 1) {
            $payment->update(['status' => 'canceled']);
            return ['ok' => false, 'message' => 'پرداخت توسط کاربر لغو شد.'];
        }

        $merchant = config('payment.gateways.zibal.merchant', 'zibal');
        $verifyUrl = config('payment.gateways.zibal.verify_url', 'https://gateway.zibal.ir/v1/verify');

        $response = Http::timeout(15)->post($verifyUrl, [
            'merchant' => $merchant,
            'trackId' => $trackId,
        ]);

        $data = $response->json();

        if ($response->successful() && isset($data['result']) && in_array($data['result'], [100, 201])) {
            $refNumber = (string) ($data['refNumber'] ?? $trackId);
            $cardNumber = $data['cardNumber'] ?? null;

            $payment->update([
                'status' => 'paid',
                'ref_number' => $refNumber,
                'card_pan' => $cardNumber,
                'gateway_response' => $data,
            ]);

            $subscription = $this->activateSubscription($payment);

            return [
                'ok' => true,
                'payment' => $payment,
                'subscription' => $subscription,
                'ref_number' => $refNumber,
                'message' => "پرداخت با موفقیت انجام شد. شماره پیگیری: {$refNumber}",
            ];
        }

        $payment->update(['status' => 'failed', 'gateway_response' => $data]);
        return ['ok' => false, 'message' => 'تأیید تراکنش در زیبال با شکست مواجه شد.'];
    }

    private function verifyPayPing(Payment $payment, array $params): array
    {
        $token = config('payment.gateways.payping.token');
        $verifyUrl = config('payment.gateways.payping.verify_url', 'https://api.payping.net/v2/pay/verify');
        $refId = $params['refid'] ?? null;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
        ])->timeout(15)->post($verifyUrl, [
            'amount' => $payment->amount,
            'refId' => $refId,
        ]);

        $data = $response->json();

        if ($response->successful()) {
            $payment->update([
                'status' => 'paid',
                'ref_number' => (string) $refId,
                'card_pan' => $data['cardNumber'] ?? null,
                'gateway_response' => $data,
            ]);

            $subscription = $this->activateSubscription($payment);

            return [
                'ok' => true,
                'payment' => $payment,
                'subscription' => $subscription,
                'ref_number' => (string) $refId,
                'message' => "پرداخت با موفقیت انجام شد. شماره پیگیری: {$refId}",
            ];
        }

        $payment->update(['status' => 'failed', 'gateway_response' => $data]);
        return ['ok' => false, 'message' => 'تأیید تراکنش در پی‌پینگ با شکست مواجه شد.'];
    }

    private function verifyZarinpal(Payment $payment, array $params): array
    {
        $authority = $params['Authority'] ?? $payment->track_id;
        $status = $params['Status'] ?? 'NOK';

        if ($status !== 'OK') {
            $payment->update(['status' => 'canceled']);
            return ['ok' => false, 'message' => 'پرداخت توسط کاربر لغو شد.'];
        }

        $merchantId = config('payment.gateways.zarinpal.merchant_id');
        $verifyUrl = config('payment.gateways.zarinpal.verify_url', 'https://payment.zarinpal.com/pg/v4/payment/verify.json');

        $response = Http::timeout(15)->post($verifyUrl, [
            'merchant_id' => $merchantId,
            'amount' => $payment->amount * 10,
            'authority' => $authority,
        ]);

        $data = $response->json();

        if (isset($data['data']['code']) && in_array($data['data']['code'], [100, 101])) {
            $refId = (string) ($data['data']['ref_id'] ?? $authority);
            $cardPan = $data['data']['card_pan'] ?? null;

            $payment->update([
                'status' => 'paid',
                'ref_number' => $refId,
                'card_pan' => $cardPan,
                'gateway_response' => $data,
            ]);

            $subscription = $this->activateSubscription($payment);

            return [
                'ok' => true,
                'payment' => $payment,
                'subscription' => $subscription,
                'ref_number' => $refId,
                'message' => "پرداخت با موفقیت انجام شد. کد پیگیری: {$refId}",
            ];
        }

        $payment->update(['status' => 'failed', 'gateway_response' => $data]);
        return ['ok' => false, 'message' => 'تأیید پرداخت در زرین‌پال ناموفق بود.'];
    }

    /**
     * فعال‌سازی اشتراک کاربر بر اساس پلن خریداری شده
     */
    private function activateSubscription(Payment $payment): Subscription
    {
        $user = $payment->user;
        $plan = $payment->plan;

        $durationDays = $plan ? $plan->duration_days : 30;

        // اگر کاربر اشتراک فعال دارد، به انتهای آن اضافه کن
        $activeSub = $user->activeSubscription();
        $startsAt = now();
        $endsAt = $activeSub && $activeSub->ends_at->isFuture()
            ? $activeSub->ends_at->copy()->addDays($durationDays)
            : now()->addDays($durationDays);

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'payment_id' => $payment->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'active',
        ]);
    }
}
