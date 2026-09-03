<template>
    <nav
        class="fixed bottom-0 inset-x-0 z-40 bg-gray-900/90 backdrop-blur-xl border-t border-gray-700/70 shadow-[0_-8px_30px_rgba(0,0,0,0.45)] px-2 pt-1.5 pb-[calc(0.375rem+env(safe-area-inset-bottom))]"
        aria-label="منوی اصلی"
    >
        <div class="max-w-3xl mx-auto grid grid-cols-5 items-end gap-1">
            <!-- ۲ آیتم اول -->
            <button
                v-for="tab in leftTabs"
                :key="tab.id"
                type="button"
                @click="setActiveTab(tab.id)"
                class="nav-item"
                :class="activeTab === tab.id ? 'nav-item--active' : 'nav-item--idle'"
                :aria-current="activeTab === tab.id ? 'page' : null"
            >
                <span class="text-xl leading-none">{{ tab.icon }}</span>
                <span class="text-[10px] font-bold tracking-tight whitespace-nowrap">{{ tab.shortLabel }}</span>
            </button>

            <!-- دکمهٔ مرکزی برجسته (کلونر) -->
            <div class="flex flex-col items-center justify-end min-h-[48px]">
                <button
                    type="button"
                    @click="setActiveTab(centerTab.id)"
                    class="-mt-8 w-14 h-14 rounded-full flex items-center justify-center text-2xl border-4 border-gray-900 transition-all duration-200 active:scale-95 select-none"
                    :class="activeTab === centerTab.id
                        ? 'bg-gradient-to-br from-amber-300 to-orange-500 text-gray-950 shadow-lg shadow-amber-500/50 ring-2 ring-amber-300/60'
                        : 'bg-gradient-to-br from-amber-500 to-orange-600 text-gray-950 shadow-lg shadow-amber-500/30'"
                    :aria-current="activeTab === centerTab.id ? 'page' : null"
                    :aria-label="centerTab.label"
                >
                    {{ centerTab.icon }}
                </button>
                <span
                    class="mt-1 text-[10px] font-bold tracking-tight whitespace-nowrap"
                    :class="activeTab === centerTab.id ? 'text-amber-300' : 'text-gray-400'"
                >{{ centerTab.shortLabel }}</span>
            </div>

            <!-- آیتم چهارم -->
            <button
                v-for="tab in rightTabs"
                :key="tab.id"
                type="button"
                @click="setActiveTab(tab.id)"
                class="nav-item"
                :class="activeTab === tab.id ? 'nav-item--active' : 'nav-item--idle'"
                :aria-current="activeTab === tab.id ? 'page' : null"
            >
                <span class="text-xl leading-none">{{ tab.icon }}</span>
                <span class="text-[10px] font-bold tracking-tight whitespace-nowrap">{{ tab.shortLabel }}</span>
            </button>

            <!-- بیشتر -->
            <button
                type="button"
                @click="moreOpen = true"
                class="nav-item relative"
                :class="moreIsActive ? 'nav-item--active' : 'nav-item--idle'"
                aria-haspopup="dialog"
                :aria-expanded="moreOpen ? 'true' : 'false'"
            >
                <span class="text-xl leading-none">{{ moreIsActive ? activeSecondary.icon : '☰' }}</span>
                <span class="text-[10px] font-bold tracking-tight whitespace-nowrap">بیشتر</span>
                <span
                    v-if="moreIsActive"
                    class="absolute top-1.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-amber-400 shadow shadow-amber-400/60"
                ></span>
            </button>
        </div>

        <MoreSheet
            :open="moreOpen"
            :activeTab="activeTab"
            @close="moreOpen = false"
            @select="onSelectFromSheet"
        />
    </nav>
</template>

<script>
import MoreSheet from "@/Components/Dashboard/MoreSheet.vue"
import { PRIMARY_SECTIONS, SECONDARY_SECTIONS } from "@/Components/Dashboard/dashboardSections.js"

export default {
    name: "BottomNav",
    components: { MoreSheet },
    props: {
        activeTab: {
            type: String,
            default: 'profile'
        }
    },
    emits: ['update:activeTab'],
    data() {
        return {
            moreOpen: false,
            primary: PRIMARY_SECTIONS,
            secondary: SECONDARY_SECTIONS,
        }
    },
    computed: {
        centerTab() {
            return this.primary.find(t => t.primary) || this.primary[2]
        },
        sideTabs() {
            return this.primary.filter(t => t.id !== this.centerTab.id)
        },
        leftTabs() {
            return this.sideTabs.slice(0, 2)
        },
        rightTabs() {
            return this.sideTabs.slice(2)
        },
        activeSecondary() {
            return this.secondary.find(t => t.id === this.activeTab) || null
        },
        moreIsActive() {
            return !!this.activeSecondary
        }
    },
    methods: {
        setActiveTab(tabId) {
            // خارج از صفحهٔ داشبورد (مثلاً آزمایشگاه بیس) با دیپ‌لینک ?tab= برگرد
            if (typeof window !== 'undefined' && window.location.pathname.replace(/\/+$/, '') !== '/dashboard') {
                this.$inertia.visit(`/dashboard?tab=${tabId}`)
                return
            }
            this.$emit('update:activeTab', tabId)
            window.scrollTo({ top: 0, behavior: 'smooth' })
        },
        onSelectFromSheet(tabId) {
            this.moreOpen = false
            this.setActiveTab(tabId)
        }
    }
}
</script>

<style scoped>
.nav-item {
    @apply flex flex-col items-center justify-center gap-0.5 min-h-[48px] py-1 rounded-xl transition-all duration-200 select-none active:scale-95;
}
.nav-item--active {
    @apply bg-gradient-to-t from-amber-500/20 to-amber-500/5 text-amber-300 border border-amber-500/40 shadow-md;
}
.nav-item--idle {
    @apply text-gray-400 border border-transparent hover:text-gray-200 hover:bg-gray-800/60;
}
</style>
