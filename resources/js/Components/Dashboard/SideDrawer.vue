<template>
    <Teleport to="body">
        <!-- پس‌زمینه -->
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[70] bg-black/60 backdrop-blur-[2px]"
                @click="close"
                aria-hidden="true"
            ></div>
        </Transition>

        <!-- دراور کناری (از سمت راست) -->
        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <aside
                v-if="open"
                role="dialog"
                aria-modal="true"
                aria-label="منوی داشبورد"
                class="fixed inset-y-0 right-0 z-[71] w-[85vw] max-w-sm bg-gray-900/95 backdrop-blur-xl border-l border-gray-700/80 shadow-[-12px_0_40px_rgba(0,0,0,0.6)] flex flex-col pt-[env(safe-area-inset-top)] pb-[env(safe-area-inset-bottom)]"
            >
                <!-- سربرگ: نام کاربر + استریک -->
                <div class="flex items-center gap-3 p-4 border-b border-gray-800">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-xl shadow-lg shadow-amber-500/20 font-black text-gray-950 shrink-0">
                        ⚡
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-black text-white truncate">{{ user.name }}</div>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold">فرمانده CoCAI</span>
                            <span
                                v-if="user.task_streak > 0"
                                class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full bg-gradient-to-r from-orange-600 to-amber-600 text-white font-black"
                                title="تعداد روزهای متوالی تکمیل تسک"
                            >
                                <span>🔥</span>
                                <span class="font-mono">{{ user.task_streak }} روز</span>
                            </span>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="close"
                        class="w-10 h-10 rounded-xl flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-800 transition shrink-0"
                        aria-label="بستن منو"
                    >✕</button>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <!-- انتخاب بازی -->
                    <div class="p-4 border-b border-gray-800">
                        <div class="text-[11px] font-bold text-gray-500 mb-2">بازی</div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="game in games"
                                :key="game.id"
                                type="button"
                                @click="selectGame(game.id)"
                                class="flex items-center gap-1.5 min-h-[40px] px-3 py-1.5 rounded-xl text-xs font-black transition"
                                :class="activeGame === game.id ? game.activeClass : 'bg-gray-800/80 text-gray-400 hover:text-white hover:bg-gray-700'"
                            >
                                <span class="text-base">{{ game.icon }}</span>
                                <span>{{ game.nameFa }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- بخش‌های داشبورد -->
                    <div class="p-4 border-b border-gray-800">
                        <div class="text-[11px] font-bold text-gray-500 mb-2">بخش‌های داشبورد</div>
                        <div class="space-y-1">
                            <button
                                v-for="tab in sections"
                                :key="tab.id"
                                type="button"
                                @click="selectTab(tab.id)"
                                class="w-full flex items-center gap-3 min-h-[48px] px-3 py-2 rounded-xl text-sm font-bold transition text-right border"
                                :class="isActive(tab.id)
                                    ? 'bg-gradient-to-l from-amber-500/25 to-amber-500/5 border-amber-500/40 text-amber-200'
                                    : 'border-transparent text-gray-300 hover:bg-gray-800 hover:text-white'"
                                :aria-current="isActive(tab.id) ? 'page' : null"
                            >
                                <span class="text-xl leading-none w-7 text-center">{{ tab.icon }}</span>
                                <span class="flex-1">{{ tab.label }}</span>
                                <span v-if="isActive(tab.id)" class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                            </button>
                        </div>
                    </div>

                    <!-- لینک‌ها -->
                    <div class="p-4">
                        <div class="text-[11px] font-bold text-gray-500 mb-2">دسترسی‌ها</div>
                        <div class="space-y-1">
                            <a
                                :href="strategyLab.href"
                                class="w-full flex items-center gap-3 min-h-[48px] px-3 py-2 rounded-xl text-sm font-bold text-teal-200 bg-teal-600/15 border border-teal-500/30 hover:bg-teal-600/25 transition"
                            >
                                <span class="text-xl leading-none w-7 text-center">{{ strategyLab.icon }}</span>
                                <span>{{ strategyLab.label }}</span>
                            </a>
                            <a
                                :href="profileLink.href"
                                class="w-full flex items-center gap-3 min-h-[48px] px-3 py-2 rounded-xl text-sm font-bold text-gray-300 hover:bg-gray-800 hover:text-white transition"
                            >
                                <span class="text-xl leading-none w-7 text-center">{{ profileLink.icon }}</span>
                                <span>{{ profileLink.label }}</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- خروج -->
                <div class="p-4 border-t border-gray-800">
                    <button
                        type="button"
                        @click="logout"
                        class="w-full flex items-center justify-center gap-2 min-h-[48px] rounded-xl bg-gray-800/80 border border-gray-700 text-gray-300 text-sm font-bold hover:bg-red-600 hover:text-white hover:border-red-500 transition"
                    >
                        <span>🚪</span>
                        <span>خروج از حساب</span>
                    </button>
                </div>
            </aside>
        </Transition>
    </Teleport>
</template>

<script>
import { SUPERCELL_GAMES } from "@/Components/Dashboard/GameSwitcherBar.vue"
import { ALL_SECTIONS, STRATEGY_LAB_LINK, PROFILE_LINK } from "@/Components/Dashboard/dashboardSections.js"

export default {
    name: "SideDrawer",
    props: {
        open: {
            type: Boolean,
            default: false
        },
        user: {
            type: Object,
            required: true
        },
        activeTab: {
            type: String,
            default: 'profile'
        },
        activeGame: {
            type: String,
            default: 'coc'
        }
    },
    emits: ['close', 'select-tab', 'select-game'],
    data() {
        return {
            games: SUPERCELL_GAMES,
            sections: ALL_SECTIONS,
            strategyLab: STRATEGY_LAB_LINK,
            profileLink: PROFILE_LINK,
        }
    },
    watch: {
        open(isOpen) {
            if (isOpen) {
                document.addEventListener('keydown', this.onKeydown)
                document.body.style.overflow = 'hidden'
            } else {
                document.removeEventListener('keydown', this.onKeydown)
                document.body.style.overflow = ''
            }
        }
    },
    beforeUnmount() {
        document.removeEventListener('keydown', this.onKeydown)
        if (this.open) document.body.style.overflow = ''
    },
    methods: {
        isActive(tabId) {
            return this.activeGame === 'coc' && this.activeTab === tabId
        },
        close() {
            this.$emit('close')
        },
        selectTab(tabId) {
            this.$emit('select-tab', tabId)
            this.close()
        },
        selectGame(gameId) {
            this.$emit('select-game', gameId)
            this.close()
        },
        logout() {
            this.close()
            this.$inertia.post('/logout')
        },
        onKeydown(e) {
            if (e.key === 'Escape') this.close()
        }
    }
}
</script>
