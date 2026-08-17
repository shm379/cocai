<script setup>
import Login from './Login.vue';

defineProps({
    canResetPassword: {
        type: Boolean,
        default: true,
    },
    status: {
        type: String,
    }
});
</script>

<template>
    <Login :canResetPassword="canResetPassword" :status="status" defaultMode="register" />
</template>
