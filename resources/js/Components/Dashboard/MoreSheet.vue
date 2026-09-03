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
                class="fixed inset-0 z-[60] bg-black/60 backdrop-blur-[2px]"
                @click="close"
                aria-hidden="true"
            ></div>
        </Transition>

        <!-- شیت پایین -->
        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            enter-from-class="translate-y-full"
            enter-to-class="translate-y-0"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-from-class="translate-y-0"
            leave-to-class="translate-y-full"
        >
            <div
                v-if="open"
                ref="panel"
                role="dialog"
                aria-modal="true"
                aria-label="بخش‌های بیشتر"
                class="fixed inset-x-0 bottom-0 z-[61] max-w-3xl mx-auto rounded-t-3xl bg-gray-900/95 backdrop-blur-xl border-t border-x border-gray-700/80 shadow-[0_-12px_40px_rgba(0,0,0,0.6)] pb-[calc(1rem+env(safe-area-inset-bottom))]"
                :class="dragging ? '' : 'transition-transform duration-200'"
                :style="{ transform: dragY > 0 ? `translateY(${dragY}px)` : '' }"
                @touchstart.passive="onTouchStart"
                @touchmove.passive="onTouchMove"
                @touchend="onTouchEnd"
                @touchcancel="onTouchEnd"
            >
                <!-- دستگیره -->
                <div class="pt-2.5 pb-1 flex justify-center cursor-grab">
                    <span class="w-12 h-1.5 rounded-full bg-gray-600"></span>
                </div>

                <div class="px-4 pb-2 flex items-center justify-between">
                    <h3 class="text-sm font-black text-white">بخش‌های بیشتر</h3>
                    <button
                        type="button"
                        @click="close"
                        class="w-10 h-10 -ml-2 rounded-xl flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-800 transition"
                        aria-label="بستن"
                    >✕</button>
                </div>

                <!-- گرید تب‌ها -->
                <div class="px-4 grid grid-cols-2 gap-3 max-h-[60vh] overflow-y-auto">
                    <button
                        v-for="tab in sections"
                        :key="tab.id"
                        type="button"
                        @click="select(tab.id)"
                        class="flex items-center gap-3 min-h-[64px] px-4 py-3 rounded-2xl border transition-all duration-150 text-right active:scale-[0.98]"
                        :class="activeTab === tab.id
                            ? 'bg-gradient-to-br from-amber-500/25 to-amber-500/5 border-amber-500/50 text-amber-200 shadow-md shadow-amber-500/10'
                            : 'bg-gray-800/80 border-gray-700/80 text-gray-200 hover:bg-gray-700/80 hover:border-gray-600'"
                        :aria-current="activeTab === tab.id ? 'page' : null"
                    >
                        <span class="text-2xl leading-none">{{ tab.icon }}</span>
                        <span class="text-sm font-bold">{{ tab.label }}</span>
                    </button>

                    <!-- آزمایشگاه بیس (لینک) -->
                    <a
                        :href="strategyLab.href"
                        class="flex items-center gap-3 min-h-[64px] px-4 py-3 rounded-2xl border border-teal-500/40 bg-gradient-to-br from-teal-600/30 to-emerald-600/10 text-teal-100 hover:from-teal-600/40 transition-all duration-150 active:scale-[0.98]"
                    >
                        <span class="text-2xl leading-none">{{ strategyLab.icon }}</span>
                        <span class="text-sm font-bold">{{ strategyLab.label }}</span>
                    </a>
                </div>

                <!-- ردیف پروفایل / خروج -->
                <div class="px-4 mt-3 pt-3 border-t border-gray-800 grid grid-cols-2 gap-3">
                    <a
                        :href="profileLink.href"
                        class="flex items-center justify-center gap-2 min-h-[48px] rounded-xl bg-gray-800/80 border border-gray-700 text-gray-200 text-sm font-bold hover:bg-gray-700 transition"
                    >
                        <span>{{ profileLink.icon }}</span>
                        <span>{{ profileLink.label }}</span>
                    </a>
                    <button
                        type="button"
                        @click="logout"
                        class="flex items-center justify-center gap-2 min-h-[48px] rounded-xl bg-gray-800/80 border border-gray-700 text-gray-300 text-sm font-bold hover:bg-red-600 hover:text-white hover:border-red-500 transition"
                    >
                        <span>🚪</span>
                        <span>خروج</span>
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script>
import { SECONDARY_SECTIONS, STRATEGY_LAB_LINK, PROFILE_LINK } from "@/Components/Dashboard/dashboardSections.js"

export default {
    name: "MoreSheet",
    props: {
        open: {
            type: Boolean,
            default: false
        },
        activeTab: {
            type: String,
            default: 'profile'
        }
    },
    emits: ['close', 'select'],
    data() {
        return {
            sections: SECONDARY_SECTIONS,
            strategyLab: STRATEGY_LAB_LINK,
            profileLink: PROFILE_LINK,
            dragStartY: null,
            dragY: 0,
            dragging: false,
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
                this.resetDrag()
            }
        }
    },
    beforeUnmount() {
        document.removeEventListener('keydown', this.onKeydown)
        if (this.open) document.body.style.overflow = ''
    },
    methods: {
        close() {
            this.$emit('close')
        },
        select(tabId) {
            this.$emit('select', tabId)
        },
        logout() {
            this.$emit('close')
            this.$inertia.post('/logout')
        },
        onKeydown(e) {
            if (e.key === 'Escape') this.close()
        },
        // کشیدن به پایین برای بستن
        onTouchStart(e) {
            // اگر لیست داخلی اسکرول شده باشد، سوایپ را شروع نکن
            const scroller = e.target.closest('.overflow-y-auto')
            if (scroller && scroller.scrollTop > 0) return
            this.dragStartY = e.touches[0].clientY
            this.dragging = true
            this.dragY = 0
        },
        onTouchMove(e) {
            if (this.dragStartY === null) return
            const dy = e.touches[0].clientY - this.dragStartY
            this.dragY = Math.max(0, dy)
        },
        onTouchEnd() {
            if (this.dragStartY === null) return
            const shouldClose = this.dragY > 90
            this.resetDrag()
            if (shouldClose) this.close()
        },
        resetDrag() {
            this.dragStartY = null
            this.dragY = 0
            this.dragging = false
        }
    }
}
</script>
