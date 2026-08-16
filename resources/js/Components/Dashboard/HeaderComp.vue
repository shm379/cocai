<template>
    <div id="header" class="w-full max-w-md mb-6">
        <div class="p-4 bg-red-800 rounded-xl shadow-lg flex items-center justify-between">
            <h1 class="text-2xl font-bold text-white">سلام فرمانده {{ user.name }}</h1>
            <div
                v-if="user.task_streak > 0"
                class="flex items-center gap-1 bg-orange-600 px-3 py-1 rounded-full text-white text-sm font-bold"
                title="تعداد روزهای متوالی تکمیل تسک"
            >
                <span>🔥</span>
                <span>{{ user.task_streak }}</span>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "HeaderComp",
    props: {
        user: {
            type: Object,
            required: true
        }
    }
};
</script>

<style scoped>
/* استایل دلخواه */
</style>
