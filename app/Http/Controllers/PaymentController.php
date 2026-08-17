<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService)
    {
    }

    /**
     * لیست پلن‌های فعال
     */
    public function plans()
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $user = auth()->user();

        return response()->json([
            'ok' => true,
            'plans' => $plans,
            'has_subscription' => $user ? $user->hasActiveSubscription() : false,
            'days_remaining' => $user ? $user->subscriptionDaysLeft() : 0,
            'gateways' => [
                ['id' => 'zibal', 'name' => 'درگاه پرداخت زیبال (Zibal)', 'icon' => '💳', 'recommended' => true],
                ['id' => 'payping', 'name' => 'درگاه پرداخت پی‌پینگ (PayPing)', 'icon' => '⚡', 'recommended' => false],
                ['id' => 'zarinpal', 'name' => 'درگاه پرداخت زرین‌پال (ZarinPal)', 'icon' => '🛡️', 'recommended' => false],
            ],
        ]);
    }

    /**
     * شروع فرآیند خرید و اتصال به درگاه
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'gateway' => 'required|in:zibal,payping,zarinpal',
        ]);

        $user = auth()->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $plan = Plan::findOrFail($request->input('plan_id'));
        $gateway = $request->input('gateway');

        $result = $this->paymentService->requestPayment($user, $plan, $gateway);

        if ($result['ok'] && isset($result['action_url'])) {
            return Inertia::location($result['action_url']);
        }

        return redirect()->route('dashboard')->with('error', $result['message'] ?? 'خطا در هدایت به درگاه پرداخت');
    }

    /**
     * کال‌بک بازگشت از درگاه پرداخت
     */
    public function callback(Request $request, string $gateway)
    {
        $result = $this->paymentService->verifyPayment($gateway, $request->all());

        if ($result['ok']) {
            return redirect()->route('dashboard')->with('success', $result['message'] ?? 'اشتراک ویژه شما با موفقیت فعال شد!');
        }

        return redirect()->route('dashboard')->with('error', $result['message'] ?? 'پرداخت ناموفق بود.');
    }
}
