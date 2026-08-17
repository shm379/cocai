<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg border border-gray-700 space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-gray-700/60">
            <div class="flex items-center gap-2.5">
                <span class="text-2xl">🛡️</span>
                <div>
                    <h3 class="text-base font-bold text-white">ماتریس دفاعی و خنثی‌سازی ساختمان‌های سنگین (Defense Matrix)</h3>
                    <p class="text-xs text-gray-400">راهنمای تخصصی مقابله با دفاعی‌های ادغامی و سنگین TH15 تا TH18</p>
                </div>
            </div>
        </div>

        <!-- Defenses Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div
                v-for="def in defenses"
                :key="def.name"
                class="bg-gray-700/40 hover:bg-gray-700/70 p-3.5 rounded-xl border border-gray-600/50 space-y-2 transition"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">{{ def.icon }}</span>
                        <h4 class="text-xs font-bold text-white">{{ def.name }}</h4>
                    </div>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-red-500/20 text-red-300 border border-red-500/30 font-mono">
                        {{ def.dps }}
                    </span>
                </div>

                <p class="text-xs text-gray-300 leading-relaxed">{{ def.description }}</p>

                <div class="bg-gray-900/60 p-2 rounded-lg border border-gray-700/80 text-[11px] space-y-1">
                    <div class="text-amber-300 font-bold">
                        🎯 روش خنثی‌سازی: <span class="text-gray-200 font-normal">{{ def.counter }}</span>
                    </div>
                    <div class="text-teal-300 font-bold">
                        🧪 اسپل و هیرو پیشنهادی: <span class="text-gray-300 font-normal">{{ def.bestSpell }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'DefenseMatrixAdvisor',
    data() {
        return {
            defenses: [
                {
                    name: 'مونو‌لیث (Monolith)',
                    icon: '🗿',
                    dps: '200 + 15% Max HP DPS',
                    description: 'خطرناک‌ترین دفاعی بازی برای هیروها و تانک‌های سنگین (گولم، روت‌رایدر، پکا). دمیج درصدی به حداکثر جان نیرو وارد می‌کند.',
                    counter: 'به هیچ وجه اجازه ندهید روی کینگ یا کوئین قفل بماند؛ با نیروهای دسته‌جمعی (Skeleton/Bat) یا فریز خنثی کنید.',
                    bestSpell: 'اسپل Freeze / اسپل Overgrowth / تجهیزات Giant Gauntlet'
                },
                {
                    name: 'ریکوشت کنون (Ricochet Cannon)',
                    icon: '💥',
                    dps: '420+ DPS (Dual Bounce)',
                    description: 'کنون ادغامی تاون‌هال ۱۶ به بالا که گلوله‌های آن بعد از اصابت به اولین هدف، به هدف مجاور پرتاب می‌شوند.',
                    counter: 'پخش کردن نیروها و جلوگیری از تجمع هیروها با نیروهای ضعیف در زاویهٔ مستقیم شلیک.',
                    bestSpell: 'Healer Puppet / Fireball واردن / سپر هوایی'
                },
                {
                    name: 'مولتی آرچر تاور (Multi-Archer Tower)',
                    icon: '🏹',
                    dps: '120 DPS x 3 Targets',
                    description: 'آرچر تاور ادغامی که همزمان به ۳ هدف زمینی یا هوایی شلیک سریع می‌کند.',
                    counter: 'خنثی‌سازی با اسپل‌های یخ و ورود سریع تانک‌ها جهت جذب هر ۳ تیر.',
                    bestSpell: 'Rage Spell + Poison Lizard'
                },
                {
                    name: 'اسپل تاورها (Spell Towers - Poison/Invis/Rage)',
                    icon: '🧪',
                    dps: 'Area Control & Buff',
                    description: 'ایجاد مه سم برای کشتن سریع نیروها، نامرئی کردن تاون‌هال و دفاعی‌ها، یا باف خشم روی منولیت و ایگل.',
                    counter: 'استفاده از اسپل زلزله یا تریگر کردن زودهنگام با ۱ بالون/هوگ قبل از حمله اصلی.',
                    bestSpell: 'Overgrowth Spell (خنثی‌سازی کامل تاور و تاون‌هال برای ۲۸ ثانیه)'
                },
                {
                    name: 'ایگل آرتیلری (Eagle Artillery)',
                    icon: '🦅',
                    dps: '500+ Burst Splash',
                    description: 'شلیک ۳ موج مرگبار به سراسر نقشه پس از پیاده‌سازی ۲۰۰ واحد اکسیر از ارتش شما.',
                    counter: 'وارد کردن ارتش اصلی با قابلیت سپر طلایی Grand Warden (Eternal Tome) در حین اولین شلیک ایگل.',
                    bestSpell: 'Grand Warden Eternal Tome + Healing Tome'
                },
                {
                    name: 'گیگا اینفرنو تاون‌هال (Giga Inferno TH16-18)',
                    icon: '🏛️',
                    dps: '3200 Splash Death Bomb',
                    description: 'انفجار نهایی مرگبار با اسپل سم و یخ هنگام نابودی تاون‌هال که ارتش درون محدوده را نابود می‌کند.',
                    counter: 'فعال‌سازی توانایی واردن هنگام ترکیدن تاون‌هال یا استفاده از Blimp با اسپل نامرئی.',
                    bestSpell: 'Invisibility Spell / Warden Eternal Tome'
                }
            ]
        }
    }
}
</script>
