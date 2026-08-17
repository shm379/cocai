<template>
    <div class="bg-gray-800/90 backdrop-blur-xl border border-gray-700/80 rounded-3xl p-4 sm:p-6 shadow-2xl" dir="rtl">
        <!-- هدر کنترل پنل ربات اتک اندروید -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5 pb-4 border-b border-gray-700/70">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-green-500 via-emerald-600 to-teal-500 flex items-center justify-center text-2xl shadow-lg shadow-green-500/20 text-gray-950 font-black shrink-0">
                    🤖
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base sm:text-lg font-black text-white">مرکز کنترل اتک خودکار روی گوشی و سرور اندروید (CoCAI Android Bot)</h2>
                        <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-green-500/20 text-green-300 border border-green-500/40 font-bold">
                            اجرای واقعی روی دستگاه
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">شبیه‌سازی و اجرای مستقیم تاچ خودکار بر روی گوشی هوشمند، ترموکس یا سرور ابری کلود اندروید</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a
                    href="/downloads/cocai-android/cocai-android-agent.py"
                    download
                    class="px-4 py-2 rounded-xl bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-500 hover:to-emerald-500 text-white font-bold text-xs shadow-md shadow-green-500/20 transition flex items-center gap-1.5"
                >
                    <span>📥</span>
                    <span>دانلود اسکریپت ربات اندروید</span>
                </a>
            </div>
        </div>

        <!-- تنظیمات رزولوشن و هدف حمله -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
            <div>
                <label class="block text-xs font-bold text-gray-300 mb-1.5">رزولوشن صفحه نمایش گوشی شما:</label>
                <select
                    v-model="deviceConfig.resolution"
                    @change="onResolutionChange"
                    class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:outline-none focus:border-green-500"
                >
                    <option value="2400x1080">۲۴۰۰ × ۱۰۸۰ (اکثر گوشی‌های مدرن شیائومی و سامسونگ)</option>
                    <option value="1920x1080">۱۹۲۰ × ۱۰۸۰ (Full HD استاندارد / شبیه‌ساز)</option>
                    <option value="2340x1080">۲۳۴۰ × ۱۰۸۰ (FHD+ عریض)</option>
                    <option value="1600x720">۱۶۰۰ × ۷۲۰ (HD+)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-300 mb-1.5">روش اجرای اتک روی گوشی:</label>
                <select
                    v-model="executionMode"
                    class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:outline-none focus:border-green-500"
                >
                    <option value="termux">📱 اجرای مستقیم در ترموکس (Termux / Shizuku)</option>
                    <option value="adb">💻 اتصال کابل USB / WiFi با ADB به کامپیوتر</option>
                    <option value="cloud">☁️ سرور ابری اندروید (Cloud Android VPS)</option>
                </select>
            </div>

            <div class="flex items-end">
                <button
                    type="button"
                    @click="generateMacro"
                    :disabled="loading"
                    class="w-full py-2 px-4 rounded-xl bg-gradient-to-r from-green-500 to-teal-500 hover:from-green-400 hover:to-teal-400 text-gray-950 font-black text-xs shadow transition flex items-center justify-center gap-1.5 disabled:opacity-50"
                >
                    <span>⚡</span>
                    <span>{{ loading ? 'در حال تولید کدهای تاچ...' : 'تولید ماکروی تاچ خودکار' }}</span>
                </button>
            </div>
        </div>

        <!-- پیش‌نمایش مختصات تاچ و کدهای ADB -->
        <div v-if="macroData" class="space-y-4">
            <!-- بنر وضعیت -->
            <div class="p-3.5 rounded-2xl bg-gray-900/90 border border-green-500/30 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🎮</span>
                    <div>
                        <div class="text-xs font-bold text-white">ماکروی تاچ هوشمند ۳ ستاره تولید شد!</div>
                        <div class="text-[11px] text-gray-400">تعداد دستورات لمسی: {{ macroData.events_count }} کلیک زمان‌بندی شده</div>
                    </div>
                </div>
                <span class="text-xs font-mono text-green-300 font-bold bg-green-500/15 px-2.5 py-1 rounded-lg border border-green-500/30">
                    {{ macroData.resolution }}
                </span>
            </div>

            <!-- جدول مراحل لمسی خودکار -->
            <div class="max-h-60 overflow-y-auto rounded-2xl border border-gray-700/80 bg-gray-950/60 p-2 space-y-1.5">
                <div
                    v-for="(ev, eidx) in macroData.events"
                    :key="eidx"
                    class="flex items-center justify-between p-2 rounded-xl bg-gray-900/80 border border-gray-800 text-xs"
                >
                    <div class="flex items-center gap-2">
                        <span class="w-5 h-5 rounded-full bg-green-500/20 text-green-300 flex items-center justify-center font-mono font-bold text-[10px]">
                            {{ eidx + 1 }}
                        </span>
                        <span class="text-gray-200">{{ ev.desc }}</span>
                    </div>

                    <div class="flex items-center gap-3 font-mono text-[11px]">
                        <span class="text-amber-400 font-bold">X: {{ ev.x }} , Y: {{ ev.y }}</span>
                        <span class="text-gray-400">+{{ ev.delay_ms }}ms</span>
                    </div>
                </div>
            </div>

            <!-- اسکریپت قابل کپی برای ADB یا Termux -->
            <div class="bg-gray-900/90 rounded-2xl border border-gray-700/80 p-3.5">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-gray-300">اسکریپت مستقیم دستورات ADB شل (قابل کپی و اجرا):</span>
                    <button
                        type="button"
                        @click="copyAdbScript"
                        class="text-[11px] text-amber-300 hover:text-amber-200 font-bold flex items-center gap-1 bg-gray-800 px-2.5 py-1 rounded-lg border border-gray-700 transition"
                    >
                        <span>📋</span>
                        <span>{{ copied ? 'کپی شد!' : 'کپی دستورات' }}</span>
                    </button>
                </div>
                <pre class="text-[11px] font-mono text-emerald-400 bg-gray-950 p-3 rounded-xl overflow-x-auto max-h-36 no-scrollbar">{{ macroData.adb_script }}</pre>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "AndroidCompanionControlCenter",
    data() {
        return {
            executionMode: 'termux',
            deviceConfig: {
                resolution: '2400x1080',
                screen_width: 2400,
                screen_height: 1080,
                target_th: 16,
                army_type: 'root_rider_smash',
            },
            macroData: null,
            loading: false,
            copied: false,
        };
    },
    mounted() {
        this.generateMacro();
    },
    methods: {
        onResolutionChange() {
            const parts = this.deviceConfig.resolution.split('x');
            this.deviceConfig.screen_width = parseInt(parts[0], 10);
            this.deviceConfig.screen_height = parseInt(parts[1], 10);
            this.generateMacro();
        },
        async generateMacro() {
            this.loading = true;
            try {
                const res = await fetch('/api/android/generate-macro', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify(this.deviceConfig)
                });
                const data = await res.json();
                if (data.ok) {
                    this.macroData = data;
                }
            } catch (e) {
                console.error("Failed to generate macro", e);
            } finally {
                this.loading = false;
            }
        },
        copyAdbScript() {
            if (this.macroData?.adb_script) {
                navigator.clipboard.writeText(this.macroData.adb_script);
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            }
        }
    }
};
</script>
