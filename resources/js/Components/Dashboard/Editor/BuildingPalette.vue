<template>
    <div class="space-y-2" dir="rtl">
        <div class="flex items-center justify-between gap-2">
            <p class="text-sm font-black text-gray-100">➕ افزودن ساختمان</p>
            <span v-if="activeType" class="text-[10px] text-cyan-200 font-bold">روی نقشه لمس/کلیک کنید · Esc = خروج</span>
        </div>

        <p v-if="limitReached" class="text-[11px] text-red-200 bg-red-500/10 border border-red-400/30 rounded-lg p-2">
            سقف {{ fa(limit) }} ساختمان پر شده است.
        </p>

        <input
            v-model.trim="q"
            type="search"
            class="w-full min-h-[44px] rounded-xl bg-gray-900 border-white/10 text-sm text-gray-100 placeholder-gray-500 focus:border-cyan-400 focus:ring-cyan-400"
            placeholder="جست‌وجوی ساختمان…"
            aria-label="جست‌وجوی ساختمان"
        >

        <div v-if="!catalog" class="text-xs text-gray-400 p-3 text-center">در حال بارگذاری کاتالوگ…</div>

        <div v-else-if="!groups.length" class="text-xs text-gray-400 p-3 text-center">چیزی پیدا نشد.</div>

        <div v-for="g in groups" :key="g.key" class="space-y-1">
            <p class="text-[11px] font-bold text-gray-400 flex items-center gap-1">
                <span class="w-2 h-2 rounded-full" :class="g.dot"></span>
                {{ g.label }}
            </p>
            <div class="grid grid-cols-2 gap-1.5">
                <button
                    v-for="t in g.items"
                    :key="t.type"
                    type="button"
                    class="min-h-[44px] rounded-xl border px-2 py-1 flex items-center gap-2 text-right transition disabled:opacity-40"
                    :class="t.type === activeType ? 'bg-cyan-500/20 border-cyan-400 ring-2 ring-cyan-400/60' : 'bg-gray-900/60 border-white/10 hover:border-white/30'"
                    :disabled="limitReached"
                    :title="`${t.label} — ${fa(t.size)}×${fa(t.size)}`"
                    @click="$emit('pick', t.type === activeType ? null : t.type)"
                >
                    <span class="text-lg shrink-0">{{ t.icon || '🏠' }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-[11px] font-bold text-gray-100 truncate">{{ t.label }}</span>
                        <span class="block text-[10px] text-gray-500 font-mono" dir="ltr">{{ t.size }}×{{ t.size }}</span>
                    </span>
                    <span v-if="t.count" class="shrink-0 text-[10px] font-mono text-gray-300 bg-white/10 rounded-full px-1.5">×{{ t.count }}</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { faDigits } from '../Iso/iso'

const CATEGORY_ORDER = ['core', 'defense', 'resource', 'army', 'hero', 'other']
const CATEGORY_LABELS = {
    core: 'اصلی',
    defense: 'دفاعی',
    resource: 'منابع',
    army: 'ارتش',
    hero: 'قهرمان‌ها',
    other: 'سایر',
}
const CATEGORY_DOTS = {
    core: 'bg-amber-400',
    defense: 'bg-red-400',
    resource: 'bg-yellow-300',
    army: 'bg-violet-400',
    hero: 'bg-pink-400',
    other: 'bg-gray-400',
}

/**
 * پالت افزودن ساختمان: کاتالوگ گروه‌بندی‌شده بر اساس دسته با شمار موجود در بیس.
 */
export default {
    name: 'BuildingPalette',
    props: {
        /** {types:{[type]:{size,label,color,icon,category}}} یا null تا بارگذاری */
        catalog: { type: Object, default: null },
        /** شمار هر نوع در پیش‌نویس فعلی */
        counts: { type: Object, default: () => ({}) },
        activeType: { type: String, default: null },
        limit: { type: Number, default: 300 },
        limitReached: { type: Boolean, default: false },
    },
    emits: ['pick'],
    data() {
        return { q: '' }
    },
    computed: {
        groups() {
            const types = this.catalog?.types || {}
            const q = this.q.toLowerCase()
            const byCat = {}
            for (const [type, meta] of Object.entries(types)) {
                if (type === 'wall') continue
                const label = meta.label || type
                if (q && !label.toLowerCase().includes(q) && !type.includes(q)) continue
                const cat = CATEGORY_LABELS[meta.category] ? meta.category : 'other'
                if (!byCat[cat]) byCat[cat] = []
                byCat[cat].push({ type, ...meta, label, count: this.counts[type] || 0 })
            }
            return CATEGORY_ORDER
                .filter(c => byCat[c]?.length)
                .map(c => ({
                    key: c,
                    label: CATEGORY_LABELS[c],
                    dot: CATEGORY_DOTS[c],
                    items: byCat[c].sort((a, b) => a.label.localeCompare(b.label, 'fa')),
                }))
        },
    },
    methods: {
        fa: faDigits,
    },
}
</script>
