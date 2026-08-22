<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};

const isLocal = computed(() => usePage().props.app_env === 'local');
</script>

<template>
    <GuestLayout>
        <Head title="بازیابی رمز عبور" />

        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400" dir="rtl">
            رمز عبور خود را فراموش کرده‌اید؟ مشکلی نیست. ایمیل خود را وارد کنید تا لینک بازیابی رمز عبور برای شما ارسال شود.
        </div>

        <div
            v-if="status"
            class="mb-4 text-sm font-medium text-green-600 dark:text-green-400"
            dir="rtl"
        >
            {{ status }}
            <span v-if="isLocal" class="block mt-1 text-xs text-gray-500">
                (در حالت توسعه، لینک بازیابی همچنین در فایل <code>storage/logs/password-reset.log</code> ذخیره می‌شود.)
            </span>
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="ایمیل" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    ارسال لینک بازیابی
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
