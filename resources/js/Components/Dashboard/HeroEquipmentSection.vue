<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg mb-6 border border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="text-xl">🛡️</span>
                <h3 class="text-lg font-bold text-white">تجهیزات قهرمانان (Hero Equipment)</h3>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400">سینرژی تجهیزات:</span>
                <span class="text-emerald-400 font-bold text-sm">{{ equipment.synergy_score || 0 }}٪</span>
            </div>
        </div>

        <div v-if="!hasEquipment" class="text-gray-400 text-sm">
            اطلاعات تجهیزات قهرمانان هنوز ثبت نشده است.
        </div>

        <div v-else>
            <!-- لیست کارت‌های تجهیزات -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div
                    v-for="(item, idx) in equipment.items"
                    :key="idx"
                    class="bg-gray-700/60 p-3 rounded-lg border transition-all hover:bg-gray-700/80"
                    :class="item.is_equipped ? 'border-amber-500/50 bg-gray-700/70' : 'border-gray-600/40'"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-white flex items-center gap-1.5">
                                {{ item.name }}
                                <span v-if="item.tier === 'S'" class="text-amber-400 text-xs" title="اولویت S">⭐</span>
                            </span>
                            <span class="text-xs text-gray-400">{{ item.hero }}</span>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <span
                                class="text-[10px] px-1.5 py-0.5 rounded font-bold uppercase"
                                :class="item.rarity === 'epic' ? 'bg-purple-600/80 text-purple-200' : 'bg-blue-600/80 text-blue-200'"
                            >
                                {{ item.rarity === 'epic' ? 'Epic' : 'Common' }}
                            </span>
                            <span v-if="item.is_equipped" class="text-[10px] text-emerald-400 font-semibold flex items-center gap-0.5">
                                ✓ مجهز
                            </span>
                        </div>
                    </div>

                    <!-- نوار لول -->
                    <div class="mt-3">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-400">لِوِل:</span>
                            <span class="text-white font-bold">{{ item.level }} / {{ item.max_level }}</span>
                        </div>
                        <div class="w-full bg-gray-600 rounded-full h-1.5">
                            <div
                                class="h-1.5 rounded-full transition-all"
                                :class="item.rarity === 'epic' ? 'bg-gradient-to-r from-purple-500 to-indigo-400' : 'bg-gradient-to-r from-blue-500 to-cyan-400'"
                                :style="{ width: `${item.percent}%` }"
                            ></div>
                        </div>
                    </div>

                    <p v-if="item.description_fa" class="text-[11px] text-gray-400 mt-2 line-clamp-2 leading-relaxed">
                        {{ item.description_fa }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'HeroEquipmentSection',
    props: {
        equipment: {
            type: Object,
            default: () => ({})
        }
    },
    computed: {
        hasEquipment() {
            return (this.equipment?.items?.length || 0) > 0;
        }
    }
}
</script>
