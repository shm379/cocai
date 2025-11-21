<template>
    <div class="mb-6 p-4 bg-gray-800 rounded-xl shadow-lg dashboard-container">
        <!-- بخش نیروهای عادی (type=0) -->
        <div v-if="normalTroops.length" class="section">
            <h4 class="text-lg font-bold text-yellow-300 mb-2">نیروهای عادی</h4>
            <div class="grid-container">
                <div
                    v-for="(item, index) in normalTroops"
                    :key="index"
                    class="grid-item"
                >
                    <img
                        :src="item.icon || '/images/default_icon.webp'"
                        :alt="item.name"
                        class="item-icon"
                    />
                    <span class="level-badge">
            {{ item.level }}/{{ item.maxLevel }}
          </span>
                </div>
            </div>
        </div>

        <!-- بخش نیروهای دارک (type=1) -->
        <div v-if="darkTroops.length" class="section">
            <h4 class="text-lg font-bold text-purple-400 mb-2">نیروهای دارک</h4>
            <div class="grid-container">
                <div
                    v-for="(item, index) in darkTroops"
                    :key="index"
                    class="grid-item"
                >
                    <img
                        :src="item.icon || '/images/default_icon.webp'"
                        :alt="item.name"
                        class="item-icon"
                    />
                    <span class="level-badge">
            {{ item.level }}/{{ item.maxLevel }}
          </span>
                </div>
            </div>
        </div>

        <!-- بخش سوپر نیروها (type=2) -->
        <div v-if="superTroops.length" class="section">
            <h4 class="text-lg font-bold text-pink-400 mb-2">سوپر نیروها</h4>
            <div class="grid-container">
                <div
                    v-for="(item, index) in superTroops"
                    :key="index"
                    class="grid-item"
                >
                    <img
                        :src="item.icon || '/images/default_icon.webp'"
                        :alt="item.name"
                        class="item-icon"
                    />
                    <span class="level-badge">
            {{ item.level }}/{{ item.maxLevel }}
          </span>
                </div>
            </div>
        </div>

        <!-- ماشین‌های محاصره (type=3) -->
        <div v-if="siegeMachines.length" class="section">
            <h4 class="text-lg font-bold text-red-400 mb-2">ماشین محاصره</h4>
            <div class="grid-container">
                <div
                    v-for="(item, index) in siegeMachines"
                    :key="index"
                    class="grid-item"
                >
                    <img
                        :src="item.icon || '/images/default_icon.webp'"
                        :alt="item.name"
                        class="item-icon"
                    />
                    <span class="level-badge">
            {{ item.level }}/{{ item.maxLevel }}
          </span>
                </div>
            </div>
        </div>

        <!-- نیروهای بیلدر بیس (type=4) -->
        <div v-if="builderTroops.length" class="section">
            <h4 class="text-lg font-bold text-green-400 mb-2">نیروهای بیلدر بیس</h4>
            <div class="grid-container">
                <div
                    v-for="(item, index) in builderTroops"
                    :key="index"
                    class="grid-item"
                >
                    <img
                        :src="item.icon || '/images/default_icon.webp'"
                        :alt="item.name"
                        class="item-icon"
                    />
                    <span class="level-badge">
            {{ item.level }}/{{ item.maxLevel }}
          </span>
                </div>
            </div>
        </div>

        <!-- طلسم‌ها (از gameProfile.spells که type=8) -->
        <div v-if="spellList.length" class="section">
            <h4 class="text-lg font-bold text-blue-400 mb-2">طلسم‌ها</h4>
            <div class="grid-container">
                <div
                    v-for="(spell, index) in spellList"
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

        <!-- هیروها: دهکده اصلی (type=10) -->
        <div v-if="heroHome.length" class="section">
            <h4 class="text-lg font-bold text-red-300 mb-2">قهرمان‌های دهکده اصلی</h4>
            <div class="grid-container">
                <div
                    v-for="(hero, index) in heroHome"
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

        <!-- هیروها: بیلدر بیس (type=11) -->
        <div v-if="heroBuilder.length" class="section">
            <h4 class="text-lg font-bold text-indigo-300 mb-2">قهرمان‌های بیلدر بیس</h4>
            <div class="grid-container">
                <div
                    v-for="(hero, index) in heroBuilder"
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

        <!-- پت قهرمان (type=12) -->
        <div v-if="heroPets.length" class="section">
            <h4 class="text-lg font-bold text-yellow-400 mb-2">پت هیرو</h4>
            <div class="grid-container">
                <div
                    v-for="(hero, index) in heroPets"
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
    </div>
</template>

<script>
export default {
    name: "TroopsSection",
    props: {
        // انتظار می‌رود structure ای شبیه:
        // gameProfile = {
        //   troops: [ {name, level, type=...}, ... ],
        //   spells: [ {name, level, type=...}, ... ],
        //   heroes: [ {name, level, type=...}, ... ]
        // }
        gameProfile: {
            type: Object,
            required: true
        },
    },
    computed: {
        // نیروهای عادی (type=0)
        normalTroops() {
            if (!this.gameProfile.troops) return [];
            return this.gameProfile.troops.filter(t => t.type === 0);
        },
        // دارک (type=1)
        darkTroops() {
            return this.gameProfile.troops?.filter(t => t.type === 1) || [];
        },
        // سوپر (type=2)
        superTroops() {
            return this.gameProfile.troops?.filter(t => t.type === 2) || [];
        },
        // ماشین محاصره (type=3)
        siegeMachines() {
            return this.gameProfile.troops?.filter(t => t.type === 3) || [];
        },
        // بیلدر بیس (type=4)
        builderTroops() {
            return this.gameProfile.troops?.filter(t => t.type === 4) || [];
        },

        // طلسم‌ها (type=8)
        spellList() {
            if (!this.gameProfile.spells) return [];
            return this.gameProfile.spells.filter(s => s.type === 8);
        },

        // هیرو دهکده اصلی (type=10)
        heroHome() {
            if (!this.gameProfile.heroes) return [];
            return this.gameProfile.heroes.filter(h => h.type === 10);
        },
        // هیرو بیلدر (type=11)
        heroBuilder() {
            return this.gameProfile.heroes?.filter(h => h.type === 11) || [];
        },
        // پت هیرو (type=12)
        heroPets() {
            return this.gameProfile.heroes?.filter(h => h.type === 12) || [];
        }
    }
}
</script>

<style scoped>
.dashboard-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
    background-color: #1c2230;
    color: #fff;
    padding: 20px;
    border-radius: 10px;
}
.section {
    margin-bottom: 20px;
}
.grid-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
    gap: 10px;
}
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
.item-icon {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 5px;
}
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
