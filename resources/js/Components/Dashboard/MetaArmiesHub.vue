<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg mb-6 border border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="text-xl">🏹</span>
                <h3 class="text-lg font-bold text-white">استراتژی‌ها و ارتش‌های متای تاون‌هال {{ townHall }}</h3>
            </div>
            <div class="flex gap-1.5">
                <button
                    @click="activeArmyType = 'war'"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition"
                    :class="activeArmyType === 'war' ? 'bg-red-600 text-white shadow' : 'bg-gray-700 text-gray-400 hover:text-white'"
                >
                    ⚔️ ارتش‌های وار (War)
                </button>
                <button
                    @click="activeArmyType = 'farm'"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition"
                    :class="activeArmyType === 'farm' ? 'bg-amber-600 text-white shadow' : 'bg-gray-700 text-gray-400 hover:text-white'"
                >
                    💰 ارتش‌های فارم (Farm)
                </button>
            </div>
        </div>

        <div v-if="armiesList.length" class="space-y-3">
            <div
                v-for="(army, idx) in armiesList"
                :key="idx"
                class="bg-gray-700/50 hover:bg-gray-700/80 transition p-3.5 rounded-xl border border-gray-600/50"
            >
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div>
                        <h4 class="text-sm font-bold text-amber-300">{{ army.name_fa }}</h4>
                        <span class="text-[11px] text-gray-400">{{ army.name }}</span>
                    </div>
                    <button
                        @click="copyStrategy(army)"
                        class="px-2.5 py-1 rounded-lg bg-gray-800 hover:bg-gray-900 border border-gray-600 text-[11px] text-gray-200 transition flex items-center gap-1 shrink-0"
                    >
                        <span>{{ copiedIdx === idx ? '✓ کپی شد' : '📋 کپی راهنما' }}</span>
                    </button>
                </div>

                <!-- نیروها و طلسم‌ها -->
                <div class="flex flex-wrap gap-1.5 mb-2.5">
                    <span
                        v-for="unit in army.units"
                        :key="unit"
                        class="px-2 py-0.5 rounded-md bg-blue-500/20 text-blue-300 border border-blue-500/30 text-[10px] font-semibold"
                    >
                        🧙 {{ unit }}
                    </span>
                    <span
                        v-for="spell in army.spells"
                        :key="spell"
                        class="px-2 py-0.5 rounded-md bg-purple-500/20 text-purple-300 border border-purple-500/30 text-[10px] font-semibold"
                    >
                        ✨ {{ spell }}
                    </span>
                </div>

                <!-- راهنمای حمله -->
                <div class="bg-gray-800/80 p-2.5 rounded-lg border border-gray-700/60 text-xs text-gray-300 leading-relaxed">
                    <p>{{ army.guide_fa }}</p>
                </div>
            </div>
        </div>

        <div v-else class="text-center text-xs text-gray-400 py-6">
            استراتژی ثبت‌شده‌ای برای این تاون‌هال یافت نشد.
        </div>
    </div>
</template>

<script>
export default {
    name: 'MetaArmiesHub',
    props: {
        townHall: {
            type: Number,
            default: 12
        },
        armies: {
            type: Object,
            default: () => ({ war: [], farm: [] })
        }
    },
    data() {
        return {
            activeArmyType: 'war',
            copiedIdx: null
        }
    },
    computed: {
        armiesList() {
            return this.armies?.[this.activeArmyType] || []
        }
    },
    methods: {
        async copyStrategy(army) {
            const text = `استراتژی ${army.name_fa} (TH${this.townHall}):\nنیروها: ${army.units.join('، ')}\nاسپل‌ها: ${army.spells.join('، ')}\nراهنما: ${army.guide_fa}`
            try {
                await navigator.clipboard.writeText(text)
                this.copiedIdx = this.armiesList.indexOf(army)
                setTimeout(() => {
                    this.copiedIdx = null
                }, 2000)
            } catch (e) {
                console.error(e)
            }
        }
    }
}
</script>
