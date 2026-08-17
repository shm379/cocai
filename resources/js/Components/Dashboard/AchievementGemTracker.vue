<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg mb-6 border border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="text-xl">💎</span>
                <h3 class="text-lg font-bold text-white">شکارچی جم و اهداف سازنده (Gem Hunter)</h3>
            </div>
            <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 text-xs font-black">
                <span>{{ totalClaimable }}</span>
                <span>💎 جم آماده دریافت</span>
            </div>
        </div>

        <p class="text-xs text-gray-300 mb-3 leading-relaxed">
            با تکمیل این دستاوردهای نیمه‌کاره، سریع‌ترین جم‌ها را برای خرید کارگرهای باقیمانده، اسکین‌ها یا معجون‌ها آزاد کنید:
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <div
                v-for="ach in topAchievements"
                :key="ach.name"
                class="bg-gray-700/50 hover:bg-gray-700/80 transition p-3 rounded-xl border border-gray-600/50 flex flex-col justify-between"
            >
                <div>
                    <div class="flex items-start justify-between gap-2 mb-1.5">
                        <span class="text-xs font-bold text-white leading-snug">{{ ach.name_fa }}</span>
                        <span class="text-xs font-black text-emerald-400 shrink-0 bg-emerald-950/60 px-2 py-0.5 rounded-md border border-emerald-500/30">
                            +{{ ach.gems_reward }} 💎
                        </span>
                    </div>
                    <p class="text-[11px] text-gray-400 line-clamp-2 mb-2">
                        {{ ach.info }}
                    </p>
                </div>

                <div>
                    <!-- Progress Bar -->
                    <div class="flex justify-between text-[10px] text-gray-300 font-mono mb-1">
                        <span>{{ formatNumber(ach.value) }} / {{ formatNumber(ach.target) }}</span>
                        <span class="font-bold text-amber-400">{{ ach.percent }}%</span>
                    </div>
                    <div class="w-full bg-gray-900 rounded-full h-1.5 overflow-hidden">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-500"
                            :style="{ width: `${ach.percent}%` }"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'AchievementGemTracker',
    props: {
        achievementsData: {
            type: Object,
            default: () => ({})
        }
    },
    computed: {
        topAchievements() {
            return this.achievementsData?.top_uncompleted || []
        },
        totalClaimable() {
            const count = this.achievementsData?.total_claimable_gems || 0
            return count.toLocaleString('fa-IR')
        }
    },
    methods: {
        formatNumber(num) {
            return Number(num || 0).toLocaleString('fa-IR')
        }
    }
}
</script>
