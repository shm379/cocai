<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
        default: true,
    },
    status: {
        type: String,
    },
    defaultMode: {
        type: String,
        default: 'login',
    }
});

const mode = ref(props.defaultMode);

// فرم ورود
const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
});

const submitLogin = () => {
    loginForm.post(route('login'), {
        onFinish: () => loginForm.reset('password'),
    });
};

// فرم ثبت‌نام
const registerForm = useForm({
    name: '',
    email: '',
    mobile: '',
    password: '',
    password_confirmation: '',
});

const submitRegister = () => {
    registerForm.post(route('register'), {
        onFinish: () => registerForm.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="mode === 'login' ? 'ورود به حساب کاربری — CoCAI' : 'ثبت‌نام فرمانده جدید — CoCAI'" />

        <!-- تب‌بار سوئیچ یکپارچه میان ورود و ثبت‌نام -->
        <div class="flex items-center p-1 bg-gray-900/90 rounded-2xl border border-gray-700/80 mb-6 select-none">
            <button
                type="button"
                @click="mode = 'login'"
                class="flex-1 py-2.5 px-3 rounded-xl text-xs sm:text-sm font-black transition-all duration-200 flex items-center justify-center gap-2"
                :class="mode === 'login'
                    ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-gray-950 shadow-md shadow-amber-500/20'
                    : 'text-gray-400 hover:text-gray-200'"
            >
                <span>🔑</span>
                <span>ورود به حساب</span>
            </button>
            <button
                type="button"
                @click="mode = 'register'"
                class="flex-1 py-2.5 px-3 rounded-xl text-xs sm:text-sm font-black transition-all duration-200 flex items-center justify-center gap-2"
                :class="mode === 'register'
                    ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-gray-950 shadow-md shadow-amber-500/20'
                    : 'text-gray-400 hover:text-gray-200'"
            >
                <span>🛡️</span>
                <span>ثبت‌نام جدید</span>
            </button>
        </div>

        <div v-if="status" class="mb-4 text-xs font-bold text-emerald-400 bg-emerald-500/20 p-3 rounded-xl border border-emerald-500/30 text-center">
            {{ status }}
        </div>

        <!-- ورود با گیم سیتی (GameCity SSO) -->
        <div class="mb-5">
            <a
                href="/auth/gamecity/redirect"
                class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 text-white font-black text-xs sm:text-sm shadow-xl shadow-purple-500/25 transition duration-200 transform hover:-translate-y-0.5 flex items-center justify-center gap-2 border border-purple-400/30"
            >
                <span class="text-lg">🎮</span>
                <span>ورود یکپارچه با حساب کاربری گیم سیتی</span>
            </a>
            <div class="relative flex items-center justify-center my-4">
                <div class="border-t border-gray-700/80 w-full"></div>
                <span class="bg-gray-900 px-3 text-[11px] text-gray-400 font-bold shrink-0">یا ادامه با اطلاعات حساب CoCAI</span>
            </div>
        </div>

        <!-- ================= ۱) فرم ورود ================= -->
        <form v-if="mode === 'login'" @submit.prevent="submitLogin" class="space-y-4">
            <div>
                <label for="login-email" class="block text-xs font-bold text-gray-300 mb-1.5">ایمیل کاربری</label>
                <input
                    id="login-email"
                    type="email"
                    class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition font-mono placeholder:font-sans placeholder:text-gray-500"
                    v-model="loginForm.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="example@mail.com"
                />
                <InputError class="mt-1.5 text-xs text-red-400" :message="loginForm.errors.email" />
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="login-password" class="block text-xs font-bold text-gray-300">رمز عبور</label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-[11px] text-amber-400 hover:text-amber-300 transition"
                    >
                        فراموشی رمز عبور؟
                    </Link>
                </div>
                <input
                    id="login-password"
                    type="password"
                    class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition placeholder:text-gray-500"
                    v-model="loginForm.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
                <InputError class="mt-1.5 text-xs text-red-400" :message="loginForm.errors.password" />
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        v-model="loginForm.remember"
                        class="w-4 h-4 rounded bg-gray-900 border-gray-700 text-amber-500 focus:ring-amber-500 focus:ring-offset-gray-900"
                    />
                    <span class="text-xs text-gray-400">مرا به خاطر بسپار</span>
                </label>
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-amber-500 via-orange-500 to-red-600 hover:from-amber-400 hover:to-red-500 text-gray-950 font-black text-sm shadow-lg shadow-amber-500/25 transition duration-200 transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    :disabled="loginForm.processing"
                >
                    <span v-if="loginForm.processing">در حال احراز هویت...</span>
                    <span v-else>🚀 ورود به حساب کاربری</span>
                </button>
            </div>
        </form>

        <!-- ================= ۲) فرم ثبت‌نام ================= -->
        <form v-else @submit.prevent="submitRegister" class="space-y-3.5">
            <div>
                <label for="reg-name" class="block text-xs font-bold text-gray-300 mb-1">نام یا لقب درون بازی</label>
                <input
                    id="reg-name"
                    type="text"
                    class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-4 py-2 text-white text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition placeholder:text-gray-500"
                    v-model="registerForm.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="مثال: Chief Reza"
                />
                <InputError class="mt-1 text-xs text-red-400" :message="registerForm.errors.name" />
            </div>

            <div>
                <label for="reg-email" class="block text-xs font-bold text-gray-300 mb-1">آدرس ایمیل</label>
                <input
                    id="reg-email"
                    type="email"
                    class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-4 py-2 text-white text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition font-mono placeholder:font-sans placeholder:text-gray-500"
                    v-model="registerForm.email"
                    required
                    autocomplete="username"
                    placeholder="example@mail.com"
                />
                <InputError class="mt-1 text-xs text-red-400" :message="registerForm.errors.email" />
            </div>

            <div>
                <label for="reg-mobile" class="block text-xs font-bold text-gray-300 mb-1">شماره موبایل</label>
                <input
                    id="reg-mobile"
                    type="text"
                    class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-4 py-2 text-white text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition font-mono placeholder:font-sans placeholder:text-gray-500 text-left"
                    v-model="registerForm.mobile"
                    required
                    autocomplete="tel"
                    placeholder="09123456789"
                />
                <InputError class="mt-1 text-xs text-red-400" :message="registerForm.errors.mobile" />
            </div>

            <div>
                <label for="reg-pass" class="block text-xs font-bold text-gray-300 mb-1">رمز عبور</label>
                <input
                    id="reg-pass"
                    type="password"
                    class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-4 py-2 text-white text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition placeholder:text-gray-500"
                    v-model="registerForm.password"
                    required
                    autocomplete="new-password"
                    placeholder="حداقل ۸ کاراکتر"
                />
                <InputError class="mt-1 text-xs text-red-400" :message="registerForm.errors.password" />
            </div>

            <div>
                <label for="reg-pass-confirm" class="block text-xs font-bold text-gray-300 mb-1">تکرار رمز عبور</label>
                <input
                    id="reg-pass-confirm"
                    type="password"
                    class="w-full bg-gray-900/90 border border-gray-700 rounded-xl px-4 py-2 text-white text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition placeholder:text-gray-500"
                    v-model="registerForm.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="تکرار رمز عبور"
                />
                <InputError class="mt-1 text-xs text-red-400" :message="registerForm.errors.password_confirmation" />
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-amber-500 via-orange-500 to-red-600 hover:from-amber-400 hover:to-red-500 text-gray-950 font-black text-sm shadow-lg shadow-amber-500/25 transition duration-200 transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    :disabled="registerForm.processing"
                >
                    <span v-if="registerForm.processing">در حال ایجاد حساب...</span>
                    <span v-else>🛡️ ثبت‌نام و شروع بازی</span>
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
