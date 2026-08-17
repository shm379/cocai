<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg border border-gray-700 space-y-4">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pb-3 border-b border-gray-700/60">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-600 to-rose-700 flex items-center justify-center text-2xl shadow-lg">
                    ⚔️
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">سامانه هماهنگی و تارگت‌های کلن وار (War Attack Caller)</h3>
                    <p class="text-xs text-gray-400">برنامه‌ریزی، رزرو اهداف و تحلیل چیدمان بیس‌های دشمن در وار و CWL</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    @click="resetWarPlanner"
                    class="px-2.5 py-1.5 rounded-lg bg-gray-700 hover:bg-gray-600 text-gray-300 text-xs font-bold transition"
                >
                    🔄 بازنشانی
                </button>
                <button
                    @click="showAddTargetModal = true"
                    class="px-3 py-1.5 rounded-lg bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white text-xs font-bold shadow transition flex items-center gap-1"
                >
                    <span>+ ثبت تارگت جدید</span>
                </button>
            </div>
        </div>

        <!-- War Scoreboard Summary -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-center">
            <div class="bg-gray-900/70 p-2.5 rounded-xl border border-gray-700/60">
                <span class="text-[11px] text-gray-400 block mb-1">مجموع ستاره‌های کسب‌شده</span>
                <span class="text-base font-black text-amber-400 font-mono">⭐ {{ totalStars }} / {{ targets.length * 3 }}</span>
            </div>
            <div class="bg-gray-900/70 p-2.5 rounded-xl border border-gray-700/60">
                <span class="text-[11px] text-gray-400 block mb-1">حملات ۳ ستاره (Full 3-Star)</span>
                <span class="text-base font-black text-emerald-400 font-mono">🔥 {{ threeStarCount }}</span>
            </div>
            <div class="bg-gray-900/70 p-2.5 rounded-xl border border-gray-700/60">
                <span class="text-[11px] text-gray-400 block mb-1">تارگت‌های رزرو شده</span>
                <span class="text-base font-black text-cyan-400 font-mono">🎯 {{ reservedCount }} / {{ targets.length }}</span>
            </div>
            <div class="bg-gray-900/70 p-2.5 rounded-xl border border-gray-700/60">
                <span class="text-[11px] text-gray-400 block mb-1">میانگین تخریب وار</span>
                <span class="text-base font-black text-purple-400 font-mono">💥 {{ averageDestruction }}%</span>
            </div>
        </div>

        <!-- Target Bases Table / Grid -->
        <div class="space-y-2">
            <div
                v-for="(target, idx) in targets"
                :key="target.id"
                class="bg-gray-700/40 hover:bg-gray-700/70 p-3 rounded-xl border border-gray-600/50 flex flex-col md:flex-row items-start md:items-center justify-between gap-3 transition"
            >
                <!-- Target Base Info -->
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-gray-900 flex items-center justify-center font-bold text-xs text-amber-400 font-mono border border-amber-500/30">
                        #{{ target.baseNumber }}
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-white text-xs">{{ target.enemyName || ('بیس حریف #' + target.baseNumber) }}</span>
                            <span class="px-2 py-0.2 rounded-full bg-red-500/20 text-red-300 border border-red-500/30 text-[10px] font-bold">
                                TH {{ target.thLevel }}
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-0.5">
                            پیشنهاد حمله: <strong class="text-teal-300">{{ target.recommendedArmy }}</strong>
                        </p>
                    </div>
                </div>

                <!-- Attacker & Status -->
                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto justify-between md:justify-end">
                    <!-- Reserved By -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] text-gray-400">اتکر:</span>
                        <input
                            v-model="target.attackerName"
                            @change="persistTargets"
                            type="text"
                            placeholder="نام بازیکن..."
                            class="bg-gray-900 border border-gray-600 rounded-lg px-2 py-1 text-white text-xs w-28 focus:outline-none focus:border-red-500"
                        />
                    </div>

                    <!-- Stars Selector -->
                    <div class="flex items-center gap-1 bg-gray-900/80 px-2 py-1 rounded-lg border border-gray-700">
                        <button
                            v-for="s in [1, 2, 3]"
                            :key="s"
                            @click="setTargetStars(target, s)"
                            class="text-xs transition"
                            :class="target.stars >= s ? 'opacity-100 scale-110' : 'opacity-30 hover:opacity-70'"
                        >
                            ⭐
                        </button>
                        <span class="text-[10px] text-gray-400 font-mono mr-1">({{ target.stars }} ستاره)</span>
                    </div>

                    <!-- Destruction % -->
                    <div class="flex items-center gap-1">
                        <input
                            v-model.number="target.destruction"
                            @change="persistTargets"
                            type="number"
                            min="0"
                            max="100"
                            placeholder="%"
                            class="bg-gray-900 border border-gray-600 rounded-lg px-2 py-1 text-white text-xs w-14 font-mono text-center focus:outline-none focus:border-red-500"
                        />
                        <span class="text-[10px] text-gray-400">%</span>
                    </div>

                    <!-- Remove -->
                    <button
                        @click="removeTarget(idx)"
                        class="text-gray-500 hover:text-red-400 text-xs px-1"
                        title="حذف تارگت"
                    >
                        ✕
                    </button>
                </div>
            </div>
        </div>

        <!-- Add Target Modal -->
        <div v-if="showAddTargetModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="bg-gray-800 border border-gray-700 rounded-2xl p-5 w-full max-w-sm shadow-2xl space-y-3">
                <h4 class="text-base font-bold text-white">افزودن تارگت به جدول وار</h4>

                <div>
                    <label class="block text-xs text-gray-400 mb-1">شماره بیس حریف (Target #)</label>
                    <input
                        v-model.number="newTarget.baseNumber"
                        type="number"
                        min="1"
                        max="50"
                        class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-white text-xs font-mono focus:outline-none focus:border-red-500"
                    />
                </div>

                <div>
                    <label class="block text-xs text-gray-400 mb-1">لول تاون‌هال حریف (TH)</label>
                    <select
                        v-model.number="newTarget.thLevel"
                        class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-red-500"
                    >
                        <option v-for="th in [18, 17, 16, 15, 14, 13, 12, 11, 10, 9]" :key="th" :value="th">
                            تاون‌هال لول {{ th }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-400 mb-1">استراتژی پیشنهادی برای بیس</label>
                    <input
                        v-model="newTarget.recommendedArmy"
                        type="text"
                        placeholder="Super Yeti Sky Smash / Root Rider / Queen Charge"
                        class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-red-500"
                    />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        @click="showAddTargetModal = false"
                        class="px-3 py-1.5 rounded-xl bg-gray-700 text-gray-300 text-xs font-bold hover:bg-gray-600"
                    >
                        انصراف
                    </button>
                    <button
                        @click="saveNewTarget"
                        class="px-4 py-1.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs font-bold shadow"
                    >
                        افزودن
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'WarPlannerHub',
    props: {
        playerTownHall: {
            type: Number,
            default: 15
        }
    },
    data() {
        return {
            showAddTargetModal: false,
            newTarget: {
                baseNumber: 1,
                thLevel: 16,
                recommendedArmy: 'Super Yeti Sky Smash'
            },
            targets: []
        }
    },
    computed: {
        totalStars() {
            return this.targets.reduce((sum, t) => sum + (t.stars || 0), 0)
        },
        threeStarCount() {
            return this.targets.filter(t => t.stars === 3).length
        },
        reservedCount() {
            return this.targets.filter(t => !!t.attackerName?.trim()).length
        },
        averageDestruction() {
            if (!this.targets.length) return 0
            const total = this.targets.reduce((sum, t) => sum + (t.destruction || 0), 0)
            return Math.round(total / this.targets.length)
        }
    },
    mounted() {
        this.loadTargets()
    },
    methods: {
        loadTargets() {
            try {
                const stored = localStorage.getItem('cocai_war_targets')
                if (stored) {
                    this.targets = JSON.parse(stored)
                } else {
                    // تارگت‌های پیش‌فرض آزمایشی
                    this.targets = [
                        { id: 1, baseNumber: 1, enemyName: 'Chief Shadow', thLevel: 17, recommendedArmy: 'Dragon Duke Hydra Charge', attackerName: 'شما', stars: 3, destruction: 100 },
                        { id: 2, baseNumber: 2, enemyName: 'Viking War', thLevel: 16, recommendedArmy: 'Root Rider Valkyrie Smash', attackerName: 'Reza', stars: 3, destruction: 100 },
                        { id: 3, baseNumber: 3, enemyName: 'Dragon Slayer', thLevel: 16, recommendedArmy: 'Super Yeti Sky Smash', attackerName: '', stars: 2, destruction: 85 },
                        { id: 4, baseNumber: 4, enemyName: 'Dark Knight', thLevel: 15, recommendedArmy: 'Queen Charge Hybrid', attackerName: '', stars: 0, destruction: 0 },
                        { id: 5, baseNumber: 5, enemyName: 'Iron Fortress', thLevel: 15, recommendedArmy: 'Lavaloon Zap Charge', attackerName: '', stars: 0, destruction: 0 },
                    ]
                    this.persistTargets()
                }
            } catch (e) {
                console.error(e)
            }
        },
        persistTargets() {
            try {
                localStorage.setItem('cocai_war_targets', JSON.stringify(this.targets))
            } catch (e) {
                console.error(e)
            }
        },
        setTargetStars(target, stars) {
            if (target.stars === stars) {
                target.stars = 0
                target.destruction = 0
            } else {
                target.stars = stars
                if (stars === 3) target.destruction = 100
                else if (stars === 2 && target.destruction < 50) target.destruction = 75
                else if (stars === 1 && target.destruction < 50) target.destruction = 52
            }
            this.persistTargets()
        },
        saveNewTarget() {
            const nextId = this.targets.length ? Math.max(...this.targets.map(t => t.id)) + 1 : 1
            this.targets.push({
                id: nextId,
                baseNumber: this.newTarget.baseNumber || (this.targets.length + 1),
                enemyName: 'بیس حریف #' + (this.newTarget.baseNumber || (this.targets.length + 1)),
                thLevel: this.newTarget.thLevel || 16,
                recommendedArmy: this.newTarget.recommendedArmy || 'Super Yeti Sky Smash',
                attackerName: '',
                stars: 0,
                destruction: 0
            })
            this.persistTargets()
            this.showAddTargetModal = false
            this.newTarget.baseNumber = this.targets.length + 1
        },
        removeTarget(idx) {
            this.targets.splice(idx, 1)
            this.persistTargets()
        },
        resetWarPlanner() {
            if (confirm('آیا از بازنشانی تارگت‌های وار اطمینان دارید؟')) {
                this.targets = [
                    { id: 1, baseNumber: 1, enemyName: 'بیس حریف #۱', thLevel: 17, recommendedArmy: 'Dragon Duke Hydra Charge', attackerName: '', stars: 0, destruction: 0 },
                    { id: 2, baseNumber: 2, enemyName: 'بیس حریف #۲', thLevel: 16, recommendedArmy: 'Super Yeti Sky Smash', attackerName: '', stars: 0, destruction: 0 },
                    { id: 3, baseNumber: 3, enemyName: 'بیس حریف #۳', thLevel: 15, recommendedArmy: 'Root Rider Smash', attackerName: '', stars: 0, destruction: 0 },
                ]
                this.persistTargets()
            }
        }
    }
}
</script>
