<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg mb-6 border border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="text-xl">⏳</span>
                <h3 class="text-lg font-bold text-white">محاسبه‌گر زمان و آیتم‌های جادویی (Magic Items)</h3>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 font-bold">
                Smart Time Saver
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4 text-center">
            <!-- تعداد کارگرها -->
            <div class="bg-gray-700/60 p-3 rounded-lg">
                <span class="text-xs text-gray-400">تعداد کارگران فعال</span>
                <div class="flex justify-center items-center gap-3 mt-2">
                    <button
                        @click="builders = Math.max(2, builders - 1)"
                        class="w-7 h-7 rounded-lg bg-gray-600 hover:bg-gray-500 text-white font-bold flex items-center justify-center text-sm"
                    >
                        -
                    </button>
                    <span class="text-lg font-extrabold text-amber-400">🔨 {{ builders }} کارگر</span>
                    <button
                        @click="builders = Math.min(6, builders + 1)"
                        class="w-7 h-7 rounded-lg bg-gray-600 hover:bg-gray-500 text-white font-bold flex items-center justify-center text-sm"
                    >
                        +
                    </button>
                </div>
            </div>

            <!-- گلد پس 20% بوست -->
            <div class="bg-gray-700/60 p-3 rounded-lg flex flex-col justify-between">
                <span class="text-xs text-gray-400">تخفیف ۲۰٪ گلد پس (Gold Pass)</span>
                <button
                    @click="hasGoldPass = !hasGoldPass"
                    class="mt-2 py-1.5 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5"
                    :class="hasGoldPass ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'bg-gray-600/40 text-gray-400 border border-gray-600'"
                >
                    <span>{{ hasGoldPass ? '⭐ ۲۰٪ بوست فعال' : 'غیرفعال' }}</span>
                </button>
            </div>

            <!-- صرفه‌جویی با معجون بیلدر -->
            <div class="bg-gray-700/60 p-3 rounded-lg flex flex-col justify-between">
                <span class="text-xs text-gray-400">ارزش هر Builder Potion</span>
                <span class="text-base font-extrabold text-emerald-400 mt-1">⚡ {{ builders * 9 }} ساعت صرفه‌جویی</span>
                <span class="text-[11px] text-gray-400 mt-0.5">در کل کارگران شما</span>
            </div>
        </div>

        <!-- توصیه‌های آیتم جادویی هوشمند -->
        <div class="space-y-2 pt-2 border-t border-gray-700/60">
            <h4 class="text-xs font-semibold text-gray-300 mb-2">بهترین ارزش استفاده از آیتم‌های جادویی برای شما:</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                <div class="bg-gray-700/40 p-2.5 rounded-lg flex items-start gap-2">
                    <span class="text-base">📖</span>
                    <div>
                        <span class="font-bold text-amber-300">کتاب قهرمانان (Book of Heroes):</span>
                        <p class="text-gray-300 mt-0.5">روی بالاترین هیرو با زمان ارتقای ۷ الی ۸ روز استفاده کنید تا در وارها هیروهایتان در دسترس بمانند.</p>
                    </div>
                </div>

                <div class="bg-gray-700/40 p-2.5 rounded-lg flex items-start gap-2">
                    <span class="text-base">🔨</span>
                    <div>
                        <span class="font-bold text-cyan-300">چکش ساختمان (Hammer of Building):</span>
                        <p class="text-gray-300 mt-0.5">روی ایگل، اینفرنو یا اسلحه تاون‌هال ۱۲ تا ۱۸ که بیش از ۱۴ روز زمان و ۲۰ میلیون گلد نیاز دارند مصرف کنید.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'UpgradeTimeCalculator',
    props: {
        townHall: {
            type: Number,
            default: 12
        }
    },
    data() {
        return {
            builders: 5,
            hasGoldPass: false
        }
    }
}
</script>
