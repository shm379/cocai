<template>
    <div class="space-y-6">
        <!-- Search Bar -->
        <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg border border-gray-700">
            <div class="flex flex-col sm:flex-row gap-2">
                <input
                    v-model="playerTag"
                    type="text"
                    placeholder="تگ بازیکن براول استارز (مثال: 80UUV8J#)"
                    class="flex-1 bg-gray-900 border border-gray-700 rounded-xl px-4 py-2 text-white text-sm focus:outline-none focus:border-yellow-500 font-mono"
                    @keyup.enter="fetchProfile"
                />
                <button
                    @click="fetchProfile"
                    :disabled="loading || !playerTag"
                    class="bg-yellow-500 hover:bg-yellow-400 text-gray-950 font-extrabold px-5 py-2 rounded-xl text-sm transition disabled:opacity-50 flex items-center justify-center gap-1.5"
                >
                    <span>{{ loading ? 'در حال دریافت...' : '🔍 دریافت پروفایل براول' }}</span>
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
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-yellow-500 to-amber-600 flex items-center justify-center text-3xl shadow-lg">
                            🌵
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-extrabold text-white">{{ profile.player_name }}</h2>
                                <span class="text-xs px-2.5 py-0.5 rounded-full bg-yellow-500/20 text-yellow-300 border border-yellow-500/40 font-bold">
                                    Lv. {{ profile.exp_level }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 font-mono mt-0.5">{{ profile.player_tag }} • {{ profile.club?.name || 'بدون کلاب' }}</p>
                        </div>
                    </div>

                    <div class="text-right">
                        <span class="text-xs text-gray-400 block mb-0.5">رنک لیگ</span>
                        <span class="text-xs font-bold text-yellow-300">{{ profile.ranked_tier_fa }}</span>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 my-4 text-center">
                    <div class="bg-gray-700/50 p-3 rounded-xl border border-gray-600/40">
                        <span class="text-[11px] text-gray-400 block mb-1">کاپ فعلی</span>
                        <span class="text-base font-black text-amber-400 font-mono">🏆 {{ formatNumber(profile.trophies) }}</span>
                    </div>
                    <div class="bg-gray-700/50 p-3 rounded-xl border border-gray-600/40">
                        <span class="text-[11px] text-gray-400 block mb-1">رکورد کاپ</span>
                        <span class="text-base font-black text-yellow-400 font-mono">⭐ {{ formatNumber(profile.highest_trophies) }}</span>
                    </div>
                    <div class="bg-gray-700/50 p-3 rounded-xl border border-gray-600/40">
                        <span class="text-[11px] text-gray-400 block mb-1">بردهای تیمی 3v3</span>
                        <span class="text-base font-black text-emerald-400 font-mono">🔥 {{ formatNumber(profile.v3v3_victories) }}</span>
                    </div>
                    <div class="bg-gray-700/50 p-3 rounded-xl border border-gray-600/40">
                        <span class="text-[11px] text-gray-400 block mb-1">براولرهای پاور ۱۱</span>
                        <span class="text-base font-black text-purple-400 font-mono">⚡ {{ profile.p11_brawlers_count }}</span>
                    </div>
                </div>

                <!-- Mastery Status -->
                <div class="bg-yellow-950/30 border border-yellow-500/30 p-3 rounded-xl flex items-center justify-between text-xs text-yellow-200">
                    <span>عنوان مستری: <strong>{{ profile.mastery_title_fa }}</strong></span>
                    <span class="text-gray-400">سولو: {{ formatNumber(profile.solo_victories) }} | دو نفره: {{ formatNumber(profile.duo_victories) }}</span>
                </div>
            </div>

            <!-- Top Brawlers Grid -->
            <div v-if="profile.brawlers?.length" class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg border border-gray-700">
                <h3 class="text-sm font-bold text-white mb-3">براولرهای برتر و رنک‌ها (Top Brawlers):</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div
                        v-for="brawler in profile.brawlers"
                        :key="brawler.name"
                        class="bg-gray-700/60 p-3 rounded-xl border border-gray-600/50 flex items-center justify-between"
                    >
                        <div>
                            <span class="font-bold text-white text-xs block">{{ brawler.name }}</span>
                            <span class="text-[10px] text-gray-400">رنک {{ brawler.rank }} • پاور {{ brawler.power }}</span>
                        </div>
                        <span class="text-xs font-black text-amber-400 font-mono">
                            🏆 {{ brawler.trophies }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'BrawlStarsHub',
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
                const res = await fetch(`/api/supercell/profile?game=brawl_stars&player_tag=${encodeURIComponent(this.playerTag)}`)
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
