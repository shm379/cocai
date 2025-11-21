<template>
    <div>
        <!-- لیست نقشه‌ها -->
        <div v-if="maps" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="map in maps.data"
                :key="map.id"
                class="bg-gray-700 p-4 rounded-lg shadow-lg"
            >
                <img
                    :src="map.image_url"
                    alt="Map Image"
                    class="w-full h-auto mb-4 rounded"
                    loading="lazy"
                >
                <p class="text-sm text-white mb-2">{{ map.name }}</p>
                <p class="text-xs text-gray-400">
                    ویو: {{ map.view_count }} | لایک: {{ map.like_count }}
                </p>
                <a
                    :href="map.copy_link"
                    target="_blank"
                    class="text-red-300 hover:underline text-sm"
                >
                    کپی نقشه
                </a>
            </div>
        </div>

        <!-- صفحه‌بندی -->
        <div class="flex justify-center mt-4 space-x-2">
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
export default {
    props: {
        maps: Object,
        pageKey: String // مشخص می‌کند که صفحه‌بندی برای TH است یا BH
    },
    methods: {
        changePage(newPage) {
            this.$emit('pageChange', this.pageKey, newPage);
        }
    }
};
</script>
