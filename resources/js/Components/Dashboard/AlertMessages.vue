<template>
    <div>
        <!-- پیام موفقیت -->
        <div v-if="successMessage" class="w-full max-w-md mb-4">
            <div class="p-4 bg-green-700 text-green-100 rounded-lg shadow-md flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span>{{ successMessage }}</span>
            </div>
        </div>

        <!-- پیام خطا -->
        <div v-if="errorMessage" class="w-full max-w-md mb-4">
            <div class="p-4 bg-red-700 text-red-100 rounded-lg shadow-md flex items-center">
                <i class="fas fa-times-circle mr-2"></i>
                <span>{{ errorMessage }}</span>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "AlertMessages",
    props: {
        successMessage: {
            type: String,
            default: ""
        },
        errorMessage: {
            type: String,
            default: ""
        }
    }
};
</script>

<style scoped>
/* در صورت نیاز به استایل اختصاصی */
</style>
