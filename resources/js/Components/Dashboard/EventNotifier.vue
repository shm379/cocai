<template>
    <div class="bg-gray-800 p-4 rounded shadow">
        <h2 class="text-xl font-bold text-green-300 mb-2">رویدادهای جدید</h2>
        <div v-if="events.length === 0" class="text-gray-400">
            رویدادی در حال حاضر فعال نیست.
        </div>
        <ul v-else class="space-y-2">
            <li
                v-for="(ev, idx) in events"
                :key="idx"
                class="bg-gray-700 p-2 rounded"
            >
                <strong class="text-white">{{ ev.title }}</strong>
                <span class="text-sm text-gray-300 block">زمان پایان: {{ ev.endDate }}</span>
                <p class="text-gray-200 text-sm mt-1">{{ ev.description }}</p>
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    name: "EventNotifier",
    props: {
        events: {
            type: Array,
            default: () => []
            // فرمت: [ { title:'رویداد بالون', endDate:'2025-02-01', description:'تخفیف 1 جم بالون!' }, ... ]
        }
    }
}
</script>
