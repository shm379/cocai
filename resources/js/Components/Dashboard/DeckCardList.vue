<template>
    <div class="space-y-3" dir="rtl">
        <!-- خلاصه -->
        <div class="flex flex-wrap items-center gap-2 text-[11px]">
            <span class="px-2.5 py-1 rounded-full bg-pink-500/15 text-pink-200 border border-pink-500/30 font-bold">
                💧 میانگین اکسیر {{ layout.avg_elixir }}
            </span>
            <span class="px-2.5 py-1 rounded-full bg-gray-800 text-gray-200 border border-white/10">
                🃏 {{ cards.length }} / 8 کارت
            </span>
            <span v-if="layout.stats?.evolution_count" class="px-2.5 py-1 rounded-full bg-violet-500/15 text-violet-200 border border-violet-500/30">
                ✨ {{ layout.stats.evolution_count }} Evolution
            </span>
            <span v-if="layout.tower_troop" class="px-2.5 py-1 rounded-full bg-sky-500/15 text-sky-200 border border-sky-500/30">
                🏰 {{ layout.tower_troop.name }}
            </span>
            <span v-for="(count, type) in typeCounts" :key="type" class="px-2.5 py-1 rounded-full bg-gray-800 text-gray-300 border border-white/10">
                {{ typeLabel(type) }}: {{ count }}
            </span>
        </div>

        <!-- کارت‌ها -->
        <div class="grid grid-cols-4 gap-2 sm:gap-3">
            <div
                v-for="card in slots"
                :key="card.slot"
                class="relative rounded-2xl overflow-hidden border-2 aspect-[3/4] flex flex-col"
                :class="card.placeholder ? 'border-dashed border-gray-600 bg-gray-900/60' : rarityClass(card.rarity)"
                :title="card.placeholder ? card.name : `${card.name} — ${card.elixir} اکسیر — ${card.rarity}`"
            >
                <template v-if="!card.placeholder">
                    <img
                        v-if="!broken[card.key]"
                        :src="imageFor(card)"
                        :alt="card.name"
                        loading="lazy"
                        class="absolute inset-0 w-full h-full object-cover"
                        @error="broken[card.key] = true"
                    >
                    <div v-else class="absolute inset-0 flex items-center justify-center text-3xl sm:text-4xl">
                        {{ typeIcon(card.type) }}
                    </div>

                    <!-- اکسیر -->
                    <span class="absolute top-1 right-1 w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-pink-600 border-2 border-pink-200 text-white text-[11px] sm:text-xs font-black flex items-center justify-center shadow">
                        {{ card.elixir }}
                    </span>
                    <!-- Evolution -->
                    <span v-if="card.evolution" class="absolute top-1 left-1 px-1.5 py-0.5 rounded-md bg-violet-600 text-white text-[9px] font-black shadow">EVO</span>
                    <!-- سطح -->
                    <span v-if="card.level" class="absolute bottom-6 left-1 px-1.5 py-0.5 rounded-md bg-gray-950/80 text-gray-100 text-[9px] font-bold">Lv.{{ card.level }}</span>
                    <!-- نام -->
                    <div class="mt-auto relative bg-gradient-to-t from-gray-950 via-gray-950/90 to-transparent px-1.5 pt-4 pb-1.5">
                        <p class="text-[10px] sm:text-[11px] font-bold text-white leading-tight truncate text-center" dir="ltr">{{ card.name }}</p>
                    </div>
                    <span v-if="card.verified === false" class="absolute inset-x-0 top-0 h-1 bg-amber-400" title="شناسهٔ این کارت هنوز با API رسمی تأیید نشده"></span>
                </template>
                <template v-else>
                    <div class="flex-1 flex flex-col items-center justify-center gap-1 p-1 text-center">
                        <span class="text-xl">❓</span>
                        <p class="text-[10px] text-gray-400 leading-tight" dir="ltr">{{ card.name }}</p>
                    </div>
                </template>
            </div>
        </div>

        <!-- کارت‌های تشخیص‌داده‌نشده -->
        <div v-if="layout.unresolved?.length" class="text-[11px] text-amber-200 bg-amber-500/10 border border-amber-500/30 rounded-xl p-2.5">
            ⚠️ این کارت‌ها با کاتالوگ تطبیق داده نشد: <span dir="ltr">{{ layout.unresolved.join(', ') }}</span>.
            برای ساخت لینک، هر ۸ کارت باید شناخته شود؛ عکس واضح‌تری بفرستید.
        </div>
        <div v-if="layout.unverified?.length" class="text-[11px] text-amber-200/90 bg-gray-900/60 border border-white/10 rounded-xl p-2.5">
            ℹ️ شناسهٔ <span dir="ltr">{{ layout.unverified.join(', ') }}</span> از کاتالوگ محلی است و با API رسمی تأیید نشده؛ اگر لینک درست باز نشد، ادمین باید <code class="text-gray-300">php artisan cr:cards</code> را اجرا کند.
        </div>
    </div>
</template>

<script>
const RARITY_CLASS = {
    Common: 'border-gray-400 bg-gray-700',
    Rare: 'border-orange-400 bg-orange-900/40',
    Epic: 'border-purple-400 bg-purple-900/40',
    Legendary: 'border-cyan-300 bg-gradient-to-br from-cyan-900/50 via-purple-900/40 to-pink-900/40',
    Champion: 'border-amber-300 bg-amber-900/40',
}

const TYPE_LABEL = { Troop: 'نیرو', Spell: 'اسپل', Building: 'ساختمان', 'Tower Troop': 'تاور تروپ' }
const TYPE_ICON = { Troop: '⚔️', Spell: '🔮', Building: '🏗️', 'Tower Troop': '🏰' }

export default {
    name: 'DeckCardList',
    props: {
        layout: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            broken: {},
        }
    },
    computed: {
        cards() {
            return Array.isArray(this.layout?.cards) ? this.layout.cards : []
        },
        slots() {
            const items = [...this.cards]
            for (const name of (this.layout?.unresolved || [])) {
                items.push({ slot: items.length + 1, name, placeholder: true, key: `u-${name}` })
            }
            while (items.length < 8) {
                items.push({ slot: items.length + 1, name: 'خالی', placeholder: true, key: `e-${items.length}` })
            }
            return items.slice(0, 8)
        },
        typeCounts() {
            const out = {}
            for (const c of this.cards) {
                out[c.type] = (out[c.type] || 0) + 1
            }
            return out
        },
    },
    methods: {
        rarityClass(rarity) {
            return RARITY_CLASS[rarity] || RARITY_CLASS.Common
        },
        typeLabel(type) {
            return TYPE_LABEL[type] || type
        },
        typeIcon(type) {
            return TYPE_ICON[type] || '🃏'
        },
        imageFor(card) {
            return `https://cdn.royaleapi.com/static/img/cards-150/${card.key}.png`
        },
    },
}
</script>
