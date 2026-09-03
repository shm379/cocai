<template>
    <div class="space-y-3" dir="rtl">
        <div v-if="!building" class="text-xs text-gray-400 leading-relaxed p-2 space-y-2">
            <p>🖱️ روی یک ساختمان بزنید تا انتخاب شود، سپس آن را بکشید یا با جهت‌نما جابه‌جا کنید.</p>
            <p>📱 در موبایل: اول لمس برای انتخاب، بعد کشیدن برای جابه‌جایی. با دو انگشت زوم کنید.</p>
            <p class="font-mono text-[10px] text-gray-500" dir="ltr">⌨ Arrows = move · Shift = ×4 · Del = remove · Ctrl+Z / Ctrl+Y</p>
        </div>

        <template v-else>
            <div class="flex items-center gap-2">
                <span class="text-3xl shrink-0">{{ building.icon || '🏠' }}</span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-black text-gray-100 truncate">{{ building.label || building.type }}</p>
                    <p class="text-[10px] text-gray-400 font-mono" dir="ltr">#{{ building.id }} · {{ building.size }}×{{ building.size }} · ({{ building.x }}, {{ building.y }})</p>
                </div>
                <button
                    type="button"
                    class="min-w-[44px] min-h-[44px] rounded-xl bg-white/[0.06] hover:bg-white/10 border border-white/10 text-gray-200"
                    title="لغو انتخاب"
                    aria-label="لغو انتخاب"
                    @click="$emit('deselect')"
                >✕</button>
            </div>

            <div class="flex flex-wrap gap-1.5">
                <span v-if="building.placed === false" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-500/20 text-red-100 border border-red-400/40">جای‌گذاری نشده</span>
                <span v-else-if="building.uncertain" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-100 border border-amber-400/40">؟ نامطمئن</span>
                <span v-if="building.user_fixed" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-100 border border-emerald-400/40">✅ تأییدشده</span>
                <span v-if="building.source === 'user'" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-700/60 text-gray-200 border border-white/10">دستی</span>
                <span v-if="error" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-500/20 text-red-100 border border-red-400/40">⚠ {{ error }}</span>
            </div>

            <!-- جهت‌نما -->
            <div class="flex items-center justify-between gap-3">
                <div class="grid grid-cols-3 gap-2 w-[168px] shrink-0" dir="ltr" role="group" aria-label="جابه‌جایی ساختمان">
                    <span></span>
                    <button type="button" class="dpad" title="بالا (y−1)" aria-label="بالا" @click="$emit('nudge', { dx: 0, dy: -1 })">▲</button>
                    <span></span>
                    <button type="button" class="dpad" title="چپ (x−1)" aria-label="چپ" @click="$emit('nudge', { dx: -1, dy: 0 })">◀</button>
                    <button type="button" class="dpad dpad-center" title="مرکز روی نقشه" aria-label="مرکز روی نقشه" @click="$emit('center')">⌖</button>
                    <button type="button" class="dpad" title="راست (x+1)" aria-label="راست" @click="$emit('nudge', { dx: 1, dy: 0 })">▶</button>
                    <span></span>
                    <button type="button" class="dpad" title="پایین (y+1)" aria-label="پایین" @click="$emit('nudge', { dx: 0, dy: 1 })">▼</button>
                    <span></span>
                </div>
                <div class="text-[10px] text-gray-400 leading-relaxed flex-1">
                    <p>x به راست‌پایین لوزی، y به چپ‌پایین لوزی می‌رود.</p>
                    <p class="mt-1">هر ضربه = یک خانه.</p>
                </div>
            </div>

            <!-- سطح -->
            <label class="block">
                <span class="text-[11px] font-bold text-gray-300">سطح</span>
                <select
                    :value="building.level ?? ''"
                    class="mt-1 w-full min-h-[44px] rounded-xl bg-gray-900 border-white/10 text-sm text-gray-100 focus:border-cyan-400 focus:ring-cyan-400"
                    @change="$emit('level', $event.target.value === '' ? null : Number($event.target.value))"
                >
                    <option value="">نامشخص</option>
                    <option v-for="lv in 50" :key="lv" :value="lv">{{ fa(lv) }}</option>
                </select>
            </label>

            <!-- نوع -->
            <label class="block">
                <span class="text-[11px] font-bold text-gray-300">نوع ساختمان</span>
                <select
                    :value="building.type"
                    :disabled="!catalog"
                    class="mt-1 w-full min-h-[44px] rounded-xl bg-gray-900 border-white/10 text-sm text-gray-100 focus:border-cyan-400 focus:ring-cyan-400 disabled:opacity-50"
                    @change="$emit('retype', $event.target.value)"
                >
                    <optgroup v-for="g in typeGroups" :key="g.key" :label="g.label">
                        <option v-for="t in g.items" :key="t.type" :value="t.type">{{ t.icon }} {{ t.label }} ({{ fa(t.size) }}×{{ fa(t.size) }})</option>
                    </optgroup>
                    <option v-if="!typeKnown" :value="building.type">{{ building.label || building.type }}</option>
                </select>
            </label>

            <div class="grid grid-cols-2 gap-1.5">
                <button
                    v-if="building.placed === false"
                    type="button"
                    class="min-h-[44px] rounded-xl bg-cyan-600/30 hover:bg-cyan-600/50 border border-cyan-400/40 text-xs font-bold text-cyan-50 transition"
                    @click="$emit('place')"
                >📍 قرار دادن در نزدیک‌ترین جای خالی</button>
                <span
                    v-else-if="building.user_fixed"
                    class="min-h-[44px] rounded-xl border text-xs font-bold flex items-center justify-center bg-emerald-600/40 border-emerald-400/60 text-emerald-50"
                    aria-disabled="true"
                    title="تأیید دائمی است و قابل لغو نیست"
                >✅ تأییدشده</span>
                <button
                    v-else
                    type="button"
                    class="min-h-[44px] rounded-xl border text-xs font-bold transition bg-emerald-600/20 hover:bg-emerald-600/40 border-emerald-400/40 text-emerald-50"
                    @click="$emit('confirm')"
                >✅ تأیید جای فعلی</button>
                <button
                    type="button"
                    class="min-h-[44px] rounded-xl bg-red-600/20 hover:bg-red-600/40 border border-red-400/30 text-xs font-bold text-red-100 transition"
                    @click="$emit('remove')"
                >🗑️ حذف</button>
            </div>
        </template>
    </div>
</template>

<script>
import { faDigits } from '../Iso/iso'

const CATEGORY_ORDER = ['core', 'defense', 'resource', 'army', 'hero', 'other']
const CATEGORY_LABELS = { core: 'اصلی', defense: 'دفاعی', resource: 'منابع', army: 'ارتش', hero: 'قهرمان‌ها', other: 'سایر' }

/**
 * پنل جزئیات ساختمان انتخاب‌شده: جهت‌نما، سطح، تغییر نوع، تأیید/قرار دادن و حذف.
 */
export default {
    name: 'InspectorPanel',
    props: {
        building: { type: Object, default: null },
        catalog: { type: Object, default: null },
        /** پیام خطای سرور برای این ساختمان (پس از ۴۲۲) */
        error: { type: String, default: '' },
    },
    emits: ['nudge', 'level', 'retype', 'confirm', 'remove', 'place', 'deselect', 'center'],
    computed: {
        typeGroups() {
            const types = this.catalog?.types || {}
            const byCat = {}
            for (const [type, meta] of Object.entries(types)) {
                if (type === 'wall') continue
                const cat = CATEGORY_LABELS[meta.category] ? meta.category : 'other'
                if (!byCat[cat]) byCat[cat] = []
                byCat[cat].push({ type, ...meta, label: meta.label || type })
            }
            return CATEGORY_ORDER
                .filter(c => byCat[c]?.length)
                .map(c => ({ key: c, label: CATEGORY_LABELS[c], items: byCat[c].sort((a, b) => a.label.localeCompare(b.label, 'fa')) }))
        },
        typeKnown() {
            return !!(this.building && this.catalog?.types && this.catalog.types[this.building.type])
        },
    },
    methods: {
        fa: faDigits,
    },
}
</script>

<style scoped>
.dpad {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #e5e7eb;
    font-size: 16px;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s;
}
.dpad:hover {
    background: rgba(255, 255, 255, 0.14);
}
.dpad:active {
    background: rgba(34, 211, 238, 0.3);
}
.dpad-center {
    font-size: 22px;
    color: #67e8f9;
}
</style>
