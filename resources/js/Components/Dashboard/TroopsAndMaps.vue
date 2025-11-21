<template>
    <div class="mb-6 p-4 bg-gray-800 rounded-xl shadow-lg dashboard-container">
        <!-- Troops Section -->
        <div class="section">
            <h4>نیروها</h4>
            <div class="grid-container">
                <div
                    v-for="(troop, index) in gameProfile.troops"
                    :key="index"
                    class="grid-item"
                >
                    <img
                        :src="troop.icon || '/images/default_icon.webp'"
                        :alt="troop.name"
                        class="item-icon"
                    />
                    <span class="level-badge">
            {{ troop.level }}/{{ troop.maxLevel }}
          </span>
                </div>
            </div>
        </div>

        <!-- Spells Section -->
        <div class="section">
            <h4>طلسم‌ها</h4>
            <div class="grid-container">
                <div
                    v-for="(spell, index) in gameProfile.spells"
                    :key="index"
                    class="grid-item"
                >
                    <img
                        :src="spell.icon || '/images/default_icon.webp'"
                        :alt="spell.name"
                        class="item-icon"
                    />
                    <span class="level-badge">
            {{ spell.level }}/{{ spell.maxLevel }}
          </span>
                </div>
            </div>
        </div>

        <!-- Heroes Section -->
        <div class="section">
            <h4>قهرمان‌ها</h4>
            <div class="grid-container">
                <div
                    v-for="(hero, index) in gameProfile.heroes"
                    :key="index"
                    class="grid-item"
                >
                    <img
                        :src="hero.icon || '/images/default_icon.webp'"
                        :alt="hero.name"
                        class="item-icon"
                    />
                    <span class="level-badge">
            {{ hero.level }}/{{ hero.maxLevel }}
          </span>
                </div>
            </div>
        </div>

        <!-- نقشه‌های TH -->
        <div class="section w-full max-w-4xl">
            <h2 class="text-xl font-bold text-white text-center mb-4">🏰 نقشه‌های Town Hall</h2>
            <TownHallFilter
                :selectedHallLevel="selectedHallLevel"
                @filter="handleTownHallFilter"
            />
            <MapList
                :maps="townHallMaps"
                :pageKey="'thPage'"
                @pageChange="handlePageChange"
            />
        </div>

        <!-- نقشه‌های BH -->
        <div class="section w-full max-w-4xl">
            <h2 class="text-xl font-bold text-white text-center mb-4">🏗️ نقشه‌های Builder Hall</h2>
            <BuilderHallFilter
                :selectedHallLevel="selectedHallLevel"
                @filter="handleBuilderHallFilter"
            />
            <MapList
                :maps="builderHallMaps"
                :pageKey="'bhPage'"
                @pageChange="handlePageChange"
            />
        </div>
    </div>
</template>

<script>
import TownHallFilter from "@/Components/Dashboard/TownHallFilter.vue"
import BuilderHallFilter from "@/Components/Dashboard/BuilderHallFilter.vue"
import MapList from "@/Components/Dashboard/MapList.vue"

export default {
    name: "TroopsAndMaps",
    components: {
        TownHallFilter,
        BuilderHallFilter,
        MapList
    },
    props: {
        selectedHallLevel: {
            type: Number,
            default: 1
        },
        gameProfile: {
            type: Object,
            required: true
        },
        townHallMaps: {
            type: Object,
            required: true
        },
        builderHallMaps: {
            type: Object,
            required: true
        },
        thPage: {
            type: Number,
            default: 1
        },
        bhPage: {
            type: Number,
            default: 1
        },
        selectedHallType: {
            type: Number,
            default: 0
        }
    },
    methods: {
        handleTownHallFilter(level) {
            this.selectedHallLevel
            // نوع تاون هال => 0
            this.$emit('applyHallFilter', 0, level)
        },
        handleBuilderHallFilter(level) {
            // نوع بیلدرهال => 1
            this.$emit('applyHallFilter', 1, level)
        },
        handlePageChange(pageKey, newPage) {
            this.$emit('changePage', pageKey, newPage)
        }
    }
}
</script>

<style scoped>
/* سبک اصلی برای کانتینر کلی */
.dashboard-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
    background-color: #1c2230;
    color: #fff;
    padding: 20px;
    border-radius: 10px;
}

/* هر بخش (نیروها، طلسم‌ها، ...) */
.section {
    margin-bottom: 20px;
}

/* نمونه استایل اختصاصی بخش نیروها */
.section-troops {
    border: 2px solid #ffcc00;
    border-radius: 8px;
    background-color: #242b38; /* کمی متفاوت از پس‌زمینه اصلی */
    padding: 16px;
}

.section-troops h4 {
    color: #ffcc00;
    font-size: 1.25rem;
    margin-bottom: 0.75rem;
}

/* گرید کلی برای آیتم‌ها (نیرو، طلسم، قهرمان) */
.grid-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
    gap: 10px;
}

/* آیتم هر نیرو/طلسم/قهرمان */
.grid-item {
    position: relative;
    text-align: center;
    border: 1px solid #444;
    border-radius: 10px;
    background-color: #2a2f3d;
    padding: 10px;
    transition: transform 0.2s;
}

.grid-item:hover {
    transform: scale(1.05);
}

/* استایل آیکون (تصویر) */
.item-icon {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 5px;
}

/* نشان‌گر سطح (level-badge) */
.level-badge {
    position: absolute;
    bottom: 5px;
    right: 5px;
    background-color: #42a5f5;
    color: white;
    font-weight: bold;
    font-size: 12px;
    padding: 2px 5px;
    border-radius: 5px;
}

</style>
