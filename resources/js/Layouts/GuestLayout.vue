<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-gray-950 via-gray-900 to-gray-950 flex flex-col justify-center items-center p-4 sm:p-6 text-gray-100" dir="rtl">
        <!-- Logo & Branding -->
        <div class="mb-6 text-center">
            <Link href="/" class="inline-flex flex-col items-center group">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-amber-500 via-orange-500 to-red-600 flex items-center justify-center text-3xl shadow-xl shadow-amber-500/20 group-hover:scale-105 transition-transform duration-300">
                    ⚡
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-white mt-3 tracking-wide flex items-center gap-2">
                    <span>CoCAI</span>
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40 font-bold">
                        Supercell AI Hub
                    </span>
                </h1>
                <p class="text-xs text-gray-400 mt-1">دستیار هوشمند کلش اف کلنز، کلش رویال و بازی‌های سوپرسل</p>
            </Link>
        </div>

        <!-- Auth Card Container -->
        <div class="w-full max-w-md bg-gray-800/90 backdrop-blur-xl border border-gray-700/80 rounded-3xl p-6 sm:p-8 shadow-2xl shadow-black/60 relative overflow-hidden">
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-orange-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <slot />
        </div>

        <!-- Footer note -->
        <div class="mt-8 text-center text-xs text-gray-500">
            © ۲۰۲۶ CoCAI — قدرت گرفته از NabuGate AI • ویژه بازیکنان سوپرسل
        </div>
    </div>
</template>
