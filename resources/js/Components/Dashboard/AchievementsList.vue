<template>
    <div class="bg-gray-800 rounded-lg p-4 shadow-lg">
        <h2 class="text-xl font-bold text-yellow-300 mb-4">🏆 دستاوردها (Achievements)</h2>

        <!-- در صورتی که آرایه خالی باشد -->
        <div v-if="sortedAchievements.length === 0" class="text-gray-400">
            دستاوردی یافت نشد.
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="(ach, idx) in sortedAchievements"
                :key="idx"
                class="bg-gray-700 p-3 rounded-lg"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-white font-semibold">{{ ach.name }}</span>
                    <span class="text-sm text-gray-300">⭐ {{ ach.stars }}/3</span>
                </div>

                <!-- نوار پیشرفت -->
                <div class="relative w-full h-4 bg-gray-600 rounded overflow-hidden">
                    <div
                        class="bg-green-500 h-4"
                        :style="{ width: progressPercent(ach) + '%' }"
                    ></div>
                </div>

                <div class="mt-1 text-sm text-gray-400">
                    {{ ach.value }} / {{ ach.target }}
                    <span v-if="isCompleted(ach)" class="text-green-400 font-bold ml-2">
                        ✅ تکمیل شد!
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "AchievementsList",
    props: {
        achievements: {
            type: Array,
            default: () => []
        }
    },
    computed: {
        sortedAchievements() {
            return [...this.achievements].sort((a, b) => {
                const aCompleted = this.isCompleted(a) ? 1 : 0;
                const bCompleted = this.isCompleted(b) ? 1 : 0;
                return aCompleted - bCompleted; // کامل نشده‌ها بالا
            });
        }
    },
    methods: {
        progressPercent(item) {
            if (!item.target || item.target === 0) return 0;
            return Math.min(Math.round((item.value / item.target) * 100), 100);
        },
        isCompleted(item) {
            return item.value >= item.target;
        }
    }
};
</script>

<style scoped>
/* استایل‌های دلخواه */
</style>
