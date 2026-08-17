<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg mb-6 border border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="text-xl">⚒️</span>
                <h3 class="text-lg font-bold text-white">ماشین‌حساب سنگ‌های معدنی بلک‌اسمیت (Ore Calculator)</h3>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/40 font-bold">
                Blacksmith Economy
            </span>
        </div>

        <!-- کنترل‌های انتخاب -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
            <!-- نوع قطعه -->
            <div class="bg-gray-700/60 p-3 rounded-lg">
                <span class="text-xs text-gray-400 block mb-2">نوع تجهیزات (Rarity)</span>
                <div class="flex gap-2">
                    <button
                        @click="rarity = 'epic'"
                        class="flex-1 py-1.5 rounded-lg text-xs font-bold transition"
                        :class="rarity === 'epic' ? 'bg-purple-600 text-white shadow' : 'bg-gray-800 text-gray-400 hover:text-white'"
                    >
                        ★ اپیک (Epic)
                    </button>
                    <button
                        @click="rarity = 'common'"
                        class="flex-1 py-1.5 rounded-lg text-xs font-bold transition"
                        :class="rarity === 'common' ? 'bg-blue-600 text-white shadow' : 'bg-gray-800 text-gray-400 hover:text-white'"
                    >
                        کامان (Common)
                    </button>
                </div>
            </div>

            <!-- لول فعلی -->
            <div class="bg-gray-700/60 p-3 rounded-lg">
                <span class="text-xs text-gray-400 block mb-2">سطح فعلی: <strong class="text-white">{{ currentLevel }}</strong></span>
                <input
                    type="range"
                    min="1"
                    :max="rarity === 'epic' ? 26 : 17"
                    v-model.number="currentLevel"
                    class="w-full accent-amber-500 cursor-pointer"
                />
            </div>

            <!-- لول هدف -->
            <div class="bg-gray-700/60 p-3 rounded-lg">
                <span class="text-xs text-gray-400 block mb-2">سطح هدف: <strong class="text-amber-400">{{ targetLevel }}</strong></span>
                <input
                    type="range"
                    :min="currentLevel + 1"
                    :max="rarity === 'epic' ? 27 : 18"
                    v-model.number="targetLevel"
                    class="w-full accent-amber-500 cursor-pointer"
                />
            </div>
        </div>

        <!-- محاسبه سنگ‌های لازم -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-center mb-4">
            <div class="bg-gray-900/80 p-3 rounded-xl border border-blue-500/30">
                <span class="text-[11px] text-blue-300 block mb-1">🔷 سنگ براق (Shiny)</span>
                <span class="text-base font-extrabold text-blue-400 font-mono">{{ totalShiny.toLocaleString('fa-IR') }}</span>
            </div>
            <div class="bg-gray-900/80 p-3 rounded-xl border border-purple-500/30">
                <span class="text-[11px] text-purple-300 block mb-1">🔮 سنگ درخشان (Glowy)</span>
                <span class="text-base font-extrabold text-purple-400 font-mono">{{ totalGlowy.toLocaleString('fa-IR') }}</span>
            </div>
            <div class="bg-gray-900/80 p-3 rounded-xl border border-amber-500/30">
                <span class="text-[11px] text-amber-300 block mb-1">⭐ سنگ ستاره‌ای (Starry)</span>
                <span class="text-base font-extrabold text-amber-400 font-mono">{{ totalStarry.toLocaleString('fa-IR') }}</span>
            </div>
            <div class="bg-gray-900/80 p-3 rounded-xl border border-emerald-500/30">
                <span class="text-[11px] text-emerald-300 block mb-1">⏱️ تخمین زمان تکمیل</span>
                <span class="text-base font-extrabold text-emerald-400 font-mono">~{{ estimatedDays }} روز</span>
            </div>
        </div>

        <p class="text-[11px] text-gray-400 text-center">
            * محاسبه بر اساس دریافت ستاره روزانه (Star Bonus) لیگ شما به اضافه ۲ الی ۳ وار هفتگی با برد در کلن وار.
        </p>
    </div>
</template>

<script>
export default {
    name: 'BlacksmithOrePlanner',
    data() {
        return {
            rarity: 'epic',
            currentLevel: 15,
            targetLevel: 27
        }
    },
    watch: {
        rarity(newVal) {
            if (newVal === 'common') {
                this.currentLevel = Math.min(this.currentLevel, 17)
                this.targetLevel = 18
            } else {
                this.targetLevel = 27
            }
        },
        currentLevel(newVal) {
            if (this.targetLevel <= newVal) {
                this.targetLevel = newVal + 1
            }
        }
    },
    computed: {
        totalShiny() {
            const diff = Math.max(1, this.targetLevel - this.currentLevel)
            const multiplier = this.rarity === 'epic' ? 2400 : 1600
            return diff * multiplier
        },
        totalGlowy() {
            const diff = Math.max(1, this.targetLevel - this.currentLevel)
            const multiplier = this.rarity === 'epic' ? 160 : 100
            return diff * multiplier
        },
        totalStarry() {
            if (this.rarity !== 'epic') return 0
            const diff = Math.max(1, this.targetLevel - this.currentLevel)
            return diff * 25
        },
        estimatedDays() {
            // میانگین دریافت گلویی در روز ~۴۰
            return Math.max(1, Math.round(this.totalGlowy / 42))
        }
    }
}
</script>
