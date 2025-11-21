<template>
    <nav class="fixed bottom-0 left-0 right-0 bg-gray-800 bg-opacity-90 shadow-inner z-50">
        <div class="tab-buttons flex gap-2 sm:gap-4 m-6 justify-center">
            <button
                v-for="tabName in tabs"
                :key="tabName"
                @click="setActiveTab(tabName)"
                class="flex flex-col items-center px-4 sm:px-6 py-2 font-semibold rounded-full transition-colors duration-300
               text-xs sm:text-sm
               shadow focus:outline-none focus:ring-2 focus:ring-offset-2
               hover:shadow-lg"
                :class="[
          activeTab === tabName
            ? 'bg-gradient-to-r from-orange-500 to-red-500 text-white shadow-orange-400/50'
            : 'bg-gray-700 text-gray-200 hover:bg-gray-600'
        ]"
            >
                <img
                    v-if="tabIcons[tabName]"
                    :src="tabIcons[tabName]"
                    alt="Tab Icon"
                    class="w-6 h-6 mb-1"
                    style="background: #fff;border-radius: 50%"
                />
                <span>{{ tabLabels[tabName] }}</span>
            </button>
        </div>
    </nav>
</template>

<script>
export default {
    name: "BottomNav",
    props: {
        activeTab: {
            type: String,
            default: 'profile'
        }
    },
    // در Vue 3، بهتر است emits را هم صریح بنویسید:
    emits: ['update:activeTab'],

    data(){
        return {
            tabs: [
                'profile',
                'troops',
                'th_maps',
                'bh_maps',
                'achievements',
                'progressChart',
                'clanOverview',
                'builderBase',
                'assistant',
            ],
            tabIcons: {
                profile: '/images/icons/user.png',
                troops: '/images/icons/trophy.png',
                th_maps: '/images/icons/library.png',
                bh_maps: '/images/icons/construction.png',
                achievements: '/images/icons/achivement.png',
                progressChart: '/images/icons/bar-chart.png',
                clanOverview: '/images/icons/construction.png',
                builderBase: '/images/icons/construction.png',
                assistant: '/images/icons/construction.png'
            },
            tabLabels: {
                profile: 'پروفایل',
                troops: 'نیروها',
                th_maps: 'نقشه های تاون هال',
                bh_maps: 'نقشه های بیلدر هال',
                achievements: 'دستاوردها',
                progressChart: 'نمودار تروفی',
                clanOverview: 'وضعیت کلن',
                builderBase: 'بیلدر بیس',
                assistant: 'دستیار AI'
            }
        }
    },
    methods: {
        setActiveTab(tabName) {
            // به جای تغییر مستقیم prop، رویدادی emit می‌کنیم
            this.$emit('update:activeTab', tabName)
        }
    }
}
</script>
