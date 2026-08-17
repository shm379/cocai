<template>
    <div class="bg-gray-800/90 backdrop-blur-xl border border-gray-700/80 rounded-3xl p-4 sm:p-6 shadow-2xl" dir="rtl">
        <!-- هدر بوم بصری وار -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5 pb-4 border-b border-gray-700/70">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 flex items-center justify-center text-2xl shadow-lg shadow-purple-500/20 text-white font-black shrink-0">
                    🗺️
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base sm:text-lg font-black text-white">بوم بصری مسیر حمله و زاویه ورود (Visual Attack Canvas)</h2>
                        <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/40 font-bold">
                            تعاملی سه‌بعدی
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">مشاهده مسیر حرکتی روت رایدرها، شعاع ابیلیتی گرند واردن و نقاط پیاده‌سازی اسپل</p>
                </div>
            </div>

            <!-- انتخاب سناریوی نبرد -->
            <div class="flex items-center gap-2">
                <button
                    v-for="mode in attackModes"
                    :key="mode.id"
                    @click="activeMode = mode.id"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition"
                    :class="activeMode === mode.id
                        ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/25'
                        : 'bg-gray-900/80 text-gray-400 hover:text-white border border-gray-700/70'"
                >
                    {{ mode.name }}
                </button>
            </div>
        </div>

        <!-- بوم شماتیک حمله (SVG Interactive Canvas) -->
        <div class="relative w-full aspect-[16/9] max-h-[380px] bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 rounded-2xl border border-gray-700/80 overflow-hidden shadow-inner flex items-center justify-center">
            <!-- خطوط شطرنجی زمین بازی کلش -->
            <svg class="absolute inset-0 w-full h-full opacity-20 pointer-events-none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="#64748b" stroke-width="0.8"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)" />
            </svg>

            <!-- محوطه تاون‌هال و هسته مرکزی (Center Core) -->
            <div class="absolute w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-amber-500/15 border-2 border-dashed border-amber-400/60 flex flex-col items-center justify-center text-center shadow-lg shadow-amber-500/10">
                <span class="text-2xl sm:text-3xl animate-bounce">🏰</span>
                <span class="text-[10px] sm:text-xs font-black text-amber-300 mt-1">تاون‌هال ۱۶/۱۷</span>
            </div>

            <!-- شعاع ابیلیتی گرند واردن (Warden Eternal Tome Radius) -->
            <div class="absolute w-44 h-44 sm:w-56 sm:h-56 rounded-full bg-cyan-500/10 border-2 border-cyan-400/40 animate-pulse flex items-center justify-center pointer-events-none">
                <span class="absolute top-2 text-[9px] text-cyan-300 font-bold bg-gray-950/80 px-2 py-0.5 rounded-full">
                    🛡️ شعاع محافظت گرند واردن (Eternal Tome)
                </span>
            </div>

            <!-- اسپل اوورگروث بر روی اینفرنو / منولیت -->
            <div class="absolute top-8 right-12 sm:right-24 w-16 h-16 rounded-full bg-emerald-500/25 border-2 border-emerald-400 animate-ping pointer-events-none"></div>
            <div class="absolute top-8 right-12 sm:right-24 px-2 py-1 rounded-lg bg-emerald-950/90 border border-emerald-400/60 text-[9px] text-emerald-300 font-bold flex items-center gap-1 shadow">
                <span>🌿</span>
                <span>اسپل Overgrowth (منولیت غیرفعال)</span>
            </div>

            <!-- نقطه پیاده‌سازی و فلش ورود نیروها (Funnel Entrance) -->
            <div class="absolute bottom-6 left-12 sm:left-24 flex flex-col items-center">
                <div class="px-3 py-1 rounded-xl bg-gradient-to-r from-red-600 to-amber-500 text-white text-[10px] sm:text-xs font-black shadow-lg shadow-red-500/30 flex items-center gap-1">
                    <span>⚔️</span>
                    <span>ورود روت رایدرها و کینگ (فانل ساعت ۷)</span>
                </div>
                <div class="text-amber-400 text-2xl animate-bounce mt-1">⬆️</div>
            </div>

            <!-- مسیر سویپ رویال چمپیون (RC Back-end Sweep) -->
            <div class="absolute bottom-8 right-10 sm:right-20 flex flex-col items-center">
                <div class="px-2.5 py-1 rounded-xl bg-purple-900/90 border border-purple-400/50 text-purple-200 text-[10px] font-bold flex items-center gap-1">
                    <span>👑</span>
                    <span>حرکت رویال چمپیون (ساعت ۴)</span>
                </div>
            </div>
        </div>

        <!-- تایم‌لاین گام به گام نبرد -->
        <div class="mt-5 pt-4 border-t border-gray-700/70">
            <div class="flex items-center justify-between gap-2 mb-2 text-xs font-bold text-gray-300">
                <span>پیشروی زمانی حمله وار:</span>
                <span class="text-amber-400 font-mono">{{ currentStepTime }} ثانیه از نبرد</span>
            </div>
            <div class="grid grid-cols-4 gap-2">
                <button
                    v-for="step in battleSteps"
                    :key="step.time"
                    @click="currentStepTime = step.time"
                    class="p-2.5 rounded-xl border text-center transition"
                    :class="currentStepTime === step.time
                        ? 'bg-amber-500/20 border-amber-500 text-amber-300 shadow'
                        : 'bg-gray-900/80 border-gray-700/70 text-gray-400 hover:border-gray-600'"
                >
                    <div class="text-xs font-black font-mono">{{ step.label }}</div>
                    <div class="text-[10px] text-gray-300 mt-0.5 truncate">{{ step.desc }}</div>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "WarAttackVisualizer",
    data() {
        return {
            activeMode: 'smash',
            currentStepTime: '0:30',
            attackModes: [
                { id: 'smash', name: '💥 روت رایدر اسمش' },
                { id: 'sarch', name: '⚡ سوپر آرچر بلیمپ' },
                { id: 'qc', name: '🏹 کویین شارژ' },
            ],
            battleSteps: [
                { time: '0:15', label: '۰:۱۵', desc: 'پاکسازی فانل بیرون' },
                { time: '0:45', label: '۰:۴۵', desc: 'نفوذ به لایه اول' },
                { time: '1:30', label: '۱:۳۰', desc: 'ابیلیتی واردن و تاون‌هال' },
                { time: '2:15', label: '۲:۱۵', desc: 'پاکسازی ۳ ستاره' },
            ]
        };
    }
};
</script>
