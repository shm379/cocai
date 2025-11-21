<template>
    <div class="min-h-screen bg-gray-900 text-white p-4">
        <h1 class="text-2xl mb-4">نیروها</h1>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div v-for="(troop, index) in gameProfile.troops" :key="index" class="border p-2">
                <img :src="troop.icon" :alt="troop.name" class="w-16 h-16 object-cover mx-auto" />
                <p class="mt-2 text-center">{{ troop.name }}</p>
                <p class="text-center text-sm">Level: {{ troop.level }}/{{ troop.maxLevel }}</p>
            </div>
        </div>

        <inertia-link :href="route('dashboard')" class="mt-8 inline-block bg-red-600 px-4 py-2 rounded">
            بازگشت
        </inertia-link>
    </div>
</template>

<script>
export default {
    name: "TroopsPage",
    props: {
        gameProfile: {
            type: Object,
            default: () => ({ troops: [] })
        }
    }
}
</script>
