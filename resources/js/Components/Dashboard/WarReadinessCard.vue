<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg mb-6 border border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="text-xl">⚔️</span>
                <h3 class="text-lg font-bold text-white">شاخص آمادگی وار و CWL</h3>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400">قدرت تهاجمی:</span>
                <span
                    class="px-2.5 py-0.5 rounded-full text-xs font-extrabold"
                    :class="tierBadgeClass"
                >
                    Tier {{ warReadiness.tier || 'B' }} ({{ warReadiness.offense_score || 0 }}٪)
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <!-- سبک تخصصی -->
            <div class="bg-gray-700/60 p-3 rounded-lg flex flex-col justify-between">
                <span class="text-xs text-gray-400">سبک تخصصی حمله</span>
                <span class="text-sm font-bold text-amber-400 mt-1">{{ warReadiness.preferred_style || 'متعادل' }}</span>
                <span class="text-xs text-gray-400 mt-1">بر پایه نیروها و اسپل‌های ارتقایافته</span>
            </div>

            <!-- لیگ پیشنهادی CWL -->
            <div class="bg-gray-700/60 p-3 rounded-lg flex flex-col justify-between">
                <span class="text-xs text-gray-400">لیگ مناسب Clan War League</span>
                <span class="text-sm font-bold text-cyan-400 mt-1">{{ warReadiness.recommended_cwl_league || 'Master League' }}</span>
                <span class="text-xs text-gray-400 mt-1">تضمین حداکثر مدال لیگ</span>
            </div>

            <!-- استراتژیست کلن -->
            <div class="bg-gray-700/60 p-3 rounded-lg flex flex-col justify-between">
                <span class="text-xs text-gray-400">ستاره‌های وار کسب‌شده</span>
                <span class="text-sm font-bold text-yellow-400 mt-1">⭐ {{ warStars }} ستاره</span>
                <span class="text-xs text-emerald-400 mt-1">{{ warRatingFa }}</span>
            </div>
        </div>

        <!-- نوارهای تسلط هوایی و زمینی -->
        <div class="space-y-3 pt-2 border-t border-gray-700/60">
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-300 font-medium flex items-center gap-1">
                        <span>🦅</span> تسلط بر حملات هوایی (Air Attacks)
                    </span>
                    <span class="text-blue-400 font-bold">{{ warReadiness.air_mastery || 0 }}٪</span>
                </div>
                <div class="w-full bg-gray-700 rounded-full h-2">
                    <div
                        class="bg-gradient-to-r from-blue-600 to-cyan-400 h-2 rounded-full transition-all duration-500"
                        :style="{ width: `${warReadiness.air_mastery || 0}%` }"
                    ></div>
                </div>
            </div>

            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-300 font-medium flex items-center gap-1">
                        <span>🪨</span> تسلط بر حملات زمینی (Ground Attacks)
                    </span>
                    <span class="text-emerald-400 font-bold">{{ warReadiness.ground_mastery || 0 }}٪</span>
                </div>
                <div class="w-full bg-gray-700 rounded-full h-2">
                    <div
                        class="bg-gradient-to-r from-emerald-600 to-green-400 h-2 rounded-full transition-all duration-500"
                        :style="{ width: `${warReadiness.ground_mastery || 0}%` }"
                    ></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'WarReadinessCard',
    props: {
        warReadiness: {
            type: Object,
            default: () => ({})
        },
        warStars: {
            type: Number,
            default: 0
        },
        warRatingFa: {
            type: String,
            default: ''
        }
    },
    computed: {
        tierBadgeClass() {
            const tier = this.warReadiness?.tier || 'B';
            switch (tier) {
                case 'S+':
                case 'S':
                    return 'bg-amber-500/20 text-amber-300 border border-amber-500/40';
                case 'A':
                    return 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40';
                case 'B':
                    return 'bg-blue-500/20 text-blue-300 border border-blue-500/40';
                default:
                    return 'bg-gray-600/30 text-gray-300 border border-gray-600';
            }
        }
    }
}
</script>
