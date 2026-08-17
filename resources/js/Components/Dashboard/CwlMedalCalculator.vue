<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg border border-gray-700 space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-gray-700/60">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-500 to-amber-600 flex items-center justify-center text-2xl shadow-lg">
                    🏅
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">ماشین‌حساب مدال‌های لیگ وار (CWL Medals & Hammers)</h3>
                    <p class="text-xs text-gray-400">محاسبهٔ دقیق مدال‌های کسب‌شده در لیگ وار و بهترین چکش‌های ارتقا (Hammers)</p>
                </div>
            </div>
        </div>

        <!-- Interactive Calculator Inputs -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs text-gray-400 mb-1">لیگ وار کلن (League Division)</label>
                <select
                    v-model="selectedLeague"
                    class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-amber-500"
                >
                    <option v-for="lg in leagues" :key="lg.name" :value="lg">
                        {{ lg.name }} (تا {{ lg.maxMedals }} مدال)
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-400 mb-1">رتبه پایانی کلن در گروه (۱ تا ۸)</label>
                <select
                    v-model.number="clanRank"
                    class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-amber-500"
                >
                    <option v-for="r in [1, 2, 3, 4, 5, 6, 7, 8]" :key="r" :value="r">
                        رتبه {{ r }} گروه
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-400 mb-1">تعداد ستاره‌های شما در طول ۷ وار</label>
                <div class="flex items-center gap-2">
                    <input
                        v-model.number="playerStars"
                        type="range"
                        min="0"
                        max="21"
                        class="flex-1 accent-amber-500 cursor-pointer"
                    />
                    <span class="font-mono font-bold text-amber-400 text-xs w-8 text-center">⭐ {{ playerStars }}</span>
                </div>
                <span class="text-[10px] text-gray-400 block mt-0.5">
                    (با ۸ ستاره، ۱۰۰٪ پاداش مدال به شما تعلق می‌گیرد)
                </span>
            </div>
        </div>

        <!-- Calculated Results Scoreboard -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-center my-2">
            <div class="bg-gray-900/80 p-3 rounded-xl border border-gray-700">
                <span class="text-[11px] text-gray-400 block mb-1">مدال‌های سهمیه شما</span>
                <span class="text-base font-black text-yellow-400 font-mono">🏅 {{ formatNumber(calculatedMedals) }}</span>
            </div>
            <div class="bg-gray-900/80 p-3 rounded-xl border border-gray-700">
                <span class="text-[11px] text-gray-400 block mb-1">درصد دریافت پاداش</span>
                <span class="text-base font-black text-emerald-400 font-mono">{{ starPercentage }}%</span>
            </div>
            <div class="bg-gray-900/80 p-3 rounded-xl border border-gray-700">
                <span class="text-[11px] text-gray-400 block mb-1">پک‌های بونوس لیدر</span>
                <span class="text-base font-black text-purple-400 font-mono">{{ bonusPacksCount }} پک</span>
            </div>
            <div class="bg-gray-900/80 p-3 rounded-xl border border-gray-700">
                <span class="text-[11px] text-gray-400 block mb-1">مقدار هر بونوس</span>
                <span class="text-base font-black text-cyan-400 font-mono">+{{ selectedLeague.bonusAmount }} 🏅</span>
            </div>
        </div>

        <!-- League Shop Hammer Recommendations -->
        <div class="bg-gray-900/60 p-3.5 rounded-xl border border-gray-700/80 space-y-2">
            <h4 class="text-xs font-bold text-amber-300">بهترین انتخاب‌ها در شاپ لیگ با مدال‌های کسب‌شده:</h4>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                <div class="bg-gray-800/80 p-2.5 rounded-lg border border-gray-700">
                    <div class="flex items-center justify-between font-bold text-white mb-1">
                        <span>🔨 Hammer of Building</span>
                        <span class="text-yellow-400 font-mono">120 🏅</span>
                    </div>
                    <p class="text-[11px] text-gray-300">بزرگ‌ترین بازدهی زمانی؛ صرفه‌جویی ۲۰ تا ۲۲ روز زمان ساخت ایگل، منولیت و تاون‌هال ۱۷/۱۸ بدون مصرف منابع.</p>
                </div>
                <div class="bg-gray-800/80 p-2.5 rounded-lg border border-gray-700">
                    <div class="flex items-center justify-between font-bold text-white mb-1">
                        <span>⚔️ Hammer of Fighting</span>
                        <span class="text-yellow-400 font-mono">120 🏅</span>
                    </div>
                    <p class="text-[11px] text-gray-300">ارتقای آنی نیروهای سنگین دارک اکسیر (روت رایدر، سوپر دراگون) بدون اشغال آزمایشگاه.</p>
                </div>
                <div class="bg-gray-800/80 p-2.5 rounded-lg border border-gray-700">
                    <div class="flex items-center justify-between font-bold text-white mb-1">
                        <span>👑 Hammer of Heroes</span>
                        <span class="text-yellow-400 font-mono">165 🏅</span>
                    </div>
                    <p class="text-[11px] text-gray-300">لول‌آپ فوری هیرو بدون خوابیدن در وارها (توصیه فقط برای لول‌های آخر کینگ/کوئین بالای ۹۰).</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'CwlMedalCalculator',
    data() {
        const leagues = [
            { name: 'Champion League I', maxMedals: 508, bonusAmount: 105, basePacks: 4 },
            { name: 'Champion League II', maxMedals: 462, bonusAmount: 95, basePacks: 3 },
            { name: 'Champion League III', maxMedals: 418, bonusAmount: 85, basePacks: 3 },
            { name: 'Master League I', maxMedals: 376, bonusAmount: 75, basePacks: 2 },
            { name: 'Master League II', maxMedals: 336, bonusAmount: 70, basePacks: 2 },
            { name: 'Master League III', maxMedals: 298, bonusAmount: 65, basePacks: 2 },
            { name: 'Crystal League I', maxMedals: 262, bonusAmount: 55, basePacks: 2 },
            { name: 'Crystal League II', maxMedals: 228, bonusAmount: 50, basePacks: 1 },
            { name: 'Crystal League III', maxMedals: 196, bonusAmount: 45, basePacks: 1 },
            { name: 'Gold League I', maxMedals: 166, bonusAmount: 40, basePacks: 1 },
        ]
        return {
            leagues,
            selectedLeague: leagues[3], // Master I
            clanRank: 2,
            playerStars: 14,
        }
    },
    computed: {
        starPercentage() {
            if (this.playerStars >= 8) return 100
            if (this.playerStars <= 0) return 20
            return 20 + (this.playerStars * 10)
        },
        calculatedMedals() {
            // کاهش بر اساس رتبه گروه: رتبه ۱ = ۱۰۰٪، هر رتبه پایین‌تر ۲٪ تا ۴٪ کاهش
            const rankFactor = 1 - ((this.clanRank - 1) * 0.04)
            const clanBase = this.selectedLeague.maxMedals * rankFactor
            return Math.round(clanBase * (this.starPercentage / 100))
        },
        bonusPacksCount() {
            // بر اساس تعداد بردهای تقریبی (معمولا ۴ برد در طول ۷ وار)
            return this.selectedLeague.basePacks + 4
        }
    },
    methods: {
        formatNumber(num) {
            return Number(num || 0).toLocaleString('fa-IR')
        }
    }
}
</script>
