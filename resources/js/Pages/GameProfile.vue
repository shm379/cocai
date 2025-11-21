<template>
  <div class="container mx-auto p-6">
    <div class="bg-yellow-300 rounded-lg p-4 mb-6">
      <h1 class="text-2xl font-bold">تقویم ۳۰ روزه برای پیشرفت در بازی</h1>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <div v-for="day in calendar" :key="day.id" class="bg-blue-200 p-4 rounded-lg">
        <h3 class="text-lg font-semibold">روز {{ day.day }}</h3>
        <p>{{ day.task }}</p>
        <button 
          @click="markTaskCompleted(day.id)" 
          class="bg-green-500 text-white px-4 py-2 rounded-lg mt-4">
          تکمیل شد
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    gameProfile: Object,
    calendar: Array, // تقویم به عنوان یک آرایه از وظایف روزانه
  },
  methods: {
    markTaskCompleted(dayId) {
      alert(`وظیفه روز ${dayId} به اتمام رسید!`);
      // ارسال درخواست برای تغییر وضعیت انجام وظیفه به سرور
    }
  }
};
</script>
