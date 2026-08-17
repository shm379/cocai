<template>
    <div class="space-y-6">
        <!-- Search Bar -->
        <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg border border-gray-700">
            <div class="flex flex-col sm:flex-row gap-2">
                <input
                    v-model="playerTag"
                    type="text"
                    placeholder="تگ بازیکن بوم بیچ (مثال: BB881#)"
                    class="flex-1 bg-gray-900 border border-gray-700 rounded-xl px-4 py-2 text-white text-sm focus:outline-none focus:border-teal-500 font-mono"
                    @keyup.enter="fetchProfile"
                />
                <button
                    @click="fetchProfile"
                    :disabled="loading || !playerTag"
                    class="bg-teal-600 hover:bg-teal-500 text-white font-bold px-5 py-2 rounded-xl text-sm transition disabled:opacity-50 flex items-center justify-center gap-1.5"
                >
                    <span>{{ loading ? 'در حال دریافت...' : '🔍 دریافت پروفایل بوم بیچ' }}</span>
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
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-600 to-cyan-700 flex items-center justify-center text-3xl shadow-lg">
                            🏝️
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-extrabold text-white">{{ profile.player_name }}</h2>
                                <span class="text-xs px-2.5 py-0.5 rounded-full bg-teal-500/20 text-teal-300 border border-teal-500/40 font-bold">
                                    HQ {{ profile.hq_level }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 font-mono mt-0.5">{{ profile.player_tag }} • {{ profile.task_force?.name || 'بدون تسک‌فورس' }}</p>
                        </div>
                    </div>

                    <div class="text-right">
                        <span class="text-xs text-gray-400 block mb-0.5">درجه نظامی</span>
                        <span class="text-xs font-bold text-teal-300">{{ profile.rank_title_fa }}</span>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 my-4 text-center">
                    <div class="bg-gray-700/50 p-3 rounded-xl border border-gray-600/40">
                        <span class="text-[11px] text-gray-400 block mb-1">مدال‌های پیروزی (VP)</span>
                        <span class="text-base font-black text-amber-400 font-mono">🎖️ {{ formatNumber(profile.victory_points) }}</span>
                    </div>
                    <div class="bg-gray-700/50 p-3 rounded-xl border border-gray-600/40">
                        <span class="text-[11px] text-gray-400 block mb-1">لول گان‌بوت</span>
                        <span class="text-base font-black text-cyan-400 font-mono">⚓ Lv. {{ profile.gunboat_level }}</span>
                    </div>
                    <div class="bg-gray-700/50 p-3 rounded-xl border border-gray-600/40">
                        <span class="text-[11px] text-gray-400 block mb-1">سطح تجربه (Exp)</span>
                        <span class="text-base font-black text-blue-400 font-mono">Lv. {{ profile.exp_level }}</span>
                    </div>
                    <div class="bg-gray-700/50 p-3 rounded-xl border border-gray-600/40">
                        <span class="text-[11px] text-gray-400 block mb-1">امتیاز تسک‌فورس</span>
                        <span class="text-base font-black text-emerald-400 font-mono">⚔️ {{ formatNumber(profile.task_force?.forcePoints || 0) }}</span>
                    </div>
                </div>
            </div>

            <!-- Statues & Heroes -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Statues -->
                <div v-if="profile.statues?.length" class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg border border-gray-700">
                    <h3 class="text-sm font-bold text-white mb-3">مجسمه‌ها و بوست‌ها (Masterpiece Statues):</h3>
                    <div class="space-y-2">
                        <div
                            v-for="(st, idx) in profile.statues"
                            :key="idx"
                            class="bg-gray-700/50 p-2.5 rounded-lg flex items-center justify-between text-xs"
                        >
                            <span class="text-gray-200">🗿 {{ st.type }}</span>
                            <span class="font-mono font-bold text-teal-300">{{ st.bonus }}</span>
                        </div>
                    </div>
                </div>

                <!-- Heroes -->
                <div v-if="profile.heroes?.length" class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg border border-gray-700">
                    <h3 class="text-sm font-bold text-white mb-3">قهرمان‌های نبرد (Boom Heroes):</h3>
                    <div class="space-y-2">
                        <div
                            v-for="hero in profile.heroes"
                            :key="hero.name"
                            class="bg-gray-700/50 p-2.5 rounded-lg flex items-center justify-between text-xs"
                        >
                            <div>
                                <span class="font-bold text-white block">🎖️ {{ hero.name }}</span>
                                <span class="text-[10px] text-gray-400">{{ hero.ability }}</span>
                            </div>
                            <span class="text-xs font-mono font-bold text-amber-300">Lv. {{ hero.level }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'BoomBeachHub',
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
                const res = await fetch(`/api/supercell/profile?game=boom_beach&player_tag=${encodeURIComponent(this.playerTag)}`)
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
