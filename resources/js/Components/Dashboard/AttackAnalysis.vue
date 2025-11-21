<template>
    <div class="bg-gray-800 p-4 rounded shadow text-white">
        <h2 class="text-xl font-bold mb-4">تحلیل حمله/دفاع</h2>
        <p class="text-sm text-gray-300 mb-2">بردها: {{ totalWins }} / باخت‌ها: {{ totalLosses }}</p>
        <p class="text-sm text-gray-300 mb-2">میانگین ستاره: {{ averageStars.toFixed(2) }}</p>
        <p class="text-sm text-gray-300 mb-2">استراتژی محبوب: {{ popularUnit }}</p>
    </div>
</template>

<script>
export default {
    name: "AttackAnalysis",
    props: {
        logs: {
            type: Array,
            default: () => []
            // مثلا: [ { type:'attack', result:'win', stars:3, unitsUsed:{Barbarian:10, Archer:5}, date:'2025-01-01'}, ... ]
        }
    },
    computed: {
        totalWins() {
            return this.logs.filter(l => l.result === 'win').length;
        },
        totalLosses() {
            return this.logs.filter(l => l.result === 'lose').length;
        },
        averageStars() {
            if (!this.logs.length) return 0;
            let sum = 0;
            this.logs.forEach(l => sum += (l.stars || 0));
            return sum / this.logs.length;
        },
        popularUnit() {
            // خیلی ساده: بیشترین واحد استفاده‌شده مجموعا
            let unitCounts = {};
            this.logs.forEach(l => {
                if (l.unitsUsed) {
                    Object.keys(l.unitsUsed).forEach(u => {
                        if (!unitCounts[u]) unitCounts[u] = 0;
                        unitCounts[u] += l.unitsUsed[u];
                    });
                }
            });
            let maxUnit = null, maxCount = 0;
            for (let u in unitCounts) {
                if (unitCounts[u] > maxCount) {
                    maxCount = unitCounts[u];
                    maxUnit = u;
                }
            }
            return maxUnit || 'نامشخص';
        }
    }
}
</script>
