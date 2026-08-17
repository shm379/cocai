<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg border border-gray-700 space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-gray-700/60">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-2xl shadow-lg">
                    🚜
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">راهنمای تخصصی ماشین‌های محاصره (Siege Machines & CC Meta)</h3>
                    <p class="text-xs text-gray-400">بهترین ترکیب نیروهای کلن کستل درون ماشین و استراتژی نفوذ در تاون‌هال‌های بالا</p>
                </div>
            </div>
        </div>

        <!-- Siege Machines Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div
                v-for="siege in sieges"
                :key="siege.name"
                class="bg-gray-700/40 hover:bg-gray-700/70 p-3.5 rounded-xl border border-gray-600/50 space-y-2.5 transition"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">{{ siege.icon }}</span>
                        <h4 class="text-xs font-bold text-white">{{ siege.name }}</h4>
                    </div>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-orange-500/20 text-orange-300 border border-orange-500/30 font-bold">
                        {{ siege.role }}
                    </span>
                </div>

                <p class="text-xs text-gray-300 leading-relaxed">{{ siege.description }}</p>

                <div class="bg-gray-900/60 p-2.5 rounded-lg border border-gray-700/80 text-xs space-y-1">
                    <div class="text-amber-300 font-bold text-[11px]">
                        👥 بهترین نیروی کلن‌کستل (CC Loadout):
                        <span class="text-white font-normal block mt-0.5">{{ siege.bestCC }}</span>
                    </div>
                    <div class="text-teal-300 font-bold text-[11px]">
                        🎯 تکنیک پیاده‌سازی و فانل:
                        <span class="text-gray-300 font-normal block mt-0.5">{{ siege.funnelTip }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'SiegeMachineAdvisor',
    data() {
        return {
            sieges: [
                {
                    name: 'بتل بلیمپ (Battle Blimp) 🎈',
                    icon: '🎈',
                    role: 'تخریب هسته و تاون‌هال',
                    description: 'بالون پرسرعت هوایی که مستقیماً به سمت تاون‌هال پرواز می‌کند و برای استراتژی‌های SArch Blimp و Blizzard کاربرد دارد.',
                    bestCC: '۴ عدد سوپر آرچر (SArch) یا ۴ عدد سوپر ویزارد (Blizzard) + اسپل Clone و Invisibility',
                    funnelTip: 'قبل از رسیدن به مرکز بیس، واردن با قابلیت سپر Eternal Tome فعال شود و به محض فرود، زنجیره اسپل نامرئی هر ۴ ثانیه تکرار گردد.'
                },
                {
                    name: 'فلیم فلینگر (Flame Flinger) 🪨',
                    icon: '🪨',
                    role: 'پاکسازی از راه دور بدون آسیب',
                    description: 'پرتاب آتش‌بار با برد بسیار بالا (دورتر از تمامی دفاعی‌ها به جز Mortar و X-Bow) برای ایجاد فانل رایگان.',
                    bestCC: '۲ عدد دراگون رایدر یا ترکیب پکا + سوپر مینیون جهت پشتیبانی انتهای حمله',
                    funnelTip: 'در گوشه‌ای پیاده شود که هیچ مورتار یا ایکس‌بو به آن نرسد؛ می‌تواند یک بخش کامل بیس و اینفرنو را پاکسازی کند.'
                },
                {
                    name: 'سیج باراکس (Siege Barracks) ⛺',
                    icon: '⛺',
                    role: 'فانل عظیم زمینی + هیرو کمکی',
                    description: 'آزاد کردن ۱ پکا و تعدادی ویزارد در طول زمان و سپس باز شدن کلن‌کستل برای پشتیبانی ارتش اصلی.',
                    bestCC: 'سوپر هاگ ریدر / هوگ ریدر / ماینر جهت اضافه شدن به موج حمله اصلی',
                    funnelTip: 'در یک گوشه نقشه پیاده شود تا مسیر را برای ورود پکاها و هیروها به عمق بیس باریک و مستقیم کند.'
                },
                {
                    name: 'لاگ لانچر (Log Launcher) 🪵',
                    icon: '🪵',
                    role: 'شکافتن دیوارها تا هسته',
                    description: 'پرتاب تنه‌های درخت سنگین به عمق بیس که همزمان دیوارها و دفاعی‌های پشت سر هم را تا تاون‌هال منهدم می‌کند.',
                    bestCC: '۲ عدد آیس گولم + بولر / یتی برای فریز کردن هسته بیس',
                    funnelTip: 'پشت سر تانک‌های اصلی (Root Rider / Golem) رها شود تا مسیر عبور مستقیم به قلب بیس باز شود.'
                }
            ]
        }
    }
}
</script>
