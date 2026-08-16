<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg mb-6 border border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="text-xl">🤖</span>
                <h3 class="text-lg font-bold text-white">پیشرفت بیلدر بیس و کارگر ششم (B.O.B)</h3>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400">پیشرفت B.O.B:</span>
                <span class="text-amber-400 font-bold text-sm">{{ builderBase.bob_progress_percent || 0 }}٪</span>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-4 text-center">
            <div class="bg-gray-700/60 p-2.5 rounded-lg">
                <p class="text-xs text-gray-400">سطح بیلدر هال (BH)</p>
                <p class="text-base font-bold text-yellow-400 mt-1">BH {{ builderBase.builder_hall_level || 0 }}</p>
            </div>
            <div class="bg-gray-700/60 p-2.5 rounded-lg">
                <p class="text-xs text-gray-400">کاپ بیلدر بیس</p>
                <p class="text-base font-bold text-cyan-400 mt-1">🏆 {{ builderBase.trophies || 0 }}</p>
            </div>
            <div class="bg-gray-700/60 p-2.5 rounded-lg col-span-2 sm:col-span-1">
                <p class="text-xs text-gray-400">رکورد کاپ</p>
                <p class="text-base font-bold text-purple-400 mt-1">⭐ {{ builderBase.best_trophies || 0 }}</p>
            </div>
        </div>

        <!-- چک‌لیست ۴ گانه کارگر ششم -->
        <div class="space-y-2 pt-2 border-t border-gray-700/60">
            <h4 class="text-xs font-semibold text-gray-300 mb-2">شرایط بازگشایی کارگر ششم در دهکده اصلی (B.O.B):</h4>
            <div
                v-for="(task, idx) in builderBase.tasks"
                :key="idx"
                class="flex items-center justify-between bg-gray-700/40 p-2 rounded-lg text-xs"
            >
                <span class="text-gray-200">{{ task.title }}</span>
                <span
                    class="px-2 py-0.5 rounded text-[11px] font-bold"
                    :class="task.done ? 'bg-emerald-600/30 text-emerald-400 border border-emerald-500/40' : 'bg-gray-600/30 text-gray-400 border border-gray-600/40'"
                >
                    {{ task.done ? '✓ تکمیل شد' : 'در انتظار' }}
                </span>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'BuilderBaseProgressCard',
    props: {
        builderBase: {
            type: Object,
            default: () => ({})
        }
    }
}
</script>
