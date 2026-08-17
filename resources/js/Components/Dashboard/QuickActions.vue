<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg mb-6 border border-gray-700">
        <h3 class="text-lg font-bold text-white mb-4">دسترسی سریع</h3>

        <div class="flex flex-wrap gap-3">
            <button
                @click="getDailyPlan"
                :disabled="loading || !hasProfile"
                class="px-4 py-2 bg-green-600 hover:bg-green-500 disabled:opacity-50 text-white rounded-lg shadow transition flex items-center gap-2"
            >
                <span>📅</span>
                <span>برنامه روزانه</span>
            </button>

            <button
                @click="getWarStrategy"
                :disabled="loading || !hasProfile"
                class="px-4 py-2 bg-red-600 hover:bg-red-500 disabled:opacity-50 text-white rounded-lg shadow transition flex items-center gap-2"
            >
                <span>⚔️</span>
                <span>استراتژی وار</span>
            </button>

            <button
                @click="generateTask"
                :disabled="loading || !hasProfile"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white rounded-lg shadow transition flex items-center gap-2"
            >
                <span>✅</span>
                <span>تسک جدید</span>
            </button>

            <button
                @click="$emit('openCompare')"
                :disabled="!hasProfile"
                class="px-4 py-2 bg-purple-600 hover:bg-purple-500 disabled:opacity-50 text-white rounded-lg shadow transition flex items-center gap-2"
            >
                <span>📊</span>
                <span>مقایسه بازیکن</span>
            </button>

            <a
                href="/dashboard/strategy-lab"
                class="px-4 py-2 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 text-white rounded-lg shadow transition flex items-center gap-2"
            >
                <span>🧪</span>
                <span>آزمایشگاه استراتژی (Strategy Lab)</span>
            </a>

            <button
                @click="refreshProfile"
                :disabled="refreshing || !hasProfile"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white rounded-lg shadow transition flex items-center gap-2"
            >
                <span>🔄</span>
                <span>{{ refreshing ? 'در حال بروزرسانی...' : 'بروزرسانی پروفایل' }}</span>
            </button>
        </div>

        <div v-if="aiAnswer" class="mt-4 bg-gray-700 p-3 rounded-lg text-white whitespace-pre-line text-sm leading-relaxed">
            {{ aiAnswer }}
        </div>
    </div>
</template>

<script>
export default {
    name: 'QuickActions',
    props: {
        hasProfile: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            loading: false,
            refreshing: false,
            aiAnswer: ''
        }
    },
    methods: {
        async getDailyPlan() {
            await this.callAi('/tasks/daily-plan', 'plan');
        },
        async getWarStrategy() {
            await this.callAi('/tasks/war-strategy', 'strategy');
        },
        async generateTask() {
            await this.callAi('/tasks/generate', 'task');
        },
        async callAi(url, key) {
            this.loading = true;
            this.aiAnswer = '';

            try {
                const response = await window.axios.post(url);
                this.aiAnswer = response.data[key] || response.data.plan || response.data.strategy || 'پاسخی دریافت نشد.';
            } catch (error) {
                this.aiAnswer = 'خطا در دریافت پاسخ. لطفاً دوباره تلاش کنید.';
                console.error(error);
            } finally {
                this.loading = false;
            }
        },
        async refreshProfile() {
            this.refreshing = true;

            try {
                await this.$inertia.post('/profile/refresh', {}, {
                    preserveState: true,
                    onSuccess: () => {
                        this.refreshing = false;
                    },
                    onError: () => {
                        this.refreshing = false;
                    }
                });
            } catch (error) {
                this.refreshing = false;
                console.error(error);
            }
        }
    }
}
</script>
