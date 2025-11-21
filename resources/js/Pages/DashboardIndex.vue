<template>
    <div class="min-h-screen flex flex-col justify-center items-center bg-gray-900 text-white">
        <h1 class="text-2xl mb-8">صفحه اصلی داشبورد</h1>
        <div class="flex flex-col gap-4">
            <!-- لینک به صفحات دیگر با اینرتیا -->
            <Link class="bg-blue-600 px-4 py-2 rounded" :href="route('dashboard.game-info')">
                اطلاعات بازی
            </Link>
            <Link class="bg-blue-600 px-4 py-2 rounded" :href="route('dashboard.troops')">
                نیروها
            </Link>
            <Link class="bg-blue-600 px-4 py-2 rounded" :href="route('dashboard.calendar')">
                تقویم
            </Link>
            <Link class="bg-blue-600 px-4 py-2 rounded" :href="route('dashboard.today-task')">
                وظیفه روزانه
            </Link>
        </div>
    </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3';

export default {
    name: "DashboardIndex",
    components: {
        Link,
    },
}
</script>
