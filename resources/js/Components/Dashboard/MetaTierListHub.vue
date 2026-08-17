<template>
    <div class="bg-gray-800/90 backdrop-blur-xl border border-gray-700/80 rounded-3xl p-4 sm:p-6 shadow-2xl" dir="rtl">
        <!-- هدر هاب پیروزی و متا -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5 pb-4 border-b border-gray-700/70">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-red-500 via-amber-500 to-yellow-400 flex items-center justify-center text-2xl shadow-lg shadow-red-500/20 text-gray-950 font-black shrink-0">
                    🔥
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base sm:text-lg font-black text-white">تیرلیست و ارتش‌های تضمینی پیروزی متای ۲۰۲۶</h2>
                        <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-red-500/20 text-red-300 border border-red-500/40 font-bold">
                            S+ Tier گادمود
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">شگردهای سرّی ۳ ستاره وار، کمبوهای تجهیزات هیرو و کپی ۱ کلیکه به داخل بازی</p>
                </div>
            </div>

            <!-- فیلتر دسته‌بندی -->
            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar w-full sm:w-auto">
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    @click="activeCategory = cat.id"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap"
                    :class="activeCategory === cat.id
                        ? 'bg-amber-500 text-gray-950 font-black shadow'
                        : 'bg-gray-900/80 text-gray-400 hover:text-white border border-gray-700/70'"
                >
                    {{ cat.name }}
                </button>
            </div>
        </div>

        <!-- لیست کارت‌های متای برتر -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
                v-for="item in filteredItems"
                :key="item.id"
                class="bg-gray-900/80 border border-gray-700/80 hover:border-amber-500/50 rounded-2xl p-4 transition-all duration-300 flex flex-col justify-between"
            >
                <div>
                    <!-- رتبه و نرخ برد -->
                    <div class="flex items-center justify-between gap-2 mb-2.5">
                        <span
                            class="px-2.5 py-0.5 rounded-full text-[10px] font-black tracking-wider shadow"
                            :class="item.tier === 'S_PLUS'
                                ? 'bg-red-600 text-white'
                                : (item.tier === 'S' ? 'bg-amber-500 text-gray-950 font-black' : 'bg-emerald-600 text-white')"
                        >
                            {{ item.tier === 'S_PLUS' ? '🔥 S+ TIER' : (item.tier === 'S' ? '⭐ S TIER' : '✨ A TIER') }}
                        </span>

                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-bold text-emerald-400 font-mono">
                                {{ item.win_rate_percentage }}٪ نرخ ۳ ستاره
                            </span>
                            <span class="text-[10px] text-gray-400">
                                (تاون‌هال {{ item.town_hall_min }} تا {{ item.town_hall_max }})
                            </span>
                        </div>
                    </div>

                    <!-- عنوان ترکیب -->
                    <h3 class="text-sm font-bold text-white mb-2 leading-snug">
                        {{ item.title }}
                    </h3>

                    <!-- خلاصه تاکتیکی -->
                    <p class="text-xs text-gray-300 leading-relaxed mb-3.5 bg-gray-950/60 p-3 rounded-xl border border-gray-800">
                        {{ item.tactical_brief_fa }}
                    </p>

                    <!-- محتوای نیروها / تجهیزات -->
                    <div v-if="item.units_payload?.troops" class="mb-3">
                        <div class="text-[10px] font-bold text-amber-400 mb-1">نیروهای اصلی:</div>
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="(troop, tidx) in item.units_payload.troops"
                                :key="tidx"
                                class="text-[10px] px-2 py-0.5 rounded-lg bg-gray-800 text-gray-200 border border-gray-700"
                            >
                                {{ troop }}
                            </span>
                        </div>
                    </div>

                    <div v-if="item.equipment_payload" class="mb-3">
                        <div class="text-[10px] font-bold text-purple-400 mb-1">سینرژی تجهیزات هیرو:</div>
                        <div class="space-y-1">
                            <div
                                v-for="(equip, hero) in item.equipment_payload"
                                :key="hero"
                                class="text-[11px] text-gray-300 flex items-center justify-between bg-gray-800/60 px-2.5 py-1 rounded-lg border border-gray-700/60"
                            >
                                <span class="font-bold text-amber-300">{{ hero }}:</span>
                                <span class="text-gray-200">{{ Array.isArray(equip) ? equip.join(' + ') : equip }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- دکمه کپی به بازی -->
                <div class="pt-3 border-t border-gray-800 flex items-center justify-between gap-2 mt-2">
                    <span class="text-[10px] text-gray-400 flex items-center gap-1">
                        <span>📥</span>
                        <span>{{ item.copies_count }} بار کپی شده</span>
                    </span>

                    <a
                        v-if="item.army_link"
                        :href="item.army_link"
                        target="_blank"
                        rel="noopener"
                        class="px-4 py-2 rounded-xl bg-gradient-to-r from-red-600 to-amber-600 hover:from-red-500 hover:to-amber-500 text-white font-black text-xs shadow-md shadow-red-500/20 transition flex items-center gap-1.5"
                    >
                        <span>⚡</span>
                        <span>کپی مستقیم ارتش در کلش</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "MetaTierListHub",
    data() {
        return {
            items: [],
            activeCategory: 'all',
            categories: [
                { id: 'all', name: '🔥 تمام متای برتر' },
                { id: 'army', name: '⚔️ ارتش‌های S+ Tier' },
                { id: 'equipment', name: '👑 تجهیزات هیرو' },
                { id: 'attack_combo', name: '⚡ شگردهای بلیمپ' },
            ]
        };
    },
    computed: {
        filteredItems() {
            if (this.activeCategory === 'all') {
                return this.items;
            }
            return this.items.filter(item => item.category === this.activeCategory);
        }
    },
    mounted() {
        this.fetchMetaItems();
    },
    methods: {
        async fetchMetaItems() {
            try {
                const res = await fetch('/api/meta-tier-items');
                const data = await res.json();
                if (data.ok) {
                    this.items = data.items || [];
                }
            } catch (e) {
                console.error("Failed to fetch meta tier items", e);
            }
        }
    }
};
</script>
