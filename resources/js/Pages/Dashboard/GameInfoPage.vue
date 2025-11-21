<template>
    <div class="min-h-screen bg-gray-900 text-white p-4">
        <h1 class="text-2xl mb-4">اطلاعات بازی</h1>
        <p>نام کاربر: {{ gameProfile.name }}</p>
        <p>سطح تاون هال: {{ gameProfile.townHallLevel }}</p>
        <!-- سایر اطلاعات -->

        <!-- دکمه برگشت به داشبورد -->
        <inertia-link :href="route('dashboard')" class="mt-8 inline-block bg-red-600 px-4 py-2 rounded">
            بازگشت
        </inertia-link>
    </div>
</template>

<script>
export default {
    name: "GameInfoPage",
    props: {
        gameProfile: {
            type: Object,
            default: () => ({})
        }
    }
}
</script>
