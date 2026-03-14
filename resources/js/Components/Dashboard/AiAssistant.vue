<template>
    <div class="bg-gray-800 p-4 rounded-lg shadow-lg text-white">
        <h2 class="text-xl font-bold mb-4">دستیار هوش مصنوعی</h2>

        <div class="flex gap-2 mb-4">
            <button @click="getDailyPlan" :disabled="loading" class="px-3 py-1 bg-green-600 rounded hover:bg-green-500 text-sm">
                برنامه روزانه
            </button>
            <button @click="getWarStrategy" :disabled="loading" class="px-3 py-1 bg-red-600 rounded hover:bg-red-500 text-sm">
                استراتژی وار
            </button>
        </div>

        <form @submit.prevent="sendQuestion">
      <textarea
          v-model="userQuestion"
          class="w-full p-2 bg-gray-700 text-white rounded mb-2"
          rows="3"
          placeholder="سؤالتان درباره کلش اف کلنز را اینجا بنویسید..."
      ></textarea>
            <button
                class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-blue-500 rounded hover:shadow
               transition-colors"
                :disabled="loading"
            >
                ارسال
            </button>
        </form>

        <div v-if="loading" class="mt-4 text-yellow-300">
            در حال دریافت پاسخ از هوش مصنوعی ...
        </div>

        <div v-if="aiAnswer" class="mt-4 bg-gray-700 p-3 rounded">
            <p class="whitespace-pre-line">
                {{ aiAnswer }}
            </p>
        </div>
    </div>
</template>

<script>
export default {
    name: 'AiAssistant',
    props: {
        gameProfile: {
            type: Object,
            required: false
        }
    },
    data() {
        return {
            userQuestion: '',
            aiAnswer: '',
            loading: false,
        }
    },
    methods: {
        async sendQuestion() {
            if (!this.userQuestion.trim()) return;

            this.loading = true;
            this.aiAnswer = '';

            try {
                // اینجا به بک‌اند خودتان درخواست می‌فرستید
                // برای نمونه با axios:
                const response = await window.axios.post('/api/chat', {
                    question: this.userQuestion,
                    userProfile: this.gameProfile,
                });
                this.aiAnswer = response.data.answer || 'پاسخی یافت نشد!';
            } catch (error) {
                console.error(error);
                this.aiAnswer = 'خطا در دریافت پاسخ از سرور!';
            } finally {
                this.loading = false;
            }
        },
        async getDailyPlan() {
            this.loading = true;
            this.aiAnswer = '';
            try {
                const response = await window.axios.post('/tasks/daily-plan');
                this.aiAnswer = response.data.plan || 'برنامه‌ای یافت نشد.';
            } catch (error) {
                console.error(error);
                this.aiAnswer = 'خطا در دریافت برنامه روزانه.';
            } finally {
                this.loading = false;
            }
        },
        async getWarStrategy() {
            this.loading = true;
            this.aiAnswer = '';
            try {
                const response = await window.axios.post('/tasks/war-strategy');
                this.aiAnswer = response.data.strategy || 'استراتژی یافت نشد.';
            } catch (error) {
                console.error(error);
                this.aiAnswer = 'خطا در دریافت استراتژی وار.';
            } finally {
                this.loading = false;
            }
        }
    },
}
</script>

<style scoped>
/* در صورت نیاز، استایل اختصاصی‌تان را قرار دهید. */
</style>
