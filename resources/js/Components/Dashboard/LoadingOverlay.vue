<template>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-gray-800 p-6 rounded-lg flex flex-col items-center">
            <!-- SVG Spinner -->
            <svg
                class="animate-spin h-12 w-12 text-white mb-4"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                ></circle>
                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8v8H4z"
                ></path>
            </svg>
            <p class="text-white text-lg mb-2">در حال پردازش هوش مصنوعی ...</p>
            <p class="text-white text-md">
                زمان باقی‌مانده: {{ countdown }} ثانیه
            </p>
        </div>
    </div>
</template>

<script>
export default {
    name: "LoadingOverlay",
    props: {
        countdown: {
            type: Number,
            default: 15
        }
    }
};
</script>

<style scoped>
.animate-spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
