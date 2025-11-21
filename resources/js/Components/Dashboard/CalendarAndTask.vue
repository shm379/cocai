<template>
    <div class="bg-gray-800 rounded-xl p-4 shadow-lg">

        <!-- عنوان کلی -->
        <h2 class="text-lg font-bold text-red-300 mb-4">تقویم روزانه</h2>

        <!-- ساختار کلی: دو ستون -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <!-- ستون ۱ (تقویم کوچک) -->
            <div class="bg-gray-700 rounded-lg p-3">
                <div class="grid grid-cols-7 gap-1">
                    <!-- تکرار روزها (مثلاً 30 روز) -->
                    <div
                        v-for="(dayObj, index) in calendar"
                        :key="index"
                        class="h-12 flex items-center justify-center
                   border border-gray-600 text-white cursor-pointer
                   hover:bg-red-500 transition-colors"
                        :class="{
              'bg-red-600': selectedDay === dayObj.day,
              'bg-gray-700': selectedDay !== dayObj.day
            }"
                        @click="onSelectDay(dayObj.day)"
                    >
                        {{ dayObj.day }}
                    </div>
                </div>
            </div>

            <!-- ستون ۲ و ۳ (نمایش تسک روز انتخابی) -->
            <div class="md:col-span-2 bg-gray-700 rounded-lg p-4">
                <div v-if="selectedDayObject" class="text-white space-y-3">
                    <h3 class="text-xl font-semibold">
                        روز {{ selectedDayObject.day }}
                    </h3>

                    <p class="text-gray-300">
                         {{ selectedDayObject.task || '...' }}
                    </p>

                    <div>
                        <button
                            class="px-4 py-2 bg-red-600 rounded hover:bg-red-700
                     transition-colors disabled:opacity-50"
                            :disabled="selectedDayObject.completed"
                            @click="onMarkTaskCompleted(selectedDayObject.day)"
                        >
                            <span v-if="selectedDayObject.completed">تکمیل شده</span>
                            <span v-else>انجام دادم</span>
                        </button>
                    </div>
                </div>
                <!-- اگر چیزی انتخاب نشده -->
                <div v-else class="text-gray-400">
                    در حال پردازش هوش مصنوعی تا یک دقیقه دیگر مراجعه بفرمایید...
                </div>
            </div>

        </div>

    </div>
</template>

<script>
export default {
    name: "CalendarAndTask",
    props: {
        calendar: {
            type: Array,
            default: () => []
            // [{ day: 1, task: '...', completed: false }, {day:2,task:'...',completed:true}, ...]
        }
    },
    data() {
        return {
            selectedDay: 1
        };
    },
    computed: {
        selectedDayObject() {
            return this.calendar.find(item => item.day === this.selectedDay);
        }
    },
    methods: {
        onSelectDay(day) {
            this.selectedDay = day;
        },
        onMarkTaskCompleted(day) {
            // ارسال رویداد به والد
            this.$emit('markTaskCompleted', day);
            // می‌توانید در اینجا هم منطق تکمیل روز را اعمال کنید یا
            // صرفاً rely بر پاسخ prop از والد.
        }
    }
};
</script>

<style scoped>
/* می‌توانید برخی استایل‌های اضافی برای جذابیت اضافه کنید. */
</style>
