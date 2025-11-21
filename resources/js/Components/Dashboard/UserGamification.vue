<template>
    <div class="bg-gray-800 p-4 rounded shadow-lg text-white">
        <h2 class="text-xl font-bold mb-2">امتیاز شما</h2>
        <p class="text-lg text-yellow-300">امتیاز کل: {{ userPoint }}</p>

        <!-- مدال‌ها -->
        <div class="mt-4 flex space-x-3">
            <div v-for="(medal, idx) in medals" :key="idx" class="text-center">
                <img :src="medal.icon" :alt="medal.name" class="w-12 h-12 mx-auto" />
                <span class="text-sm">{{ medal.name }}</span>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "UserGamification",
    props: {
        userPoint: {
            type: Number,
            default: 0
        },
        medals: {
            type: Array,
            default: () => []
            // [{name:'مدال طلایی', icon:'/images/medals/gold.png'}, ...]
        }
    }
}
</script>
