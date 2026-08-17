<template>
    <div class="space-y-6">
        <!-- Search Bar -->
        <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg border border-gray-700">
            <div class="flex flex-col sm:flex-row gap-2">
                <input
                    v-model="playerTag"
                    type="text"
                    placeholder="تگ بازیکن کلش رویال (مثال: 9PP9LYG#)"
                    class="flex-1 bg-gray-900 border border-gray-700 rounded-xl px-4 py-2 text-white text-sm focus:outline-none focus:border-blue-500 font-mono"
                    @keyup.enter="fetchProfile"
                />
                <button
                    @click="fetchProfile"
                    :disabled="loading || !playerTag"
                    class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-5 py-2 rounded-xl text-sm transition disabled:opacity-50 flex items-center justify-center gap-1.5"
                >
                    <span>{{ loading ? 'در حال دریافت...' : '🔍 دریافت پروفایل رویال' }}</span>
                </button>
            </div>
            <div v-if="error" class="mt-2 text-red-300 text-xs bg-red-500/20 p-2 rounded-lg">
                {{ error }}
            </div>
        </div>

        <!-- Profile Content -->
        <div v-if="profile" class="space-y-6">
            <!-- Header Summary Card -->
            <div class="bg-gray-800/90 backdrop-blur p-5 rounded-2xl shadow-xl border border-gray-700">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-gray-700/60">
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center text-3xl shadow-lg">
                            👑
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-extrabold text-white">{{ profile.player_name }}</h2>
                                <span class="text-xs px-2.5 py-0.5 rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/40 font-bold">
                                    Lv. {{ profile.exp_level }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 font-mono mt-0.5">{{ profile.player_tag }} • {{ profile.arena }}</p>
                        </div>
                    </div>

                    <div class="text-right">
                        <span class="text-xs text-gray-400 block mb-0.5">رتبه لیگ</span>
                        <span class="text-xs font-bold text-amber-300">{{ profile.league_title_fa }}</span>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 my-4 text-center">
                    <div class="bg-gray-700/50 p-3 rounded-xl border border-gray-600/40">
                        <span class="text-[11px] text-gray-400 block mb-1">کاپ فعلی</span>
                        <span class="text-base font-black text-amber-400 font-mono">🏆 {{ formatNumber(profile.trophies) }}</span>
                    </div>
                    <div class="bg-gray-700/50 p-3 rounded-xl border border-gray-600/40">
                        <span class="text-[11px] text-gray-400 block mb-1">بیشترین کاپ</span>
                        <span class="text-base font-black text-yellow-400 font-mono">⭐ {{ formatNumber(profile.best_trophies) }}</span>
                    </div>
                    <div class="bg-gray-700/50 p-3 rounded-xl border border-gray-600/40">
                        <span class="text-[11px] text-gray-400 block mb-1">پیروزی‌ها</span>
                        <span class="text-base font-black text-emerald-400 font-mono">⚔️ {{ formatNumber(profile.wins) }}</span>
                    </div>
                    <div class="bg-gray-700/50 p-3 rounded-xl border border-gray-600/40">
                        <span class="text-[11px] text-gray-400 block mb-1">بردهای ۳ تاج</span>
                        <span class="text-base font-black text-purple-400 font-mono">👑 {{ formatNumber(profile.three_crown_wins) }}</span>
                    </div>
                </div>

                <!-- Deck Synergy Alert -->
                <div class="bg-blue-950/40 border border-blue-500/30 p-3 rounded-xl flex items-center justify-between text-xs text-blue-200">
                    <span>تحلیل دک: <strong>{{ profile.deck_synergy_fa }}</strong></span>
                    <span class="font-mono font-bold bg-blue-900/60 px-2 py-0.5 rounded border border-blue-400/30">
                        ⚡ {{ profile.avg_elixir }} اکسیر
                    </span>
                </div>
            </div>

            <!-- Current Deck 8 Cards -->
            <div v-if="profile.current_deck?.length" class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg border border-gray-700">
                <h3 class="text-sm font-bold text-white mb-3">دک فعال مسابقات (Battle Deck):</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2.5">
                    <div
                        v-for="card in profile.current_deck"
                        :key="card.name"
                        class="bg-gray-700/60 p-2 rounded-xl border border-gray-600/50 text-center flex flex-col items-center justify-between"
                    >
                        <img
                            v-if="card.iconUrls?.medium"
                            :src="card.iconUrls.medium"
                            :alt="card.name"
                            class="w-16 h-20 object-contain drop-shadow-md mb-1"
                        />
                        <div v-else class="w-16 h-20 bg-gray-800 rounded-lg flex items-center justify-center text-xs text-gray-400 mb-1">
                            {{ card.name }}
                        </div>
                        <span class="text-[11px] font-bold text-white truncate w-full">{{ card.name }}</span>
                        <span class="text-[10px] px-1.5 py-0.2 rounded bg-blue-500/20 text-blue-300 font-mono mt-0.5">
                            Lv. {{ card.level }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ClashRoyaleHub',
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
                const res = await fetch(`/api/supercell/profile?game=clash_royale&player_tag=${encodeURIComponent(this.playerTag)}`)
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
