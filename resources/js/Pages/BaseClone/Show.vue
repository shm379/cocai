<template>
    <Head :title="clone.title || 'بازسازی‌شده با AI'" />

    <div class="min-h-screen bg-gray-950 text-white" dir="rtl">
        <div class="relative overflow-hidden">
            <div class="pointer-events-none absolute -top-32 -right-32 w-96 h-96 rounded-full bg-fuchsia-600/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-32 -left-32 w-96 h-96 rounded-full bg-cyan-500/20 blur-3xl"></div>

            <div class="relative max-w-5xl mx-auto p-4 sm:p-6 pb-16 space-y-4">
                <!-- هدر -->
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-fuchsia-500 to-cyan-400 flex items-center justify-center text-2xl shadow-lg shadow-fuchsia-500/30 shrink-0">
                            {{ clone.game_icon || '🧬' }}
                        </div>
                        <div class="min-w-0">
                            <h1 class="text-lg sm:text-2xl font-black leading-tight truncate">{{ clone.title }}</h1>
                            <p class="text-[11px] sm:text-xs text-gray-400 mt-1 flex flex-wrap gap-x-2">
                                <span class="text-cyan-300 font-bold">{{ clone.game_label }}</span>
                                <span v-if="clone.th_level" class="text-amber-300 font-bold">· سطح {{ clone.th_level }}</span>
                                <span v-if="isDeck">· {{ stats.card_count }} کارت · میانگین اکسیر {{ clone.layout.avg_elixir }}</span>
                                <span v-else>· {{ stats.placed_count }} ساختمان · {{ stats.wall_count }} دیوار</span>
                                <span>· {{ clone.view_count }} بازدید</span>
                            </p>
                        </div>
                    </div>
                    <Link
                        :href="$page.props.auth?.user ? route('dashboard') + '?tab=cloner' : '/'"
                        class="min-h-[40px] px-4 rounded-xl bg-white/[0.05] hover:bg-white/10 border border-white/10 text-xs font-bold text-gray-200 flex items-center transition"
                    >
                        {{ $page.props.auth?.user ? '🧬 ساخت یکی دیگر' : 'CoCAI — دستیار هوشمند کلش' }}
                    </Link>
                </div>

                <!-- لینک داخل بازی -->
                <div
                    v-if="clone.copy_link"
                    class="rounded-3xl bg-gradient-to-l from-emerald-500/15 via-cyan-500/10 to-transparent border border-emerald-400/30 p-4 sm:p-5 space-y-3"
                >
                    <div class="flex items-start gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-emerald-500/20 border border-emerald-400/40 flex items-center justify-center text-2xl shrink-0">🎯</div>
                        <div>
                            <p class="text-sm sm:text-base font-black text-emerald-100">
                                {{ isDeck ? 'لینک رسمی کپی دک — مستقیم در کلش رویال باز می‌شود' : 'این بیس در آرشیو موجود است — لینک اصلی کپی داخل بازی' }}
                            </p>
                            <p v-if="!isDeck && clone.matched_map" class="text-[11px] text-emerald-200/80 mt-0.5">{{ clone.matched_map.name }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                        <div class="flex-1 min-w-0 bg-gray-950/80 px-3 py-2 rounded-xl border border-white/10 text-[11px] font-mono text-gray-300 break-all select-all" dir="ltr">{{ clone.copy_link }}</div>
                        <div class="flex items-center gap-2 shrink-0">
                            <CopyMapButton :link="clone.copy_link" class="min-h-[44px] flex-1 sm:flex-none justify-center" />
                            <a :href="clone.copy_link" target="_blank" rel="noopener" class="min-h-[44px] px-5 flex-1 sm:flex-none rounded-xl bg-gradient-to-r from-red-600 to-amber-600 hover:from-red-500 hover:to-amber-500 text-white text-sm font-black shadow transition flex items-center justify-center">
                                🚀 باز کردن در بازی
                            </a>
                        </div>
                    </div>
                    <div v-if="isDeck && clone.layout.copy_link_evo" class="pt-2 border-t border-white/10 space-y-2">
                        <p class="text-[11px] text-violet-200 font-bold">✨ نسخهٔ با Evolution / تاور تروپ:</p>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <div class="flex-1 min-w-0 bg-gray-950/80 px-3 py-2 rounded-xl border border-white/10 text-[11px] font-mono text-gray-300 break-all select-all" dir="ltr">{{ clone.layout.copy_link_evo }}</div>
                            <CopyMapButton :link="clone.layout.copy_link_evo" />
                        </div>
                    </div>
                </div>

                <!-- بدنه -->
                <div class="rounded-3xl bg-white/[0.03] border border-white/10 p-4 sm:p-5 space-y-3">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <p class="text-sm font-black">{{ isDeck ? '🃏 دک خوانده‌شده' : '🗺️ چیدمان بازسازی‌شده روی شبکهٔ ۴۴×۴۴' }}</p>
                        <div v-if="!isDeck" class="flex items-center gap-1 bg-gray-900 rounded-xl p-1 border border-white/10">
                            <button type="button" @click="iso = true" class="min-h-[32px] px-3 rounded-lg text-[11px] font-bold transition" :class="iso ? 'bg-fuchsia-600 text-white' : 'text-gray-300 hover:text-white'">نمای بازی</button>
                            <button type="button" @click="iso = false" class="min-h-[32px] px-3 rounded-lg text-[11px] font-bold transition" :class="!iso ? 'bg-fuchsia-600 text-white' : 'text-gray-300 hover:text-white'">شبکه</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 items-start">
                        <div class="bg-gray-950 rounded-2xl p-2 border border-white/10">
                            <DeckCardList v-if="isDeck" :layout="clone.layout" />
                            <BaseLayoutGrid v-else :layout="clone.layout" :iso="iso" />
                        </div>
                        <div class="bg-gray-950 rounded-2xl p-2 border border-white/10">
                            <img :src="clone.image_url" alt="تصویر اصلی" class="w-full rounded-xl object-contain max-h-96">
                            <p class="text-[10px] text-gray-500 mt-1 text-center">تصویر اصلی</p>
                        </div>
                    </div>

                    <!-- لینک اشتراک‌گذاری -->
                    <div class="pt-3 border-t border-white/10 flex flex-col sm:flex-row sm:items-center gap-2">
                        <div class="flex-1 min-w-0 bg-gray-950/80 px-3 py-2 rounded-xl border border-white/10 text-[11px] font-mono text-gray-300 break-all select-all" dir="ltr">{{ clone.share_url }}</div>
                        <CopyMapButton :link="clone.share_url" />
                    </div>
                </div>

                <!-- فهرست ساختمان‌ها (فقط چیدمان) -->
                <div v-if="!isDeck" class="rounded-3xl bg-white/[0.03] border border-white/10 p-4 sm:p-5">
                    <p class="text-sm font-black mb-2">📋 فهرست ساختمان‌ها (برای ساخت خانه‌به‌خانه)</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 text-xs">
                        <div v-for="row in typeRows" :key="row.type" class="flex items-center gap-2 bg-gray-900/60 rounded-xl p-2 border border-white/5">
                            <span class="w-3 h-3 rounded-sm shrink-0" :style="{ backgroundColor: row.color }"></span>
                            <span class="truncate">{{ row.icon }} {{ row.label }}</span>
                            <span class="mr-auto text-gray-400 font-mono">×{{ row.count }}</span>
                        </div>
                    </div>

                    <details class="mt-3">
                        <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-200 min-h-[36px] flex items-center">مختصات دقیق هر ساختمان (x, y از گوشهٔ بالای نقشه)</summary>
                        <div class="mt-2 max-h-72 overflow-y-auto">
                            <table class="w-full text-[11px]">
                                <thead class="text-gray-400">
                                    <tr>
                                        <th class="text-right p-1">#</th>
                                        <th class="text-right p-1">ساختمان</th>
                                        <th class="text-right p-1">خانه</th>
                                        <th class="text-right p-1">ابعاد</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="b in placedBuildings" :key="b.id" class="border-t border-white/5">
                                        <td class="p-1 text-gray-500">{{ b.id }}</td>
                                        <td class="p-1">{{ b.icon }} {{ b.label }}<span v-if="b.level" class="text-gray-500"> (سطح {{ b.level }})</span></td>
                                        <td class="p-1 font-mono" dir="ltr">({{ b.x }}, {{ b.y }})</td>
                                        <td class="p-1 font-mono">{{ b.size }}×{{ b.size }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </details>
                </div>

                <p class="text-[11px] text-gray-500 leading-relaxed">
                    <template v-if="isDeck">
                        این دک با هوش مصنوعی از روی تصویر خوانده شده است. لینک کپی دک همان فرمت رسمی بازی است؛ اگر کارتی اشتباه تشخیص داده شده، عکس واضح‌تری بفرستید.
                    </template>
                    <template v-else>
                        این چیدمان از روی تصویر و با هوش مصنوعی بازسازی شده است. لینک «کپی در بازی» فقط از داخل خود بازی ساخته می‌شود؛ اگر بالا نمایش داده نشده، بیس در آرشیو ما نبوده و باید با کمک این نقشه به‌صورت دستی ساخته شود.
                    </template>
                </p>
            </div>
        </div>
    </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import BaseLayoutGrid from '@/Components/Dashboard/BaseLayoutGrid.vue'
import DeckCardList from '@/Components/Dashboard/DeckCardList.vue'
import CopyMapButton from '@/Components/Dashboard/CopyMapButton.vue'

export default {
    name: 'BaseCloneShow',
    components: { Head, Link, BaseLayoutGrid, DeckCardList, CopyMapButton },
    props: {
        clone: {
            type: Object,
            required: true,
        },
        isOwner: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            iso: true,
        }
    },
    computed: {
        isDeck() {
            return this.clone.result_type === 'deck'
        },
        stats() {
            return this.clone.layout?.stats || { placed_count: 0, wall_count: 0, card_count: 0 }
        },
        placedBuildings() {
            return (this.clone.layout?.buildings || []).filter(b => b.placed !== false)
        },
        typeRows() {
            const rows = {}
            for (const b of this.placedBuildings) {
                if (!rows[b.type]) {
                    rows[b.type] = { type: b.type, label: b.label, icon: b.icon, color: b.color, count: 0 }
                }
                rows[b.type].count++
            }
            return Object.values(rows).sort((a, b) => b.count - a.count)
        },
    },
}
</script>
