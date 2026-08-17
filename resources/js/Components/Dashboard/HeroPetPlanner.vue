<template>
    <div class="bg-gray-800/90 backdrop-blur p-4 rounded-xl shadow-lg border border-gray-700 space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-gray-700/60">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-600 to-teal-700 flex items-center justify-center text-2xl shadow-lg">
                    🐾
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">راهنمای حیوانات خانگی هیروها و متای همراهی (Hero Pets Matrix)</h3>
                    <p class="text-xs text-gray-400">بهترین ترکیب پت‌ها با هر قهرمان بر اساس متای مسابقات جهانی ۲۰۲۶</p>
                </div>
            </div>
        </div>

        <!-- Hero-Pet Pairings Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div
                v-for="pair in pairings"
                :key="pair.hero"
                class="bg-gray-700/40 hover:bg-gray-700/70 p-3.5 rounded-xl border border-gray-600/50 space-y-2.5 transition"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">{{ pair.heroIcon }}</span>
                        <h4 class="text-xs font-bold text-white">{{ pair.hero }}</h4>
                    </div>
                    <span class="text-[11px] px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 font-bold">
                        بهترین پت: {{ pair.bestPet }}
                    </span>
                </div>

                <div class="bg-gray-900/60 p-2.5 rounded-lg border border-gray-700/80 text-xs space-y-1.5">
                    <div class="text-amber-300 font-bold">
                        ⚡ دلیل انتخاب در متا:
                        <span class="text-gray-300 font-normal block mt-0.5">{{ pair.reason }}</span>
                    </div>
                    <div class="text-cyan-300 font-bold text-[11px]">
                        🔄 جایگزین دوم (Alternative):
                        <span class="text-gray-300 font-normal">{{ pair.altPet }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- All 10 Pets Roster -->
        <div class="mt-4 pt-3 border-t border-gray-700/60">
            <h4 class="text-xs font-bold text-gray-300 mb-3">مشخصات و قابلیت‌های ویژه تمامی ۱۰ پت بازی:</h4>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-center text-xs">
                <div
                    v-for="pet in allPets"
                    :key="pet.name"
                    class="bg-gray-900/80 p-2.5 rounded-xl border border-gray-700 hover:border-emerald-500/50 transition flex flex-col justify-between"
                >
                    <span class="text-2xl mb-1">{{ pet.icon }}</span>
                    <span class="font-bold text-white text-[11px]">{{ pet.name }}</span>
                    <span class="text-[10px] text-gray-400 mt-1">{{ pet.ability }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'HeroPetPlanner',
    data() {
        return {
            pairings: [
                {
                    hero: 'آرچر کوئین (Archer Queen)',
                    heroIcon: '👑',
                    bestPet: 'اسپریت فاکس (Spirit Fox) 🦊',
                    reason: 'نامرئی کردن متناوب کوئین در هر ۴ ثانیه به مدت ۳ ثانیه، بقای کوئین در کوئین‌واک و لایه‌های مرکزی بیس را دو برابر می‌کند.',
                    altPet: 'تک‌شاخ یونیکورن (Unicorn) 🦄 برای هیل پیوسته'
                },
                {
                    hero: 'رویال چمپیون (Royal Champion)',
                    heroIcon: '🛡️',
                    bestPet: 'دیگی (Diggy) 🦔 یا اسپریت فاکس',
                    reason: 'استن (Stun) کردن مداوم دفاعی‌ها به محض خروج از زیر زمین، به آرسی اجازه می‌دهد مونو‌لیث و اینفرنو را بدون آسیب نابود کند.',
                    altPet: 'انگری ژلی (Angry Jelly) 🪼 برای پرش از روی دیوارها'
                },
                {
                    hero: 'باربارین کینگ (Barbarian King)',
                    heroIcon: '⚔️',
                    bestPet: 'ققنوس (Phoenix) 🦅',
                    reason: 'هنگام کشته شدن کینگ، ققنوس او را برای ۸ ثانیه رویین‌تن به همراه تخم احیا زنده نگه می‌دارد تا تاون‌هال یا بخش‌های سنگین را پاکسازی کند.',
                    altPet: 'مایتی یاک (Mighty Yak) 🦬 برای شکستن دیوارهای لایه اول'
                },
                {
                    hero: 'گرند واردن (Grand Warden)',
                    heroIcon: '🧙‍♂️',
                    bestPet: 'فراستی (Frosty) 🦎 یا الکترو اول',
                    reason: 'کاهش سرعت شلیک دفاعی‌های سنگین اطراف واردن با پرتاب گلوله‌های یخی و ایجاد کندی (Slowdown).',
                    altPet: 'الکترو اول (Electro Owl) 🦉 برای دمیج زنجیره‌ای هوایی'
                }
            ],
            allPets: [
                { name: 'Spirit Fox', icon: '🦊', ability: 'نامرئی‌سازی متناوب هیرو' },
                { name: 'Diggy', icon: '🦔', ability: 'حفر زمین و استن دفاعی‌ها' },
                { name: 'Phoenix', icon: '🦅', ability: 'احیای موقت هیرو پس از مرگ' },
                { name: 'Unicorn', icon: '🦄', ability: 'هیل مداوم جان هیرو' },
                { name: 'Frosty', icon: '🦎', ability: 'کندی و فریز دفاعی‌ها' },
                { name: 'Angry Jelly', icon: '🪼', ability: 'تغییر هدف هیرو به دفاعی' },
                { name: 'Poison Lizard', icon: '🦎', ability: 'پرتاب تف سمی به هیروهای حریف' },
                { name: 'Electro Owl', icon: '🦉', ability: 'شلیک صاعقه زنجیره‌ای' },
                { name: 'Mighty Yak', icon: '🦬', ability: 'تخریب سریع دیوارهای سنگی' },
                { name: 'L.A.S.S.I', icon: '🐶', ability: 'پرش از روی دیوارها و حمله' }
            ]
        }
    }
}
</script>
