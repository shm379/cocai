<template>
    <div class="bg-gray-800 p-4 rounded-lg shadow-lg mb-6">
        <div class="flex items-center space-x-4 mb-4">
            <!-- اگر league.iconUrls وجود دارد، نشان لیگ را نمایش بده -->
            <img
                v-if="leagueIcon"
                :src="leagueIcon"
                alt="League Icon"
                class="w-12 h-12 object-contain"
            />
            <div>
                <h2 class="text-xl text-white font-bold">
                    {{ gameProfile.name }} (لول {{ gameProfile.expLevel }})
                </h2>
                <p class="text-gray-300 text-sm">
                    {{ gameProfile.clan ? gameProfile.clan.name : 'بدون کلن' }}
                    | {{ leagueName }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
            <div class="bg-gray-700 rounded-lg p-3">
                <p class="text-sm text-gray-400">کاپ فعلی</p>
                <p class="text-xl text-yellow-400 font-bold">{{ gameProfile.trophies }}</p>
            </div>
            <div class="bg-gray-700 rounded-lg p-3">
                <p class="text-sm text-gray-400">بهترین کاپ</p>
                <p class="text-xl text-yellow-400 font-bold">{{ gameProfile.bestTrophies }}</p>
            </div>
            <div class="bg-gray-700 rounded-lg p-3">
                <p class="text-sm text-gray-400">War Stars</p>
                <p class="text-xl text-yellow-400 font-bold">{{ gameProfile.warStars }}</p>
            </div>
            <div class="bg-gray-700 rounded-lg p-3">
                <p class="text-sm text-gray-400">Builder Trophies</p>
                <p class="text-xl text-blue-400 font-bold">{{ gameProfile.builderBaseTrophies }}</p>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ProfileSummary',
    props: {
        gameProfile: {
            type: Object,
            required: true
        }
    },
    computed: {
        leagueIcon() {
            return this.gameProfile.league?.iconUrls?.medium || null;
        },
        leagueName() {
            return this.gameProfile.league?.name || 'بدون لیگ';
        }
    }
}
</script>

<style scoped>
/* استایل دلخواه */
</style>
