<template>
    <div class="space-y-2" dir="rtl">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="text-sm font-black text-amber-100">؟ {{ fa(items.length) }} ساختمان نامطمئن</p>
                <p class="text-[11px] text-gray-400 leading-relaxed mt-0.5">
                    هوش مصنوعی جای این‌ها را مطمئن نیست. با تصویر اصلی مقایسه کنید؛ اگر درست است «تأیید» بزنید، وگرنه جابه‌جا یا حذف کنید.
                </p>
            </div>
        </div>

        <div v-if="!items.length" class="rounded-xl bg-emerald-500/10 border border-emerald-400/30 p-4 text-center text-sm font-bold text-emerald-100">
            🎉 همهٔ ساختمان‌ها تأیید شده‌اند
        </div>

        <ul v-else class="space-y-1.5">
            <li
                v-for="b in items"
                :key="b.id"
                class="rounded-xl border p-2 transition"
                :class="b.id === selectedId ? 'bg-cyan-500/10 border-cyan-400/50' : 'bg-gray-900/60 border-white/10'"
            >
                <button
                    type="button"
                    class="w-full min-h-[44px] flex items-center gap-2 text-right"
                    :title="'انتخاب و نمایش روی نقشه'"
                    @click="$emit('select', b.id)"
                >
                    <span class="text-xl shrink-0">{{ b.icon || '🏠' }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs font-bold text-gray-100 truncate">
                            {{ b.label || b.type }}
                            <span v-if="b.level" class="text-gray-400 font-medium">· سطح {{ fa(b.level) }}</span>
                        </span>
                        <span class="block text-[10px] text-gray-400 font-mono" dir="ltr">#{{ b.id }} ({{ b.x }}, {{ b.y }})</span>
                    </span>
                    <span
                        class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold border"
                        :class="reasonClass(b)"
                    >{{ reasonLabel(b) }}</span>
                </button>

                <div class="flex items-center gap-1.5 mt-1">
                    <button
                        v-if="b.placed === false"
                        type="button"
                        class="flex-1 min-h-[44px] rounded-lg bg-cyan-600/30 hover:bg-cyan-600/50 border border-cyan-400/40 text-[11px] font-bold text-cyan-50 transition"
                        @click="$emit('place', b.id)"
                    >📍 قرار دادن</button>
                    <button
                        v-else
                        type="button"
                        class="flex-1 min-h-[44px] rounded-lg bg-emerald-600/30 hover:bg-emerald-600/50 border border-emerald-400/40 text-[11px] font-bold text-emerald-50 transition"
                        @click="$emit('confirm', b.id)"
                    >✅ تأیید</button>
                    <button
                        type="button"
                        class="flex-1 min-h-[44px] rounded-lg bg-white/[0.06] hover:bg-white/10 border border-white/10 text-[11px] font-bold text-gray-100 transition"
                        @click="$emit('select', b.id)"
                    >🎯 جابه‌جا</button>
                    <button
                        type="button"
                        class="min-w-[44px] min-h-[44px] rounded-lg bg-red-600/20 hover:bg-red-600/40 border border-red-400/30 text-sm text-red-100 transition"
                        title="حذف"
                        aria-label="حذف"
                        @click="$emit('remove', b.id)"
                    >🗑️</button>
                </div>
            </li>
        </ul>
    </div>
</template>

<script>
import { faDigits } from '../Iso/iso'

/**
 * فهرست ساختمان‌های نامطمئن ویرایشگر: پرش روی نقشه، تأیید، قرار دادن و حذف.
 */
export default {
    name: 'UncertainPanel',
    props: {
        items: { type: Array, default: () => [] },
        selectedId: { type: Number, default: null },
    },
    emits: ['select', 'confirm', 'remove', 'place'],
    methods: {
        fa: faDigits,
        reasonLabel(b) {
            if (b.placed === false) return 'جای‌گذاری نشد'
            if (Number(b.shift) >= 2) return 'جابه‌جا شده'
            return 'تخمینی'
        },
        reasonClass(b) {
            if (b.placed === false) return 'bg-red-500/20 text-red-100 border-red-400/40'
            if (Number(b.shift) >= 2) return 'bg-amber-500/20 text-amber-100 border-amber-400/40'
            return 'bg-gray-700/60 text-gray-200 border-white/10'
        },
    },
}
</script>
