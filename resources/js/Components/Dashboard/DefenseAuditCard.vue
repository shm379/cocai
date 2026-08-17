<template>
    <div class="bg-gray-800/90 backdrop-blur-xl border border-gray-700/80 rounded-3xl p-4 sm:p-6 shadow-2xl" dir="rtl">
        <!-- هدر اسکنر امنیتی بیس -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5 pb-4 border-b border-gray-700/70">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-500 flex items-center justify-center text-2xl shadow-lg shadow-emerald-500/20 text-gray-950 font-black shrink-0">
                    🛡️
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base sm:text-lg font-black text-white">اسکنر آسیب‌پذیری‌های دفاعی پایگاه (AI Defense Audit)</h2>
                        <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 font-bold">
                            تحلیل زنده
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">شناسایی تله‌های نامناسب، آسیب‌پذیری در برابر روت رایدر و بلیمپ با راهکار تقویت هوشمند</p>
                </div>
            </div>

            <button
                type="button"
                @click="scanDefense"
                :disabled="loading"
                class="w-full sm:w-auto px-4 py-2 rounded-xl bg-gray-700/80 hover:bg-gray-600 text-white font-bold text-xs transition flex items-center justify-center gap-1.5"
            >
                <span>🔄</span>
                <span>{{ loading ? 'در حال اسکن...' : 'اسکن مجدد' }}</span>
            </button>
        </div>

        <div v-if="auditData" class="space-y-4">
            <!-- نمره نفوذناپذیری دفاعی -->
            <div class="p-4 rounded-2xl bg-gray-900/80 border border-gray-700/80 flex items-center justify-between gap-4">
                <div>
                    <div class="text-xs text-gray-400 mb-1">وضعیت کلی نفوذناپذیری بیس:</div>
                    <h3 class="text-sm sm:text-base font-black text-white">{{ auditData.defense_rating_fa }}</h3>
                </div>

                <div class="flex items-center gap-3">
                    <div class="text-2xl sm:text-3xl font-black text-emerald-400 font-mono">
                        {{ auditData.defense_security_score }}<span class="text-xs text-gray-400">/۱۰۰</span>
                    </div>
                </div>
            </div>

            <!-- لیست آسیب‌پذیری‌های شناسایی‌شده -->
            <div class="space-y-2.5">
                <div
                    v-for="(vuln, idx) in auditData.vulnerabilities"
                    :key="idx"
                    class="p-3.5 rounded-2xl border transition"
                    :class="vuln.type === 'critical'
                        ? 'bg-red-500/10 border-red-500/30'
                        : 'bg-amber-500/10 border-amber-500/30'"
                >
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm">{{ vuln.type === 'critical' ? '🚨' : '⚠️' }}</span>
                        <h4 class="text-xs font-bold text-white">{{ vuln.title }}</h4>
                    </div>
                    <p class="text-[11px] text-gray-300 mb-2">{{ vuln.description }}</p>
                    <div class="p-2 rounded-xl bg-gray-950/60 text-[11px] text-emerald-300 border border-emerald-500/20">
                        <span class="font-bold">راهکار معمار:</span> {{ vuln.solution }}
                    </div>
                </div>
            </div>

            <!-- قوانین تله‌گذاری متای دفاعی -->
            <div class="bg-gray-900/80 border border-gray-700/80 rounded-2xl p-4">
                <h4 class="text-xs font-bold text-teal-300 mb-2.5 flex items-center gap-1.5">
                    <span>📐</span>
                    <span>بهینه‌سازی چینش تله‌ها برای مسابقات وار:</span>
                </h4>
                <div class="space-y-2 text-xs">
                    <div
                        v-for="(rule, trapName) in auditData.trap_optimization_rules"
                        :key="trapName"
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 p-2 rounded-xl bg-gray-950/60 border border-gray-800"
                    >
                        <span class="font-bold text-amber-300">{{ trapName }}</span>
                        <span class="text-[11px] text-gray-300">{{ rule }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "DefenseAuditCard",
    data() {
        return {
            auditData: null,
            loading: false,
        };
    },
    mounted() {
        this.scanDefense();
    },
    methods: {
        async scanDefense() {
            this.loading = true;
            try {
                const res = await fetch('/api/ai/defense-scan');
                const data = await res.json();
                if (data.ok) {
                    this.auditData = data;
                }
            } catch (e) {
                console.error("Defense scan failed", e);
            } finally {
                this.loading = false;
            }
        }
    }
};
</script>
