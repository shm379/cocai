<template>
    <div class="bg-gray-800/95 backdrop-blur p-2 sm:p-3 rounded-2xl shadow-xl mb-6 border border-gray-700/80">
        <div class="flex items-center justify-between gap-2 overflow-x-auto pb-1 sm:pb-0">
            <button
                v-for="game in games"
                :key="game.id"
                @click="$emit('select-game', game.id)"
                class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-black transition whitespace-nowrap shrink-0"
                :class="activeGame === game.id ? game.activeClass : 'bg-gray-700/50 text-gray-400 hover:text-white hover:bg-gray-700'"
            >
                <span class="text-base">{{ game.icon }}</span>
                <span>{{ game.nameFa }}</span>
            </button>
        </div>
    </div>
</template>

<script>
// فهرست بازی‌های سوپرسل — به‌صورت named export تا SideDrawer هم از همین لیست استفاده کند
// (کلاس‌های Tailwind اینجا می‌مانند تا در اسکن content پیدا شوند)
export const SUPERCELL_GAMES = [
    {
        id: 'coc',
        nameFa: 'Clash of Clans',
        icon: '🏰',
        activeClass: 'bg-amber-500 text-gray-950 shadow-lg shadow-amber-500/20'
    },
    {
        id: 'clash_royale',
        nameFa: 'Clash Royale',
        icon: '👑',
        activeClass: 'bg-blue-600 text-white shadow-lg shadow-blue-600/20'
    },
    {
        id: 'brawl_stars',
        nameFa: 'Brawl Stars',
        icon: '🌵',
        activeClass: 'bg-yellow-500 text-gray-950 shadow-lg shadow-yellow-500/20'
    },
    {
        id: 'squad_busters',
        nameFa: 'Squad Busters',
        icon: '⚡',
        activeClass: 'bg-purple-600 text-white shadow-lg shadow-purple-600/20'
    },
    {
        id: 'boom_beach',
        nameFa: 'Boom Beach',
        icon: '🏝️',
        activeClass: 'bg-teal-600 text-white shadow-lg shadow-teal-600/20'
    }
]

export default {
    name: 'GameSwitcherBar',
    props: {
        activeGame: {
            type: String,
            default: 'coc'
        }
    },
    data() {
        return {
            games: SUPERCELL_GAMES
        }
    }
}
</script>
