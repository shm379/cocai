<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg border border-gray-700 space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-gray-700/60">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-600 to-blue-600 flex items-center justify-center text-2xl shadow-lg">
                    📋
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">مولد لینک مستقیم ارتش (In-Game Army Link Generator)</h3>
                    <p class="text-xs text-gray-400">کپی و ارسال مستقیم ارتش به بازی کلش اف کلنز با ۱ کلیک</p>
                </div>
            </div>
        </div>

        <!-- Army Presets Selector -->
        <div class="space-y-3">
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="preset in presets"
                    :key="preset.name"
                    @click="selectPreset(preset)"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition border"
                    :class="selectedPreset?.name === preset.name ? 'bg-indigo-600 border-indigo-500 text-white shadow' : 'bg-gray-700/60 border-gray-600/60 text-gray-300 hover:bg-gray-700'"
                >
                    {{ preset.name }}
                </button>
            </div>

            <!-- Selected Army Details & Link -->
            <div v-if="selectedPreset" class="bg-gray-900/70 p-3.5 rounded-xl border border-gray-700/80 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <span class="text-xs font-bold text-white block">{{ selectedPreset.name }} (تاون‌هال {{ selectedPreset.th }})</span>
                        <span class="text-[11px] text-gray-400">{{ selectedPreset.troopsSummary }}</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            @click="copyLink"
                            class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow transition flex items-center gap-1"
                        >
                            <span>{{ copied ? '✅ کپی شد!' : '📋 کپی لینک ارتش' }}</span>
                        </button>
                        <a
                            :href="selectedPreset.url"
                            target="_blank"
                            class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow transition flex items-center gap-1"
                        >
                            <span>🚀 باز کردن در کلش</span>
                        </a>
                    </div>
                </div>

                <div class="bg-gray-950 p-2 rounded-lg border border-gray-800 text-[11px] font-mono text-gray-300 break-all select-all">
                    {{ selectedPreset.url }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ArmyLinkGenerator',
    data() {
        return {
            copied: false,
            selectedPreset: null,
            presets: [
                {
                    name: 'Super Yeti Sky Smash (TH18/17)',
                    th: 18,
                    troopsSummary: '4x Super Yeti, 3x Root Rider, 4x Healer, 8x Balloon, 3x Rage, 2x Freeze, 1x Overgrowth',
                    url: 'https://link.clashofclans.com/en?action=CopyArmy&army=u4x107-3x110-4x7-8x5s3x2-2x5-1x70'
                },
                {
                    name: 'Root Rider Valkyrie Overgrowth (TH16)',
                    th: 16,
                    troopsSummary: '6x Root Rider, 10x Valkyrie, 3x Ice Golem, 5x Bowler, 2x Rage, 3x Freeze, 2x Overgrowth',
                    url: 'https://link.clashofclans.com/en?action=CopyArmy&army=u6x110-10x12-3x58-5x22s2x2-3x5-2x70'
                },
                {
                    name: 'Super Archer Blimp Hydra (TH15)',
                    th: 15,
                    troopsSummary: '5x Dragon, 4x Dragon Rider, 6x Balloon, 4x Super Archer (Blimp), 1x Clone, 5x Invisibility',
                    url: 'https://link.clashofclans.com/en?action=CopyArmy&army=u5x8-4x65-6x5s1x16-5x35-1x2'
                },
                {
                    name: 'Queen Charge Hybrid Miners (TH13/14)',
                    th: 14,
                    troopsSummary: '5x Healer, 14x Miner, 12x Hog Rider, 2x Baby Dragon, 3x Heal, 2x Rage, 1x Poison',
                    url: 'https://link.clashofclans.com/en?action=CopyArmy&army=u5x7-14x24-12x11-2x23s3x1-2x2-1x9'
                },
                {
                    name: 'Zap Witch Golem Wave (TH12/11)',
                    th: 12,
                    troopsSummary: '3x Golem, 14x Witch, 8x Lightning Spell, 2x Earthquake, 1x Freeze',
                    url: 'https://link.clashofclans.com/en?action=CopyArmy&army=u3x13-14x15s8x0-2x10-1x5'
                },
                {
                    name: 'Sneaky Goblin Farm Master (All THs)',
                    th: 11,
                    troopsSummary: '70x Sneaky Goblin, 8x Super Wall Breaker, 4x Jump Spell, 3x Invisibility Spell',
                    url: 'https://link.clashofclans.com/en?action=CopyArmy&army=u70x55-8x57s4x4-3x35'
                }
            ]
        }
    },
    mounted() {
        this.selectedPreset = this.presets[0]
    },
    methods: {
        selectPreset(preset) {
            this.selectedPreset = preset
            this.copied = false
        },
        async copyLink() {
            if (!this.selectedPreset?.url) return
            try {
                await navigator.clipboard.writeText(this.selectedPreset.url)
                this.copied = true
                setTimeout(() => {
                    this.copied = false
                }, 2500)
            } catch (e) {
                console.error(e)
            }
        }
    }
}
</script>
