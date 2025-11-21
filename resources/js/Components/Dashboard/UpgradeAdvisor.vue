<template>
    <div class="bg-gray-800 p-4 rounded shadow-lg text-white">
        <h2 class="text-xl font-bold mb-4">پیشنهاد ارتقا (Upgrade Advisor)</h2>
        <div v-if="adviceList.length === 0" class="text-gray-300">
            داده کافی برای پیشنهاد ارتقا وجود ندارد.
        </div>
        <ul v-else class="list-disc list-inside">
            <li v-for="(adv, idx) in adviceList" :key="idx">
                {{ adv }}
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    name: "UpgradeAdvisor",
    props: {
        currentGold: {
            type: Number,
            default: 0
        },
        currentElixir: {
            type: Number,
            default: 0
        },
        buildings: {
            type: Array,
            default: () => []
            // انتظار می‌رود هر ساختمان { name, level, upgradeCostGold, upgradeCostElixir }
        }
    },
    computed: {
        adviceList() {
            // یک الگوریتم ساده: لیست ساختمان‌هایی را که هزینه‌شان <= منابع فعلی است برگرداند
            let results = [];
            for (let b of this.buildings) {
                if (b.upgradeCostGold && b.upgradeCostGold <= this.currentGold) {
                    results.push(
                        `می‌توانید ${b.name} سطح ${b.level} را با گلد ارتقا دهید.`
                    );
                }
                if (b.upgradeCostElixir && b.upgradeCostElixir <= this.currentElixir) {
                    results.push(
                        `می‌توانید ${b.name} سطح ${b.level} را با الیکسیر ارتقا دهید.`
                    );
                }
            }
            return results;
        }
    }
}
</script>

<style scoped>
/* دلخواه */
</style>
