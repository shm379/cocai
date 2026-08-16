<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div class="bg-gray-800 border border-gray-700 rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-700 bg-gray-900/50">
                <div class="flex items-center gap-2">
                    <span class="text-xl">📊</span>
                    <h3 class="text-lg font-bold text-white">مقایسه دو بازیکن کلش اف کلنز</h3>
                </div>
                <button @click="$emit('close')" class="text-gray-400 hover:text-white text-xl">✕</button>
            </div>

            <!-- Body -->
            <div class="p-4 overflow-y-auto space-y-4">
                <!-- Search second tag -->
                <div class="flex gap-2">
                    <input
                        v-model="opponentTag"
                        type="text"
                        placeholder="تگ بازیکن دوم (مثال: 2PP0J9VL#)"
                        class="flex-1 bg-gray-900 border border-gray-700 rounded-xl px-4 py-2 text-white text-sm focus:outline-none focus:border-amber-500"
                        @keyup.enter="comparePlayers"
                    />
                    <button
                        @click="comparePlayers"
                        :disabled="loading || !opponentTag"
                        class="bg-gradient-to-r from-amber-500 to-yellow-600 text-gray-950 font-bold px-4 py-2 rounded-xl text-sm transition hover:opacity-90 disabled:opacity-50"
                    >
                        {{ loading ? 'در حال دریافت...' : 'مقایسه' }}
                    </button>
                </div>

                <div v-if="error" class="bg-red-500/20 border border-red-500/40 text-red-300 text-xs p-3 rounded-xl">
                    {{ error }}
                </div>

                <!-- Comparison Table -->
                <div v-if="opponentData" class="space-y-4">
                    <div class="grid grid-cols-3 gap-2 text-center text-xs font-bold border-b border-gray-700 pb-2">
                        <div class="text-amber-400">{{ myData.name }} (شما)</div>
                        <div class="text-gray-400">شاخص</div>
                        <div class="text-cyan-400">{{ opponentData.name }} (حریف)</div>
                    </div>

                    <div class="space-y-2 text-xs">
                        <!-- تاون هال -->
                        <div class="grid grid-cols-3 gap-2 items-center bg-gray-700/40 p-2.5 rounded-lg text-center">
                            <span class="font-bold text-white">TH {{ myData.townHallLevel }}</span>
                            <span class="text-gray-400">تاون‌هال</span>
                            <span class="font-bold text-white">TH {{ opponentData.townHallLevel }}</span>
                        </div>

                        <!-- کاپ -->
                        <div class="grid grid-cols-3 gap-2 items-center bg-gray-700/40 p-2.5 rounded-lg text-center">
                            <span class="font-bold text-amber-300">🏆 {{ myData.trophies }}</span>
                            <span class="text-gray-400">کاپ فعلی</span>
                            <span class="font-bold text-cyan-300">🏆 {{ opponentData.trophies }}</span>
                        </div>

                        <!-- ستاره‌های وار -->
                        <div class="grid grid-cols-3 gap-2 items-center bg-gray-700/40 p-2.5 rounded-lg text-center">
                            <span class="font-bold text-yellow-400">⭐ {{ myData.warStars }}</span>
                            <span class="text-gray-400">ستاره‌های وار</span>
                            <span class="font-bold text-yellow-400">⭐ {{ opponentData.warStars }}</span>
                        </div>

                        <!-- لول اکانت -->
                        <div class="grid grid-cols-3 gap-2 items-center bg-gray-700/40 p-2.5 rounded-lg text-center">
                            <span class="font-bold text-blue-400">Lv. {{ myData.expLevel }}</span>
                            <span class="text-gray-400">سطح اکانت (Exp)</span>
                            <span class="font-bold text-blue-400">Lv. {{ opponentData.expLevel }}</span>
                        </div>

                        <!-- کلن -->
                        <div class="grid grid-cols-3 gap-2 items-center bg-gray-700/40 p-2.5 rounded-lg text-center">
                            <span class="font-semibold text-gray-200 truncate">{{ myData.clan?.name || 'بدون کلن' }}</span>
                            <span class="text-gray-400">کلن</span>
                            <span class="font-semibold text-gray-200 truncate">{{ opponentData.clan?.name || 'بدون کلن' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'PlayerComparisonModal',
    props: {
        show: {
            type: Boolean,
            default: false
        },
        myData: {
            type: Object,
            default: () => ({})
        }
    },
    data() {
        return {
            opponentTag: '',
            opponentData: null,
            loading: false,
            error: null
        }
    },
    methods: {
        async comparePlayers() {
            if (!this.opponentTag) return;
            this.loading = true;
            this.error = null;

            try {
                const res = await fetch(`/clash/player?player_tag=${encodeURIComponent(this.opponentTag)}`);
                const json = await res.json();
                if (json.data) {
                    this.opponentData = json.data;
                } else {
                    this.error = json.error || 'اطلاعات بازیکن یافت نشد.';
                }
            } catch (e) {
                this.error = 'خطا در برقراری ارتباط با سرور.';
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
