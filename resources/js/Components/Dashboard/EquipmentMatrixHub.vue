<template>
    <div class="bg-gray-800/90 backdrop-blur-xl border border-gray-700/80 rounded-3xl p-4 sm:p-6 shadow-2xl" dir="rtl">
        <!-- هدر متریکس تجهیزات هیرو -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5 pb-4 border-b border-gray-700/70">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-600 via-indigo-600 to-blue-500 flex items-center justify-center text-2xl shadow-lg shadow-purple-500/20 text-white font-black shrink-0">
                    👑
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base sm:text-lg font-black text-white">متریکس هم‌افزایی تجهیزات هیرو و برنامه سنگ‌های معدنی (Ore Synergy Hub)</h2>
                        <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/40 font-bold">
                            تجهیزات اپیک و متای ۲۰۲۶
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">محاسبه سینرژی لوداوت‌ها، اولویت مصرف سنگ‌های استاری و گلووی، و پیش‌بینی توان تخریب</p>
                </div>
            </div>

            <!-- انتخاب هیرو -->
            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar w-full sm:w-auto">
                <button
                    v-for="h in heroesList"
                    :key="h.id"
                    @click="activeHero = h.id"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap"
                    :class="activeHero === h.id
                        ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-black shadow-lg shadow-purple-500/20'
                        : 'bg-gray-900/80 text-gray-400 hover:text-white border border-gray-700/70'"
                >
                    {{ h.icon }} {{ h.name }}
                </button>
            </div>
        </div>

        <!-- محتوای تجهیزات هیروی انتخاب‌شده -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- ستون ۱ و ۲: لوداوت‌های گادمود پیشنهادی -->
            <div class="lg:col-span-2 space-y-3">
                <h3 class="text-xs font-bold text-gray-300 flex items-center gap-1.5">
                    <span>⚡</span>
                    <span>ترکیب‌های تجهیزاتی دارای بالاترین سینرژی (God-Mode Loadouts):</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div
                        v-for="(loadout, lidx) in currentHeroData.loadouts"
                        :key="lidx"
                        class="p-4 rounded-2xl bg-gray-900/80 border border-gray-700/80 hover:border-purple-500/40 transition flex flex-col justify-between"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-black text-amber-300">{{ loadout.title }}</span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold border border-emerald-500/30">
                                    {{ loadout.synergy }}٪ سینرژی
                                </span>
                            </div>

                            <div class="space-y-1.5 mb-3">
                                <div
                                    v-for="(item, iidx) in loadout.items"
                                    :key="iidx"
                                    class="text-xs text-gray-200 flex items-center justify-between p-2 rounded-xl bg-gray-950/60 border border-gray-800"
                                >
                                    <span class="font-bold flex items-center gap-1.5">
                                        <span class="text-sm">{{ item.icon }}</span>
                                        <span>{{ item.name }}</span>
                                    </span>
                                    <span
                                        class="text-[9px] px-1.5 py-0.5 rounded uppercase font-black"
                                        :class="item.rarity === 'epic' ? 'bg-purple-600 text-white' : 'bg-blue-600 text-white'"
                                    >
                                        {{ item.rarity }}
                                    </span>
                                </div>
                            </div>

                            <p class="text-[11px] text-gray-400 leading-relaxed bg-gray-950/40 p-2.5 rounded-xl border border-gray-800/80">
                                {{ loadout.tactic }}
                            </p>
                        </div>

                        <div class="pt-3 border-t border-gray-800 mt-3 flex items-center justify-between text-[10px] text-gray-400">
                            <span>سبک بازی: <strong class="text-purple-300">{{ loadout.playstyle }}</strong></span>
                            <span>اولویت: <strong class="text-amber-400">S+ Tier</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ستون ۳: ماشین حساب ارتقای سنگ‌های معدنی (Ore Budgeting) -->
            <div class="p-4 rounded-2xl bg-gray-900/90 border border-purple-500/30 flex flex-col justify-between">
                <div>
                    <h3 class="text-xs font-bold text-white mb-3 flex items-center gap-1.5">
                        <span>💎</span>
                        <span>بودجه‌بندی سنگ‌های معدنی (Blacksmith Ores):</span>
                    </h3>

                    <div class="space-y-2.5 mb-4">
                        <div class="p-3 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">🔹</span>
                                <div>
                                    <div class="text-[10px] text-gray-400">سنگ براق (Shiny):</div>
                                    <div class="text-xs font-bold text-blue-300">نیازمند روزانه استار بانس</div>
                                </div>
                            </div>
                            <span class="text-xs font-mono font-black text-white">۵۰,۰۰۰ / سقف</span>
                        </div>

                        <div class="p-3 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">🔮</span>
                                <div>
                                    <div class="text-[10px] text-gray-400">سنگ درخشان (Glowy):</div>
                                    <div class="text-xs font-bold text-purple-300">گلوگاه اصلی ارتقا</div>
                                </div>
                            </div>
                            <span class="text-xs font-mono font-black text-white">۳,۸۰۰ / سقف</span>
                        </div>

                        <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">⭐</span>
                                <div>
                                    <div class="text-[10px] text-gray-400">سنگ ستاره‌ای (Starry):</div>
                                    <div class="text-xs font-bold text-amber-300">فقط از وار و رویدادها</div>
                                </div>
                            </div>
                            <span class="text-xs font-mono font-black text-white">۴۸۰ / سقف</span>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-gray-950/70 border border-gray-800 text-[11px] text-gray-300 leading-relaxed">
                        <span class="text-amber-400 font-bold">💡 فرمول طلایی عمر:</span>
                        تجهیزات اپیک را تا لول ۱۸ با سنگ استاری ارتقا دهید تا به نقطه اوج کارایی (Break-even Point) برسید؛ سپس روی تجهیز دوم تمرکز کنید.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "EquipmentMatrixHub",
    data() {
        return {
            activeHero: 'king',
            heroesList: [
                { id: 'king', name: 'کینگ بربر', icon: '👑' },
                { id: 'queen', name: 'آرچر کویین', icon: '🏹' },
                { id: 'warden', name: 'گرند واردن', icon: '🧙‍♂️' },
                { id: 'champion', name: 'رویال چمپیون', icon: '🛡️' },
            ],
            heroesEquipmentMap: {
                king: {
                    loadouts: [
                        {
                            title: 'تخریبگر هسته و سوپر تانک (Core Breaker)',
                            synergy: 99,
                            playstyle: 'اسمش زمینی و وار ۳ ستاره',
                            tactic: 'ترکیب جاینت گانتلت برای کاهش دمیج و اسپاک بال برای شلیک به ۵ دفاعی کلیدی پشت دیوار.',
                            items: [
                                { name: 'Giant Gauntlet (دستکش غول‌پیکر)', rarity: 'epic', icon: '🥊' },
                                { name: 'Spiky Ball (توپ خاردار)', rarity: 'epic', icon: '⚽' },
                            ]
                        },
                        {
                            title: 'فانلینگ سریع و بقای حداکثری (Sustain Funnel)',
                            synergy: 94,
                            playstyle: 'کویین شارژ و پاکسازی کناره',
                            tactic: 'سبیل خون‌آشام برای بازسازی پیوسته سلامت در برابر دیوارهای طولانی با اسپل خشم داخلی.',
                            items: [
                                { name: 'Giant Gauntlet', rarity: 'epic', icon: '🥊' },
                                { name: 'Vampstache (سبیل خون‌آشام)', rarity: 'common', icon: '🩸' },
                            ]
                        }
                    ]
                },
                queen: {
                    loadouts: [
                        {
                            title: 'کویین شارژ مرگبار و یخ‌زننده (QC God-Mode)',
                            synergy: 98,
                            playstyle: 'کویین شارژ هیبرید و روت رایدر',
                            tactic: 'تیر یخی سرعت شلیک منولیت و ایگل را ۷۵٪ کاهش می‌دهد و نامرئی کویین را از مرگ نجات می‌دهد.',
                            items: [
                                { name: 'Frozen Arrow (تیر منجمد)', rarity: 'epic', icon: '❄️' },
                                { name: 'Invisibility Vial (شیشه نامرئی)', rarity: 'common', icon: '🧪' },
                            ]
                        },
                        {
                            title: 'کلون دوبل و آینه جادویی (Magic Mirror Dive)',
                            synergy: 95,
                            playstyle: 'نفوذ مستقیم به تاون‌هال',
                            tactic: 'تولید ۲ کویین دوم همزمان با قدرت شلیک کامل برای پاکسازی ۲ برابر سریع‌تر هسته.',
                            items: [
                                { name: 'Magic Mirror (آینه جادویی)', rarity: 'epic', icon: '🪞' },
                                { name: 'Frozen Arrow', rarity: 'epic', icon: '❄️' },
                            ]
                        }
                    ]
                },
                warden: {
                    loadouts: [
                        {
                            title: 'حفاظت و بازیابی ابدی ارتش (Immortal Push)',
                            synergy: 99,
                            playstyle: 'روت رایدر اسمش و بالون دراگون',
                            tactic: '۹.۵ ثانیه نامیرایی کامل برای تمام نیروها + بازیابی پیوسته سلامت تا آخرین لحظه.',
                            items: [
                                { name: 'Eternal Tome (کتاب ابدیت)', rarity: 'common', icon: '📖' },
                                { name: 'Healing Tome (کتاب شفا)', rarity: 'common', icon: '💖' },
                            ]
                        },
                        {
                            title: 'تخریب یکباره با فایربال (Fireball Nuke)',
                            synergy: 96,
                            playstyle: 'بلیمپ و حملات دوربرد',
                            tactic: 'شلیک گلوله آتشین با دمیج ۳,۵۰۰ به محوطه فشرده دفاعی‌ها همراه با ریج دائم.',
                            items: [
                                { name: 'Fireball (گلوله آتشین)', rarity: 'epic', icon: '🔥' },
                                { name: 'Rage Gem (گوهر خشم)', rarity: 'common', icon: '💎' },
                            ]
                        }
                    ]
                },
                champion: {
                    loadouts: [
                        {
                            title: 'اسپیر موشکی و رگبار دوربرد (Rocket Sniper)',
                            synergy: 97,
                            playstyle: 'بک‌اند کلین‌آپ و وار ۳ ستاره',
                            tactic: '۸ پرتاب نیزه دوربرد از پشت دیوارها با سرعت حمله فوق‌العاده بالا.',
                            items: [
                                { name: 'Rocket Spear (نیزه راکتی)', rarity: 'epic', icon: '🚀' },
                                { name: 'Haste Vial (شیشه شتاب)', rarity: 'common', icon: '⚡' },
                            ]
                        },
                        {
                            title: 'سپر جستجوگر و هاگ راش (Shield & Hog Burst)',
                            synergy: 92,
                            playstyle: 'هیبرید و انحراف اینفرنوها',
                            tactic: 'شلیک سپر با دمیج ۴ تایی همراه با احضار هاگ رایدرها برای تانک کردن دفاعی‌ها.',
                            items: [
                                { name: 'Seeking Shield (سپر جوینده)', rarity: 'common', icon: '🛡️' },
                                { name: 'Hog Rider Puppet', rarity: 'common', icon: '🐗' },
                            ]
                        }
                    ]
                }
            }
        };
    },
    computed: {
        currentHeroData() {
            return this.heroesEquipmentMap[this.activeHero] || this.heroesEquipmentMap.king;
        }
    }
};
</script>
