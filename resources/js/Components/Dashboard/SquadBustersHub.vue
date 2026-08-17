<template>
    <div class="space-y-6">
        <!-- Search Bar -->
        <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg border border-gray-700">
            <div class="flex flex-col sm:flex-row gap-2">
                <input
                    v-model="playerTag"
                    type="text"
                    placeholder="تگ بازیکن اسکواد باستر (مثال: SB991#)"
                    class="flex-1 bg-gray-900 border border-gray-700 rounded-xl px-4 py-2 text-white text-sm focus:outline-none focus:border-purple-500 font-mono"
                    @keyup.enter="fetchProfile"
                />
                <button
                    @click="fetchProfile"
                    :disabled="loading || !playerTag"
                    class="bg-purple-600 hover:bg-purple-500 text-white font-bold px-5 py-2 rounded-xl text-sm transition disabled:opacity-50 flex items-center justify-center gap-1.5"
                >
                    <span>{{ loading ? 'در حال دریافت...' : '🔍 دریافت پروفایل اسکواد' }}</span>
                </button>
            </div>
            <div v-if="error" class="mt-2 text-red-300 text-xs bg-red-500/20 p-2 rounded-lg">
                {{ error }}
            </div>
        </div>

        <!-- Profile Content -->
        <div v-if="profile" class="space-y-6">
            <!-- Header Card -->
            <div class="bg-gray-800/90 backdrop-blur p-5 rounded-2xl shadow-xl border border-gray-700">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-gray-700/60">
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center text-3xl shadow-lg">
                            ⚡
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-extrabold text-white">{{ profile.player_name }}</h2>
                                <span class="text-xs px-2.5 py-0.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/40 font-bold">
                                    پلازا لول {{ profile.plaza_level }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 font-mono mt-0.5">{{ profile.player_tag }} • {{ profile.current_world }}</p>
                        </div>
                    </div>

                    <div class="text-right">
                        <span class="text-xs text-gray-400 block mb-0.5">وضعیت تکامل اسکواد</span>
                        <span class="text-xs font-bold text-purple-300">{{ profile.synergy_title_fa }}</span>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 my-4 text-center">
                    <div class="bg-gray-700/50 p-3 rounded-xl border border-gray-600/40">
                        <span class="text-[11px] text-gray-400 block mb-1">کاپ اسکواد لیگ</span>
                        <span class="text-base font-black text-amber-400 font-mono">🏆 {{ formatNumber(profile.squad_league_trophies) }}</span>
                    </div>
                    <div class="bg-gray-700/50 p-3 rounded-xl border border-gray-600/40">
                        <span class="text-[11px] text-gray-400 block mb-1">رتبه‌های ۱ (Top 1)</span>
                        <span class="text-base font-black text-yellow-400 font-mono">👑 {{ formatNumber(profile.top1_finishes) }}</span>
                    </div>
                    <div class="bg-gray-700/50 p-3 rounded-xl border border-gray-600/40">
                        <span class="text-[11px] text-gray-400 block mb-1">انرژی پورتال</span>
                        <span class="text-base font-black text-cyan-400 font-mono">🌀 {{ formatNumber(profile.portal_energy) }}</span>
                    </div>
                    <div class="bg-gray-700/50 p-3 rounded-xl border border-gray-600/40">
                        <span class="text-[11px] text-gray-400 block mb-1">کاراکترهای اولترا</span>
                        <span class="text-base font-black text-pink-400 font-mono">⭐⭐⭐⭐ {{ profile.ultra_count }}</span>
                    </div>
                </div>
            </div>

            <!-- Characters Grid -->
            <div v-if="profile.characters?.length" class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg border border-gray-700">
                <h3 class="text-sm font-bold text-white mb-3">شخصیت‌ها و سطح تکامل (Squad Roster):</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div
                        v-for="char in profile.characters"
                        :key="char.name"
                        class="bg-gray-700/60 p-3 rounded-xl border border-gray-600/50 flex flex-col justify-between"
                    >
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-white text-xs">{{ char.name }}</span>
                            <span class="text-[10px] text-gray-400">{{ char.role }}</span>
                        </div>
                        <span class="text-[11px] font-bold text-purple-300">
                            {{ char.evolution }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'SquadBustersHub',
    data() {
        return {
            playerTag: 'DEMO',
            profile: null,
            loading: false,
            error: null,
        }
    },
    mounted() {
        this.fetchProfile()
    },
    methods: {
        formatNumber(num) {
            return Number(num || 0).toLocaleString('fa-IR')
        },
        async fetchProfile() {
            if (!this.playerTag) return
            this.loading = true
            this.error = null

            try {
                const res = await fetch(`/api/supercell/profile?game=squad_busters&player_tag=${encodeURIComponent(this.playerTag)}`)
                const json = await res.json()
                if (json.data) {
                    this.profile = json.data
                } else {
                    this.error = json.error || 'پروفایل یافت نشد.'
                }
            } catch (e) {
                this.error = 'خطا در ارتباط با سرور.'
            } finally {
                this.loading = false
            }
        }
    }
}
</script>
