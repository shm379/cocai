<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg border border-gray-700">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-gray-700/60">
            <div class="flex items-center gap-3">
                <img
                    v-if="clan?.badgeUrls?.medium || clan?.badgeUrls?.small"
                    :src="clan.badgeUrls.medium || clan.badgeUrls.small"
                    alt="Clan Badge"
                    class="w-14 h-14 object-contain drop-shadow-md"
                />
                <div v-else class="w-14 h-14 rounded-xl bg-gray-700 flex items-center justify-center text-2xl">
                    🛡️
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-bold text-white">{{ clan?.name || 'بدون کلن' }}</h2>
                        <span v-if="clan?.clanLevel" class="text-xs px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40 font-bold">
                            سطح {{ clan.clanLevel }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 font-mono mt-0.5">{{ clan?.tag || '' }}</p>
                </div>
            </div>

            <!-- دکمه جزییات زنده کلن -->
            <button
                v-if="clan?.tag"
                @click="toggleClanDetails"
                :disabled="loadingClan"
                class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-blue-600 text-white font-bold text-xs hover:shadow-lg transition flex items-center gap-1.5"
            >
                <span>{{ loadingClan ? 'در حال بارگذاری...' : (showDetails ? 'بستن مشخصات کلن' : '⚡ مشخصات زنده و اعضا') }}</span>
            </button>
        </div>

        <!-- آمار فعالیت بازیکن در کلن -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 my-4 text-center">
            <div class="bg-gray-700/50 p-2.5 rounded-xl border border-gray-600/40">
                <span class="text-[11px] text-gray-400 block mb-1">نقش شما در کلن</span>
                <span class="text-xs font-bold text-amber-300">{{ playerRoleFa }}</span>
            </div>
            <div class="bg-gray-700/50 p-2.5 rounded-xl border border-gray-600/40">
                <span class="text-[11px] text-gray-400 block mb-1">مشارکت در پایتخت</span>
                <span class="text-xs font-bold text-cyan-300 font-mono">{{ formatNumber(capitalContributed) }} 🪙</span>
            </div>
            <div class="bg-gray-700/50 p-2.5 rounded-xl border border-gray-600/40">
                <span class="text-[11px] text-gray-400 block mb-1">دونیت ارسالی / دریافتی</span>
                <span class="text-xs font-bold text-emerald-300 font-mono">{{ donations }} / {{ donationsReceived }}</span>
            </div>
            <div class="bg-gray-700/50 p-2.5 rounded-xl border border-gray-600/40">
                <span class="text-[11px] text-gray-400 block mb-1">ستاره‌های وار</span>
                <span class="text-xs font-bold text-yellow-400 font-mono">⭐ {{ formatNumber(warStars) }}</span>
            </div>
        </div>

        <!-- بخش اطلاعات زنده و اعضا (آکاردئون بازشونده) -->
        <div v-if="showDetails && clanData" class="mt-4 pt-4 border-t border-gray-700/60 space-y-4">
            <!-- آمارهای کلنی -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs">
                <div class="bg-gray-900/80 p-2.5 rounded-lg border border-gray-700">
                    <span class="text-gray-400 block mb-1">استریک بردهای وار</span>
                    <span class="font-bold text-orange-400">🔥 {{ clanData.warWinStreak || 0 }} برد پیاپی</span>
                </div>
                <div class="bg-gray-900/80 p-2.5 rounded-lg border border-gray-700">
                    <span class="text-gray-400 block mb-1">لیگ وار کلن (CWL)</span>
                    <span class="font-bold text-purple-400">{{ clanData.warLeague?.name || 'تعریف‌نشده' }}</span>
                </div>
                <div class="bg-gray-900/80 p-2.5 rounded-lg border border-gray-700">
                    <span class="text-gray-400 block mb-1">سطح پایتخت (Capital)</span>
                    <span class="font-bold text-cyan-400">🏛️ لول {{ clanData.clanCapital?.capitalHallLevel || 1 }}</span>
                </div>
                <div class="bg-gray-900/80 p-2.5 rounded-lg border border-gray-700">
                    <span class="text-gray-400 block mb-1">تعداد اعضا</span>
                    <span class="font-bold text-white">👥 {{ clanData.members || (clanData.memberList?.length || 0) }} / ۵۰</span>
                </div>
            </div>

            <!-- لیست اعضای کلن -->
            <div v-if="clanData.memberList?.length" class="space-y-2">
                <h4 class="text-xs font-bold text-gray-300 mb-2">اعضای برتر کلن:</h4>
                <div class="max-h-60 overflow-y-auto space-y-1.5 pr-1">
                    <div
                        v-for="member in clanData.memberList"
                        :key="member.tag"
                        class="bg-gray-700/40 hover:bg-gray-700/80 p-2 rounded-lg flex items-center justify-between text-xs transition"
                    >
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-gray-400 w-5 text-center">{{ member.clanRank }}</span>
                            <div>
                                <span class="font-bold text-white">{{ member.name }}</span>
                                <span class="text-[10px] text-gray-400 block">{{ formatRole(member.role) }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 text-left font-mono">
                            <span class="text-amber-300 font-bold">🏆 {{ member.trophies }}</span>
                            <span class="text-gray-300 text-[11px]">⚔️ {{ member.donations }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "ClanOverview",
    props: {
        clan: {
            type: Object,
            default: () => ({})
        },
        playerRole: {
            type: String,
            default: ''
        },
        capitalContributed: {
            type: Number,
            default: 0
        },
        donations: {
            type: Number,
            default: 0
        },
        donationsReceived: {
            type: Number,
            default: 0
        },
        warStars: {
            type: Number,
            default: 0
        }
    },
    data() {
        return {
            showDetails: false,
            loadingClan: false,
            clanData: null,
        }
    },
    computed: {
        playerRoleFa() {
            return this.formatRole(this.playerRole || this.clan?.role || 'member')
        }
    },
    methods: {
        formatNumber(num) {
            return Number(num || 0).toLocaleString('fa-IR')
        },
        formatRole(role) {
            const map = {
                leader: 'رهبر (Leader) 👑',
                coLeader: 'کولیدر (Co-Leader) ⭐',
                admin: 'الدر (Elder) 🛡️',
                member: 'عضو کلن (Member)'
            }
            return map[role] || 'عضو کلن'
        },
        async toggleClanDetails() {
            if (this.showDetails) {
                this.showDetails = false
                return
            }

            if (this.clanData) {
                this.showDetails = true
                return
            }

            if (!this.clan?.tag) return

            this.loadingClan = true
            try {
                const res = await fetch(`/clash/clan?clan_tag=${encodeURIComponent(this.clan.tag)}`)
                const json = await res.json()
                if (json.data) {
                    this.clanData = json.data
                    this.showDetails = true
                }
            } catch (e) {
                console.error('Failed to load clan data:', e)
            } finally {
                this.loadingClan = false
            }
        }
    }
}
</script>
