<template>
    <div class="btn_groups">
        <div
            v-for="(hall, index) in builderHalls"
            :key="index"
            class="hall-filter"
            :class="{ selected: selectedHallLevel === hall.level }"
            @click="filterByHall(hall.level)"
        >
            <img :src="hall.img" class="w-10 h-10 mb-1" />
            <span class="text-white text-sm font-semibold">{{ hall.label }}</span>
        </div>
    </div>
</template>

<script>
import { ref } from 'vue';

export default {
    name: 'BuilderHallFilter',
    props: {
        /**
         * سطح فعلی بیلدرهال انتخاب‌شده (مثلاً 4,5,6...)
         */
        selectedHallLevel: {
            type: Number,
            default: null
        }
    },
    emits: ['filter'],
    setup(props, { emit }) {
        // آرایه سطوح بیلدرهال
        const builderHalls = ref([
            { level: 4, img: '/images/coc/units/Builder_Hall4.png', label: 'BH 4' },
            { level: 5, img: '/images/coc/units/Builder_Hall5.png', label: 'BH 5' },
            { level: 6, img: '/images/coc/units/Builder_Hall6.png', label: 'BH 6' },
            { level: 7, img: '/images/coc/units/Builder_Hall7.png', label: 'BH 7' },
            { level: 8, img: '/images/coc/units/Builder_Hall8.png', label: 'BH 8' },
            { level: 9, img: '/images/coc/units/Builder_Hall9.png', label: 'BH 9' },
            { level: 10, img: '/images/coc/units/Builder_Hall10.png', label: 'BH 10' },
        ]);

        const filterByHall = (level) => {
            emit('filter', level);
        };

        return {
            builderHalls,
            filterByHall
        };
    }
};
</script>

<style scoped>
.btn_groups {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 8px;
}

.hall-filter {
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 10px;
    padding: 10px;
    cursor: pointer;
    text-align: center;
    transition: all 0.3s ease-in-out;
    width: 70px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.hall-filter:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: scale(1.05);
}

/* استایل انتخاب‌شده (می‌توانید رنگ دیگری بگذارید) */
.selected {
    background: #10b981; /* سبز */
    border-color: #10b981;
    color: white;
    transform: scale(1.1);
    box-shadow: 0px 4px 10px rgba(16,185,129,0.3);
}
</style>
