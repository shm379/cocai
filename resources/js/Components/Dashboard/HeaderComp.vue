<template>
    <header class="w-full max-w-5xl mb-4">
        <div class="bg-gray-800/95 backdrop-blur-md border border-gray-700/80 rounded-2xl p-4 sm:p-5 shadow-2xl flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-start">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-xl shadow-lg shadow-amber-500/20 font-black text-gray-950">
                        ⚡
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-base sm:text-lg font-black text-white">سلام، {{ user.name }}</h1>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold">
                                فرمانده CoCAI
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">دستیار هوشمند و تحلیلگر مسابقات سوپرسل</p>
                    </div>
                </div>

                <!-- استریک روزانه در موبایل -->
                <div
                    v-if="user.task_streak > 0"
                    class="flex sm:hidden items-center gap-1.5 bg-gradient-to-r from-orange-600 to-amber-600 px-3 py-1 rounded-xl text-white text-xs font-black shadow"
                    title="تعداد روزهای متوالی تکمیل تسک"
                >
                    <span>🔥</span>
                    <span class="font-mono">{{ user.task_streak }} روز</span>
                </div>
            </div>

            <!-- بخش اکشن‌ها و استریک دسکتاپ -->
            <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                <div
                    v-if="user.task_streak > 0"
                    class="hidden sm:flex items-center gap-2 bg-gradient-to-r from-orange-600 to-amber-600 px-3.5 py-1.5 rounded-xl text-white text-xs font-black shadow-lg shadow-orange-500/20"
                    title="تعداد روزهای متوالی تکمیل تسک"
                >
                    <span class="text-base">🔥</span>
                    <span>استریک تسک: <strong class="font-mono text-amber-200">{{ user.task_streak }} روز</strong></span>
                </div>

                <a
                    href="/dashboard/strategy-lab"
                    class="px-3.5 py-2 rounded-xl bg-teal-600/80 hover:bg-teal-500 text-white text-xs font-bold transition flex items-center gap-1.5 border border-teal-500/40"
                >
                    <span>🧪</span>
                    <span class="hidden sm:inline">آزمایشگاه بیس</span>
                </a>

                <button
                    @click="logout"
                    class="px-3.5 py-2 rounded-xl bg-gray-700/80 hover:bg-red-600 text-gray-300 hover:text-white text-xs font-bold transition flex items-center gap-1.5 border border-gray-600"
                    title="خروج از حساب"
                >
                    <span>🚪</span>
                    <span class="hidden sm:inline">خروج</span>
                </button>
            </div>
        </div>
    </header>
</template>

<script>
export default {
    name: "HeaderComp",
    props: {
        user: {
            type: Object,
            required: true
        }
    },
    methods: {
        logout() {
            this.$inertia.post('/logout');
        }
    }
};
</script>
