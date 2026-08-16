<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Welcome" />
    <div class="min-h-screen bg-gray-900 text-white flex flex-col">
        <!-- Background decoration -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-red-600/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-orange-500/20 rounded-full blur-3xl"></div>
        </div>

        <header class="relative z-10 w-full max-w-6xl mx-auto px-6 py-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-orange-600 rounded-lg flex items-center justify-center font-bold text-lg">
                    C
                </div>
                <span class="text-xl font-bold tracking-wide">CoCAI</span>
            </div>

            <nav v-if="canLogin" class="flex items-center gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="px-4 py-2 bg-red-600 hover:bg-red-500 rounded-lg transition font-medium"
                >
                    داشبورد
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="text-gray-300 hover:text-white transition"
                    >
                        ورود
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="px-4 py-2 bg-red-600 hover:bg-red-500 rounded-lg transition font-medium"
                    >
                        ثبت‌نام رایگان
                    </Link>
                </template>
            </nav>
        </header>

        <main class="relative z-10 flex-1 flex flex-col items-center justify-center px-6 py-12 text-center">
            <h1 class="text-4xl sm:text-6xl font-extrabold mb-6 leading-tight">
                دستیار هوشمند <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-orange-400">کلش اف کلنز</span>
            </h1>
            <p class="text-lg sm:text-xl text-gray-300 max-w-2xl mb-10">
                تگ بازیکن خود را وارد کنید، تحلیل پیشرفت واقعی بگیرید، تسک روزانه دریافت کنید،
                استراتژی وار بسازید و بهترین نقشه‌ها را پیدا کنید.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 mb-16">
                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="px-8 py-3 bg-gradient-to-r from-red-600 to-orange-600 hover:from-red-500 hover:to-orange-500 rounded-xl font-bold shadow-lg shadow-red-600/30 transition"
                >
                    شروع کنید — رایگان
                </Link>
                <Link
                    v-if="canLogin && !$page.props.auth.user"
                    :href="route('login')"
                    class="px-8 py-3 bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-xl font-bold transition"
                >
                    ورود به حساب
                </Link>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 max-w-4xl w-full">
                <div class="bg-gray-800/60 backdrop-blur p-6 rounded-xl border border-gray-700">
                    <div class="text-3xl mb-3">📊</div>
                    <h3 class="font-bold text-lg mb-2">تحلیل پیشرفت</h3>
                    <p class="text-sm text-gray-400">امتیاز راش، وضعیت هیروها و لَب بر اساس داده واقعی بازی</p>
                </div>
                <div class="bg-gray-800/60 backdrop-blur p-6 rounded-xl border border-gray-700">
                    <div class="text-3xl mb-3">🤖</div>
                    <h3 class="font-bold text-lg mb-2">دستیار AI</h3>
                    <p class="text-sm text-gray-400">تسک روزانه، برنامه آپگرید و استراتژی وار شخصی‌سازی‌شده</p>
                </div>
                <div class="bg-gray-800/60 backdrop-blur p-6 rounded-xl border border-gray-700">
                    <div class="text-3xl mb-3">🗺️</div>
                    <h3 class="font-bold text-lg mb-2">کتابخانه نقشه</h3>
                    <p class="text-sm text-gray-400">نقشه‌های تاون‌هال و بیلدر هال با فیلتر و کپی سریع</p>
                </div>
            </div>
        </main>

        <footer class="relative z-10 py-6 text-center text-sm text-gray-500">
            CoCAI — ساخته‌شده برای بازیکنان کلش اف کلنز
        </footer>
    </div>
</template>
