<template>
    <div class="w-full">
        <!-- نوار اسکرول افقی تاون‌هال‌ها -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 no-scrollbar px-1" dir="rtl">
            <button
                type="button"
                v-for="hall in townHalls"
                :key="hall.level"
                @click="filterByHall(hall.level)"
                class="flex flex-col items-center justify-center p-2 rounded-2xl border transition-all duration-200 min-w-[68px] sm:min-w-[76px] shrink-0"
                :class="selectedHallLevel === hall.level
                    ? 'bg-amber-500/20 border-amber-500 shadow-lg shadow-amber-500/20 scale-105'
                    : 'bg-gray-800/80 border-gray-700/80 hover:border-gray-600 text-gray-400 hover:text-white'"
            >
                <div class="w-10 h-10 flex items-center justify-center mb-1">
                    <img
                        :src="hall.img"
                        :alt="hall.label"
                        class="w-10 h-10 object-contain drop-shadow"
                        @error="handleImgError"
                    />
                </div>
                <span class="text-xs font-black" :class="selectedHallLevel === hall.level ? 'text-amber-300' : 'text-gray-300'">
                    {{ hall.label }}
                </span>
                <span v-if="hall.level >= 16" class="text-[8px] font-bold px-1.5 py-0.2 rounded-full bg-red-500/30 text-red-300 border border-red-500/40 mt-0.5">
                    متا
                </span>
            </button>
        </div>
    </div>
</template>

<script>
import { ref } from 'vue';

export default {
    name: 'TownHallFilter',
    props: {
        selectedHallLevel: {
            type: Number,
            default: null
        }
    },
    emits: ['filter'],
    setup(props, { emit }) {
        const townHalls = ref([
            { level: 18, img: 'https://api-assets.clashofclans.com/leagues/72/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png', label: 'TH 18' },
            { level: 17, img: 'https://api-assets.clashofclans.com/leagues/72/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png', label: 'TH 17' },
            { level: 16, img: '/images/coc/units/Town_Hall16.png', label: 'TH 16' },
            { level: 15, img: '/images/coc/units/Town_Hall15.png', label: 'TH 15' },
            { level: 14, img: '/images/coc/units/Town_Hall14.png', label: 'TH 14' },
            { level: 13, img: '/images/coc/units/Town_Hall13.png', label: 'TH 13' },
            { level: 12, img: '/images/coc/units/Town_Hall12.png', label: 'TH 12' },
            { level: 11, img: '/images/coc/units/Town_Hall11.png', label: 'TH 11' },
            { level: 10, img: '/images/coc/units/Town_Hall10.png', label: 'TH 10' },
            { level: 9, img: '/images/coc/units/Town_Hall9.png', label: 'TH 9' },
            { level: 8, img: '/images/coc/units/Town_Hall8.png', label: 'TH 8' },
            { level: 7, img: '/images/coc/units/Town_Hall7.png', label: 'TH 7' },
        ]);

        const filterByHall = (level) => {
            emit('filter', level);
        };

        const handleImgError = (e) => {
            e.target.src = 'https://api-assets.clashofclans.com/leagues/72/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png';
        };

        return {
            townHalls,
            filterByHall,
            handleImgError
        };
    }
};
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
