<template>
    <div style="text-align:center; margin-top:40px;">
        <h1 style="font-size:1.5rem; margin-bottom:20px;">آزمایش صفحه‌بندی مینیمال</h1>

        <button
            @click="goToPage(page - 1)"
            :disabled="page <= 1"
            style="padding:10px 16px; margin:0 10px; background:#888; color:white;"
        >
            قبلی
        </button>

        <span style="font-size:1.25rem; padding:0 10px;">
      صفحه: {{ page }}
    </span>

        <button
            @click="goToPage(page + 1)"
            style="padding:10px 16px; margin:0 10px; background:#888; color:white;"
        >
            بعدی
        </button>
    </div>
</template>

<script>
export default {
    name: "MinimalPaginationTest",
    props: {
        // از کنترلر مقدار عددی دریافت می‌کنیم
        page: {
            type: Number,
            default: 1
        }
    },
    methods: {
        goToPage(newPage) {
            // این قسمت درخواست Inertia را می‌فرستد
            // تا پارامتر ?page=newPage را به سرور بدهد
            this.$inertia.get(
                '/test-pagination',   // آدرس همان روت
                { page: newPage },    // Query Param
                {
                    preserveState: true,   // وضع فعلی را حفظ کن (بجای رفرش کامل)
                    preserveScroll: true,  // اسکرول تغییر نکند
                    replace: true          // در History به جای ایجاد رکورد جدید، جایگزین شود
                }
            );
        }
    }
};
</script>

<style scoped>
/* استایل دلخواه */
</style>
