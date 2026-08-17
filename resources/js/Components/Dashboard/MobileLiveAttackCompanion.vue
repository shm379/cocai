<template>
    <div class="bg-gray-800/90 backdrop-blur-xl border border-gray-700/80 rounded-3xl p-4 sm:p-6 shadow-2xl relative overflow-hidden" dir="rtl">
        <!-- هدر دستیار زنده اتک موبایل -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5 pb-4 border-b border-gray-700/70">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-500 flex items-center justify-center text-2xl shadow-lg shadow-emerald-500/20 text-gray-950 font-black shrink-0">
                    📱
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base sm:text-lg font-black text-white">دستیار زنده اتک روی موبایل (In-Game Live HUD & AI Auto-Tactic)</h2>
                        <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 font-bold">
                            هوشمند در حین نبرد
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">اسکن ۳۰ ثانیه‌ای بیس حریف، زاویه ورود بهینه، راهنمای صوتی ثانیه‌به‌ثانیه و شمارش معکوس ابیلیتی‌ها</p>
                </div>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button
                    type="button"
                    @click="isHudActive = !isHudActive"
                    class="w-full sm:w-auto px-4 py-2 rounded-xl text-xs font-black transition flex items-center justify-center gap-2"
                    :class="isHudActive
                        ? 'bg-red-600 text-white shadow-lg shadow-red-500/25'
                        : 'bg-gradient-to-r from-emerald-500 to-teal-500 text-gray-950 shadow-lg shadow-emerald-500/25'"
                >
                    <span>{{ isHudActive ? '⏹️ توقف HUD نبرد' : '▶️ فعال‌سازی HUD زنده اتک' }}</span>
                </button>
            </div>
        </div>

        <!-- پیش‌نمایش زاویه ورود و تنظیمات اسکن حریف -->
        <div v-if="!isHudActive" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-300 mb-1.5">تاون‌هال حریف در وار:</label>
                    <select
                        v-model="scoutData.target_th"
                        class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:outline-none focus:border-emerald-500"
                    >
                        <option :value="18">تاون‌هال ۱۸ (TH 18)</option>
                        <option :value="17">تاون‌هال ۱۷ (TH 17)</option>
                        <option :value="16">تاون‌هال ۱۶ (TH 16)</option>
                        <option :value="15">تاون‌هال ۱۵ (TH 15)</option>
                        <option :value="14">تاون‌هال ۱۴ (TH 14)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-300 mb-1.5">جهت باد سویپرهای حریف:</label>
                    <select
                        v-model="scoutData.sweeper_facing"
                        class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:outline-none focus:border-emerald-500"
                    >
                        <option value="north_east">شمال شرقی (ساعت ۱ تا ۳)</option>
                        <option value="south_west">جنوب غربی (ساعت ۶ تا ۹)</option>
                        <option value="center_split">پوشش دوطرفه هسته</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-300 mb-1.5">موقعیت تاون‌هال حریف:</label>
                    <select
                        v-model="scoutData.th_position"
                        class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:outline-none focus:border-emerald-500"
                    >
                        <option value="center">مرکز بیس (Box / Anti-3 Base)</option>
                        <option value="offset_north">گوشه بالا (Offset North)</option>
                        <option value="offset_south">گوشه پایین (Offset South)</option>
                    </select>
                </div>
            </div>

            <!-- خروجی اسکن قبل از نبرد -->
            <div v-if="hudPlan" class="p-4 rounded-2xl bg-gray-900/90 border border-emerald-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs text-gray-400">زاویه ورود محاسبه‌شده با هوش مصنوعی:</span>
                        <span class="text-xs font-black text-amber-300 bg-amber-500/15 px-2.5 py-0.5 rounded-lg border border-amber-500/30">
                            {{ hudPlan.optimal_entry.angle_fa }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-300">{{ hudPlan.optimal_entry.threat_assessment }}</p>
                </div>

                <div class="text-left">
                    <div class="text-[11px] text-gray-400">احتمال ۳ ستاره زنده:</div>
                    <div class="text-xl font-black text-emerald-400 font-mono">{{ hudPlan.win_rate_forecast }}٪</div>
                </div>
            </div>
        </div>

        <!-- ================= HUD زنده نبرد (Live In-Game HUD Overlay Mode) ================= -->
        <div v-else class="space-y-4">
            <!-- تایمر بزرگ ۳ دقیقه‌ای اتک -->
            <div class="p-5 rounded-2xl bg-gradient-to-r from-gray-950 via-gray-900 to-gray-950 border-2 border-emerald-400/80 shadow-2xl flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-500/20 border border-emerald-400/40 flex items-center justify-center text-3xl font-mono font-black text-emerald-400 animate-pulse">
                        {{ formattedTime }}
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 font-bold">تایمر نبرد زنده کلش:</div>
                        <div class="text-sm font-black text-white">{{ currentStep?.action_title || 'در حال آماده‌باش' }}</div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="toggleTimer"
                        class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-gray-950 font-black text-xs shadow transition"
                    >
                        {{ timerRunning ? '⏸️ توقف' : '▶️ شروع زمان نبرد' }}
                    </button>
                    <button
                        type="button"
                        @click="resetTimer"
                        class="px-3 py-2 rounded-xl bg-gray-800 text-gray-300 hover:text-white text-xs font-bold border border-gray-700 transition"
                    >
                        🔄 ریست
                    </button>
                </div>
            </div>

            <!-- نمایش زنده اسکرین‌شات از گوشی -->
            <div v-if="liveScreenshot" class="relative rounded-2xl overflow-hidden border-2 border-emerald-500/50 shadow-xl shadow-emerald-500/10">
                <div class="absolute top-2 left-2 bg-red-600 text-white text-[10px] px-2 py-0.5 rounded-full font-bold animate-pulse z-10 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 bg-white rounded-full"></span> LIVE
                </div>
                <img :src="liveScreenshot" alt="Live Game Screen" class="w-full h-auto object-cover max-h-64 opacity-90" />
            </div>

            <!-- راهنمای فوری اقدام فعلی (Active Action Card) -->
            <div
                v-if="currentStep"
                class="p-4 rounded-2xl border-2 transition-all duration-300"
                :class="currentStep.priority === 'urgent'
                    ? 'bg-red-500/20 border-red-500 shadow-xl shadow-red-500/20 animate-bounce'
                    : 'bg-emerald-500/15 border-emerald-500/50'"
            >
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">{{ currentStep.icon }}</span>
                        <h4 class="text-sm font-black text-white">{{ currentStep.action_title }}</h4>
                    </div>
                    <span class="text-xs font-mono font-bold text-amber-300 bg-gray-950/80 px-2.5 py-0.5 rounded-lg border border-gray-800">
                        ⏱️ {{ currentStep.time }}
                    </span>
                </div>
                <p class="text-xs text-gray-200 leading-relaxed mb-2">{{ currentStep.action_detail }}</p>

                <!-- پیام صوتی راهنما -->
                <div class="p-2 rounded-xl bg-gray-950/80 text-[11px] text-teal-300 border border-teal-500/30 flex items-center gap-2">
                    <span>🗣️</span>
                    <span class="font-bold">{{ currentStep.voice_cue }}</span>
                </div>
            </div>

            <!-- توالی گام‌های بعدی در تایم‌لاین -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
                <div
                    v-for="(step, sidx) in hudPlan?.timeline_steps"
                    :key="sidx"
                    class="p-3 rounded-xl border text-xs transition"
                    :class="activeStepIndex === sidx
                        ? 'bg-emerald-500/20 border-emerald-500 text-white font-bold shadow'
                        : 'bg-gray-900/60 border-gray-800 text-gray-400'"
                >
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-black text-amber-300">{{ step.time }}</span>
                        <span>{{ step.icon }}</span>
                    </div>
                    <div class="truncate text-[11px]">{{ step.action_title }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "MobileLiveAttackCompanion",
    data() {
        return {
            isHudActive: false,
            timerRunning: false,
            secondsLeft: 180,
            timerInterval: null,
            screenshotInterval: null,
            liveScreenshot: null,
            scoutData: {
                target_th: 16,
                sweeper_facing: 'north_east',
                th_position: 'center',
                army_type: 'root_rider_smash',
            },
            hudPlan: null,
            activeStepIndex: 0,
        };
    },
    computed: {
        formattedTime() {
            const m = Math.floor(this.secondsLeft / 60);
            const s = this.secondsLeft % 60;
            return `${m}:${s < 10 ? '0' : ''}${s}`;
        },
        currentStep() {
            if (!this.hudPlan?.timeline_steps?.length) return null;
            return this.hudPlan.timeline_steps[this.activeStepIndex] || this.hudPlan.timeline_steps[0];
        }
    },
    watch: {
        scoutData: {
            deep: true,
            handler() {
                this.fetchHudPlan();
            }
        },
        isHudActive(newVal) {
            if (newVal) {
                this.startScreenshotPolling();
            } else {
                this.stopScreenshotPolling();
            }
        }
    },
    mounted() {
        this.fetchHudPlan();
    },
    beforeUnmount() {
        if (this.timerInterval) clearInterval(this.timerInterval);
        this.stopScreenshotPolling();
    },
    methods: {
        async fetchHudPlan() {
            try {
                const res = await fetch('/api/ai/live-attack-plan', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify(this.scoutData)
                });
                const data = await res.json();
                if (data.ok) {
                    this.hudPlan = data;
                }
            } catch (e) {
                console.error("Failed to fetch live HUD plan", e);
            }
        },
        toggleTimer() {
            if (this.timerRunning) {
                clearInterval(this.timerInterval);
                this.timerRunning = false;
            } else {
                this.timerRunning = true;
                this.timerInterval = setInterval(() => {
                    if (this.secondsLeft > 0) {
                        this.secondsLeft--;
                        this.updateActiveStep();
                    } else {
                        clearInterval(this.timerInterval);
                        this.timerRunning = false;
                    }
                }, 1000);
            }
        },
        resetTimer() {
            if (this.timerInterval) clearInterval(this.timerInterval);
            this.timerRunning = false;
            this.secondsLeft = 180;
            this.activeStepIndex = 0;
        },
        updateActiveStep() {
            if (!this.hudPlan?.timeline_steps) return;
            const steps = this.hudPlan.timeline_steps;
            for (let i = 0; i < steps.length; i++) {
                if (this.secondsLeft >= steps[i].seconds_remaining) {
                    this.activeStepIndex = i;
                    break;
                }
            }
        },
        startScreenshotPolling() {
            this.fetchScreenshot();
            this.screenshotInterval = setInterval(() => {
                this.fetchScreenshot();
            }, 3000); // 3 seconds
        },
        stopScreenshotPolling() {
            if (this.screenshotInterval) {
                clearInterval(this.screenshotInterval);
                this.screenshotInterval = null;
            }
        },
        async fetchScreenshot() {
            try {
                const res = await fetch('/api/android/latest-screenshot');
                const data = await res.json();
                if (data.ok && data.image) {
                    this.liveScreenshot = data.image;
                }
            } catch (e) {
                console.error("Failed to fetch live screenshot", e);
            }
        }
    }
};
</script>
