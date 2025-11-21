<template>
    <div class="btn_groups">
        <div
            v-for="(hall, index) in townHalls"
            :key="index"
            class="hall-filter"
            :class="{ selected: selectedHallLevel === hall.level }"
            @click="filterByHall(hall.level)"
        >
            <img
                :src="hall.img"
                class="w-10 h-10 mb-1"
            />
            <span class="text-white text-sm font-semibold">{{ hall.label }}</span>
        </div>
    </div>
</template>

<script>
import { ref } from 'vue';

export default {
    name: 'TownHallFilter',
    props: {
        /**
         * سطح فعلی تاون‌هال انتخاب‌شده (مثلاً 10, 11, 12 ...)
         */
        selectedHallLevel: {
            type: Number,
            default: null
        }
    },
    emits: ['filter'],
    setup(props, { emit }) {
        // آرایه تاون‌هال‌ها با آیکن و لیبل
        const townHalls = ref([
            { level: 1, img: '/images/coc/units/Town_Hall1.png', label: 'TH 1' },
            { level: 2, img: '/images/coc/units/Town_Hall2.png', label: 'TH 2' },
            { level: 3, img: '/images/coc/units/Town_Hall3.png', label: 'TH 3' },
            { level: 4, img: '/images/coc/units/Town_Hall4.png', label: 'TH 4' },
            { level: 5, img: '/images/coc/units/Town_Hall5.png', label: 'TH 5' },
            { level: 6, img: '/images/coc/units/Town_Hall6.png', label: 'TH 6' },
            { level: 7, img: '/images/coc/units/Town_Hall7.png', label: 'TH 7' },
            { level: 8, img: '/images/coc/units/Town_Hall8.png', label: 'TH 8' },
            { level: 9, img: '/images/coc/units/Town_Hall9.png', label: 'TH 9' },
            { level: 10, img: '/images/coc/units/Town_Hall10.png', label: 'TH 10' },
            { level: 11, img: '/images/coc/units/Town_Hall11.png', label: 'TH 11' },
            { level: 12, img: '/images/coc/units/Town_Hall12.png', label: 'TH 12' },
            { level: 13, img: '/images/coc/units/Town_Hall13.png', label: 'TH 13' },
            { level: 14, img: '/images/coc/units/Town_Hall14.png', label: 'TH 14' },
            { level: 15, img: '/images/coc/units/Town_Hall15.png', label: 'TH 15' },
            { level: 16, img: '/images/coc/units/Town_Hall16.png', label: 'TH 16' },
            { level: 17, img: '/images/coc/units/Town_Hall17.png', label: 'TH 17' },
        ]);

        // کلیک روی یک تاون‌هال => ارسال رویداد "filter" + سطح انتخاب‌شده
        const filterByHall = (level) => {
            emit('filter', level);
        };

        return {
            townHalls,
            filterByHall
        };
    }
};
</script>

<style scoped>
.btn_groups {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
}

.hall-filter {
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 10px;
    padding: 10px;
    cursor: pointer;
    text-align: center;
    transition: all 0.3s ease-in-out;
    width: 70px; /* دلخواه */
    display: flex;
    flex-direction: column;
    align-items: center;
}

.hall-filter:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: scale(1.05);
}

/* استایل انتخاب‌شده (می‌توانید رنگ دلخواه بگذارید) */
.selected {
    background: #3b82f6; /* آبی */
    border-color: #3b82f6;
    color: white;
    transform: scale(1.08);
    box-shadow: 0 4px 10px rgba(59,130,246,0.3);
}
</style>
