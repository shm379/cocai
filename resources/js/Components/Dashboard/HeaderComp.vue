<template>
    <div id="header" class="w-full max-w-md mb-6">
        <div class="p-4 bg-red-800 rounded-xl shadow-lg flex items-center justify-center">
            <h1 class="text-2xl font-bold text-white">سلام فرمانده {{ user.name }}</h1>
        </div>
    </div>
</template>

<script>
export default {
    name: "HeaderComp",
    props: {
        user: {
            type: Object,
            required: true
        }
    }
};
</script>

<style scoped>
/* استایل دلخواه */
</style>
