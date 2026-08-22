<template>
    <div class="bg-gray-800/90 backdrop-blur-xl border border-gray-700/80 rounded-3xl p-4 sm:p-6 shadow-2xl" dir="rtl">
        <!-- هدر -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5 pb-4 border-b border-gray-700/70">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-pink-600 via-rose-500 to-amber-400 flex items-center justify-center text-2xl shadow-lg shadow-pink-500/20 text-gray-950 font-black shrink-0">
                    ❤️
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-black text-white">بیس‌های نشان‌شده من</h2>
                    <p class="text-xs text-gray-400 mt-0.5">
                        یادداشت، برچسب و مدیریت نقشه‌های ذخیره‌شده
                    </p>
                </div>
            </div>

            <!-- فیلتر برچسب -->
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select
                    v-model="selectedTag"
                    @change="fetchFavorites(1)"
                    class="flex-1 sm:w-48 bg-gray-900/90 border border-gray-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-pink-500 transition"
                >
                    <option value="">همه برچسب‌ها</option>
                    <option v-for="tag in allTags" :key="tag" :value="tag">{{ tag }}</option>
                </select>
                <button
                    v-if="selectedTag"
                    @click="selectedTag = ''; fetchFavorites(1)"
                    class="px-3 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-xl text-xs font-bold transition"
                >
                    ✕
                </button>
            </div>
        </div>

        <!-- وضعیت بارگذاری -->
        <div v-if="loading && maps.data.length === 0" class="py-16 text-center text-gray-400">
            <span class="inline-block animate-spin text-3xl mb-2">⏳</span>
            <p>در حال بارگذاری بیس‌های نشان‌شده...</p>
        </div>

        <!-- لیست خالی -->
        <div v-else-if="!loading && maps.data.length === 0" class="text-center py-16 px-4 bg-gray-900/50 border border-gray-700/60 rounded-3xl">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-800 flex items-center justify-center text-4xl">
                🗺️
            </div>
            <h3 class="text-base font-bold text-white mb-1">هنوز بیسی نشان نکرده‌اید</h3>
            <p class="text-xs text-gray-400">
                از تب «نقشه‌ها و بیس‌ها» می‌توانید بیس‌های مورد علاقه را ذخیره کنید.
            </p>
        </div>

        <!-- گرید نقشه‌ها -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="map in maps.data"
                :key="map.id"
                class="bg-gray-900/80 border border-gray-700/80 hover:border-pink-500/50 rounded-2xl p-4 shadow-lg transition-all duration-300 flex flex-col"
            >
                <!-- تصویر -->
                <div class="relative overflow-hidden rounded-xl bg-gray-950 aspect-video mb-3 border border-gray-700/60">
                    <img
                        :src="map.image_url || '/images/default_icon.webp'"
                        :alt="map.name"
                        class="w-full h-full object-cover hover:scale-105 transition-transform duration-500 cursor-pointer"
                        loading="lazy"
                        @click="previewImage = map.image_url"
                    />
                </div>

                <!-- نام و دکمه حذف -->
                <div class="flex items-start justify-between gap-2 mb-3">
                    <h3 class="text-sm font-bold text-white line-clamp-2 leading-snug flex-1">
                        {{ map.name }}
                    </h3>
                    <button
                        @click="removeFavorite(map)"
                        :disabled="map._processing"
                        class="text-gray-400 hover:text-red-400 transition disabled:opacity-50"
                        title="حذف از نشان‌شده‌ها"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                            <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" />
                        </svg>
                    </button>
                </div>

                <!-- برچسب‌ها -->
                <div class="flex flex-wrap gap-1.5 mb-3">
                    <span
                        v-for="tag in parsedTags(map)"
                        :key="tag"
                        class="px-2 py-0.5 rounded-lg bg-pink-500/15 text-pink-300 border border-pink-500/30 text-[10px] font-bold"
                    >
                        {{ tag }}
                    </span>
                    <button
                        v-if="!map._editing"
                        @click="startEdit(map)"
                        class="px-2 py-0.5 rounded-lg bg-gray-700 text-gray-300 text-[10px] font-bold hover:bg-gray-600 transition"
                    >
                        + برچسب/یادداشت
                    </button>
                </div>

                <!-- یادداشت -->
                <div v-if="map.pivot?.notes && !map._editing" class="mb-3">
                    <p class="text-[11px] text-gray-300 bg-gray-800/80 rounded-lg p-2 border border-gray-700/60">
                        {{ map.pivot.notes }}
                    </p>
                </div>

                <!-- فرم ویرایش -->
                <div v-if="map._editing" class="mb-3 space-y-2">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 mb-1">برچسب‌ها (با کاما جدا کنید):</label>
                        <input
                            v-model="map._editTags"
                            type="text"
                            placeholder="مثال: وار, فارم, TH16"
                            class="w-full bg-gray-950 border border-gray-700 rounded-lg px-2.5 py-1.5 text-white text-xs focus:outline-none focus:border-pink-500"
                        />
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 mb-1">یادداشت:</label>
                        <textarea
                            v-model="map._editNotes"
                            rows="2"
                            placeholder="یادداشت شخصی درباره این بیس..."
                            class="w-full bg-gray-950 border border-gray-700 rounded-lg px-2.5 py-1.5 text-white text-xs focus:outline-none focus:border-pink-500 resize-none"
                        ></textarea>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="saveEdit(map)"
                            :disabled="map._saving"
                            class="flex-1 py-1.5 bg-pink-600 hover:bg-pink-500 text-white rounded-lg text-[11px] font-bold transition disabled:opacity-50"
                        >
                            {{ map._saving ? '...' : 'ذخیره' }}
                        </button>
                        <button
                            @click="cancelEdit(map)"
                            class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg text-[11px] font-bold transition"
                        >
                            انصراف
                        </button>
                    </div>
                </div>

                <!-- دکمه عملیات -->
                <div class="mt-auto pt-3 border-t border-gray-700/60 flex items-center gap-2">
                    <CopyMapButton :link="map.copy_link" class="flex-1" />
                    <button
                        v-if="!map._editing"
                        @click="startEdit(map)"
                        class="px-3 py-2 rounded-xl bg-gray-700 hover:bg-gray-600 text-white text-[11px] font-bold transition"
                    >
                        ✏️ ویرایش
                    </button>
                </div>
            </div>
        </div>

        <!-- صفحه‌بندی -->
        <div v-if="maps.last_page > 1" class="flex justify-center items-center mt-6 gap-2">
            <button
                @click="changePage(maps.current_page - 1)"
                :disabled="maps.current_page === 1 || loading"
                class="px-4 py-2 bg-gray-800 border border-gray-700 text-white rounded-xl text-xs font-bold shadow hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
            >
                ← صفحه قبلی
            </button>

            <span class="px-4 py-2 bg-gray-900 border border-pink-500/40 text-pink-300 rounded-xl text-xs font-black font-mono">
                {{ maps.current_page }} از {{ maps.last_page }}
            </span>

            <button
                @click="changePage(maps.current_page + 1)"
                :disabled="maps.current_page === maps.last_page || loading"
                class="px-4 py-2 bg-gray-800 border border-gray-700 text-white rounded-xl text-xs font-bold shadow hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
            >
                صفحه بعدی →
            </button>
        </div>

        <!-- مودال پیش‌نمایش -->
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

export default {
    name: 'FavoritesHub',
    components: { CopyMapButton },
    data() {
        return {
            maps: {
                data: [],
                current_page: 1,
                last_page: 1,
            },
            loading: false,
            selectedTag: '',
            previewImage: null,
        };
    },
    computed: {
        allTags() {
            const tags = new Set();
            this.maps.data.forEach(map => {
                this.parsedTags(map).forEach(tag => tags.add(tag));
            });
            return Array.from(tags).sort();
        },
    },
    mounted() {
        this.fetchFavorites(1);
    },
    methods: {
        async fetchFavorites(page = 1) {
            this.loading = true;
            try {
                const params = { page };
                if (this.selectedTag) {
                    params.tag = this.selectedTag;
                }
                const response = await window.axios.get('/maps/favorites', { params });
                this.maps = response.data;
            } catch (error) {
                console.error('خطا در دریافت علاقه‌مندی‌ها:', error);
            } finally {
                this.loading = false;
            }
        },
        changePage(page) {
            this.fetchFavorites(page);
        },
        parsedTags(map) {
            const tags = map.pivot?.tags ?? map.tags ?? [];
            if (Array.isArray(tags)) return tags;
            try {
                return JSON.parse(tags) || [];
            } catch (e) {
                return [];
            }
        },
        startEdit(map) {
            map._editing = true;
            map._editTags = this.parsedTags(map).join('، ');
            map._editNotes = map.pivot?.notes || map.notes || '';
        },
        cancelEdit(map) {
            map._editing = false;
        },
        async saveEdit(map) {
            map._saving = true;
            try {
                const tags = map._editTags
                    .split(/[،,]/)
                    .map(t => t.trim())
                    .filter(Boolean);

                const response = await window.axios.put(`/maps/${map.id}/favorite`, {
                    tags,
                    notes: map._editNotes.trim() || null,
                });

                if (response.data.ok) {
                    map.pivot = {
                        ...(map.pivot || {}),
                        tags: response.data.tags,
                        notes: response.data.notes,
                    };
                    map._editing = false;
                }
            } catch (error) {
                console.error('خطا در ذخیره یادداشت:', error);
                alert('خطا در ذخیره‌سازی. لطفاً دوباره تلاش کنید.');
            } finally {
                map._saving = false;
            }
        },
        async removeFavorite(map) {
            map._processing = true;
            try {
                await window.axios.post(`/maps/${map.id}/favorite`);
                this.fetchFavorites(this.maps.current_page);
            } catch (error) {
                console.error('خطا در حذف علاقه‌مندی:', error);
            } finally {
                map._processing = false;
            }
        },
    },
};
</script>
