<template>
    <div class="bg-gray-800/90 backdrop-blur-xl border border-gray-700/80 rounded-3xl p-4 sm:p-6 shadow-2xl" dir="rtl">
        <!-- هدر شبیه‌ساز حمله -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5 pb-4 border-b border-gray-700/70">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-red-600 via-orange-500 to-amber-400 flex items-center justify-center text-2xl shadow-lg shadow-red-500/20 text-gray-950 font-black shrink-0">
                    🎯
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base sm:text-lg font-black text-white">شبیه‌ساز هوشمند حمله وار (AI Attack Simulator)</h2>
                        <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-red-500/20 text-red-300 border border-red-500/40 font-bold">
                            تایم‌لاین ۳ فازی
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">طراحی و مهندسی معکوس نقشه حمله ۳ ستاره بر اساس تاون‌هال هدف و ارتش متای انتخابی</p>
                </div>
            </div>

            <button
                type="button"
                @click="simulateAttack"
                :disabled="loading"
                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-gradient-to-r from-red-600 via-orange-500 to-amber-500 hover:from-red-500 hover:to-amber-400 text-gray-950 font-black text-xs sm:text-sm shadow-lg shadow-red-500/20 transition duration-200 transform hover:-translate-y-0.5 disabled:opacity-50 flex items-center justify-center gap-2"
            >
                <span>⚡</span>
                <span>{{ loading ? 'در حال شبیه‌سازی نبرد...' : 'شروع شبیه‌سازی و تولید نقشه' }}</span>
            </button>
        </div>

        <!-- کنترل‌های انتخاب تاون‌هال و ارتش -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-xs font-bold text-gray-300 mb-2">تاون‌هال پایگاه هدف (حریف در وار):</label>
                <div class="grid grid-cols-4 gap-2">
                    <button
                        type="button"
                        v-for="th in [14, 15, 16, 17, 18]"
                        :key="th"
                        @click="targetTh = th"
                        class="py-2.5 rounded-xl border text-xs font-black transition text-center"
                        :class="targetTh === th
                            ? 'bg-amber-500/20 border-amber-500 text-amber-300 shadow'
                            : 'bg-gray-900/80 border-gray-700/80 text-gray-400 hover:border-gray-600'"
                    >
                        TH {{ th }}
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-300 mb-2">ارتش متای مورد نظر برای حمله:</label>
                <select
                    v-model="selectedArmy"
                    class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-4 py-2.5 text-white text-xs sm:text-sm focus:outline-none focus:border-amber-500 transition"
                >
                    <option value="root_rider_smash">💥 روت رایدر اسمش + اوورگروث (S+ Tier)</option>
                    <option value="sarch_hydra">⚡ سوپر آرچر بلیمپ + دراگون ریدر (S Tier)</option>
                    <option value="zap_titan">⚡ زپ الکترو تایتان اسمش (S Tier)</option>
                    <option value="queen_charge_hybrid">🏹 کویین شارژ هیبرید (S Tier)</option>
                </select>
            </div>
        </div>

        <!-- نمایش خروجی شبیه‌ساز حمله -->
        <div v-if="blueprint" class="space-y-4">
            <!-- بنر درصد احتمال ۳ ستاره -->
            <div class="p-4 rounded-2xl bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 border border-amber-500/30 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-3xl">🏆</span>
                    <div>
                        <h4 class="text-sm font-bold text-white">{{ blueprint.army_name }}</h4>
                        <p class="text-xs text-gray-400 mt-0.5">ماشین محاصره پیشنهادی: <span class="text-amber-300 font-bold">{{ blueprint.siege_engine }}</span></p>
                    </div>
                </div>

                <div class="text-left">
                    <div class="text-xs text-gray-400">احتمال ۳ ستاره:</div>
                    <div class="text-xl font-black text-emerald-400 font-mono">{{ blueprint.win_probability }}٪</div>
                </div>
            </div>

            <!-- مراحل ۳ گانه تایم‌لاین حمله -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                <div
                    v-for="phase in blueprint.phases"
                    :key="phase.phase_number"
                    class="bg-gray-900/90 border border-gray-700/80 hover:border-amber-500/40 rounded-2xl p-4 transition-all duration-200"
                >
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30">
                            {{ phase.badge }}
                        </span>
                        <span class="text-[11px] font-mono text-gray-400 font-bold">
                            ⏱️ {{ phase.time_window }}
                        </span>
                    </div>

                    <h4 class="text-xs sm:text-sm font-bold text-white mb-2">{{ phase.title }}</h4>
                    <p class="text-xs text-gray-300 leading-relaxed">{{ phase.instruction }}</p>
                </div>
            </div>

            <!-- نکات طلایی و هشدارهای حیاتی نبرد -->
            <div class="bg-gray-900/80 border border-gray-700/80 rounded-2xl p-4">
                <h4 class="text-xs font-bold text-amber-400 mb-2.5 flex items-center gap-1.5">
                    <span>👑</span>
                    <span>نکات کلیدی برای تضمین پیروزی:</span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs text-gray-300">
                    <div
                        v-for="(val, label) in blueprint.key_tactics"
                        :key="label"
                        class="p-2.5 rounded-xl bg-gray-950/60 border border-gray-800"
                    >
                        <span class="font-bold text-amber-300 block mb-1">{{ label }}</span>
                        <span class="text-[11px] text-gray-400">{{ val }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "AiTacticalSimulator",
    data() {
        return {
            targetTh: 16,
            selectedArmy: 'root_rider_smash',
            loading: false,
            blueprint: null,
        };
    },
    mounted() {
        this.simulateAttack();
    },
    methods: {
        async simulateAttack() {
            this.loading = true;
            try {
                const res = await fetch('/api/ai/simulate-attack', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        target_town_hall: this.targetTh,
                        army_type: this.selectedArmy,
                    })
                });
                const data = await res.json();
                if (data.ok) {
                    this.blueprint = data;
                }
            } catch (e) {
                console.error("Attack simulation failed", e);
            } finally {
                this.loading = false;
            }
        }
    }
};
</script>
