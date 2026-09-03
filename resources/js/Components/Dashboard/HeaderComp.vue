<template>
    <header class="w-full max-w-5xl mb-4 sticky top-0 z-30 sm:static -mt-4 pt-2 pb-2 sm:mt-0 sm:pt-0 sm:pb-0 before:absolute before:inset-y-0 before:-inset-x-4 before:bg-gray-900/75 before:backdrop-blur-xl before:-z-10 sm:before:hidden">
        <div class="bg-gray-900/80 sm:bg-gray-800/95 backdrop-blur-xl border border-gray-700/80 rounded-2xl p-2.5 sm:p-5 shadow-xl sm:shadow-2xl flex items-center justify-between gap-2 sm:gap-3">
            <!-- هویت -->
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                <!-- همبرگر: سمت شروع (راست در RTL) -->
                <button
                        type="button"
                        @click="drawerOpen = true"
                        class="w-11 h-11 rounded-xl bg-gray-800/80 hover:bg-gray-700 border border-gray-700 text-gray-200 hover:text-white text-xl flex items-center justify-center transition active:scale-95"
                        aria-label="بازکردن منو"
                        aria-haspopup="dialog"
                        :aria-expanded="drawerOpen ? 'true' : 'false'"
                    >
                        ☰
                    </button>
                <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-lg sm:text-xl shadow-lg shadow-amber-500/20 font-black text-gray-950 shrink-0">
                    ⚡
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 min-w-0">
                        <h1 class="text-sm sm:text-lg font-black text-white truncate">سلام، {{ user.name }}</h1>
                        <span class="hidden sm:inline text-[10px] px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold whitespace-nowrap">
                            فرمانده CoCAI
                        </span>
                    </div>
                    <p class="hidden sm:block text-xs text-gray-400 mt-0.5">دستیار هوشمند و تحلیلگر مسابقات سوپرسل</p>
                </div>
            </div>

            <!-- اکشن‌ها -->
            <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
                <!-- استریک روزانه (موبایل: فشرده / دسکتاپ: کامل) -->
                <div
                    v-if="user.task_streak > 0"
                    class="flex items-center gap-1 sm:gap-2 bg-gradient-to-r from-orange-600 to-amber-600 px-2 sm:px-3.5 py-1 sm:py-1.5 rounded-xl text-white text-[11px] sm:text-xs font-black shadow sm:shadow-lg sm:shadow-orange-500/20"
                    title="تعداد روزهای متوالی تکمیل تسک"
                >
                    <span class="sm:text-base">🔥</span>
                    <span class="hidden sm:inline">استریک تسک: </span>
                    <strong class="font-mono sm:text-amber-200">{{ user.task_streak }} روز</strong>
                </div>

                <!-- آزمایشگاه بیس (فقط دسکتاپ؛ در موبایل از دراور) -->
                <a
                    href="/dashboard/strategy-lab"
                    class="hidden sm:flex px-3.5 py-2 rounded-xl bg-teal-600/80 hover:bg-teal-500 text-white text-xs font-bold transition items-center gap-1.5 border border-teal-500/40"
                >
                    <span>🧪</span>
                    <span>آزمایشگاه بیس</span>
                </a>

                <!-- خروج (دسکتاپ) -->
                <button
                    @click="logout"
                    class="hidden sm:flex px-3.5 py-2 rounded-xl bg-gray-700/80 hover:bg-red-600 text-gray-300 hover:text-white text-xs font-bold transition items-center gap-1.5 border border-gray-600"
                    title="خروج از حساب"
                >
                    <span>🚪</span>
                    <span>خروج</span>
                </button>
            </div>
        </div>

        <SideDrawer
            :open="drawerOpen"
            :user="user"
            :activeTab="activeTab"
            :activeGame="activeSupercellGame"
            @close="drawerOpen = false"
            @select-tab="val => $emit('update:activeTab', val)"
            @select-game="val => $emit('select-game', val)"
        />
    </header>
</template>

<script>
import SideDrawer from "@/Components/Dashboard/SideDrawer.vue"

export default {
    name: "HeaderComp",
    components: { SideDrawer },
    props: {
        user: {
            type: Object,
            required: true
        },
        activeTab: {
            type: String,
            default: 'profile'
        },
        activeSupercellGame: {
            type: String,
            default: 'coc'
        }
    },
    emits: ['update:activeTab', 'select-game'],
    data() {
        return {
            drawerOpen: false
        }
    },
    methods: {
        logout() {
            this.$inertia.post('/logout');
        }
    }
};
</script>
