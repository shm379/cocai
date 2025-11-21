<template>
    <div class="min-h-screen bg-gray-900 text-white p-4">
        <h1 class="text-2xl mb-4">تقویم</h1>
        <div>
            <div
                v-for="item in calendar"
                :key="item.id"
                class="my-2 border-b border-gray-600 pb-2"
            >
                <p class="font-bold">روز {{ item.day }}:</p>
                <p>{{ item.task }}</p>
            </div>
        </div>

        <inertia-link :href="route('dashboard')" class="mt-8 inline-block bg-red-600 px-4 py-2 rounded">
            بازگشت
        </inertia-link>
    </div>
</template>

<script>
export default {
    name: "CalendarPage",
    props: {
        calendar: {
            type: Array,
            default: () => []
        }
    }
}
</script>
