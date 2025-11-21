<template>
    <div class="chart-container p-4 bg-gray-800 rounded-xl shadow-lg">
        <h4 class="text-lg font-bold text-yellow-400 mb-2">📊 پیشرفت تروفی</h4>
        <canvas id="trophyChart"></canvas>
    </div>
</template>

<script>
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

export default {
    name: "TrophiesChart",
    props: {
        trophyData: {
            type: Array,
            required: true
        }
    },
    mounted() {
        this.renderChart();
    },
    methods: {
        renderChart() {
            const ctx = document.getElementById('trophyChart').getContext('2d');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.trophyData.map(entry => entry.date),
                    datasets: [{
                        label: '🏆 تروفی‌ها',
                        data: this.trophyData.map(entry => entry.trophy),
                        borderColor: 'rgba(255, 159, 64, 1)',
                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false
                        }
                    }
                }
            });
        }
    }
};
</script>

<style scoped>
.chart-container {
    width: 100%;
    height: 400px;
}
</style>
