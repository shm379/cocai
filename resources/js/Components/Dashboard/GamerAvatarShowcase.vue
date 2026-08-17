<template>
    <div class="p-4 sm:p-5 rounded-3xl bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 border border-amber-500/30 shadow-2xl relative overflow-hidden" dir="rtl">
        <!-- نور پس‌زمینه درخشان -->
        <div class="absolute -top-12 -right-12 w-48 h-48 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-12 -left-12 w-48 h-48 bg-red-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col lg:flex-row items-center justify-between gap-5 relative z-10">
            <!-- ستون مشخصات فرمانده و تروفی -->
            <div class="flex items-center gap-4 w-full lg:w-auto">
                <!-- آواتار متحرک قهرمان با افکت معلق -->
                <div class="relative group shrink-0">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-amber-400 via-orange-500 to-red-600 p-1 hero-frame-gold animate-float shadow-xl flex items-center justify-center">
                        <img
                            :src="heroAvatarUrl"
                            alt="Hero Avatar"
                            class="w-full h-full object-cover rounded-xl bg-gray-950"
                            @error="handleImgError"
                        />
                    </div>
                    <span class="absolute -bottom-2 -right-2 px-2 py-0.5 rounded-full bg-red-600 text-white text-[10px] font-black shadow-md border border-red-400">
                        TH {{ gameProfile.townHallLevel || 15 }}
                    </span>
                </div>

                <!-- اطلاعات لول و نام -->
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base sm:text-xl font-black text-white tracking-wide">
                            {{ gameProfile.name || 'فرمانده کلش' }}
                        </h2>
                        <span class="text-[11px] font-mono px-2 py-0.5 rounded-lg bg-gray-800 text-amber-300 border border-gray-700">
                            {{ gameProfile.tag || '#TAG' }}
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 mt-1.5 text-xs text-gray-300">
                        <span class="flex items-center gap-1 font-bold text-amber-400">
                            <span>🏆</span>
                            <span class="font-mono">{{ formatNumber(gameProfile.trophies || 2800) }}</span>
                            <span class="text-[10px] text-gray-400">کاپ</span>
                        </span>
                        <span>•</span>
                        <span class="flex items-center gap-1 font-bold text-red-400">
                            <span>⭐</span>
                            <span class="font-mono">{{ formatNumber(gameProfile.warStars || 500) }}</span>
                            <span class="text-[10px] text-gray-400">ستاره وار</span>
                        </span>
                        <span>•</span>
                        <span class="flex items-center gap-1 font-bold text-emerald-400">
                            <span>⚔️</span>
                            <span class="font-mono">{{ gameProfile.attackWins || 45 }}</span>
                            <span class="text-[10px] text-gray-400">پیروزی اتک</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- ردیف ویجت‌های استریک، کلن و نشان‌های بازی سوپرسل -->
            <div class="flex flex-wrap items-center justify-center lg:justify-end gap-2.5 w-full lg:w-auto">
                <!-- استریک روزانه تسک با آتش متحرک -->
                <div class="px-3.5 py-2 rounded-2xl bg-gradient-to-br from-amber-500/15 to-orange-500/15 border border-amber-500/40 flex items-center gap-2 shadow-md">
                    <span class="text-2xl animate-bounce">🔥</span>
                    <div>
                        <div class="text-[10px] text-gray-400 font-bold">زنجیره تسک روزانه:</div>
                        <div class="text-xs font-black text-amber-300 font-mono">{{ taskStreak || 1 }} روز پیاپی</div>
                    </div>
                </div>

                <!-- نشان کلن -->
                <div v-if="gameProfile.clan" class="px-3.5 py-2 rounded-2xl bg-gray-900/90 border border-gray-700/80 flex items-center gap-2.5 shadow-md">
                    <img
                        :src="gameProfile.clan.badgeUrls?.small || 'https://api-assets.clashofclans.com/badges/70/4e8o4N6U8.png'"
                        class="w-7 h-7 object-contain drop-shadow"
                    />
                    <div>
                        <div class="text-[10px] text-gray-400">کلن:</div>
                        <div class="text-xs font-bold text-white max-w-[120px] truncate">{{ gameProfile.clan.name }}</div>
                    </div>
                </div>

                <!-- وضعیت آماده‌باش هوش مصنوعی -->
                <div class="px-3 py-2 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center gap-1.5 text-xs text-emerald-300 font-bold shadow">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>۵ ایجنت آماده خدمت</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "GamerAvatarShowcase",
    props: {
        gameProfile: {
            type: Object,
            default: () => ({})
        },
        taskStreak: {
            type: Number,
            default: 1
        }
    },
    computed: {
        heroAvatarUrl() {
            const th = this.gameProfile.townHallLevel || 15;
            if (th >= 16) {
                return 'https://api-assets.clashofclans.com/leagues/288/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png';
            }
            return '/images/coc/units/Town_Hall' + Math.min(16, Math.max(1, th)) + '.png';
        }
    },
    methods: {
        formatNumber(num) {
            if (!num) return '0';
            return Number(num).toLocaleString('fa-IR');
        },
        handleImgError(e) {
            e.target.src = 'https://api-assets.clashofclans.com/leagues/288/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png';
        }
    }
};
</script>
