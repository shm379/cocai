<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="ورود به حساب کاربری — CoCAI" />

        <div class="mb-6 text-center">
            <h2 class="text-xl font-bold text-white">ورود به پنل فرماندهی</h2>
            <p class="text-xs text-gray-400 mt-1">برای مشاهده تحلیل‌ها و برنامه‌ریزی تسک‌ها وارد شوید</p>
        </div>

        <div v-if="status" class="mb-4 text-xs font-bold text-emerald-400 bg-emerald-500/20 p-3 rounded-xl border border-emerald-500/30 text-center">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-bold text-gray-300 mb-1.5">ایمیل کاربری</label>
                <div class="relative">
                    <input
                        id="email"
                        type="email"
                        class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition font-mono placeholder:font-sans placeholder:text-gray-500"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="example@mail.com"
                    />
                </div>
                <InputError class="mt-1.5 text-xs text-red-400" :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-bold text-gray-300">رمز عبور</label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-[11px] text-amber-400 hover:text-amber-300 transition"
                    >
                        فراموشی رمز عبور؟
                    </Link>
                </div>
                <div class="relative">
                    <input
                        id="password"
                        type="password"
                        class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition placeholder:text-gray-500"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                </div>
                <InputError class="mt-1.5 text-xs text-red-400" :message="form.errors.password" />
            </div>

            <!-- Remember me -->
            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        v-model="form.remember"
                        class="w-4 h-4 rounded bg-gray-900 border-gray-700 text-amber-500 focus:ring-amber-500 focus:ring-offset-gray-900"
                    />
                    <span class="text-xs text-gray-400">مرا به خاطر بسپار</span>
                </label>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button
                    type="submit"
                    class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-amber-500 via-orange-500 to-red-600 hover:from-amber-400 hover:to-red-500 text-gray-950 font-black text-sm shadow-lg shadow-amber-500/25 transition duration-200 transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">در حال احراز هویت...</span>
                    <span v-else>🚀 ورود به حساب کاربری</span>
                </button>
            </div>

            <!-- Switch to Register -->
            <div class="pt-4 mt-4 border-t border-gray-700/60 text-center text-xs text-gray-400">
                <span>هنوز حساب کاربری نساخته‌اید؟</span>
                <Link
                    :href="route('register')"
                    class="text-amber-400 font-bold hover:text-amber-300 mr-1.5 underline underline-offset-4"
                >
                    ثبت‌نام رایگان
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
