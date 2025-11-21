<template>
    <div id="game-info" class="mb-6">
        <div class="p-4 bg-gray-800 rounded-xl shadow-lg">
            <h2 class="text-lg font-medium text-red-300 mb-4">اطلاعات بازی</h2>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-red-300">نام:</span>
                    <span class="text-white">{{ gameProfile.name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-red-300">سطح تاون هال:</span>
                    <span class="text-white">{{ gameProfile.townHallLevel }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-red-300">سطح بهترین تروفی:</span>
                    <span class="text-white">{{ gameProfile.bestTrophies }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-red-300">ستاره‌های جنگی:</span>
                    <span class="text-white">{{ gameProfile.warStars }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "GameInfo",
    props: {
        gameProfile: {
            type: Object,
            required: true
        }
    }
};
</script>

<style scoped>
/* در صورت نیاز */
</style>
