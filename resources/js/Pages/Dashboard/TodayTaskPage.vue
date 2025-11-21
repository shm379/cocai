<template>
    <div class="min-h-screen bg-gray-900 text-white p-4">
        <h1 class="text-2xl mb-4">وظیفه امروز</h1>
        <div v-html="todayTask"></div>
        <Link :href="route('dashboard')" class="mt-8 inline-block bg-red-600 px-4 py-2 rounded">
            بازگشت
        </Link>
    </div>
</template>

<script>

import { Link } from '@inertiajs/inertia-vue3';

export default {
    name: "TodayTaskPage",

    props: {
        todayTask: {
            type: String,
            default: ""
        }
    },
    components(){
        Link
    }
}
</script>
