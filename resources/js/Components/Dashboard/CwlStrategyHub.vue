<template>
    <div class="bg-gray-800/90 backdrop-blur-xl border border-gray-700/80 rounded-3xl p-4 sm:p-6 shadow-2xl" dir="rtl">
        <!-- هدر اتاق جنگ CWL -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5 pb-4 border-b border-gray-700/70">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 via-orange-600 to-red-600 flex items-center justify-center text-2xl shadow-lg shadow-orange-500/20 text-white font-black shrink-0">
                    🎖️
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base sm:text-lg font-black text-white">اتاق استراتژی و پیش‌بینی وارلیگ (CWL War Room)</h2>
                        <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40 font-bold">
                            تخمین مدال و ستاره
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">محاسبه هوشمند مدال‌های لیگ، تارگت‌های پیشنهادی ۷ روزه و فرمول مدال پاداش</p>
                </div>
            </div>

            <!-- انتخاب لیگ کلن -->
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select
                    v-model="selectedLeague"
                    @change="fetchCwlData"
                    class="w-full sm:w-auto bg-gray-900/90 border border-gray-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:outline-none focus:border-amber-500 transition"
                >
                    <option value="champions_1">🏆 چمپیون ۱ (Champion I)</option>
                    <option value="champions_2">🏆 چمپیون ۲ (Champion II)</option>
                    <option value="champions_3">🏆 چمپیون ۳ (Champion III)</option>
                    <option value="masters_1">⭐ مستر ۱ (Master I)</option>
                    <option value="masters_2">⭐ مستر ۲ (Master II)</option>
                    <option value="masters_3">⭐ مستر ۳ (Master III)</option>
                    <option value="crystal_1">✨ کریستال ۱ (Crystal I)</option>
                </select>
            </div>
        </div>

        <div v-if="cwlData" class="space-y-4">
            <!-- بنر خلاصه پیش‌بینی مدال و ستاره -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="p-4 rounded-2xl bg-gray-900/90 border border-amber-500/30 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] text-gray-400">تخمین مدال لیگ شما:</div>
                        <div class="text-xl font-black text-amber-400 font-mono mt-0.5">
                            {{ cwlData.predicted_medals }} <span class="text-xs text-gray-400">مدال</span>
                        </div>
                    </div>
                    <span class="text-3xl">🏅</span>
                </div>

                <div class="p-4 rounded-2xl bg-gray-900/90 border border-purple-500/30 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] text-gray-400">تخمین مجموع ستاره (۷ روز):</div>
                        <div class="text-xl font-black text-purple-400 font-mono mt-0.5">
                            {{ cwlData.predicted_stars_total }} <span class="text-xs text-gray-400">/ ۲۱ ستاره</span>
                        </div>
                    </div>
                    <span class="text-3xl">⭐</span>
                </div>

                <div class="p-4 rounded-2xl bg-gray-900/90 border border-emerald-500/30 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] text-gray-400">سقف مدال لیگ انتخابی:</div>
                        <div class="text-xl font-black text-emerald-400 font-mono mt-0.5">
                            {{ cwlData.max_possible_medals }} <span class="text-xs text-gray-400">مدال</span>
                        </div>
                    </div>
                    <span class="text-3xl">🎁</span>
                </div>
            </div>

            <!-- برنامه تاکتیکی ۷ روزه مسابقات -->
            <div class="space-y-2.5">
                <h4 class="text-xs font-bold text-gray-300">نقشه راه و برنامه تارگت‌های ۷ روزه وارلیگ:</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div
                        v-for="(matchup, idx) in cwlData.recommended_matchups"
                        :key="idx"
                        class="p-3.5 rounded-2xl bg-gray-900/80 border border-gray-700/80 hover:border-amber-500/40 transition"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-black text-amber-300">{{ matchup.day }}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-400 font-bold border border-amber-500/20">
                                {{ matchup.target_role }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-300 leading-relaxed">{{ matchup.tactic }}</p>
                    </div>
                </div>
            </div>

            <!-- راهنمای شفاف توزیع مدال پاداش (Bonus Medals) -->
            <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-xs text-amber-200">
                <span class="font-bold">⚖️ راهنمای عادلانه لیدرها:</span> {{ cwlData.bonus_medal_formula_fa }}
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "CwlStrategyHub",
    data() {
        return {
            selectedLeague: 'masters_1',
            cwlData: null,
            loading: false,
        };
    },
    mounted() {
        this.fetchCwlData();
    },
    methods: {
        async fetchCwlData() {
            this.loading = true;
            try {
                const res = await fetch(`/api/ai/cwl-analysis?league=${this.selectedLeague}`);
                const data = await res.json();
                if (data.ok) {
                    this.cwlData = data;
                }
            } catch (e) {
                console.error("CWL data fetch failed", e);
            } finally {
                this.loading = false;
            }
        }
    }
};
</script>
