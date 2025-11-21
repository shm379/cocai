<template>
    <div class="min-h-screen bg-gray-900 flex flex-col items-center p-4 pb-20 relative">

        <!-- اعلان‌های موفق/خطا -->
        <AlertMessages :successMessage="successMessage" :errorMessage="errorMessage" />

        <!-- هدر بالای صفحه -->
        <HeaderComp :user="user" />

        <!-- اگر Player Tag ثبت نشده، فرم نمایش بده -->
        <div v-if="!user.game_profile">
            <div id="player-tag-form" class="w-full max-w-md mb-6">
                <div class="p-6 bg-gray-800 rounded-xl shadow-lg">
                    <h2 class="text-lg font-medium text-red-300 mb-4">
                        برای ادامه، لطفاً Player Tag خود را وارد کنید:
                    </h2>
                    <form @submit.prevent="handlePlayerTagSubmit" class="space-y-4">
                        <div>
                            <label for="player_tag" class="block text-sm font-medium text-red-300">
                                Player Tag
                            </label>
                            <input
                                type="text"
                                id="player_tag"
                                v-model="playerTagLocal"
                                class="mt-1 block w-full px-4 py-2 border border-red-500 rounded-lg bg-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                                placeholder="#RPLVYLL2"
                                required
                            />
                        </div>
                        <button
                            :disabled="saving || !playerTagLocal"
                            type="submit"
                            class="w-full py-2 px-4 bg-red-600 text-white rounded-lg shadow hover:bg-red-700 transition duration-200 disabled:opacity-50"
                        >
                            <span v-if="saving">در حال ذخیره‌سازی...</span>
                            <span v-else>ثبت</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- اگر player_tag ثبت شده، بقیه بخش‌ها را نمایش بده -->
        <div v-else class="w-full max-w-5xl flex-1 mt-4">

            <!-- نوار تب‌ها -->

            <!-- تب ۱: پروفایل (Summary + تقویم) -->
            <div v-if="activeTab === 'profile'">
                <ProfileSummary :gameProfile="gameProfile" />
                <CalendarAndTask
                    :calendar="calendar"
                    :todayTask="todayTask"
                    :saving="saving"
                    @markTaskCompleted="markTaskCompleted"
                />
            </div>

            <div class="mb-6 p-4 bg-gray-800 rounded-xl shadow-lg dashboard-container"v-else-if="activeTab === 'th_maps'">
                <TownHallFilter
                    :selectedHallLevel="selectedHallLevel"
                    @filter="applyHallFilter(0, $event)"
                />
                <MapList
                    :maps="townHallMaps"
                    pageKey="thPage"
                    @pageChange="changePage"
                />
            </div>

            <div class="mb-6 p-4 bg-gray-800 rounded-xl shadow-lg dashboard-container" v-else-if="activeTab === 'bh_maps'">
                <BuilderHallFilter
                    :selectedHallLevel="selectedHallLevel"
                    @filter="applyHallFilter(1, $event)"
                />
                <MapList
                    :maps="builderHallMaps"
                    pageKey="bhPage"
                    @pageChange="changePage"
                />
            </div>

            <!-- تب ۲: نیروها (TroopsSection) -->
            <div v-else-if="activeTab === 'troops'">
                <TroopsSection :gameProfile="gameProfile" />
            </div>

            <!-- تب ۳: دستاوردها (AchievementsList) -->
            <div v-else-if="activeTab === 'achievements'">
                <AchievementsList :achievements="gameProfile.achievements" />
            </div>

            <!-- تب ۴: نمودار پیشرفت تروفی (TrophiesChart) -->
            <div v-else-if="activeTab === 'progressChart'">
                <TrophiesChart :trophyData="trophyHistory" />
            </div>

            <!-- تب ۵: کلن (ClanOverview) -->
            <div v-else-if="activeTab === 'clanOverview'">
                <ClanOverview :clan="gameProfile.clan" />
            </div>

            <!-- تب ۶: بیلدر بیس (BuilderBase) -->
            <div v-else-if="activeTab === 'builderBase'">
                <BuilderBase
                    :builderHallLevel="gameProfile.builderHallLevel"
                    :builderBaseTrophies="gameProfile.builderBaseTrophies"
                    :builderTroops="builderTroops"
                />
            </div>

            <!-- تب ۷: دستیار هوش مصنوعی (AI Assistant) -->
            <div v-else-if="activeTab === 'assistant'">
                <AiAssistant :gameProfile="gameProfile" />
            </div>

        </div>

        <!-- منوی پایین صفحه -->
        <BottomNav
            :activeTab="activeTab"
            @update:activeTab="val => activeTab = val"
            v-if="user.game_profile"
        />
        <!-- لودینگ اورلی + شمارش معکوس (در صورت نیاز) -->
        <LoadingOverlay
            v-if="showCountdown"
            :countdown="countdown"
        />
    </div>
</template>

<script>
import { Inertia } from '@inertiajs/inertia'
import AlertMessages from "@/Components/Dashboard/AlertMessages.vue"
import HeaderComp from "@/Components/Dashboard/HeaderComp.vue"
import PlayerTagForm from "@/Components/Dashboard/PlayerTagForm.vue"
import CalendarAndTask from "@/Components/Dashboard/CalendarAndTask.vue"
import BottomNav from "@/Components/Dashboard/BottomNav.vue"
import LoadingOverlay from "@/Components/Dashboard/LoadingOverlay.vue"

/* تب‌های سفارشی */
import ProfileSummary from "@/Components/Dashboard/ProfileSummary.vue"
import TroopsSection from "@/Components/Dashboard/TroopsSection.vue"
import AchievementsList from "@/Components/Dashboard/AchievementsList.vue"
import TrophiesChart from "@/Components/Dashboard/TrophiesChart.vue"
import ClanOverview from "@/Components/Dashboard/ClanOverview.vue"
import BuilderBase from "@/Components/Dashboard/BuilderBase.vue"
import AiAssistant from "@/Components/Dashboard/AiAssistant.vue"

/* اگر MapList و فیلترها هم داشتید... */
import MapList from "@/Components/Dashboard/MapList.vue"
import TownHallFilter from "@/Components/Dashboard/TownHallFilter.vue"
import BuilderHallFilter from "@/Components/Dashboard/BuilderHallFilter.vue"

export default {
    props: {
        user: Object,
        successMessage: String,
        errorMessage: String,

        // اطلاعات بازی
        gameProfile: Object,
        calendar: Array,
        todayTask: String,
        townHallMaps: Object,
        builderHallMaps: Object,
        // اگر نمودار تروفی را می‌خواهید نمایش دهید
        trophyHistory: {
            type: Array,
            default: () => []
            // در صورت تمایل، از سرور بگیرید یا محاسبه کنید
        }
    },
    components: {
        AlertMessages,
        HeaderComp,
        PlayerTagForm,
        CalendarAndTask,
        BottomNav,
        LoadingOverlay,
        MapList,
        TownHallFilter,
        BuilderHallFilter,

        ProfileSummary,
        TroopsSection,
        AchievementsList,
        TrophiesChart,
        ClanOverview,
        BuilderBase,
        AiAssistant,
    },
    data() {
        return {
            saving: false,
            loading: false,
            playerTagLocal: '',
            activeTab: 'profile',

            countdown: 15,
            timer: null,
            showCountdown: false,
        }
    },
    computed: {
        // اگر بخواهید نیروهای بیلدر بیس را جداگانه به BuilderBase بفرستید
        builderTroops() {
            if (!this.gameProfile?.troops) return [];
            // type=4 را جدا می‌کنیم
            return this.gameProfile.troops.filter(t => t.type === 4);
        }
    },
    methods: {
        changePage(pageKey, newPage) {
            this.$inertia.get('/dashboard', {
                    [pageKey]: newPage,
                    hallLevel: this.selectedHallLevel,
                    hallType: this.selectedHallType,
                }, {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['townHallMaps', 'builderHallMaps','selectedHallLevel','selectedHallType']
                }
            );
        },

        applyHallFilter(type, level) {
            this.$inertia.get('/dashboard', {
                hallType: type,
                hallLevel: level
            }, {
                preserveState: true,
                preserveScroll: true,
                only: ['townHallMaps', 'builderHallMaps','selectedHallLevel','selectedHallType']
            })
        },
        async handlePlayerTagSubmit(playerTagValue) {
            if (!playerTagValue) return;
            console.log(playerTagValue)
            this.saving = true;
            this.loading = true;
            this.startCountdown(10);

            try {
                const sanitizedTag = this.playerTagLocal.replace('#', '').trim();

                await this.$inertia.post('/save-player-tag',
                    {player_tag: sanitizedTag},
                    {
                        preserveState: true,
                        onSuccess: () => {

                        },
                        onError: () => {

                        },
                    }
                );
            } catch (error) {
                console.error("خطای غیرمنتظره:", error);
            } finally {
                setTimeout(()=>{
                    this.showCountdown = false;
                    this.saving = false;
                    this.loading = false;
                    this.clearTimer();
                },5000)

                setTimeout(() => {
                    location.reload()
                }, 10000);
            }
        },

        // نمونه مارک تسک
        async markTaskCompleted(day = null) {
            this.startCountdown();
            this.showCountdown = true;
            this.saving = true;

            try {
                await Inertia.post('/tasks/complete', day ? {day} : {}, {
                    preserveState: true,
                    onSuccess: () => {
                    }
                });
            } catch (error) {
                console.error("خطا در تکمیل تسک:", error);
            } finally {
                this.saving = false;
                setTimeout(() => {
                    this.showCountdown = false;
                    this.clearTimer();
                }, 2000);
            }
        },

        // شمارش معکوس
        startCountdown(count = 15) {
            if (this.timer) clearInterval(this.timer);
            this.showCountdown = true;
            this.countdown = count;

            this.timer = setInterval(() => {
                if (this.countdown > 0) {
                    this.countdown--;
                } else {
                    this.clearTimer();
                    this.showCountdown = false;
                }
            }, 1000);
        },
        clearTimer() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        }
    },
    beforeUnmount() {
        this.clearTimer();
    }
}
</script>
<style scoped>
.min-h-screen {
    background: url('/847433.jpg') no-repeat center center;
    background-size: cover;
}
</style>
