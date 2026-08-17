<template>
    <div class="min-h-screen bg-gray-900 flex flex-col items-center p-4 pb-20 relative">

        <!-- اعلان‌های موفق/خطا -->
        <AlertMessages :successMessage="successMessage" :errorMessage="errorMessage" />

        <!-- هدر بالای صفحه -->
        <HeaderComp :user="user" />

        <!-- محتوای اصلی -->
        <div class="w-full max-w-5xl flex-1 mt-4">
            <!-- نوار انتخاب بازی سوپرسل -->
            <GameSwitcherBar
                :activeGame="activeSupercellGame"
                @select-game="val => activeSupercellGame = val"
            />

            <!-- ۱) کلش رویال -->
            <ClashRoyaleHub v-if="activeSupercellGame === 'clash_royale'" />

            <!-- ۲) براول استارز -->
            <BrawlStarsHub v-else-if="activeSupercellGame === 'brawl_stars'" />

            <!-- ۳) اسکواد باستر -->
            <SquadBustersHub v-else-if="activeSupercellGame === 'squad_busters'" />

            <!-- ۴) بوم بیچ -->
            <BoomBeachHub v-else-if="activeSupercellGame === 'boom_beach'" />

            <!-- ۵) کلش اف کلنز -->
            <template v-else>
                <!-- اگر Player Tag ثبت نشده، فرم نمایش بده -->
                <div v-if="!user.game_profile" class="w-full flex flex-col items-center">
                    <div class="text-center mb-6">
                        <h1 class="text-3xl font-bold text-white mb-2">به CoCAI خوش آمدید</h1>
                        <p class="text-gray-300">دستیار هوشمند کلش اف کلنز — تسک روزانه، استراتژی و نقشه</p>
                    </div>
                    <PlayerTagForm
                        :saving="saving"
                        @submit="handlePlayerTagSubmit"
                    />
                </div>

                <!-- اگر player_tag ثبت شده، بقیه بخش‌ها را نمایش بده -->
                <div v-else>
                    <!-- تب ۱: پروفایل (Summary + تقویم + تحلیل‌های هوشمند) -->
                    <div v-if="activeTab === 'profile'">
                        <ProfileSummary :gameProfile="gameProfile" />
                        <ProgressSummary :analysis="analysis" />
                        <WarReadinessCard
                            v-if="analysis.war_readiness"
                            :warReadiness="analysis.war_readiness"
                            :warStars="gameProfile.warStars || 0"
                            :warRatingFa="analysis.clan_activity?.war_rating_fa || ''"
                        />
                        <FarmingAdvisorCard
                            v-if="analysis.farming"
                            :farming="analysis.farming"
                            :townHall="analysis.town_hall || gameProfile.townHallLevel || 1"
                            :currentTrophies="gameProfile.trophies || 0"
                        />
                        <HeroEquipmentSection
                            v-if="analysis.equipment"
                            :equipment="analysis.equipment"
                        />
                <HeroEquipmentLoadouts />
                <BlacksmithOrePlanner />
                <AchievementGemTracker
                    v-if="analysis.achievements"
                    :achievementsData="analysis.achievements"
                />
                <MetaArmiesHub
                    v-if="analysis.armies"
                    :townHall="analysis.town_hall || gameProfile.townHallLevel || 1"
                    :armies="analysis.armies"
                />
                <WarPlannerHub
                    :playerTownHall="analysis.town_hall || gameProfile.townHallLevel || 15"
                />
                <ArmyLinkGenerator />
                <HeroPetPlanner />
                <SiegeMachineAdvisor />
                <DefenseMatrixAdvisor />
                <CwlMedalCalculator />
                <ClanCapitalPlanner />
                <BuilderBaseProgressCard
                    v-if="analysis.builder_base"
                    :builderBase="analysis.builder_base"
                />
                <UpgradeTimeCalculator
                    :townHall="analysis.town_hall || gameProfile.townHallLevel || 1"
                />
                <AccountSwitcher
                    :currentTag="gameProfile?.tag || user.game_profile?.player_tag || ''"
                    :currentName="gameProfile?.name || ''"
                />
                <QuickActions
                    :has-profile="!!user.game_profile"
                    @openCompare="showCompareModal = true"
                />
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
                    :favorite-map-ids="favoriteMapIds"
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
                    :favorite-map-ids="favoriteMapIds"
                    @pageChange="changePage"
                />
            </div>

            <!-- تب علاقه‌مندی‌ها -->
            <div v-else-if="activeTab === 'favorites'" class="mb-6 p-4 bg-gray-800 rounded-xl shadow-lg dashboard-container">
                <h2 class="text-lg font-bold text-white mb-4">نقشه‌های مورد علاقه</h2>
                <MapList
                    :maps="favoriteMaps"
                    pageKey="favPage"
                    :favorite-map-ids="favoriteMapIds"
                    @pageChange="fetchFavorites"
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
                <ClanOverview
                    :clan="gameProfile.clan"
                    :playerRole="gameProfile.role"
                    :capitalContributed="gameProfile.clanCapitalContributions || 0"
                    :donations="gameProfile.donations || 0"
                    :donationsReceived="gameProfile.donationsReceived || 0"
                    :warStars="gameProfile.warStars || 0"
                />
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
            </template>
        </div>

        <!-- منوی پایین صفحه (فقط در حالت کلش اف کلنز) -->
        <BottomNav
            v-if="user.game_profile && activeSupercellGame === 'coc'"
            :activeTab="activeTab"
            @update:activeTab="val => activeTab = val"
        />
        <!-- لودینگ اورلی + شمارش معکوس (در صورت نیاز) -->
        <LoadingOverlay
            v-if="showCountdown"
            :countdown="countdown"
        />

        <!-- مودال مقایسه بازیکن -->
        <PlayerComparisonModal
            :show="showCompareModal"
            :myData="gameProfile"
            @close="showCompareModal = false"
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

/* نوار انتخاب بازی و هاب بازی‌های سوپرسل */
import GameSwitcherBar from "@/Components/Dashboard/GameSwitcherBar.vue"
import ClashRoyaleHub from "@/Components/Dashboard/ClashRoyaleHub.vue"
import BrawlStarsHub from "@/Components/Dashboard/BrawlStarsHub.vue"
import SquadBustersHub from "@/Components/Dashboard/SquadBustersHub.vue"
import BoomBeachHub from "@/Components/Dashboard/BoomBeachHub.vue"

/* تب‌های سفارشی */
import ProfileSummary from "@/Components/Dashboard/ProfileSummary.vue"
import ProgressSummary from "@/Components/Dashboard/ProgressSummary.vue"
import WarReadinessCard from "@/Components/Dashboard/WarReadinessCard.vue"
import FarmingAdvisorCard from "@/Components/Dashboard/FarmingAdvisorCard.vue"
import HeroEquipmentSection from "@/Components/Dashboard/HeroEquipmentSection.vue"
import HeroEquipmentLoadouts from "@/Components/Dashboard/HeroEquipmentLoadouts.vue"
import BlacksmithOrePlanner from "@/Components/Dashboard/BlacksmithOrePlanner.vue"
import AchievementGemTracker from "@/Components/Dashboard/AchievementGemTracker.vue"
import MetaArmiesHub from "@/Components/Dashboard/MetaArmiesHub.vue"
import DefenseMatrixAdvisor from "@/Components/Dashboard/DefenseMatrixAdvisor.vue"
import WarPlannerHub from "@/Components/Dashboard/WarPlannerHub.vue"
import HeroPetPlanner from "@/Components/Dashboard/HeroPetPlanner.vue"
import ClanCapitalPlanner from "@/Components/Dashboard/ClanCapitalPlanner.vue"
import SiegeMachineAdvisor from "@/Components/Dashboard/SiegeMachineAdvisor.vue"
import CwlMedalCalculator from "@/Components/Dashboard/CwlMedalCalculator.vue"
import ArmyLinkGenerator from "@/Components/Dashboard/ArmyLinkGenerator.vue"
import BuilderBaseProgressCard from "@/Components/Dashboard/BuilderBaseProgressCard.vue"
import UpgradeTimeCalculator from "@/Components/Dashboard/UpgradeTimeCalculator.vue"
import PlayerComparisonModal from "@/Components/Dashboard/PlayerComparisonModal.vue"
import QuickActions from "@/Components/Dashboard/QuickActions.vue"
import AccountSwitcher from "@/Components/Dashboard/AccountSwitcher.vue"
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
        analysis: {
            type: Object,
            default: () => ({})
        },
        favoriteMapIds: {
            type: Array,
            default: () => []
        },
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

        GameSwitcherBar,
        ClashRoyaleHub,
        BrawlStarsHub,
        SquadBustersHub,
        BoomBeachHub,

        ProfileSummary,
        ProgressSummary,
        WarReadinessCard,
        FarmingAdvisorCard,
        HeroEquipmentSection,
        HeroEquipmentLoadouts,
        BlacksmithOrePlanner,
        AchievementGemTracker,
        MetaArmiesHub,
        DefenseMatrixAdvisor,
        WarPlannerHub,
        HeroPetPlanner,
        ClanCapitalPlanner,
        SiegeMachineAdvisor,
        CwlMedalCalculator,
        ArmyLinkGenerator,
        BuilderBaseProgressCard,
        UpgradeTimeCalculator,
        PlayerComparisonModal,
        QuickActions,
        AccountSwitcher,
        TroopsSection,
        AchievementsList,
        TrophiesChart,
        ClanOverview,
        BuilderBase,
        AiAssistant,
    },
    data() {
        return {
            activeSupercellGame: 'coc',
            saving: false,
            loading: false,
            activeTab: 'profile',
            showCompareModal: false,
            favoriteMaps: {
                data: [],
                current_page: 1,
                last_page: 1,
            },
            loadingFavorites: false,

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
    watch: {
        activeTab(newTab) {
            if (newTab === 'favorites') {
                this.fetchFavorites(1);
            }
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
        async fetchFavorites(page = 1) {
            this.loadingFavorites = true;

            try {
                const response = await window.axios.get('/maps/favorites', { params: { page } });
                this.favoriteMaps = response.data;
            } catch (error) {
                console.error('خطا در دریافت علاقه‌مندی‌ها:', error);
            } finally {
                this.loadingFavorites = false;
            }
        },
        async handlePlayerTagSubmit(sanitizedTag) {
            if (!sanitizedTag) return;

            this.saving = true;
            this.loading = true;
            this.startCountdown(10);

            try {
                await this.$inertia.post('/save-player-tag',
                    {player_tag: sanitizedTag},
                    {
                        preserveState: true,
                        onError: (errors) => {
                            console.error("خطا در ثبت تگ:", errors);
                        },
                    }
                );
            } catch (error) {
                console.error("خطای غیرمنتظره:", error);
            } finally {
                setTimeout(() => {
                    this.showCountdown = false;
                    this.saving = false;
                    this.loading = false;
                    this.clearTimer();
                }, 5000);

                setTimeout(() => {
                    location.reload();
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
