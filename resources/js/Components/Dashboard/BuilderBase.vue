<template>
    <div class="bg-gray-800 p-4 rounded shadow-lg">
        <h2 class="text-xl text-blue-300 font-bold mb-4">بیلدر بیس</h2>
        <p class="text-gray-100">
            لول بیلدر هال: {{ builderHallLevel }}
        </p>
        <p class="text-gray-100">
            تروفی بیلدر بیس: {{ builderBaseTrophies }}
        </p>

        <!-- نمایش نیروهای بیلدر -->
        <div class="mt-4">
            <h3 class="text-lg font-bold text-green-300 mb-2">نیروهای بیلدر</h3>
            <div class="grid grid-cols-5 gap-2">
                <div
                    v-for="(troop, index) in builderTroops"
                    :key="index"
                    class="bg-gray-700 p-2 rounded text-center relative"
                >
                    <img
                        :src="troop.icon || '/images/default_icon.webp'"
                        alt="Builder Troop"
                        class="mx-auto w-12 h-12"
                    />
                    <span class="absolute top-1 right-1 text-xs bg-gray-900 px-1 rounded">
            {{ troop.level }}/{{ troop.maxLevel }}
          </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'BuilderBase',
    props: {
        builderHallLevel: {
            type: Number,
            default: 1
        },
        builderBaseTrophies: {
            type: Number,
            default: 0
        },
        builderTroops: {
            type: Array,
            default: () => []
        }
    }
};
</script>

<style scoped>
/* دلخواه */
</style>
