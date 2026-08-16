<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg mb-6 border border-gray-700">
        <h3 class="text-lg font-bold text-white mb-4">وضعیت پیشرفت</h3>

        <div v-if="!hasAnalysis" class="text-gray-400 text-sm">
            اطلاعات بازی در دسترس نیست. پروفایل خود را به‌روزرسانی کنید.
        </div>

        <div v-else class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
            <div class="bg-gray-700 rounded-lg p-3">
                <p class="text-xs text-gray-400">تاون‌هال</p>
                <p class="text-xl text-yellow-400 font-bold">{{ analysis.town_hall }}</p>
            </div>

            <div class="bg-gray-700 rounded-lg p-3">
                <p class="text-xs text-gray-400">پیشرفت لَب</p>
                <p class="text-xl text-blue-400 font-bold">{{ labPercent }}%</p>
                <div class="w-full bg-gray-600 rounded-full h-2 mt-2">
                    <div
                        class="bg-blue-500 h-2 rounded-full transition-all"
                        :style="{ width: `${labPercent}%` }"
                    ></div>
                </div>
            </div>

            <div class="bg-gray-700 rounded-lg p-3">
                <p class="text-xs text-gray-400">وضعیت راش</p>
                <p class="text-sm font-bold" :class="rushColor">{{ analysis.rush?.label_fa || '-' }}</p>
                <p class="text-xs text-gray-400 mt-1">امتیاز: {{ analysis.rush?.score ?? '-' }}</p>
            </div>

            <div class="bg-gray-700 rounded-lg p-3">
                <p class="text-xs text-gray-400">اولویت بعدی</p>
                <p class="text-sm text-white font-semibold truncate">{{ nextUpgradeName }}</p>
                <p class="text-xs text-gray-400">{{ nextUpgradeLevel }}</p>
            </div>
        </div>

        <!-- صف آپگرید -->
        <div v-if="hasQueue" class="mt-4">
            <h4 class="text-sm font-semibold text-gray-300 mb-2">۵ اولویت بعدی</h4>
            <ul class="space-y-2">
                <li
                    v-for="(item, index) in topQueue"
                    :key="index"
                    class="flex items-center justify-between bg-gray-700/50 rounded-lg p-2 text-sm"
                >
                    <span class="text-white">{{ item.name }}</span>
                    <span class="text-gray-400">{{ item.current }} → {{ item.target }}</span>
                    <span
                        class="text-xs px-2 py-1 rounded"
                        :class="item.urgent ? 'bg-red-600 text-white' : 'bg-gray-600 text-gray-200'"
                    >
                        {{ item.urgent ? 'فوری' : 'عادی' }}
                    </span>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ProgressSummary',
    props: {
        analysis: {
            type: Object,
            default: () => ({})
        }
    },
    computed: {
        hasAnalysis() {
            return this.analysis?.ok === true;
        },
        labPercent() {
            return this.analysis.lab?.overall_percent_of_game_max ?? 0;
        },
        rushColor() {
            const score = this.analysis.rush?.score ?? 0;
            if (score >= 60) return 'text-red-500';
            if (score >= 35) return 'text-orange-400';
            if (score >= 15) return 'text-yellow-400';
            return 'text-green-400';
        },
        nextUpgradeName() {
            return this.analysis.upgrade_queue?.[0]?.name || '-';
        },
        nextUpgradeLevel() {
            const item = this.analysis.upgrade_queue?.[0];
            return item ? `${item.current} → ${item.target}` : '';
        },
        hasQueue() {
            return (this.analysis.upgrade_queue?.length || 0) > 0;
        },
        topQueue() {
            return (this.analysis.upgrade_queue || []).slice(0, 5);
        }
    }
}
</script>

<style scoped>
.truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
