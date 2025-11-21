<template>
    <div>
        <!-- منوی بالا (هدر) یا سایدبار اینجا -->
        <slot />
        <!-- منوی پایین صفحه -->
        <BottomNav />
    </div>
</template>

<script>
import BottomNav from "@/Components/Dashboard/BottomNav.vue";
export default {
    name: "DashboardLayout",
    components: { BottomNav }
}
</script>
