<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg mb-6 border border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="text-xl">⚔️</span>
                <h3 class="text-lg font-bold text-white">ترکیب‌های برتر تجهیزات هیروها (Hero Equipment Loadouts)</h3>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/40 font-bold">
                Meta 2026
            </span>
        </div>

        <!-- تب‌های انتخاب هیرو -->
        <div class="flex flex-wrap gap-1.5 mb-4 pb-2 border-b border-gray-700/60">
            <button
                v-for="hero in heroesList"
                :key="hero.name"
                @click="selectedHero = hero.name"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5"
                :class="selectedHero === hero.name ? 'bg-amber-500 text-gray-950 shadow-md' : 'bg-gray-700/60 text-gray-300 hover:bg-gray-700'"
            >
                <span>{{ hero.icon }}</span>
                <span>{{ hero.nameFa }}</span>
            </button>
        </div>

        <!-- لوداوت‌های پیشنهادی برای هیرو انتخاب‌شده -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div
                v-for="(loadout, idx) in currentHeroLoadouts"
                :key="idx"
                class="bg-gray-700/50 hover:bg-gray-700/70 transition p-3 rounded-xl border border-gray-600/50 flex flex-col justify-between"
            >
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-amber-300">{{ loadout.titleFa }}</span>
                        <span
                            class="text-[10px] font-black px-2 py-0.5 rounded-full"
                            :class="loadout.tier === 'S+' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30'"
                        >
                            Tier {{ loadout.tier }}
                        </span>
                    </div>

                    <!-- دو قطعه تجهیزات -->
                    <div class="grid grid-cols-2 gap-2 mb-2">
                        <div
                            v-for="gear in loadout.equipment"
                            :key="gear.name"
                            class="bg-gray-800/80 p-2 rounded-lg border text-center"
                            :class="gear.rarity === 'epic' ? 'border-purple-500/60' : 'border-blue-500/40'"
                        >
                            <span class="block text-[11px] font-bold text-white truncate">{{ gear.nameFa }}</span>
                            <span class="text-[9px]" :class="gear.rarity === 'epic' ? 'text-purple-300 font-bold' : 'text-blue-300'">
                                {{ gear.rarity === 'epic' ? '★ اپیک (Epic)' : 'کامان (Common)' }}
                            </span>
                        </div>
                    </div>

                    <p class="text-xs text-gray-300 leading-relaxed mb-2">
                        {{ loadout.descriptionFa }}
                    </p>
                </div>

                <div class="pt-2 border-t border-gray-600/40 flex items-center justify-between text-[11px] text-gray-400">
                    <span>سبک حمله: <strong class="text-gray-200">{{ loadout.styleFa }}</strong></span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'HeroEquipmentLoadouts',
    data() {
        return {
            selectedHero: 'Barbarian King',
            heroesList: [
                { name: 'Barbarian King', nameFa: 'پادشاه بربر', icon: '👑' },
                { name: 'Archer Queen', nameFa: 'ملکه کماندار', icon: '🏹' },
                { name: 'Grand Warden', nameFa: 'واردن بزرگ', icon: '🧙‍♂️' },
                { name: 'Royal Champion', nameFa: 'رویال چمپیون', icon: '🛡️' },
                { name: 'Minion Prince', nameFa: 'شاهزاده مینیون', icon: '🦇' },
                { name: 'Dragon Duke', nameFa: 'دوک اژدها', icon: '🐉' }
            ],
            loadouts: {
                'Barbarian King': [
                    {
                        titleFa: 'ترکیب نفوذ به مرکز (Center Smash)',
                        tier: 'S+',
                        styleFa: 'اسمش زمینی / روت رایدر',
                        equipment: [
                            { name: 'Giant Gauntlet', nameFa: 'دستکش غول‌آسا', rarity: 'epic' },
                            { name: 'Spiky Ball', nameFa: 'توپ خاردار', rarity: 'epic' }
                        ],
                        descriptionFa: 'کینگ غول‌پیکر با مقاومت در برابر آسیب و پاکسازی دفاعی‌های متراکم مرکزی با توپ خاردار.'
                    },
                    {
                        titleFa: 'ترکیب تانک و بقای طولانی (Rage & Heal)',
                        tier: 'S',
                        styleFa: 'قیچی و پاکسازی کناره مپ (Funnel)',
                        equipment: [
                            { name: 'Giant Gauntlet', nameFa: 'دستکش غول‌آسا', rarity: 'epic' },
                            { name: 'Rage Vial', nameFa: 'معجون خشم', rarity: 'common' }
                        ],
                        descriptionFa: 'ترکیب استاندارد و مطمئن برای بقای کینگ در حملات سخت و گرفتن دمیج سنگین.'
                    }
                ],
                'Archer Queen': [
                    {
                        titleFa: 'آینه جادویی و تیر یخی (Magic Double)',
                        tier: 'S+',
                        styleFa: 'کوئین چارج فوق‌العاده سنگین',
                        equipment: [
                            { name: 'Magic Mirror', nameFa: 'آینه جادویی', rarity: 'epic' },
                            { name: 'Frozen Arrow', nameFa: 'تیر یخی', rarity: 'epic' }
                        ],
                        descriptionFa: 'کوئین بدل‌هایی با قدرت تیر یخی احضار می‌کند و سنگین‌ترین دفاعی‌ها و هیروهای حریف را فریز و ذوب می‌کند.'
                    },
                    {
                        titleFa: 'کوئین چارج کلاسیک و نامرئی',
                        tier: 'S',
                        styleFa: 'حملات لالو و هیبرید',
                        equipment: [
                            { name: 'Invisibility Vial', nameFa: 'معجون نامرئی', rarity: 'common' },
                            { name: 'Frozen Arrow', nameFa: 'تیر یخی', rarity: 'epic' }
                        ],
                        descriptionFa: 'کنترل سرعت حرکت دفاعی‌ها با فریز مداوم و فرار بحرانی از دمیج سینگل اینفرنو با نامرئی شدن.'
                    }
                ],
                'Grand Warden': [
                    {
                        titleFa: 'جاودانگی و درمان ارتش (Tome Combo)',
                        tier: 'S+',
                        styleFa: 'کلیه حملات زمینی و هوایی سنگین',
                        equipment: [
                            { name: 'Eternal Tome', nameFa: 'کتاب جاودانگی', rarity: 'common' },
                            { name: 'Healing Tome', nameFa: 'کتاب شفا', rarity: 'common' }
                        ],
                        descriptionFa: 'ترکیب جاودانه و بی‌رقیب: آسیب‌ناپذیری کامل کل ارتش تا ۹.۵ ثانیه به همراه بازیابی مداوم خون نیروها.'
                    },
                    {
                        titleFa: 'شلیک بمب آتشین واردن (Fireball Snipe)',
                        tier: 'S+',
                        styleFa: 'واردن واک تخصصی و زپ مپ',
                        equipment: [
                            { name: 'Fireball', nameFa: 'گوی آتشین', rarity: 'epic' },
                            { name: 'Rage Gem', nameFa: 'گوهر خشم', rarity: 'common' }
                        ],
                        descriptionFa: 'انفجار یک بخش بزرگ از مپ با شلیک گوی آتشین همراه با هاله خشم دائمی برای نیروها.'
                    }
                ],
                'Royal Champion': [
                    {
                        titleFa: 'شتاب و نیزه دوربرد (Haste Sniper)',
                        tier: 'S+',
                        styleFa: 'وار لیگ و پاکسازی دفاعی‌های هسته',
                        equipment: [
                            { name: 'Rocket Spear', nameFa: 'نیزه راکتی', rarity: 'epic' },
                            { name: 'Haste Vial', nameFa: 'معجون شتاب', rarity: 'common' }
                        ],
                        descriptionFa: 'چمپیون با سرعت برق‌آسا از بیرون محدوده دفاعی‌ها، تاون‌هال و اینفرنوها را با نیزه راکتی دوربرد نابود می‌کند.'
                    },
                    {
                        titleFa: 'چکمه شوک الکتریکی (Electro Shock)',
                        tier: 'S',
                        styleFa: 'پوش مستقیم با ارتش روت رایدر',
                        equipment: [
                            { name: 'Electro Boots', nameFa: 'چکمه الکتریکی', rarity: 'epic' },
                            { name: 'Haste Vial', nameFa: 'معجون شتاب', rarity: 'common' }
                        ],
                        descriptionFa: 'ایجاد شوک مداوم به ساختمان‌ها و دیوارهای اطراف با ورود سریع رویال چمپیون.'
                    }
                ],
                'Minion Prince': [
                    {
                        titleFa: 'طوفان تاریکی (Dark Storm)',
                        tier: 'S+',
                        styleFa: 'پشتیبانی هوایی و مینیون راش',
                        equipment: [
                            { name: 'Dark Crown', nameFa: 'تاج تاریکی', rarity: 'epic' },
                            { name: 'Dark Orb', nameFa: 'گوی تاریک', rarity: 'common' }
                        ],
                        descriptionFa: 'افزایش چشمگیر قدرت جادویی و پرتاب شلیک‌های نفوذگر تاریک به عمق مپ.'
                    }
                ],
                'Dragon Duke': [
                    {
                        titleFa: 'قلب آتشین و صاعقه (Dragon Tempest)',
                        tier: 'S+',
                        styleFa: 'وار تاون‌هال ۱۷ و ۱۸ هوایی',
                        equipment: [
                            { name: 'Fire Heart', nameFa: 'قلب آتشین', rarity: 'epic' },
                            { name: 'Electro Fangs', nameFa: 'نیش الکتریکی', rarity: 'epic' }
                        ],
                        descriptionFa: 'شلیک امواج آتش سراسری و صاعقه زنجیره‌ای برای فلج کردن و سوزاندن دفاعی‌های فوق پیشرفته.'
                    }
                ]
            }
        }
    },
    computed: {
        currentHeroLoadouts() {
            return this.loadouts[this.selectedHero] || []
        }
    }
}
</script>
