<template>
    <nav class="fixed bottom-0 left-0 right-0 z-40 bg-gray-900/95 backdrop-blur-lg border-t border-gray-700/80 shadow-2xl py-2 px-2 sm:px-4">
        <div class="max-w-3xl mx-auto flex items-center justify-between sm:justify-center gap-1 sm:gap-2 overflow-x-auto no-scrollbar">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="setActiveTab(tab.id)"
                class="flex flex-col items-center justify-center py-1.5 px-2.5 sm:px-4 rounded-xl transition-all duration-200 shrink-0 text-center select-none"
                :class="activeTab === tab.id
                    ? 'bg-gradient-to-t from-amber-500/20 to-amber-500/10 text-amber-300 font-bold border border-amber-500/40 shadow-md'
                    : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/60'"
            >
                <span class="text-lg sm:text-xl mb-0.5 leading-none">{{ tab.icon }}</span>
                <span class="text-[10px] sm:text-xs tracking-tight whitespace-nowrap">{{ tab.label }}</span>
            </button>
        </div>
    </nav>
</template>

<script>
export default {
    name: "BottomNav",
    props: {
        activeTab: {
            type: String,
            default: 'profile'
        }
    },
    emits: ['update:activeTab'],
    data() {
        return {
            tabs: [
                { id: 'profile', label: 'پیشرفت', icon: '🏰' },
                { id: 'strategy', label: 'وار و حمله', icon: '⚔️' },
                { id: 'heroes', label: 'هیرو و پت', icon: '👑' },
                { id: 'clanOverview', label: 'کلن و رید', icon: '🛡️' },
                { id: 'troops', label: 'لَب و نیروها', icon: '🧪' },
                { id: 'th_maps', label: 'نقشه‌ها', icon: '🗺️' },
                { id: 'cloner', label: 'کلونر AI', icon: '🧬' },
                { id: 'favorites', label: 'نشان‌شده‌ها', icon: '❤️' },
                { id: 'builderBase', label: 'بیلدر بیس', icon: '🔨' },
                { id: 'assistant', label: 'مربی AI', icon: '🤖' },
            ]
        }
    },
    methods: {
        setActiveTab(tabId) {
            this.$emit('update:activeTab', tabId)
            window.scrollTo({ top: 0, behavior: 'smooth' })
        }
    }
}
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
