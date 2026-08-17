<template>
    <div>
        <!-- دکمه شناور یا کارت اشتراک در داشبورد -->
        <div class="p-4 rounded-2xl bg-gradient-to-r from-amber-500/15 via-orange-500/15 to-red-500/15 border border-amber-500/30 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-lg backdrop-blur-md">
            <div class="flex items-center gap-3 text-right w-full sm:w-auto">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-600 flex items-center justify-center text-2xl shadow-lg shadow-amber-500/30 text-gray-950 font-black shrink-0">
                    💎
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm sm:text-base font-black text-white">عضویت ویژه CoCAI VIP</h3>
                        <span v-if="daysRemaining > 0" class="text-[10px] px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 font-bold font-mono">
                            {{ daysRemaining }} روز باقی‌مانده
                        </span>
                        <span v-else class="text-[10px] px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40 font-bold">
                            دسترسی رایگان / آزمایشی
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">آنالیز نامحدود بیس‌ها با AI، دسترسی به تمام بازی‌های سوپرسل و ارتش‌های متای ۲۰۲۶</p>
                </div>
            </div>

            <button
                type="button"
                @click="showModal = true"
                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 via-orange-500 to-red-600 hover:from-amber-400 hover:to-red-500 text-gray-950 font-black text-xs sm:text-sm shadow-lg shadow-amber-500/25 transition duration-200 transform hover:scale-105 active:scale-95 flex items-center justify-center gap-2 shrink-0"
            >
                <span>⚡</span>
                <span>{{ daysRemaining > 0 ? 'تمدید اشتراک VIP' : 'خرید و ارتقای اشتراک' }}</span>
            </button>
        </div>

        <!-- مودال ارتقای اشتراک -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/80 backdrop-blur-md overflow-y-auto"
            dir="rtl"
        >
            <div class="bg-gray-900 border border-gray-700/80 rounded-3xl max-w-3xl w-full p-5 sm:p-7 shadow-2xl relative my-8">
                <!-- دکمه بستن -->
                <button
                    @click="showModal = false"
                    class="absolute top-4 left-4 w-9 h-9 rounded-full bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white flex items-center justify-center transition"
                >
                    ✕
                </button>

                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-600 text-3xl shadow-xl shadow-amber-500/20 mb-3 text-gray-950 font-black">
                        👑
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-white">انتخاب پلن اشتراک هوشمند CoCAI</h2>
                    <p class="text-xs sm:text-sm text-gray-400 mt-1">با خرید اشتراک ماهانه یا سالانه، قفل تمامی امکانات و بازی‌های سوپرسل برای شما باز می‌شود</p>
                </div>

                <!-- لیست پلن‌ها -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div
                        v-for="plan in plans"
                        :key="plan.id"
                        @click="selectedPlanId = plan.id"
                        class="p-4 rounded-2xl border-2 cursor-pointer transition-all duration-200 relative flex flex-col justify-between"
                        :class="selectedPlanId === plan.id
                            ? 'bg-amber-500/10 border-amber-500 shadow-lg shadow-amber-500/20'
                            : 'bg-gray-800/80 border-gray-700 hover:border-gray-600'"
                    >
                        <div v-if="plan.is_popular" class="absolute -top-3 right-4 px-2.5 py-0.5 rounded-full bg-gradient-to-r from-red-500 to-orange-500 text-white text-[10px] font-black shadow">
                            🔥 محبوب‌ترین
                        </div>

                        <div>
                            <h3 class="text-sm font-bold text-white mb-1">{{ plan.name }}</h3>
                            <p class="text-[11px] text-gray-400 mb-3 min-h-[30px]">{{ plan.description }}</p>

                            <div class="mb-3">
                                <div v-if="plan.original_price" class="text-xs text-gray-500 line-through font-mono">
                                    {{ formatNumber(plan.original_price) }} تومان
                                </div>
                                <div class="text-lg font-black text-amber-300 font-mono">
                                    {{ formatNumber(plan.price) }} <span class="text-xs font-sans text-gray-300">تومان</span>
                                </div>
                                <span class="text-[10px] text-gray-400">برای {{ plan.duration_days }} روز دسترسی</span>
                            </div>

                            <ul class="space-y-1.5 text-[11px] text-gray-300 mb-4">
                                <li v-for="(feat, idx) in plan.features" :key="idx" class="flex items-center gap-1.5">
                                    <span class="text-emerald-400 font-bold">✓</span>
                                    <span>{{ feat }}</span>
                                </li>
                            </ul>
                        </div>

                        <div
                            class="w-full py-2 rounded-xl text-center text-xs font-bold transition"
                            :class="selectedPlanId === plan.id
                                ? 'bg-amber-500 text-gray-950 font-black'
                                : 'bg-gray-700 text-gray-300'"
                        >
                            {{ selectedPlanId === plan.id ? '✓ انتخاب شده' : 'انتخاب این پلن' }}
                        </div>
                    </div>
                </div>

                <!-- انتخاب درگاه پرداخت -->
                <div class="bg-gray-800/70 border border-gray-700/80 rounded-2xl p-4 mb-6">
                    <label class="block text-xs font-bold text-gray-300 mb-2.5">درگاه پرداخت امن را انتخاب کنید:</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <button
                            type="button"
                            v-for="gw in gateways"
                            :key="gw.id"
                            @click="selectedGateway = gw.id"
                            class="p-3 rounded-xl border flex items-center justify-between text-right transition"
                            :class="selectedGateway === gw.id
                                ? 'bg-amber-500/15 border-amber-500 text-amber-300'
                                : 'bg-gray-900/80 border-gray-700 text-gray-400 hover:border-gray-600'"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-lg">{{ gw.icon }}</span>
                                <span class="text-xs font-bold">{{ gw.name }}</span>
                            </div>
                            <span v-if="selectedGateway === gw.id" class="text-xs text-amber-400 font-black">●</span>
                        </button>
                    </div>
                </div>

                <!-- دکمه هدایت به درگاه -->
                <form action="/subscription/checkout" method="POST">
                    <input type="hidden" name="_token" :value="csrfToken" />
                    <input type="hidden" name="plan_id" :value="selectedPlanId" />
                    <input type="hidden" name="gateway" :value="selectedGateway" />

                    <button
                        type="submit"
                        class="w-full py-3.5 px-4 rounded-2xl bg-gradient-to-r from-amber-500 via-orange-500 to-red-600 hover:from-amber-400 hover:to-red-500 text-gray-950 font-black text-sm shadow-xl shadow-amber-500/25 transition duration-200 transform hover:-translate-y-0.5 flex items-center justify-center gap-2"
                    >
                        <span>💳</span>
                        <span>پرداخت آنلاین و فعال‌سازی آنی اشتراک</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "SubscriptionUpgradeModal",
    data() {
        return {
            showModal: false,
            plans: [],
            gateways: [
                { id: 'zibal', name: 'درگاه زیبال (Zibal)', icon: '💳' },
                { id: 'payping', name: 'درگاه پی‌پینگ (PayPing)', icon: '⚡' },
                { id: 'zarinpal', name: 'درگاه زرین‌پال (ZarinPal)', icon: '🛡️' },
            ],
            selectedPlanId: 2,
            selectedGateway: 'zibal',
            daysRemaining: 0,
            hasSubscription: false,
            csrfToken: '',
        };
    },
    mounted() {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        if (tokenMeta) {
            this.csrfToken = tokenMeta.getAttribute('content');
        }
        this.fetchPlans();
    },
    methods: {
        async fetchPlans() {
            try {
                const res = await fetch('/api/subscription/plans');
                const data = await res.json();
                if (data.ok) {
                    this.plans = data.plans || [];
                    this.daysRemaining = data.days_remaining || 0;
                    this.hasSubscription = data.has_subscription || false;
                    if (this.plans.length > 0 && !this.selectedPlanId) {
                        const popular = this.plans.find(p => p.is_popular);
                        this.selectedPlanId = popular ? popular.id : this.plans[0].id;
                    }
                }
            } catch (e) {
                console.error("Failed to fetch subscription plans", e);
            }
        },
        formatNumber(num) {
            if (!num) return '0';
            return Number(num).toLocaleString('fa-IR');
        }
    }
};
</script>
