<template>
    <div class="bg-gray-800 p-4 rounded-xl shadow-lg">
        <div class="flex items-center">
            <img
                v-if="clan.badgeUrls"
                :src="clan.badgeUrls.medium"
                alt="Clan Badge"
                class="w-14 h-14 object-contain mr-4"
            />
            <div>
                <h2 class="text-xl font-bold text-white">{{ clan.name }}</h2>
                <p class="text-sm text-gray-300">کلن لول: {{ clan.clanLevel }}</p>
            </div>
        </div>
        <!-- اطلاعات اضافی اگر دارید -->
        <div class="mt-4 text-gray-300">
            تگ کلن: {{ clan.tag }}
            <!-- اگر جنگ‌های کلن یا تعداد اعضا هم دارید، می‌توانید نشان دهید -->
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
        }
    }
}
</script>

<style scoped>
/* استایل */
</style>
