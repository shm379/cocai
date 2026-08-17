<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg border border-gray-700 space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-gray-700/60">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-600 to-yellow-600 flex items-center justify-center text-2xl shadow-lg">
                    🏛️
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">راهنمای پایتخت کلن و بهینه‌ساز مدال رید (Clan Capital & Raid Medals)</h3>
                    <p class="text-xs text-gray-400">استراتژی حملات ویکند پایتخت و بهترین اولویت‌های خرید از فروشگاه تریدر</p>
                </div>
            </div>
        </div>

        <!-- Raid Medals Calculator / Trader Priority -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Trader Shop Spending Tier List -->
            <div class="bg-gray-900/70 p-3.5 rounded-xl border border-gray-700/80 space-y-2.5">
                <h4 class="text-xs font-bold text-amber-300 flex items-center gap-1.5">
                    <span>🛒</span>
                    <span>اولویت‌های خرید از تریدر با مدال‌های رید (Raid Medals):</span>
                </h4>

                <div class="space-y-2 text-xs">
                    <div
                        v-for="item in traderPriorities"
                        :key="item.name"
                        class="p-2.5 rounded-lg border flex items-center justify-between"
                        :class="item.bgClass"
                    >
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-white">{{ item.name }}</span>
                                <span class="text-[10px] px-1.5 py-0.2 rounded font-bold" :class="item.badgeClass">
                                    {{ item.tier }}
                                </span>
                            </div>
                            <span class="text-[11px] text-gray-300 block mt-0.5">{{ item.desc }}</span>
                        </div>
                        <span class="font-mono font-bold text-yellow-400 text-xs shrink-0 mr-2">
                            {{ item.cost }} 🪙
                        </span>
                    </div>
                </div>
            </div>

            <!-- Capital District Attack Meta -->
            <div class="bg-gray-900/70 p-3.5 rounded-xl border border-gray-700/80 space-y-2.5">
                <h4 class="text-xs font-bold text-cyan-300 flex items-center gap-1.5">
                    <span>⚔️</span>
                    <span>بهترین ترکیب‌های حمله به مناطق پایتخت (District Meta):</span>
                </h4>

                <div class="space-y-2 text-xs">
                    <div
                        v-for="strat in districtStrats"
                        :key="strat.district"
                        class="bg-gray-800/80 p-2.5 rounded-lg border border-gray-700/60"
                    >
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-white">{{ strat.district }}</span>
                            <span class="text-[10px] text-teal-300 font-mono">{{ strat.composition }}</span>
                        </div>
                        <p class="text-[11px] text-gray-300 leading-relaxed">{{ strat.tip }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ClanCapitalPlanner',
    data() {
        return {
            traderPriorities: [
                {
                    name: 'سنگ‌های معدن متای هیرو (Starry / Glowy Ore)',
                    tier: 'S+ Tier (حیاتی)',
                    desc: 'ارزش بسیار بالا برای ماکس کردن تجهیزات اپیک هیروها (Giant Gauntlet, Fireball, Rocket Spear)',
                    cost: '300-350',
                    bgClass: 'bg-purple-950/40 border-purple-500/40',
                    badgeClass: 'bg-purple-500/20 text-purple-300 border border-purple-500/40'
                },
                {
                    name: 'پوشن آزمایشگاه (Research Potion)',
                    tier: 'S Tier (فوق‌العاده)',
                    desc: 'صرفه‌جویی ۲۴ ساعت زمان ارتقای نیروها در لَب (خرید ۳ عدد در هر هفته مجاز است)',
                    cost: '200',
                    bgClass: 'bg-blue-950/40 border-blue-500/40',
                    badgeClass: 'bg-blue-500/20 text-blue-300 border border-blue-500/40'
                },
                {
                    name: 'پوشن حیوانات خانگی (Pet Potion)',
                    tier: 'A Tier (عالی)',
                    desc: 'شتاب ۲۴ ساعته برای لول‌آپ پت‌های کلیدی (Spirit Fox, Diggy, Phoenix)',
                    cost: '250',
                    bgClass: 'bg-emerald-950/40 border-emerald-500/40',
                    badgeClass: 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40'
                },
                {
                    name: 'حلقه‌های دیوار (Wall Rings)',
                    tier: 'B Tier (معمولی)',
                    desc: 'تنها در صورت ماکس بودن ارتش و نیاز به ارتقای سریع دیوارهای سنگین TH16/17',
                    cost: '300',
                    bgClass: 'bg-gray-800 border-gray-700',
                    badgeClass: 'bg-gray-700 text-gray-300'
                }
            ],
            districtStrats: [
                {
                    district: 'کپیتال پیک (Capital Peak)',
                    composition: 'Mountain Golem + Rage + Freeze',
                    tip: 'فرستادن مانتین گولم پشت سر اسپل خشم در مسیر مستقیم به سمت قلعه اصلی و راکت‌آرتیلری‌ها.'
                },
                {
                    district: 'گابلین ماینز و اسکلتون پارک',
                    composition: 'Super Miners + Frost Spell',
                    tip: 'سوپر ماینرها به دلیل دور زدن دیوارها و بمب انفجاری هنگام مرگ، بهترین بازدهی را دارند.'
                },
                {
                    district: 'دراگون کلیفز (Dragon Cliffs)',
                    composition: 'Sneaky Archers + Zap / Heal',
                    tip: 'کشتن سوپر دراگون‌های دفاعی با اسنیکی آرچرهای نامرئی در موج اول پیش از ورود ارتش اصلی.'
                }
            ]
        }
    }
}
</script>
