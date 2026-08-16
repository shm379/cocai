<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg mb-6 border border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="text-xl">💰</span>
                <h3 class="text-lg font-bold text-white">مشاور فارمینگ و سنگ‌های معدنی (Ores)</h3>
            </div>
            <span
                class="px-2.5 py-0.5 rounded-full text-xs font-bold"
                :class="statusBadgeClass"
            >
                {{ statusText }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <!-- محدوده کاپ بهینه -->
            <div class="bg-gray-700/60 p-3 rounded-lg flex flex-col justify-between">
                <span class="text-xs text-gray-400">محدوده کاپ طلایی برای تاون‌هال {{ townHall }}</span>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-base font-extrabold text-amber-400">🏆 {{ farming.optimal_min }} - {{ farming.optimal_max }}</span>
                    <span class="text-xs text-gray-400">(فعلی: {{ currentTrophies }})</span>
                </div>
                <p class="text-xs text-gray-300 mt-2 leading-relaxed">{{ farming.status_fa }}</p>
            </div>

            <!-- پاداش تخمینی Ores روزانه از Star Bonus -->
            <div class="bg-gray-700/60 p-3 rounded-lg flex flex-col justify-between">
                <span class="text-xs text-gray-400">پاداش ستاره روزانه (Star Bonus Ores)</span>
                <div class="grid grid-cols-3 gap-2 mt-2 text-center">
                    <div class="bg-gray-800/80 p-2 rounded">
                        <p class="text-[10px] text-blue-300">Shiny Ore 🔹</p>
                        <p class="text-sm font-bold text-white mt-0.5">+{{ farming.daily_ores?.shiny || 0 }}</p>
                    </div>
                    <div class="bg-gray-800/80 p-2 rounded">
                        <p class="text-[10px] text-purple-300">Glowy Ore 🟣</p>
                        <p class="text-sm font-bold text-white mt-0.5">+{{ farming.daily_ores?.glowy || 0 }}</p>
                    </div>
                    <div class="bg-gray-800/80 p-2 rounded">
                        <p class="text-[10px] text-amber-300">Starry Ore ⭐</p>
                        <p class="text-sm font-bold text-white mt-0.5">+{{ farming.daily_ores?.starry || 0 }}</p>
                    </div>
                </div>
                <span class="text-[11px] text-gray-400 mt-2">برای ارتقای تجهیزات قهرمانان در آهنگری</span>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'FarmingAdvisorCard',
    props: {
        farming: {
            type: Object,
            default: () => ({})
        },
        townHall: {
            type: Number,
            default: 1
        },
        currentTrophies: {
            type: Number,
            default: 0
        }
    },
    computed: {
        statusBadgeClass() {
            switch (this.farming?.status) {
                case 'in_sweetspot':
                    return 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40';
                case 'too_high':
                    return 'bg-blue-500/20 text-blue-300 border border-blue-500/40';
                case 'too_low':
                    return 'bg-amber-500/20 text-amber-300 border border-amber-500/40';
                default:
                    return 'bg-gray-600/30 text-gray-300';
            }
        },
        statusText() {
            switch (this.farming?.status) {
                case 'in_sweetspot':
                    return 'محدوده طلایی';
                case 'too_high':
                    return 'کاپ بالا (Push)';
                case 'too_low':
                    return 'کاپ پایین';
                default:
                    return '-';
            }
        }
    }
}
</script>
