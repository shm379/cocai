<template>
    <div class="w-full">
        <!-- نوار اسکرول افقی بیلدرهال‌ها -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 no-scrollbar px-1" dir="rtl">
            <button
                type="button"
                v-for="hall in builderHalls"
                :key="hall.level"
                @click="filterByHall(hall.level)"
                class="flex flex-col items-center justify-center p-2 rounded-2xl border transition-all duration-200 min-w-[68px] sm:min-w-[76px] shrink-0"
                :class="selectedHallLevel === hall.level
                    ? 'bg-emerald-500/20 border-emerald-500 shadow-lg shadow-emerald-500/20 scale-105'
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
                <span class="text-xs font-black" :class="selectedHallLevel === hall.level ? 'text-emerald-300' : 'text-gray-300'">
                    {{ hall.label }}
                </span>
                <span v-if="hall.level >= 9" class="text-[8px] font-bold px-1.5 py-0.2 rounded-full bg-emerald-500/30 text-emerald-300 border border-emerald-500/40 mt-0.5">
                    ربات ۶
                </span>
            </button>
        </div>
    </div>
</template>

<script>
import { ref } from 'vue';

export default {
    name: 'BuilderHallFilter',
    props: {
        selectedHallLevel: {
            type: Number,
            default: null
        }
    },
    emits: ['filter'],
    setup(props, { emit }) {
        const builderHalls = ref([
            { level: 10, img: '/images/coc/units/Builder_Hall10.png', label: 'BH 10' },
            { level: 9, img: '/images/coc/units/Builder_Hall9.png', label: 'BH 9' },
            { level: 8, img: '/images/coc/units/Builder_Hall8.png', label: 'BH 8' },
            { level: 7, img: '/images/coc/units/Builder_Hall7.png', label: 'BH 7' },
            { level: 6, img: '/images/coc/units/Builder_Hall6.png', label: 'BH 6' },
            { level: 5, img: '/images/coc/units/Builder_Hall5.png', label: 'BH 5' },
            { level: 4, img: '/images/coc/units/Builder_Hall4.png', label: 'BH 4' },
        ]);

        const filterByHall = (level) => {
            emit('filter', level);
        };

        const handleImgError = (e) => {
            e.target.src = 'https://api-assets.clashofclans.com/leagues/72/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png';
        };

        return {
            builderHalls,
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
