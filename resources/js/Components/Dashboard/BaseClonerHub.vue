<template>
    <section class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-gray-900 via-gray-900 to-indigo-950 shadow-2xl" dir="rtl">
        <!-- نورهای پس‌زمینه -->
        <div class="pointer-events-none absolute -top-28 -right-28 w-80 h-80 rounded-full bg-fuchsia-600/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-28 -left-28 w-80 h-80 rounded-full bg-cyan-500/20 blur-3xl"></div>

        <!-- هدر -->
        <header class="relative p-5 sm:p-6 flex items-start justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-br from-fuchsia-500 to-cyan-400 flex items-center justify-center text-2xl sm:text-3xl shadow-lg shadow-fuchsia-500/30 shrink-0">
                    🧬
                </div>
                <div>
                    <h2 class="text-lg sm:text-xl font-black text-white leading-tight">موتور بازسازی از روی عکس</h2>
                    <p class="text-xs text-gray-300/80 mt-0.5">عکس بیس یا دک را بده؛ هوش مصنوعی همان را می‌سازد و لینک می‌دهد.</p>
                </div>
            </div>
            <span
                class="hidden sm:inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-full border shrink-0"
                :class="engineOnline ? 'text-emerald-300 bg-emerald-500/10 border-emerald-500/30' : 'text-amber-300 bg-amber-500/10 border-amber-500/30'"
            >
                <span class="w-1.5 h-1.5 rounded-full" :class="engineOnline ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400'"></span>
                {{ engineOnline ? 'AI Vision آنلاین' : 'AI Vision غیرفعال' }}
            </span>
        </header>

        <!-- انتخاب بازی -->
        <div class="relative px-5 sm:px-6">
            <div class="flex gap-2 overflow-x-auto no-scrollbar pb-1 -mx-1 px-1">
                <button
                    v-for="g in games"
                    :key="g.key"
                    type="button"
                    :disabled="g.coming_soon"
                    @click="selectGame(g)"
                    class="relative flex items-center gap-2 min-h-[44px] px-3.5 py-2 rounded-2xl text-xs font-black border transition whitespace-nowrap shrink-0 disabled:cursor-not-allowed"
                    :class="gameButtonClass(g)"
                >
                    <span class="text-lg leading-none">{{ g.icon }}</span>
                    <span>{{ g.short }}</span>
                    <span v-if="g.coming_soon" class="text-[9px] font-bold px-1.5 py-0.5 rounded-md bg-gray-700 text-gray-300">به‌زودی</span>
                </button>
            </div>
            <p class="mt-2 text-[11px] leading-relaxed text-gray-300/90">
                <span class="font-bold text-white">{{ selectedGame?.label }}:</span>
                {{ selectedGame?.hint }}
            </p>
        </div>

        <!-- مراحل -->
        <ol class="relative px-5 sm:px-6 mt-4 grid grid-cols-4 gap-1.5">
            <li
                v-for="(step, i) in steps"
                :key="step.key"
                class="flex flex-col items-center gap-1 text-center"
            >
                <div class="w-full h-1.5 rounded-full transition-colors" :class="stepBarClass(i)"></div>
                <span class="text-[10px] sm:text-[11px] font-bold" :class="stepIndex >= i ? 'text-white' : 'text-gray-500'">
                    {{ step.icon }} {{ step.label }}
                </span>
            </li>
        </ol>

        <!-- بدنه -->
        <div class="relative p-5 sm:p-6 grid grid-cols-1 lg:grid-cols-5 gap-4">
            <!-- ناحیهٔ آپلود -->
            <div
                class="lg:col-span-3"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="onDrop"
            >
                <label
                    v-if="!previewUrl"
                    class="group flex flex-col items-center justify-center gap-3 min-h-[240px] sm:min-h-[300px] p-6 rounded-3xl border-2 border-dashed cursor-pointer transition"
                    :class="dragging ? 'border-cyan-400 bg-cyan-500/10' : 'border-white/15 bg-white/[0.03] hover:border-fuchsia-400/60 hover:bg-white/[0.05]'"
                >
                    <input type="file" accept="image/*" class="hidden" @change="onFileChange">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-white/10 to-white/5 border border-white/10 flex items-center justify-center text-3xl group-hover:scale-105 transition">
                        {{ selectedGame?.icon || '🖼️' }}
                    </div>
                    <div class="text-center">
                        <p class="text-sm sm:text-base font-black text-white">{{ selectedGame?.placeholder || 'عکس' }} را اینجا بینداز یا انتخاب کن</p>
                        <p class="text-[11px] text-gray-400 mt-1">یا اسکرین‌شات را با <kbd class="px-1 py-0.5 rounded bg-gray-800 border border-white/10 text-gray-200">Ctrl+V</kbd> بچسبان · JPG / PNG / WebP · تا ۸ مگابایت</p>
                    </div>
                    <span class="mt-1 inline-flex items-center justify-center min-h-[48px] px-6 rounded-2xl bg-white/10 hover:bg-white/15 border border-white/15 text-sm font-black text-white transition">
                        📁 انتخاب عکس
                    </span>
                </label>

                <div v-else class="relative rounded-3xl overflow-hidden border border-white/10 bg-gray-950">
                    <img :src="previewUrl" alt="پیش‌نمایش" class="w-full max-h-[420px] object-contain">

                    <!-- اورلی اسکن هنگام تحلیل -->
                    <div v-if="loading" class="absolute inset-0 pointer-events-none">
                        <div class="absolute inset-0 bg-gray-950/40"></div>
                        <div class="scan-grid absolute inset-0 opacity-40"></div>
                        <div class="scan-line absolute inset-x-0 h-16"></div>
                        <div class="absolute bottom-3 inset-x-3 flex items-center justify-between text-[11px] text-white">
                            <span class="px-2.5 py-1 rounded-full bg-gray-950/80 border border-white/10 font-bold">🤖 {{ loadingLabel }}</span>
                            <span class="px-2.5 py-1 rounded-full bg-gray-950/80 border border-white/10 font-mono" dir="ltr">{{ elapsed }}s</span>
                        </div>
                    </div>

                    <div v-else class="absolute top-2 left-2 flex gap-1.5">
                        <label class="min-h-[36px] px-3 rounded-xl bg-gray-950/80 hover:bg-gray-900 border border-white/10 text-[11px] font-bold text-white cursor-pointer flex items-center">
                            تغییر عکس
                            <input type="file" accept="image/*" class="hidden" @change="onFileChange">
                        </label>
                        <button type="button" @click="reset" class="min-h-[36px] px-3 rounded-xl bg-gray-950/80 hover:bg-red-600 border border-white/10 text-[11px] font-bold text-white transition">
                            حذف
                        </button>
                    </div>
                    <span v-if="file" class="absolute bottom-2 right-2 px-2 py-1 rounded-lg bg-gray-950/80 border border-white/10 text-[10px] text-gray-300 font-mono" dir="ltr">
                        {{ fileLabel }}
                    </span>
                </div>
            </div>

            <!-- کنترل‌ها -->
            <div class="lg:col-span-2 flex flex-col gap-3">
                <input
                    v-model="title"
                    type="text"
                    maxlength="120"
                    placeholder="عنوان دلخواه (اختیاری)"
                    class="w-full min-h-[46px] bg-white/[0.04] border border-white/10 rounded-2xl px-4 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-fuchsia-400/70 focus:bg-white/[0.06] transition"
                >

                <button
                    type="button"
                    @click="submit"
                    :disabled="!canSubmit"
                    class="w-full min-h-[52px] rounded-2xl text-white text-sm sm:text-base font-black shadow-lg transition flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed disabled:shadow-none"
                    :class="loading ? 'bg-gray-700' : 'bg-gradient-to-l from-fuchsia-600 via-purple-600 to-cyan-500 hover:from-fuchsia-500 hover:via-purple-500 hover:to-cyan-400 shadow-fuchsia-600/30'"
                >
                    <span v-if="loading" class="animate-spin inline-block w-5 h-5 border-2 border-white/30 border-t-white rounded-full"></span>
                    <span>{{ loading ? loadingLabel : ctaLabel }}</span>
                </button>

                <button
                    v-if="file || result || error"
                    type="button"
                    @click="reset"
                    :disabled="loading"
                    class="w-full min-h-[40px] rounded-2xl bg-white/[0.05] hover:bg-white/10 border border-white/10 text-gray-200 text-xs font-bold transition disabled:opacity-40"
                >
                    شروع مجدد
                </button>

                <ul class="mt-1 space-y-1.5 text-[11px] text-gray-300/90 leading-relaxed">
                    <li v-for="tip in tips" :key="tip" class="flex items-start gap-1.5">
                        <span class="text-cyan-300 mt-0.5">✔</span>
                        <span>{{ tip }}</span>
                    </li>
                </ul>

                <p class="mt-auto text-[10px] text-gray-500 leading-relaxed">
                    تصویر فقط برای تحلیل استفاده می‌شود و در صفحهٔ اشتراک‌گذاری شما نمایش داده می‌شود؛ هر زمان می‌توانید حذفش کنید.
                </p>
            </div>
        </div>

        <!-- خطا -->
        <div v-if="error" class="relative mx-5 sm:mx-6 mb-5 rounded-2xl bg-red-500/10 border border-red-500/40 p-4 space-y-3">
            <div class="flex items-start gap-2">
                <span class="text-lg">⚠️</span>
                <div>
                    <p class="text-sm font-bold text-red-100">{{ error }}</p>
                    <p class="text-[11px] text-red-200/70 mt-1">{{ errorTip }}</p>
                </div>
            </div>
            <div v-if="errorMatches.length" class="space-y-1.5">
                <p class="text-[11px] text-amber-200 font-bold">با این حال، این نقشه‌های آرشیو به تصویر شما شبیه‌اند:</p>
                <MatchRow v-for="m in errorMatches" :key="m.id" :match="m" />
            </div>
        </div>

        <!-- نتیجه -->
        <div v-if="result" class="relative border-t border-white/10 p-5 sm:p-6 space-y-4">
            <!-- کارت اصلی: لینک کپی -->
            <div
                v-if="clone.copy_link"
                class="rounded-3xl bg-gradient-to-l from-emerald-500/15 via-cyan-500/10 to-transparent border border-emerald-400/30 p-4 sm:p-5 space-y-3"
            >
                <div class="flex items-start gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-emerald-500/20 border border-emerald-400/40 flex items-center justify-center text-2xl shrink-0">🎯</div>
                    <div class="min-w-0">
                        <p class="text-sm sm:text-base font-black text-emerald-100">
                            <template v-if="isDeck">لینک رسمی کپی دک آماده است</template>
                            <template v-else>همین بیس در آرشیو پیدا شد (شباهت {{ clone.match_similarity }}٪)</template>
                        </p>
                        <p class="text-[11px] text-emerald-200/80 mt-0.5">
                            <template v-if="isDeck">روی «باز کردن در بازی» بزن تا دک مستقیم در کلش رویال کپی شود.</template>
                            <template v-else>{{ clone.matched_map?.name }} — لینک اصلی OpenLayout بازی.</template>
                        </p>
                    </div>
                </div>
                <LinkActions :link="clone.copy_link" open-label="🚀 باز کردن در بازی" />
                <div v-if="isDeck && clone.layout.copy_link_evo" class="pt-2 border-t border-white/10 space-y-2">
                    <p class="text-[11px] text-violet-200 font-bold">✨ نسخهٔ با Evolution / تاور تروپ (فرمت جدید بازی):</p>
                    <LinkActions :link="clone.layout.copy_link_evo" open-label="باز کردن" compact />
                </div>
            </div>

            <div
                v-else-if="isDeck"
                class="rounded-3xl bg-amber-500/10 border border-amber-400/30 p-4 text-sm text-amber-100"
            >
                <p class="font-black">دک کامل تشخیص داده نشد ({{ clone.layout.stats?.card_count || 0 }} از ۸ کارت)</p>
                <p class="text-[11px] text-amber-200/80 mt-1">لینک کپی فقط با ۸ کارت شناخته‌شده ساخته می‌شود. کارت‌های زیر را ببین و عکس واضح‌تری بفرست.</p>
            </div>

            <div
                v-else-if="result.matches?.length"
                class="rounded-3xl bg-amber-500/10 border border-amber-400/30 p-4 space-y-2"
            >
                <p class="text-sm font-black text-amber-100">🔎 بیس‌های مشابه احتمالی در آرشیو</p>
                <MatchRow v-for="m in result.matches" :key="m.id" :match="m" />
            </div>

            <div v-else class="rounded-3xl bg-white/[0.04] border border-white/10 p-4 text-[12px] text-gray-300 leading-relaxed">
                <span class="font-black text-white">در آرشیو پیدا نشد.</span>
                لینک «کپی در بازی» فقط از داخل خود بازی ساخته می‌شود؛ چیدمان دقیق پایین را خانه‌به‌خانه بساز یا لینک اشتراک‌گذاری را برای دوستانت بفرست.
            </div>

            <!-- بدنهٔ نتیجه -->
            <div class="rounded-3xl bg-white/[0.03] border border-white/10 p-4 sm:p-5 space-y-3">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div class="min-w-0">
                        <p class="text-sm font-black text-white flex items-center gap-1.5">
                            <span>{{ clone.game_icon }}</span>
                            <span class="truncate">{{ clone.title }}</span>
                        </p>
                        <p class="text-[11px] text-gray-400 mt-0.5">
                            {{ clone.game_label }}
                            <span v-if="clone.th_level" class="text-amber-300"> · سطح {{ clone.th_level }}</span>
                        </p>
                    </div>
                    <div v-if="!isDeck" class="flex items-center gap-1 bg-gray-900/80 rounded-xl p-1 border border-white/10">
                        <button type="button" @click="iso = true" class="min-h-[32px] px-3 rounded-lg text-[11px] font-bold transition" :class="iso ? 'bg-fuchsia-600 text-white' : 'text-gray-300 hover:text-white'">نمای بازی</button>
                        <button type="button" @click="iso = false" class="min-h-[32px] px-3 rounded-lg text-[11px] font-bold transition" :class="!iso ? 'bg-fuchsia-600 text-white' : 'text-gray-300 hover:text-white'">شبکه</button>
                    </div>
                </div>

                <DeckCardList v-if="isDeck" :layout="clone.layout" />

                <template v-else>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 items-start">
                        <div class="bg-gray-950 rounded-2xl p-2 border border-white/10">
                            <BaseLayoutGrid :layout="clone.layout" :iso="iso" />
                        </div>
                        <div class="bg-gray-950 rounded-2xl p-2 border border-white/10">
                            <img :src="clone.image_url" alt="تصویر اصلی" class="w-full rounded-xl object-contain max-h-80">
                            <p class="text-[10px] text-gray-500 mt-1 text-center">تصویر اصلی</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 text-[11px]">
                        <span class="px-2.5 py-1 rounded-full bg-gray-800 text-gray-200 border border-white/10">🏗️ {{ layoutStats.placed_count }} ساختمان</span>
                        <span class="px-2.5 py-1 rounded-full bg-gray-800 text-gray-200 border border-white/10">🧱 {{ layoutStats.wall_count }} دیوار</span>
                        <span v-for="(count, cat) in layoutStats.by_category" :key="cat" class="px-2.5 py-1 rounded-full bg-gray-800 text-gray-300 border border-white/10">{{ categoryLabel(cat) }}: {{ count }}</span>
                        <span v-if="layoutStats.unplaced_count" class="px-2.5 py-1 rounded-full bg-red-500/20 text-red-200 border border-red-500/30">⚠️ {{ layoutStats.unplaced_count }} ساختمان جا نشد</span>
                        <span v-if="clone.layout.corners_source !== 'model'" class="px-2.5 py-1 rounded-full bg-amber-500/20 text-amber-200 border border-amber-500/30">مقیاس تخمینی</span>
                    </div>
                </template>

                <!-- لینک اشتراک‌گذاری -->
                <div class="pt-3 border-t border-white/10 space-y-2">
                    <p class="text-xs font-black text-white">🔗 لینک اشتراک‌گذاری (بدون نیاز به ورود باز می‌شود)</p>
                    <LinkActions :link="clone.share_url" open-label="باز کردن صفحه" compact />
                </div>
            </div>
        </div>

        <!-- تاریخچه -->
        <div v-if="myClones.length" class="relative border-t border-white/10 p-5 sm:p-6 space-y-3">
            <div class="flex items-center justify-between">
                <p class="text-xs font-black text-gray-200">🕘 بازسازی‌های قبلی شما</p>
                <span class="text-[10px] text-gray-500">{{ myClones.length }} مورد</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div
                    v-for="c in myClones"
                    :key="c.id"
                    class="flex items-center gap-3 rounded-2xl bg-white/[0.03] hover:bg-white/[0.06] border border-white/10 p-2.5 transition"
                >
                    <img :src="c.image_url" alt="" class="w-16 h-12 object-cover rounded-xl border border-white/10 shrink-0">
                    <div class="min-w-0 flex-1">
                        <a :href="c.share_url" target="_blank" rel="noopener" class="text-xs font-bold text-white hover:text-cyan-300 truncate block">
                            {{ c.game_icon }} {{ c.title }}
                        </a>
                        <p class="text-[10px] text-gray-400 mt-0.5 truncate">
                            {{ c.game_short }}
                            · {{ c.result_type === 'deck' ? `${c.layout?.stats?.card_count || 0} کارت` : `${c.layout?.stats?.placed_count || 0} ساختمان` }}
                            · {{ c.view_count }} بازدید
                            <span v-if="c.copy_link" class="text-emerald-300">· لینک بازی ✓</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <button
                            type="button"
                            @click="copyText(c.copy_link || c.share_url, 'hist-' + c.slug)"
                            class="min-h-[36px] min-w-[36px] px-2 rounded-xl bg-white/[0.06] hover:bg-white/10 border border-white/10 text-sm transition"
                            :title="c.copy_link ? 'کپی لینک بازی' : 'کپی لینک اشتراک‌گذاری'"
                        >{{ copiedKey === 'hist-' + c.slug ? '✅' : '📋' }}</button>
                        <button
                            type="button"
                            @click="remove(c)"
                            :disabled="deletingSlug === c.slug"
                            class="min-h-[36px] min-w-[36px] px-2 rounded-xl bg-white/[0.06] hover:bg-red-600 border border-white/10 text-sm transition disabled:opacity-50"
                            title="حذف"
                        >🗑️</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import { defineComponent, h } from 'vue'
import BaseLayoutGrid from './BaseLayoutGrid.vue'
import DeckCardList from './DeckCardList.vue'

const CATEGORY_LABELS = { core: 'هسته', hero: 'هیرو', defense: 'دفاع', resource: 'منابع', army: 'ارتش', other: 'سایر' }

const COLOR_ACTIVE = {
    amber: 'bg-amber-500 text-gray-950 border-amber-400 shadow-lg shadow-amber-500/20',
    orange: 'bg-orange-500 text-gray-950 border-orange-400 shadow-lg shadow-orange-500/20',
    blue: 'bg-blue-600 text-white border-blue-400 shadow-lg shadow-blue-600/20',
    yellow: 'bg-yellow-500 text-gray-950 border-yellow-400',
    purple: 'bg-purple-600 text-white border-purple-400',
    teal: 'bg-teal-600 text-white border-teal-400',
}

const FALLBACK_GAMES = [
    { key: 'coc_home', label: 'کلش آف کلنز — دهکدهٔ اصلی', short: 'دهکدهٔ اصلی', icon: '🏰', color: 'amber', result_type: 'layout', hint: 'اسکرین‌شات کامل بیس.', placeholder: 'عکس بیس تاون‌هال', coming_soon: false, configured: true },
    { key: 'coc_builder', label: 'کلش آف کلنز — بیلدر بیس', short: 'بیلدر بیس', icon: '🔨', color: 'orange', result_type: 'layout', hint: 'اسکرین‌شات بیلدر بیس.', placeholder: 'عکس بیلدر بیس', coming_soon: false, configured: true },
    { key: 'clash_royale', label: 'کلش رویال — دک', short: 'کلش رویال', icon: '👑', color: 'blue', result_type: 'deck', hint: 'اسکرین‌شات دک ۸ کارتی.', placeholder: 'عکس دک ۸ کارتی', coming_soon: false, configured: true },
]

const TIPS = {
    coc_home: ['کل بیس در کادر باشد؛ زوم بیرون کافی است.', 'اسکرین‌شات مستقیم بازی یا عکس سایت‌های بیس هر دو قبول است.', 'اگر همین بیس در آرشیو باشد، لینک اصلی کپی بازی را می‌گیری.'],
    coc_builder: ['هر دو مرحلهٔ بیلدر بیس در یک عکس باشد.', 'ابعاد ساختمان‌های بیلدر بیس تقریبی است.', 'خروجی چیدمان روی شبکه است تا خانه‌به‌خانه بسازی.'],
    clash_royale: ['هر ۸ کارت واضح دیده شوند (داخل بازی یا سایت).', 'کارت‌های Evolution و تاور تروپ هم تشخیص داده می‌شوند.', 'لینک خروجی رسمی است و مستقیم در کلش رویال باز می‌شود.'],
}

const LinkActions = defineComponent({
    name: 'LinkActions',
    props: { link: { type: String, required: true }, openLabel: { type: String, default: 'باز کردن' }, compact: { type: Boolean, default: false } },
    data() { return { copied: false, timer: null } },
    beforeUnmount() { if (this.timer) clearTimeout(this.timer) },
    methods: {
        async copy() {
            try {
                await navigator.clipboard.writeText(this.link)
            } catch (e) {
                const ta = document.createElement('textarea')
                ta.value = this.link
                ta.style.position = 'fixed'
                ta.style.opacity = '0'
                document.body.appendChild(ta)
                ta.select()
                document.execCommand('copy')
                document.body.removeChild(ta)
            }
            this.copied = true
            if (this.timer) clearTimeout(this.timer)
            this.timer = setTimeout(() => { this.copied = false }, 2000)
        },
    },
    render() {
        const size = this.compact ? 'min-h-[40px] px-3 text-xs' : 'min-h-[48px] px-5 text-sm'
        return h('div', { class: 'flex flex-col sm:flex-row sm:items-center gap-2' }, [
            h('div', { class: 'flex-1 min-w-0 bg-gray-950/80 px-3 py-2 rounded-xl border border-white/10 text-[11px] font-mono text-gray-300 break-all select-all', dir: 'ltr' }, this.link),
            h('div', { class: 'flex items-center gap-2 shrink-0' }, [
                h('button', {
                    type: 'button',
                    onClick: this.copy,
                    class: `${size} flex-1 sm:flex-none rounded-xl font-black text-white transition ${this.copied ? 'bg-emerald-600' : 'bg-fuchsia-600 hover:bg-fuchsia-500'}`,
                }, this.copied ? '✅ کپی شد' : '📋 کپی لینک'),
                h('a', {
                    href: this.link,
                    target: '_blank',
                    rel: 'noopener',
                    class: `${size} flex-1 sm:flex-none rounded-xl font-black text-white bg-white/10 hover:bg-white/15 border border-white/15 transition flex items-center justify-center`,
                }, this.openLabel),
            ]),
        ])
    },
})

const MatchRow = defineComponent({
    name: 'MatchRow',
    props: { match: { type: Object, required: true } },
    data() { return { copied: false } },
    methods: {
        async copy() {
            try { await navigator.clipboard.writeText(this.match.copy_link) } catch (e) { /* ignore */ }
            this.copied = true
            setTimeout(() => { this.copied = false }, 2000)
        },
    },
    render() {
        const m = this.match
        return h('div', { class: 'flex items-center justify-between gap-2 bg-gray-950/60 rounded-xl p-2 border border-white/10' }, [
            h('div', { class: 'flex items-center gap-2 min-w-0' }, [
                (m.thumbnail_url || m.image_url) ? h('img', { src: m.thumbnail_url || m.image_url, class: 'w-14 h-9 object-cover rounded-lg border border-white/10', alt: '' }) : null,
                h('div', { class: 'min-w-0' }, [
                    h('p', { class: 'text-xs text-white truncate' }, m.name),
                    h('p', { class: 'text-[10px] text-gray-400' }, `شباهت ${m.similarity}٪`),
                ]),
            ]),
            m.copy_link ? h('button', { type: 'button', onClick: this.copy, class: 'min-h-[36px] px-3 rounded-xl bg-fuchsia-600 hover:bg-fuchsia-500 text-white text-[11px] font-black shrink-0' }, this.copied ? '✅' : '📋 کپی') : null,
        ])
    },
})

export default {
    name: 'BaseClonerHub',
    components: { BaseLayoutGrid, DeckCardList, LinkActions, MatchRow },
    props: {
        initialGame: {
            type: String,
            default: 'coc_home',
        },
    },
    data() {
        return {
            games: FALLBACK_GAMES,
            selectedKey: this.initialGame,
            file: null,
            previewUrl: null,
            title: '',
            dragging: false,
            loading: false,
            elapsed: 0,
            elapsedTimer: null,
            error: null,
            errorReason: null,
            errorMatches: [],
            result: null,
            iso: true,
            myClones: [],
            deletingSlug: null,
            copiedKey: null,
            pasteHandler: null,
        }
    },
    computed: {
        selectedGame() {
            return this.games.find(g => g.key === this.selectedKey) || this.games[0]
        },
        engineOnline() {
            return this.selectedGame ? this.selectedGame.configured !== false : true
        },
        clone() {
            return this.result?.clone || null
        },
        isDeck() {
            return this.clone?.result_type === 'deck'
        },
        layoutStats() {
            return this.clone?.layout?.stats || { placed_count: 0, wall_count: 0, unplaced_count: 0, by_category: {} }
        },
        steps() {
            return [
                { key: 'upload', label: 'آپلود', icon: '📤' },
                { key: 'analyze', label: 'تحلیل AI', icon: '🤖' },
                { key: 'build', label: this.selectedGame?.result_type === 'deck' ? 'خواندن دک' : 'بازسازی', icon: '🧬' },
                { key: 'link', label: 'لینک', icon: '🔗' },
            ]
        },
        stepIndex() {
            if (this.result) return 3
            if (this.loading) return 1
            if (this.file) return 0
            return -1
        },
        canSubmit() {
            return !!this.file && !this.loading && !!this.selectedGame && !this.selectedGame.coming_soon
        },
        ctaLabel() {
            return this.selectedGame?.result_type === 'deck' ? '👑 خواندن دک و ساخت لینک' : '🧬 بازسازی بیس با AI'
        },
        loadingLabel() {
            if (this.elapsed < 4) return 'در حال ارسال تصویر…'
            if (this.elapsed < 15) return this.selectedGame?.result_type === 'deck' ? 'در حال شناسایی کارت‌ها…' : 'در حال شناسایی ساختمان‌ها…'
            if (this.elapsed < 60) return 'در حال بازسازی چیدمان…'
            if (this.elapsed < 120) return 'بیس‌های بزرگ تا ۲ دقیقه طول می‌کشند…'
            return 'کمی بیشتر طول کشید، لطفاً صبر کنید…'
        },
        tips() {
            return TIPS[this.selectedKey] || TIPS.coc_home
        },
        errorTip() {
            const infra = ['connection', 'auth', 'model', 'server', 'timeout']
            if (this.errorReason === 'timeout') return 'سرویس شلوغ است؛ چند ثانیه صبر کن و دوباره بزن. عکس شما مشکلی ندارد.'
            if (infra.includes(this.errorReason)) return 'این مشکل از سمت سرور/سرویس هوش مصنوعی است، نه عکس شما. کمی بعد دوباره تلاش کن؛ اگر ادامه داشت به پشتیبانی بگو.'
            if (this.errorReason === 'empty') return 'مدل چیزی برنگرداند. یک بار دیگر بزن؛ اگر تکرار شد عکس کامل‌تر و واضح‌تری بفرست.'
            return 'راه حل: عکس واضح‌تر و کامل‌تر بفرست، یا بازی درست را انتخاب کن.'
        },
        fileLabel() {
            if (!this.file) return ''
            const kb = this.file.size / 1024
            return `${this.file.name.slice(0, 24)} · ${kb > 1024 ? (kb / 1024).toFixed(1) + ' MB' : Math.round(kb) + ' KB'}`
        },
    },
    mounted() {
        this.fetchGames()
        this.fetchMine()
        this.pasteHandler = (e) => this.onPaste(e)
        window.addEventListener('paste', this.pasteHandler)
    },
    beforeUnmount() {
        this.revokePreview()
        this.stopTimer()
        if (this.pasteHandler) window.removeEventListener('paste', this.pasteHandler)
    },
    methods: {
        categoryLabel(cat) {
            return CATEGORY_LABELS[cat] || cat
        },
        gameButtonClass(g) {
            if (g.coming_soon) return 'bg-white/[0.03] text-gray-500 border-white/10 opacity-70'
            if (g.key === this.selectedKey) return COLOR_ACTIVE[g.color] || COLOR_ACTIVE.amber
            return 'bg-white/[0.05] text-gray-200 border-white/10 hover:bg-white/10 hover:text-white'
        },
        stepBarClass(i) {
            if (this.stepIndex > i || (this.stepIndex === i && !this.loading)) return 'bg-gradient-to-l from-fuchsia-500 to-cyan-400'
            if (this.stepIndex === i && this.loading) return 'bg-cyan-400 animate-pulse'
            return 'bg-white/10'
        },
        selectGame(g) {
            if (g.coming_soon) return
            this.selectedKey = g.key
            this.result = null
            this.error = null
            this.errorMatches = []
        },
        async fetchGames() {
            try {
                const { data } = await window.axios.get('/api/base-clones/games')
                if (Array.isArray(data.games) && data.games.length) {
                    this.games = data.games
                    if (!this.games.some(g => g.key === this.selectedKey && !g.coming_soon)) {
                        this.selectedKey = this.games.find(g => !g.coming_soon)?.key || this.selectedKey
                    }
                }
            } catch (err) {
                console.warn('fetch games failed, using fallback list', err)
            }
        },
        revokePreview() {
            if (this.previewUrl) {
                URL.revokeObjectURL(this.previewUrl)
                this.previewUrl = null
            }
        },
        startTimer() {
            this.elapsed = 0
            this.stopTimer()
            this.elapsedTimer = setInterval(() => { this.elapsed++ }, 1000)
        },
        stopTimer() {
            if (this.elapsedTimer) {
                clearInterval(this.elapsedTimer)
                this.elapsedTimer = null
            }
        },
        onFileChange(event) {
            const file = event.target.files?.[0]
            if (file) this.acceptFile(file)
            event.target.value = ''
        },
        onDrop(event) {
            this.dragging = false
            const file = [...(event.dataTransfer?.files || [])].find(f => f.type.startsWith('image/'))
            if (file) this.acceptFile(file)
        },
        onPaste(event) {
            const items = [...(event.clipboardData?.items || [])]
            const item = items.find(i => i.type.startsWith('image/'))
            if (!item) return
            const file = item.getAsFile()
            if (file) {
                event.preventDefault()
                this.acceptFile(new File([file], `pasted-${Date.now()}.png`, { type: file.type }))
            }
        },
        async acceptFile(file) {
            if (!file.type.startsWith('image/')) {
                this.error = 'فقط فایل تصویری قبول می‌شود.'
                return
            }
            this.error = null
            this.errorMatches = []
            this.result = null

            let prepared = file
            try {
                prepared = await this.downscale(file)
            } catch (e) {
                console.warn('downscale failed, using original', e)
            }

            if (prepared.size > 8 * 1024 * 1024) {
                this.error = 'حجم تصویر بعد از فشرده‌سازی هم بیش از ۸ مگابایت است. عکس کوچک‌تری انتخاب کن.'
                return
            }

            this.revokePreview()
            this.file = prepared
            this.previewUrl = URL.createObjectURL(prepared)
        },
        /**
         * کوچک‌سازی سمت کاربر: ضلع بزرگ حداکثر ۱۸۰۰ پیکسل، JPEG با کیفیت ۰٫۸۸.
         * آپلود سریع‌تر می‌شود و مدل Vision هم همین رزولوشن را می‌خواهد.
         */
        async downscale(file) {
            const MAX = 1800
            if (file.size < 1.5 * 1024 * 1024) return file

            const bitmap = await new Promise((resolve, reject) => {
                const img = new Image()
                const url = URL.createObjectURL(file)
                img.onload = () => { URL.revokeObjectURL(url); resolve(img) }
                img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('decode failed')) }
                img.src = url
            })

            const scale = Math.min(1, MAX / Math.max(bitmap.width, bitmap.height))
            if (scale === 1 && file.size < 4 * 1024 * 1024) return file

            const canvas = document.createElement('canvas')
            canvas.width = Math.round(bitmap.width * scale)
            canvas.height = Math.round(bitmap.height * scale)
            canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height)

            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.88))
            if (!blob) return file
            return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' })
        },
        reset() {
            this.revokePreview()
            this.file = null
            this.title = ''
            this.error = null
            this.errorReason = null
            this.errorMatches = []
            this.result = null
        },
        async submit() {
            if (!this.canSubmit) return
            this.loading = true
            this.error = null
            this.errorReason = null
            this.errorMatches = []
            this.result = null
            this.startTimer()

            const formData = new FormData()
            formData.append('image', this.file)
            formData.append('game', this.selectedKey)
            if (this.title.trim()) formData.append('title', this.title.trim())

            try {
                const { data } = await window.axios.post('/api/base-clones', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                    timeout: 240000,
                })
                this.result = { clone: data.clone, matches: data.matches || [] }
                this.fetchMine()
                this.$nextTick(() => {
                    this.$el.querySelector('[data-result]')?.scrollIntoView?.({ behavior: 'smooth', block: 'start' })
                })
            } catch (err) {
                const data = err.response?.data
                this.error = data?.message
                    || Object.values(data?.errors || {}).flat()[0]
                    || (err.code === 'ECONNABORTED' ? 'تحلیل بیش از حد طول کشید. دوباره تلاش کن.' : 'خطا در بازسازی. لطفاً دوباره تلاش کن.')
                this.errorReason = data?.reason || (err.code === 'ECONNABORTED' ? 'timeout' : null)
                this.errorMatches = Array.isArray(data?.matches) ? data.matches : []
            } finally {
                this.loading = false
                this.stopTimer()
            }
        },
        async fetchMine() {
            try {
                const { data } = await window.axios.get('/api/base-clones')
                this.myClones = data.clones || []
            } catch (err) {
                console.error('fetch base clones failed', err)
            }
        },
        async copyText(text, key) {
            if (!text) return
            try { await navigator.clipboard.writeText(text) } catch (e) { /* ignore */ }
            this.copiedKey = key
            setTimeout(() => { if (this.copiedKey === key) this.copiedKey = null }, 2000)
        },
        async remove(clone) {
            if (!confirm('این مورد حذف شود؟')) return
            this.deletingSlug = clone.slug
            try {
                await window.axios.delete(`/api/base-clones/${clone.slug}`)
                this.myClones = this.myClones.filter(c => c.slug !== clone.slug)
                if (this.clone?.slug === clone.slug) this.result = null
            } catch (err) {
                console.error('delete base clone failed', err)
            } finally {
                this.deletingSlug = null
            }
        },
    },
}
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

.scan-grid {
    background-image:
        linear-gradient(rgba(34, 211, 238, 0.25) 1px, transparent 1px),
        linear-gradient(90deg, rgba(34, 211, 238, 0.25) 1px, transparent 1px);
    background-size: 28px 28px;
}
.scan-line {
    background: linear-gradient(to bottom, transparent, rgba(217, 70, 239, 0.45), rgba(34, 211, 238, 0.7), transparent);
    animation: scan 2.2s ease-in-out infinite;
}
@keyframes scan {
    0% { top: -4rem; }
    50% { top: calc(100% - 0rem); }
    100% { top: -4rem; }
}
</style>
