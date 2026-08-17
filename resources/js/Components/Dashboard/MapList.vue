<template>
    <div>
        <!-- لیست نقشه‌ها -->
        <div v-if="maps?.data?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" dir="rtl">
            <div
                v-for="map in maps.data"
                :key="map.id"
                class="bg-gray-800/90 border border-gray-700/80 hover:border-amber-500/50 rounded-3xl p-4 shadow-xl transition-all duration-300 relative group flex flex-col justify-between"
            >
                <!-- دکمه علاقه‌مندی شناور -->
                <div class="absolute top-6 left-6 z-10">
                    <FavoriteButton
                        :map-id="map.id"
                        :initial-favorite="isFavorite(map.id)"
                        @update="onFavoriteUpdate"
                    />
                </div>

                <div>
                    <!-- تصویر مپ با پیش‌نمایش بزرگ -->
                    <div class="relative overflow-hidden rounded-2xl bg-gray-950 aspect-video mb-3.5 border border-gray-700/60 group-hover:shadow-lg group-hover:shadow-amber-500/10 transition">
                        <img
                            :src="map.image_url || '/images/default_icon.webp'"
                            :alt="map.name"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 cursor-pointer"
                            loading="lazy"
                            @click="previewImage = map.image_url"
                            @error="handleImgError"
                        />
                        <div class="absolute bottom-2 right-2 px-2 py-0.5 rounded-lg bg-gray-950/80 backdrop-blur-md text-[10px] font-bold text-amber-300 border border-amber-500/30">
                            🛡️ مپ متای ۲۰۲۶
                        </div>
                    </div>

                    <!-- نام و عنوان مپ -->
                    <h3 class="text-sm font-bold text-white mb-2 line-clamp-2 leading-snug">
                        {{ map.name }}
                    </h3>

                    <!-- آمار بازدید، لایک و دانلود -->
                    <div class="flex items-center gap-3 text-[11px] text-gray-400 mb-4">
                        <span class="flex items-center gap-1">
                            <span>👁️</span>
                            <span>{{ formatNumber(map.view_count) }}</span>
                        </span>
                        <span class="flex items-center gap-1">
                            <span>❤️</span>
                            <span>{{ formatNumber(map.like_count) }}</span>
                        </span>
                        <span v-if="map.download_count" class="flex items-center gap-1">
                            <span>📥</span>
                            <span>{{ formatNumber(map.download_count) }} کپی</span>
                        </span>
                    </div>
                </div>

                <!-- دکمه‌های عملیات -->
                <div class="flex items-center gap-2 pt-2 border-t border-gray-700/60">
                    <CopyMapButton :link="map.copy_link" class="flex-1" />
                    <a
                        v-if="map.copy_link"
                        :href="map.copy_link"
                        target="_blank"
                        rel="noopener"
                        class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-red-600 to-amber-600 hover:from-red-500 hover:to-amber-500 text-white text-xs font-black shadow-md shadow-red-500/20 transition flex items-center gap-1 shrink-0"
                    >
                        <span>🏰</span>
                        <span>کپی در بازی</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- پیام عدم وجود نقشه -->
        <div v-else class="text-center py-16 px-4 bg-gray-800/50 border border-gray-700/60 rounded-3xl" dir="rtl">
            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gray-700/50 flex items-center justify-center text-3xl">
                🗺️
            </div>
            <h3 class="text-base font-bold text-white mb-1">نقشه‌ای در این سطح یافت نشد</h3>
            <p class="text-xs text-gray-400">تاون‌هال یا بیلدرهال دیگری را از نوار بالا انتخاب کنید.</p>
        </div>

        <!-- صفحه‌بندی هوشمند -->
        <div v-if="maps?.last_page > 1" class="flex justify-center items-center mt-6 gap-2" dir="rtl">
            <button
                @click.prevent="changePage(maps.current_page - 1)"
                :disabled="maps.current_page === 1"
                class="px-4 py-2 bg-gray-800 border border-gray-700 text-white rounded-xl text-xs font-bold shadow hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
            >
                ← صفحه قبلی
            </button>

            <span class="px-4 py-2 bg-gray-900 border border-amber-500/40 text-amber-300 rounded-xl text-xs font-black font-mono">
                {{ maps.current_page }} از {{ maps.last_page }}
            </span>

            <button
                @click.prevent="changePage(maps.current_page + 1)"
                :disabled="maps.current_page === maps.last_page"
                class="px-4 py-2 bg-gray-800 border border-gray-700 text-white rounded-xl text-xs font-bold shadow hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
            >
                صفحه بعدی →
            </button>
        </div>

        <!-- مودال پیش‌نمایش بزرگ نقشه -->
        <div
            v-if="previewImage"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/90 backdrop-blur-md"
            @click.self="previewImage = null"
        >
            <div class="relative max-w-4xl w-full bg-gray-900 rounded-3xl p-3 border border-gray-700 shadow-2xl">
                <button
                    @click="previewImage = null"
                    class="absolute top-4 left-4 w-9 h-9 rounded-full bg-gray-800 hover:bg-gray-700 text-white flex items-center justify-center font-bold z-10 transition"
                >
                    ✕
                </button>
                <img :src="previewImage" class="w-full h-auto rounded-2xl max-h-[80vh] object-contain mx-auto" />
            </div>
        </div>
    </div>
</template>

<script>
import CopyMapButton from "@/Components/Dashboard/CopyMapButton.vue";
import FavoriteButton from "@/Components/Dashboard/FavoriteButton.vue";

export default {
    name: 'MapList',
    components: {
        CopyMapButton,
        FavoriteButton
    },
    props: {
        maps: Object,
        pageKey: String,
        favoriteMapIds: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            localFavoriteIds: [...this.favoriteMapIds],
            previewImage: null,
        }
    },
    watch: {
        favoriteMapIds(newVal) {
            this.localFavoriteIds = [...newVal];
        }
    },
    methods: {
        isFavorite(mapId) {
            return this.localFavoriteIds.includes(mapId);
        },
        onFavoriteUpdate({ mapId, isFavorite }) {
            if (isFavorite) {
                if (!this.localFavoriteIds.includes(mapId)) {
                    this.localFavoriteIds.push(mapId);
                }
            } else {
                this.localFavoriteIds = this.localFavoriteIds.filter(id => id !== mapId);
            }
        },
        changePage(newPage) {
            this.$emit('pageChange', this.pageKey, newPage);
        },
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
