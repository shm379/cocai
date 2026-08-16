<template>
    <div>
        <!-- لیست نقشه‌ها -->
        <div v-if="maps?.data?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="map in maps.data"
                :key="map.id"
                class="bg-gray-700 p-4 rounded-lg shadow-lg relative group"
            >
                <div class="absolute top-2 left-2 z-10">
                    <FavoriteButton
                        :map-id="map.id"
                        :initial-favorite="isFavorite(map.id)"
                        @update="onFavoriteUpdate"
                    />
                </div>

                <img
                    :src="map.image_url || '/images/default_icon.webp'"
                    alt="Map Image"
                    class="w-full h-auto mb-4 rounded bg-gray-800"
                    loading="lazy"
                >
                <p class="text-sm text-white mb-2">{{ map.name }}</p>
                <p class="text-xs text-gray-400 mb-3">
                    ویو: {{ map.view_count ?? 0 }} | لایک: {{ map.like_count ?? 0 }}
                </p>
                <div class="flex items-center gap-2">
                    <CopyMapButton :link="map.copy_link" />
                    <a
                        v-if="map.copy_link"
                        :href="map.copy_link"
                        target="_blank"
                        rel="noopener"
                        class="text-xs text-red-300 hover:underline"
                    >
                        باز کردن
                    </a>
                </div>
            </div>
        </div>

        <div v-else class="text-center text-gray-400 py-12">
            نقشه‌ای یافت نشد.
        </div>

        <!-- صفحه‌بندی -->
        <div v-if="maps?.last_page > 1" class="flex justify-center mt-4 space-x-2 gap-2">
            <button
                @click.prevent="changePage(maps.current_page - 1)"
                :disabled="maps.current_page === 1"
                class="px-4 py-2 bg-gray-600 text-white rounded-lg shadow hover:bg-gray-700 disabled:opacity-50"
            >
                ⬅️ صفحه قبلی
            </button>

            <span class="px-4 py-2 bg-gray-800 text-white rounded-lg">
                {{ maps.current_page }} از {{ maps.last_page }}
            </span>

            <button
                @click.prevent="changePage(maps.current_page + 1)"
                :disabled="maps.current_page === maps.last_page"
                class="px-4 py-2 bg-gray-600 text-white rounded-lg shadow hover:bg-gray-700 disabled:opacity-50"
            >
                صفحه بعدی ➡️
            </button>
        </div>
    </div>
</template>

<script>
import CopyMapButton from "@/Components/Dashboard/CopyMapButton.vue";
import FavoriteButton from "@/Components/Dashboard/FavoriteButton.vue";

export default {
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
            localFavoriteIds: [...this.favoriteMapIds]
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
        }
    }
};
</script>
