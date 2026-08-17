<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    mobile: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="ثبت‌نام در سامانه CoCAI" />

        <div class="mb-6 text-center">
            <h2 class="text-xl font-bold text-white">ثبت‌نام رایگان فرمانده جدید</h2>
            <p class="text-xs text-gray-400 mt-1">حساب خود را ایجاد کنید تا به آنالیزور و مربی AI دسترسی پیدا کنید</p>
        </div>

        <form @submit.prevent="submit" class="space-y-3.5">
            <!-- Name -->
            <div>
                <label for="name" class="block text-xs font-bold text-gray-300 mb-1">نام یا لقب درون بازی</label>
                <input
                    id="name"
                    type="text"
                    class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-4 py-2 text-white text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition placeholder:text-gray-500"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="مثال: Chief Reza"
                />
                <InputError class="mt-1 text-xs text-red-400" :message="form.errors.name" />
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-bold text-gray-300 mb-1">آدرس ایمیل</label>
                <input
                    id="email"
                    type="email"
                    class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-4 py-2 text-white text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition font-mono placeholder:font-sans placeholder:text-gray-500"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="example@mail.com"
                />
                <InputError class="mt-1 text-xs text-red-400" :message="form.errors.email" />
            </div>

            <!-- Mobile -->
            <div>
                <label for="mobile" class="block text-xs font-bold text-gray-300 mb-1">شماره موبایل</label>
                <input
                    id="mobile"
                    type="text"
                    class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-4 py-2 text-white text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition font-mono placeholder:font-sans placeholder:text-gray-500 text-left"
                    v-model="form.mobile"
                    required
                    autocomplete="tel"
                    placeholder="09123456789"
                />
                <InputError class="mt-1 text-xs text-red-400" :message="form.errors.mobile" />
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-bold text-gray-300 mb-1">رمز عبور</label>
                <input
                    id="password"
                    type="password"
                    class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-4 py-2 text-white text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition placeholder:text-gray-500"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="حداقل ۸ کاراکتر"
                />
                <InputError class="mt-1 text-xs text-red-400" :message="form.errors.password" />
            </div>

            <!-- Password Confirmation -->
            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-gray-300 mb-1">تکرار رمز عبور</label>
                <input
                    id="password_confirmation"
                    type="password"
                    class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-4 py-2 text-white text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition placeholder:text-gray-500"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="تکرار رمز عبور"
                />
                <InputError class="mt-1 text-xs text-red-400" :message="form.errors.password_confirmation" />
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button
                    type="submit"
                    class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-amber-500 via-orange-500 to-red-600 hover:from-amber-400 hover:to-red-500 text-gray-950 font-black text-sm shadow-lg shadow-amber-500/25 transition duration-200 transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">در حال ایجاد حساب...</span>
                    <span v-else>🛡️ ثبت‌نام و شروع بازی</span>
                </button>
            </div>

            <!-- Switch to Login -->
            <div class="pt-3 mt-3 border-t border-gray-700/60 text-center text-xs text-gray-400">
                <span>قبلاً در سامانه ثبت‌نام کرده‌اید؟</span>
                <Link
                    :href="route('login')"
                    class="text-amber-400 font-bold hover:text-amber-300 mr-1.5 underline underline-offset-4"
                >
                    ورود به حساب
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
