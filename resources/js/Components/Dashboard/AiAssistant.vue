<template>
    <div class="bg-gray-800/90 backdrop-blur-xl border border-gray-700/80 rounded-3xl p-4 sm:p-6 shadow-2xl" dir="rtl">
        <!-- هدر و معرفی دستیار چند ایجنتی -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5 pb-4 border-b border-gray-700/70">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-400 via-orange-500 to-red-600 flex items-center justify-center text-2xl shadow-lg shadow-amber-500/20 text-gray-950 font-black shrink-0">
                    🤖
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base sm:text-lg font-black text-white">مربی هوشمند چند ایجنتی NabuGate AI</h2>
                        <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40 font-bold">
                            نسخه پیشرفته ۲۰۲۶
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">مشاوره تخصصی با ۵ ایجنت هوش مصنوعی مسلط به متای وار، دفاع، اقتصاد و بازی‌های سوپرسل</p>
                </div>
            </div>

            <!-- دکمه پاکسازی گفتگو -->
            <button
                v-if="messages.length > 0"
                @click="clearChat"
                class="px-3 py-1.5 rounded-xl bg-gray-700/70 hover:bg-red-600/80 text-gray-300 hover:text-white text-xs font-bold transition flex items-center gap-1.5"
            >
                <span>🗑️</span>
                <span>شروع گفتگوی جدید</span>
            </button>
        </div>

        <!-- نوار انتخاب ایجنت متخصص -->
        <div class="mb-5">
            <label class="block text-xs font-bold text-gray-300 mb-2">ایجنت هوش مصنوعی مورد نظر را انتخاب کنید:</label>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 select-none">
                <button
                    type="button"
                    v-for="agent in agents"
                    :key="agent.id"
                    @click="activeAgentId = agent.id"
                    class="p-2.5 sm:p-3 rounded-2xl border flex flex-col items-center justify-center text-center transition-all duration-200"
                    :class="activeAgentId === agent.id
                        ? 'bg-amber-500/15 border-amber-500 text-amber-300 shadow-md shadow-amber-500/15'
                        : 'bg-gray-900/70 border-gray-700/80 text-gray-400 hover:border-gray-600 hover:text-gray-200'"
                >
                    <span class="text-xl sm:text-2xl mb-1">{{ agent.avatar }}</span>
                    <span class="text-xs font-black leading-tight">{{ agent.name }}</span>
                    <span class="text-[10px] opacity-75 mt-0.5">{{ agent.role }}</span>
                </button>
            </div>
        </div>

        <!-- کارت معرفی ایجنت فعال -->
        <div class="p-3.5 rounded-2xl bg-gray-900/60 border border-gray-700/70 mb-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="text-2xl">{{ currentAgent.avatar }}</span>
                <div>
                    <h4 class="text-xs sm:text-sm font-bold text-amber-300">{{ currentAgent.name }} — {{ currentAgent.role }}</h4>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ currentAgent.bio }}</p>
                </div>
            </div>
            <span class="hidden sm:inline-block text-[10px] px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 font-bold shrink-0">
                ● آنلاین و آماده فرمان
            </span>
        </div>

        <!-- پرامپت‌های سریع تاکتیکی متناسب با ایجنت انتخاب‌شده -->
        <div class="mb-4">
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar">
                <span class="text-[11px] font-bold text-gray-400 shrink-0 ml-1">⚡ پرسش‌های آماده:</span>
                <button
                    type="button"
                    v-for="(promptText, idx) in currentAgent.quickPrompts"
                    :key="idx"
                    @click="askQuickPrompt(promptText)"
                    :disabled="loading"
                    class="px-2.5 py-1 rounded-xl bg-gray-700/60 hover:bg-amber-500/20 hover:border-amber-500/40 border border-gray-600 text-gray-300 hover:text-amber-200 text-xs transition whitespace-nowrap shrink-0 disabled:opacity-50"
                >
                    {{ promptText }}
                </button>
            </div>
        </div>

        <!-- پنجره تاریخچه گفتگو -->
        <div class="bg-gray-900/90 border border-gray-700/80 rounded-2xl p-4 min-h-[220px] max-h-[380px] overflow-y-auto mb-4 space-y-3.5">
            <div v-if="messages.length === 0" class="flex flex-col items-center justify-center py-8 text-center text-gray-500">
                <span class="text-3xl mb-2">{{ currentAgent.avatar }}</span>
                <p class="text-xs font-bold text-gray-400">سؤالی از {{ currentAgent.name }} بپرسید یا از دکمه‌های آماده بالا استفاده کنید.</p>
                <p class="text-[11px] text-gray-500 mt-1">پاسخ‌ها بر اساس سطح تاون‌هال، نیروها و هیروهای پروفایل واقعی شما شخصی‌سازی می‌شوند.</p>
            </div>

            <div
                v-for="(msg, index) in messages"
                :key="index"
                class="flex flex-col"
                :class="msg.role === 'user' ? 'items-end' : 'items-start'"
            >
                <div class="flex items-start gap-2 max-w-[90%] sm:max-w-[85%]">
                    <!-- آواتار فرستنده -->
                    <div
                        v-if="msg.role !== 'user'"
                        class="w-7 h-7 rounded-xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-sm shrink-0 mt-0.5"
                    >
                        {{ currentAgent.avatar }}
                    </div>

                    <div
                        class="p-3.5 rounded-2xl text-xs sm:text-sm leading-relaxed"
                        :class="msg.role === 'user'
                            ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-gray-950 font-bold rounded-tl-none shadow-md shadow-amber-500/15'
                            : 'bg-gray-800 border border-gray-700 text-gray-100 rounded-tr-none shadow'"
                    >
                        <div v-if="msg.role !== 'user'" class="flex items-center justify-between gap-4 mb-1 text-[10px] text-amber-400 font-bold">
                            <span>{{ currentAgent.name }} ({{ currentAgent.role }})</span>
                            <button
                                @click="copyText(msg.content)"
                                class="text-gray-400 hover:text-white transition"
                                title="کپی متن"
                            >
                                📋 کپی
                            </button>
                        </div>
                        <div v-if="msg.action && msg.action !== 'chat'" class="mb-2">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-emerald-500/15 text-emerald-300 border border-emerald-500/40 text-[10px] font-bold">
                                <span>⚡</span>
                                <span>{{ actionLabel(msg.action) }}</span>
                            </span>
                        </div>
                        <p class="whitespace-pre-line">{{ msg.content }}</p>
                    </div>
                </div>
            </div>

            <!-- حالت در حال تایپ -->
            <div v-if="loading" class="flex items-center gap-2 text-xs text-amber-300 p-2">
                <span class="animate-spin text-base">⏳</span>
                <span>{{ currentAgent.name }} در حال تدوین نقشه عملیاتی و پردازش پاسخ است...</span>
            </div>
        </div>

        <!-- فرم ورودی پیام -->
        <form @submit.prevent="sendMessage" class="flex gap-2">
            <input
                type="text"
                v-model="userQuestion"
                :disabled="loading"
                placeholder="دستور یا پرسش تاکتیکی خود را بنویسید..."
                class="flex-1 bg-gray-900 border border-gray-700 rounded-2xl px-4 py-3 text-white text-xs sm:text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition placeholder:text-gray-500"
            />
            <button
                type="submit"
                :disabled="loading || !userQuestion.trim()"
                class="px-5 py-3 rounded-2xl bg-gradient-to-r from-amber-500 via-orange-500 to-red-600 hover:from-amber-400 hover:to-red-500 text-gray-950 font-black text-xs sm:text-sm shadow-lg shadow-amber-500/20 transition duration-200 transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-1.5 shrink-0"
            >
                <span>ارسال</span>
                <span>🚀</span>
            </button>
        </form>
    </div>
</template>

<script>
export default {
    name: 'AiAssistant',
    props: {
        gameProfile: {
            type: Object,
            required: false,
            default: () => ({})
        }
    },
    data() {
        return {
            activeAgentId: 'war_general',
            userQuestion: '',
            loading: false,
            messages: [],
            agents: [
                {
                    id: 'war_general',
                    name: 'ژنرال تایتوس',
                    role: 'ژنرال وار و CWL',
                    avatar: '⚔️',
                    bio: 'متخصص تضمین اتک‌های ۳ ستاره، فانلینگ، ترکیب اسپل‌ها، سوپر آرچر بلیمپ و هیرو دایو.',
                    quickPrompts: [
                        'پروفایل کلش من را بروزرسانی کن',
                        'برام یک تسک جدید بساز',
                        'استراتژی وار برای اکانت من بگو',
                        'چطور با ارتش متای تاون‌هالم ۳ ستاره تضمینی بزنم؟',
                    ]
                },
                {
                    id: 'progression_coach',
                    name: 'مهندس عمر',
                    role: 'تحلیلگر اقتصاد و ارتقا',
                    avatar: '📈',
                    bio: 'متخصص بودجه‌بندی سنگ‌های بلک‌اسمیت Ores، کارگرها و رفع راش بودن دهکده.',
                    quickPrompts: [
                        'سنگ‌های Ores بلک‌اسمیت رو اول روی کدوم تجهیزات خرج کنم؟',
                        'بهترین استفاده از چکش و کتاب هیرو برای اکانت من چیه؟',
                        'برنامه اولویت کارگرهام برای این ماه چی باشه؟',
                    ]
                },
                {
                    id: 'base_architect',
                    name: 'استاد داوینچی',
                    role: 'معمار بیس و دفاع',
                    avatar: '🛡️',
                    bio: 'متخصص چیدمان دفاعی، زاویه سویپرها، نقاط کور منولیت و تله‌های آنتی روت رایدر.',
                    quickPrompts: [
                        'منولیت و ریکوشت کنون رو کجای مپ بذارم که خنثی نشن؟',
                        'چطور زاویه سویپرها رو برای ضد دراگون تنظیم کنم؟',
                        'بهترین چیدمان تله‌های اسکلتی و جاینت بمب کجاست؟',
                    ]
                },
                {
                    id: 'farming_master',
                    name: 'شاه بارنابی',
                    role: 'استاد لوت و فارم',
                    avatar: '🌾',
                    bio: 'متخصص لوت ساعتی بالا، اسنیکی گابلین، بهترین محدوده کاپ و جمع‌آوری دارک اکسیر.',
                    quickPrompts: [
                        'بهترین ترکیب اسنیکی گابلین برای پر کردن سریع مخازن چیه؟',
                        'برای تاون‌هال من الان چه محدوده کاپی بیشترین لوت مرده رو داره؟',
                        'چطور روزانه بدون جم دارک اکسیر هیروها رو تامین کنم؟',
                    ]
                },
                {
                    id: 'supercell_pro',
                    name: 'مربی اسپارک',
                    role: 'سوپرسل پرو مستر',
                    avatar: '👑',
                    bio: 'مربی تخصصی کلش رویال، براول استارز، اسکواد باستر و بوم بیچ.',
                    quickPrompts: [
                        'بهترین دک کلش رویال متناسب با لول کارتهام چیه؟',
                        'کدوم براولرها رو برای هایپرشارژ و رنکد اولویت بدم؟',
                        'تاکتیک فیوژن و باز کردن پورتال در اسکواد باستر چیه؟',
                    ]
                },
            ]
        };
    },
    computed: {
        currentAgent() {
            return this.agents.find(a => a.id === this.activeAgentId) || this.agents[0];
        }
    },
    methods: {
        askQuickPrompt(promptText) {
            this.userQuestion = promptText;
            this.sendMessage();
        },
        async sendMessage() {
            const question = this.userQuestion.trim();
            if (!question || this.loading) return;

            this.messages.push({
                role: 'user',
                content: question,
            });

            this.userQuestion = '';
            this.loading = true;

            try {
                const response = await fetch('/api/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        question: question,
                        agent_mode: this.activeAgentId,
                    }),
                });

                const data = await response.json();

                this.messages.push({
                    role: 'assistant',
                    action: data.action || 'chat',
                    content: data.answer || 'پاسخی از ایجنت دریافت نشد.',
                });
            } catch (error) {
                console.error("AI Coach Request Failed", error);
                this.messages.push({
                    role: 'assistant',
                    content: 'خطا در برقراری ارتباط با مرکز فرماندهی هوش مصنوعی. لطفاً لحظاتی دیگر تلاش کنید.',
                });
            } finally {
                this.loading = false;
            }
        },
        clearChat() {
            this.messages = [];
        },
        actionLabel(action) {
            const labels = {
                refresh_profile: 'پروفایل به‌روز شد',
                generate_task: 'تسک جدید ساخته شد',
                daily_plan: 'برنامه روزانه آماده شد',
                war_strategy: 'استراتژی وار آماده شد',
                crawl_maps: 'کراول نقشه‌ها شروع شد',
            };
            return labels[action] || action;
        },
        copyText(text) {
            navigator.clipboard.writeText(text);
        }
    }
};
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
