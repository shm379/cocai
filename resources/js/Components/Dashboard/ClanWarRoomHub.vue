<template>
    <div class="p-6 bg-gray-800/90 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-700/80 text-white relative overflow-hidden">
        <!-- نور پس‌زمینه تزئینی -->
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-red-600/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 left-1/4 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- هدر اتاق جنگ -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-b border-gray-700/80 pb-5 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-600 to-amber-600 flex items-center justify-center text-2xl shadow-lg shadow-red-900/30">
                    ⚔️
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-black tracking-tight text-white">اتاق جنگ و کال اهداف کلن (Clan War Room)</h2>
                        <span class="px-2 py-0.5 rounded-full bg-red-500/20 border border-red-500/40 text-red-400 text-xs font-bold animate-pulse">
                            LIVE
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">
                        هماهنگی هوشمند اهداف وار، جلوگیری از تداخل اتک‌ها و تحلیل احتمال ۳ ستاره با هوش مصنوعی
                    </p>
                </div>
            </div>

            <!-- انتخاب سایز وار و رفرش -->
            <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                <div class="flex bg-gray-900/80 p-1 rounded-xl border border-gray-700 text-xs">
                    <button
                        v-for="size in [15, 30, 50]"
                        :key="size"
                        @click="changeWarSize(size)"
                        :class="[
                            'px-3 py-1.5 rounded-lg font-bold transition-all',
                            warSize === size ? 'bg-red-600 text-white shadow-md' : 'text-gray-400 hover:text-white'
                        ]"
                    >
                        {{ size }}v{{ size }}
                    </button>
                </div>
                <button
                    @click="fetchWarState"
                    :disabled="loading"
                    class="p-2 bg-gray-700/60 hover:bg-gray-700 text-gray-200 rounded-xl transition border border-gray-600 flex items-center gap-1.5 text-xs"
                    title="به‌روزرسانی نقشه"
                >
                    <span :class="{ 'animate-spin': loading }">🔄</span>
                    <span class="hidden sm:inline">بروزرسانی</span>
                </button>
            </div>
        </div>

        <!-- کارت‌های آمار وضعیت وار -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="bg-gray-900/60 border border-gray-700/60 rounded-xl p-3 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center text-xl">
                    ⭐
                </div>
                <div>
                    <div class="text-[11px] text-gray-400">ستاره‌های کسب‌شده</div>
                    <div class="text-lg font-black text-amber-400">{{ totalStars }} / {{ warSize * 3 }}</div>
                </div>
            </div>

            <div class="bg-gray-900/60 border border-gray-700/60 rounded-xl p-3 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl">
                    👑
                </div>
                <div>
                    <div class="text-[11px] text-gray-400">اهداف ۳ ستاره کامل</div>
                    <div class="text-lg font-black text-emerald-400">{{ clearedCount }} / {{ warSize }}</div>
                </div>
            </div>

            <div class="bg-gray-900/60 border border-gray-700/60 rounded-xl p-3 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center text-xl">
                    🎯
                </div>
                <div>
                    <div class="text-[11px] text-gray-400">اهداف در حال رزرو</div>
                    <div class="text-lg font-black text-blue-400">{{ activeCalledCount }}</div>
                </div>
            </div>

            <div class="bg-gray-900/60 border border-gray-700/60 rounded-xl p-3 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center text-xl">
                    🏰
                </div>
                <div>
                    <div class="text-[11px] text-gray-400">اهداف آزاد</div>
                    <div class="text-lg font-black text-purple-300">{{ openCount }}</div>
                </div>
            </div>
        </div>

        <!-- گرید نقشه اهداف وار -->
        <div v-if="loading && targets.length === 0" class="py-16 text-center text-gray-400">
            <span class="inline-block animate-spin text-3xl mb-2">⏳</span>
            <p>در حال بارگذاری وضعیت اهداف کلن وار...</p>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3.5">
            <div
                v-for="target in targets"
                :key="target.target_number"
                :class="[
                    'p-4 rounded-xl border transition-all duration-200 relative flex flex-col justify-between min-h-[160px]',
                    targetCardClass(target)
                ]"
            >
                <!-- ردیف بالای کارت هدف -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-gray-950/80 border border-gray-700 flex items-center justify-center font-black text-sm text-amber-400">
                                #{{ target.target_number }}
                            </span>
                            <span class="px-2 py-0.5 rounded-md bg-gray-800 text-[11px] font-bold text-gray-300 border border-gray-700">
                                TH{{ target.target_th_level }}
                            </span>
                        </div>

                        <!-- برچسب وضعیت -->
                        <span :class="['px-2 py-0.5 rounded-md text-[10px] font-black', targetStatusBadge(target)]">
                            {{ targetStatusLabel(target) }}
                        </span>
                    </div>

                    <!-- جزئیات وضعیت کال / اتک -->
                    <div class="mt-2 text-xs">
                        <template v-if="target.status === 'cleared'">
                            <div class="text-emerald-400 font-bold flex items-center gap-1">
                                <span>⭐⭐⭐ ۳ ستاره</span>
                            </div>
                            <div class="text-[11px] text-gray-400 truncate mt-0.5">
                                توسط: {{ target.call?.caller_name }}
                            </div>
                        </template>

                        <template v-else-if="target.status === 'called'">
                            <div class="text-amber-300 font-bold truncate">
                                👤 {{ target.call?.caller_name }}
                            </div>
                            <div class="text-[10px] text-gray-400 flex items-center gap-1 mt-0.5">
                                <span>⏳ انقضا:</span>
                                <span>{{ formatRemainingTime(target.call?.expires_at) }}</span>
                            </div>
                            <div v-if="target.call?.win_probability" class="text-[10px] text-purple-300 font-bold mt-1">
                                🎯 شانس برد: {{ target.call.win_probability }}%
                            </div>
                        </template>

                        <template v-else-if="target.status === 'attacked'">
                            <div class="text-yellow-400 font-bold">
                                {{ '⭐'.repeat(target.call?.attack_result_stars || 0) }} ({{ target.call?.attack_destruction_percent }}%)
                            </div>
                            <div class="text-[10px] text-red-300 mt-0.5">
                                ⚠️ نیاز به کلین‌آپ
                            </div>
                        </template>

                        <template v-else>
                            <div class="text-gray-400 text-[11px] py-1">
                                آمادهٔ رزرو و ثبت اتک
                            </div>
                        </template>
                    </div>
                </div>

                <!-- دکمه‌های عملیاتی کارت -->
                <div class="mt-3 pt-2.5 border-t border-gray-700/50 flex items-center gap-1.5">
                    <!-- اگر آزاد است یا اتک ناموفق بوده -->
                    <button
                        v-if="target.status === 'open' || target.status === 'attacked'"
                        @click="openCallModal(target)"
                        class="flex-1 py-1.5 px-2 bg-gradient-to-r from-red-600 to-amber-600 hover:from-red-500 hover:to-amber-500 text-white rounded-lg text-xs font-bold transition shadow-sm flex items-center justify-center gap-1"
                    >
                        <span>🎯</span>
                        <span>کال هدف</span>
                    </button>

                    <!-- اگر توسط کاربر جاری رزرو شده -->
                    <template v-else-if="target.status === 'called' && isMyCall(target)">
                        <button
                            @click="openRecordModal(target)"
                            class="flex-1 py-1.5 px-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition flex items-center justify-center gap-1"
                        >
                            <span>⭐</span>
                            <span>ثبت نتیجه</span>
                        </button>
                        <button
                            @click="cancelCall(target.call.id)"
                            class="p-1.5 bg-gray-700 hover:bg-red-600 text-gray-300 hover:text-white rounded-lg text-xs transition"
                            title="لغو رزرو"
                        >
                            ✕
                        </button>
                    </template>

                    <!-- اگر توسط شخص دیگر رزرو شده -->
                    <template v-else-if="target.status === 'called'">
                        <button
                            @click="openEstimateModal(target)"
                            class="flex-1 py-1.5 px-2 bg-gray-700/80 hover:bg-gray-700 text-gray-300 rounded-lg text-xs transition flex items-center justify-center gap-1"
                        >
                            <span>🔍</span>
                            <span>تحلیل AI</span>
                        </button>
                    </template>

                    <!-- اگر ۳ ستاره شده -->
                    <template v-else-if="target.status === 'cleared'">
                        <div class="w-full py-1 text-center text-[10px] text-emerald-400 font-bold bg-emerald-950/40 rounded-lg border border-emerald-800/40">
                            تسخیر شده 👑
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- مودال ۱: رزرو و کال کردن هدف (Call Target Modal) -->
        <div v-if="showCallModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-gray-800 border border-gray-700 rounded-2xl max-w-lg w-full p-6 text-white shadow-2xl relative">
                <button @click="showCallModal = false" class="absolute top-4 left-4 text-gray-400 hover:text-white text-lg">
                    ✕
                </button>

                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">🎯</span>
                    <div>
                        <h3 class="text-lg font-black">رزرو هدف شماره #{{ selectedTarget?.target_number }}</h3>
                        <p class="text-xs text-gray-400">تحلیل هوشمند مصاف و رزرو ۲ ساعته برای جلوگیری از تداخل</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- انتخاب تاون‌هال حریف -->
                    <div>
                        <label class="block text-xs text-gray-300 font-bold mb-1.5">سطح تاون‌هال هدف حریف:</label>
                        <select
                            v-model="callForm.target_th_level"
                            @change="updateEstimation"
                            class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-amber-500"
                        >
                            <option v-for="th in [17, 16, 15, 14, 13, 12, 11, 10, 9]" :key="th" :value="th">
                                Town Hall {{ th }}
                            </option>
                        </select>
                    </div>

                    <!-- باکس تخمین ۳ ستاره و ارتش متای پیشنهادی -->
                    <div v-if="matchupEstimation" class="p-4 bg-gray-900/80 border border-purple-500/30 rounded-xl space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-purple-300 font-bold">پیش‌بینی احتمال ۳ ستاره هوش مصنوعی:</span>
                            <span class="text-sm font-black text-amber-400">{{ matchupEstimation.win_probability }}%</span>
                        </div>

                        <!-- نوار شانس پیروزی -->
                        <div class="w-full bg-gray-800 h-2.5 rounded-full overflow-hidden">
                            <div
                                class="h-full bg-gradient-to-r from-red-500 via-amber-500 to-emerald-500 transition-all duration-500"
                                :style="{ width: matchupEstimation.win_probability + '%' }"
                            ></div>
                        </div>

                        <div class="text-xs text-gray-300">
                            <span class="text-gray-400">درجه سختی:</span>
                            <span class="font-bold text-amber-300 mr-1">{{ matchupEstimation.rating_fa }}</span>
                        </div>

                        <!-- ارتش پیشنهادی -->
                        <div v-if="matchupEstimation.recommended_army" class="p-3 bg-gray-800/90 rounded-lg border border-gray-700 text-xs">
                            <div class="text-emerald-400 font-bold flex items-center gap-1 mb-1">
                                <span>⚔️ ارتش متای پیشنهادی:</span>
                                <span>{{ matchupEstimation.recommended_army.name_fa }}</span>
                            </div>
                            <p class="text-gray-300 text-[11px] leading-relaxed">
                                {{ matchupEstimation.tactical_advice }}
                            </p>
                        </div>
                    </div>

                    <!-- یادداشت تاکتیکی -->
                    <div>
                        <label class="block text-xs text-gray-300 font-bold mb-1.5">یادداشت برای هم‌کلنی‌ها (اختیاری):</label>
                        <input
                            v-model="callForm.tactical_notes"
                            type="text"
                            placeholder="مثال: من با سوپر آرچر بلیمپ از ساعت ۶ ورود می‌کنم..."
                            class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500"
                        />
                    </div>
                </div>

                <!-- دکمه تایید رزرو -->
                <div class="mt-6 flex items-center justify-end gap-2">
                    <button
                        @click="showCallModal = false"
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-xl text-xs font-bold transition"
                    >
                        انصراف
                    </button>
                    <button
                        @click="submitCall"
                        :disabled="calling"
                        class="px-5 py-2 bg-gradient-to-r from-red-600 to-amber-600 hover:from-red-500 hover:to-amber-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-red-900/30"
                    >
                        <span v-if="calling" class="animate-spin">⏳</span>
                        <span>{{ calling ? 'در حال ثبت...' : 'تایید و رزرو ۲ ساعته' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- مودال ۲: ثبت نتیجه اتک (Record Result Modal) -->
        <div v-if="showRecordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-gray-800 border border-gray-700 rounded-2xl max-w-md w-full p-6 text-white shadow-2xl relative">
                <button @click="showRecordModal = false" class="absolute top-4 left-4 text-gray-400 hover:text-white text-lg">
                    ✕
                </button>

                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">⭐</span>
                    <div>
                        <h3 class="text-lg font-black">ثبت نتیجه نبرد #{{ selectedTarget?.target_number }}</h3>
                        <p class="text-xs text-gray-400">ستاره‌ها و درصد تخریب حمله خود را وارد کنید</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- انتخاب ستاره -->
                    <div>
                        <label class="block text-xs text-gray-300 font-bold mb-2">تعداد ستاره‌های کسب‌شده:</label>
                        <div class="grid grid-cols-4 gap-2">
                            <button
                                v-for="star in [0, 1, 2, 3]"
                                :key="star"
                                type="button"
                                @click="recordForm.stars = star"
                                :class="[
                                    'py-2.5 rounded-xl font-black text-sm transition border flex flex-col items-center gap-0.5',
                                    recordForm.stars === star
                                        ? 'bg-amber-500 text-gray-950 border-amber-400 shadow-lg'
                                        : 'bg-gray-900 text-gray-300 border-gray-700 hover:border-gray-600'
                                ]"
                            >
                                <span>{{ star === 0 ? '❌' : '⭐'.repeat(star) }}</span>
                                <span class="text-[10px]">{{ star }} ستاره</span>
                            </button>
                        </div>
                    </div>

                    <!-- درصد تخریب -->
                    <div>
                        <div class="flex justify-between items-center mb-1.5">
                            <label class="text-xs text-gray-300 font-bold">درصد تخریب (Destruction):</label>
                            <span class="text-sm font-black text-amber-400">{{ recordForm.percent }}%</span>
                        </div>
                        <input
                            v-model.number="recordForm.percent"
                            type="range"
                            min="0"
                            max="100"
                            class="w-full accent-amber-500 bg-gray-700 h-2 rounded-lg cursor-pointer"
                        />
                    </div>
                </div>

                <!-- دکمه تایید نتیجه -->
                <div class="mt-6 flex items-center justify-end gap-2">
                    <button
                        @click="showRecordModal = false"
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-xl text-xs font-bold transition"
                    >
                        انصراف
                    </button>
                    <button
                        @click="submitRecordResult"
                        :disabled="recording"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-2"
                    >
                        <span v-if="recording" class="animate-spin">⏳</span>
                        <span>{{ recording ? 'در حال ثبت...' : 'ثبت نهایی اتک' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- مودال ۳: تحلیل مصاف بدون کال (Quick Estimate Modal) -->
        <div v-if="showEstimateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-gray-800 border border-gray-700 rounded-2xl max-w-md w-full p-6 text-white shadow-2xl relative">
                <button @click="showEstimateModal = false" class="absolute top-4 left-4 text-gray-400 hover:text-white text-lg">
                    ✕
                </button>

                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">🤖</span>
                    <div>
                        <h3 class="text-lg font-black">تحلیل تاکتیکی هدف #{{ selectedTarget?.target_number }}</h3>
                        <p class="text-xs text-gray-400">Town Hall {{ selectedTarget?.target_th_level }}</p>
                    </div>
                </div>

                <div v-if="matchupEstimation" class="space-y-3">
                    <div class="p-3.5 bg-gray-900 rounded-xl border border-gray-700 text-xs space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400">احتمال ۳ ستاره:</span>
                            <span class="text-amber-400 font-bold text-sm">{{ matchupEstimation.win_probability }}%</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400">درجه سختی:</span>
                            <span class="text-emerald-400 font-bold">{{ matchupEstimation.rating_fa }}</span>
                        </div>
                        <div class="pt-2 border-t border-gray-800">
                            <div class="text-purple-300 font-bold mb-1">⚔️ ترکیب پیشنهادی:</div>
                            <p class="text-gray-300 text-[11px] leading-relaxed">
                                {{ matchupEstimation.tactical_advice }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <button
                        @click="showEstimateModal = false"
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-xl text-xs font-bold transition"
                    >
                        بستن
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ClanWarRoomHub',
    props: {
        clanTag: {
            type: String,
            default: '',
        },
        playerTownHall: {
            type: Number,
            default: 15,
        },
    },
    data() {
        return {
            warSize: 15,
            targets: [],
            totalStars: 0,
            clearedCount: 0,
            loading: false,
            showCallModal: false,
            showRecordModal: false,
            showEstimateModal: false,
            selectedTarget: null,
            matchupEstimation: null,
            calling: false,
            recording: false,
            callForm: {
                target_th_level: 15,
                tactical_notes: '',
            },
            recordForm: {
                stars: 3,
                percent: 100,
            },
        };
    },
    computed: {
        activeCalledCount() {
            return this.targets.filter(t => t.status === 'called').length;
        },
        openCount() {
            return this.targets.filter(t => t.status === 'open').length;
        },
    },
    mounted() {
        this.fetchWarState();
    },
    methods: {
        async fetchWarState() {
            this.loading = true;
            try {
                const response = await window.axios.get('/api/war-room/state', {
                    params: {
                        clan_tag: this.clanTag,
                        total_targets: this.warSize,
                    }
                });

                const data = response.data;
                if (data.ok) {
                    this.targets = data.grid || [];
                    this.totalStars = data.total_stars || 0;
                    this.clearedCount = data.cleared_count || 0;
                }
            } catch (error) {
                console.error('Error fetching war room state:', error);
            } finally {
                this.loading = false;
            }
        },
        changeWarSize(size) {
            this.warSize = size;
            this.fetchWarState();
        },
        targetCardClass(target) {
            switch (target.status) {
                case 'cleared':
                    return 'bg-emerald-950/30 border-emerald-700/60 shadow-lg shadow-emerald-950/20';
                case 'called':
                    return 'bg-amber-950/30 border-amber-500/60 shadow-lg shadow-amber-950/20';
                case 'attacked':
                    return 'bg-red-950/30 border-red-700/60 shadow-lg shadow-red-950/20';
                default:
                    return 'bg-gray-900/60 border-gray-700/60 hover:border-gray-600';
            }
        },
        targetStatusBadge(target) {
            switch (target.status) {
                case 'cleared':
                    return 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40';
                case 'called':
                    return 'bg-amber-500/20 text-amber-300 border border-amber-500/40';
                case 'attacked':
                    return 'bg-red-500/20 text-red-300 border border-red-500/40';
                default:
                    return 'bg-gray-800 text-gray-400 border border-gray-700';
            }
        },
        targetStatusLabel(target) {
            switch (target.status) {
                case 'cleared':
                    return 'تسخیر شد';
                case 'called':
                    return 'رزرو شده';
                case 'attacked':
                    return 'اتک خورده';
                default:
                    return 'آزاد';
            }
        },
        isMyCall(target) {
            const user = this.$page?.props?.auth?.user;
            if (!user || !target.call) return false;
            return target.call.user_id === user.id;
        },
        formatRemainingTime(expiresAt) {
            if (!expiresAt) return '';
            const diff = new Date(expiresAt) - new Date();
            if (diff <= 0) return 'منقضی';
            const mins = Math.floor(diff / 60000);
            const hours = Math.floor(mins / 60);
            return hours > 0 ? `${hours} ساعت و ${mins % 60} دقیقه` : `${mins} دقیقه`;
        },
        async openCallModal(target) {
            this.selectedTarget = target;
            this.callForm.target_th_level = target.target_th_level || 15;
            this.callForm.tactical_notes = '';
            await this.updateEstimation();
            this.showCallModal = true;
        },
        async updateEstimation() {
            try {
                const response = await window.axios.post('/api/war-room/estimate', {
                    attacker_th: this.playerTownHall || 15,
                    defender_th: this.callForm.target_th_level,
                });
                this.matchupEstimation = response.data.estimation;
            } catch (error) {
                console.error('Error calculating estimation:', error);
            }
        },
        async submitCall() {
            if (!this.selectedTarget) return;
            this.calling = true;

            try {
                const response = await window.axios.post('/api/war-room/call', {
                    clan_tag: this.clanTag,
                    target_number: this.selectedTarget.target_number,
                    target_th_level: this.callForm.target_th_level,
                    tactical_notes: this.callForm.tactical_notes,
                });

                if (response.data.ok) {
                    this.showCallModal = false;
                    await this.fetchWarState();
                }
            } catch (error) {
                alert(error.response?.data?.message || 'خطا در رزرو هدف.');
            } finally {
                this.calling = false;
            }
        },
        openRecordModal(target) {
            this.selectedTarget = target;
            this.recordForm.stars = 3;
            this.recordForm.percent = 100;
            this.showRecordModal = true;
        },
        async submitRecordResult() {
            if (!this.selectedTarget?.call) return;
            this.recording = true;

            try {
                const response = await window.axios.post('/api/war-room/record-result', {
                    call_id: this.selectedTarget.call.id,
                    stars: this.recordForm.stars,
                    percent: this.recordForm.percent,
                });

                if (response.data.ok) {
                    this.showRecordModal = false;
                    await this.fetchWarState();
                }
            } catch (error) {
                alert(error.response?.data?.message || 'خطا در ثبت نتیجه اتک.');
            } finally {
                this.recording = false;
            }
        },
        async cancelCall(callId) {
            if (!confirm('آیا از لغو رزرو این هدف مطمئن هستید؟')) return;

            try {
                await window.axios.delete(`/api/war-room/calls/${callId}`);
                await this.fetchWarState();
            } catch (error) {
                alert(error.response?.data?.message || 'خطا در لغو رزرو.');
            }
        },
        async openEstimateModal(target) {
            this.selectedTarget = target;
            this.callForm.target_th_level = target.target_th_level;
            await this.updateEstimation();
            this.showEstimateModal = true;
        },
    },
};
</script>
