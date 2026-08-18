<template>
    <div class="min-h-screen bg-gray-900 text-white p-4 pb-28">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold">آزمایشگاه استراتژی</h1>
                <Link :href="route('dashboard')" class="text-sm text-gray-300 hover:text-white underline">
                    بازگشت به داشبورد
                </Link>
            </div>

            <p class="text-gray-300 mb-6">
                نقشهٔ بیس خود را آپلود کنید، ساختمان‌ها را روی آن علامت‌گذاری کنید و نقاط ضعف و بهترین مسیر ورود را دریافت کنید.
            </p>

            <!-- آپلود تصویر -->
            <div v-if="!imageUrl" class="mb-6 p-8 border-2 border-dashed border-gray-600 rounded-xl text-center">
                <label class="cursor-pointer inline-block">
                    <span class="px-6 py-3 bg-gradient-to-r from-orange-500 to-red-500 rounded-lg font-semibold hover:shadow-lg transition">
                        انتخاب عکس نقشه
                    </span>
                    <input type="file" accept="image/*" class="hidden" @change="handleImageUpload">
                </label>
                <p class="mt-3 text-sm text-gray-400">فرمت‌های JPG، PNG و WebP تا حداکثر ۵ مگابایت</p>
            </div>

            <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- بخش تصویر و علامت‌گذاری -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="bg-gray-800 p-3 rounded-xl">
                        <!-- انتخابگر نوع ساختمان -->
                        <div class="mb-3">
                            <p class="text-sm text-gray-300 mb-2">نوع ساختمان برای علامت‌گذاری:</p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="type in buildingTypes"
                                    :key="type.key"
                                    @click="selectedType = type.key"
                                    class="px-2 py-1 text-xs rounded border transition"
                                    :class="selectedType === type.key ? 'bg-orange-600 border-orange-400' : 'bg-gray-700 border-gray-600 hover:bg-gray-600'"
                                >
                                    <span class="inline-block w-2 h-2 rounded-full mr-1" :style="{ backgroundColor: type.color }"></span>
                                    {{ type.label }}
                                </button>
                            </div>
                        </div>

                        <!-- کانتینر تصویر -->
                        <div class="relative inline-block w-full">
                            <img
                                ref="mapImage"
                                :src="imageUrl"
                                class="w-full rounded-lg select-none"
                                draggable="false"
                                @click="handleMapClick"
                                @load="handleImageLoad"
                            >
                            <canvas
                                ref="markerCanvas"
                                class="absolute top-0 left-0 w-full h-full pointer-events-none"
                            ></canvas>
                        </div>

                        <p class="mt-2 text-xs text-gray-400">
                            روی تصویر کلیک کنید تا ساختمان {{ selectedTypeLabel }} اضافه شود.
                        </p>
                    </div>

                    <!-- عملیات -->
                    <div class="flex flex-wrap gap-3">
                        <button
                            @click="analyze"
                            :disabled="analyzing || buildings.length === 0"
                            class="px-4 py-2 bg-green-600 rounded-lg hover:bg-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ analyzing ? 'در حال تحلیل...' : 'تحلیل نقشه' }}
                        </button>

                        <button
                            v-if="imageFile"
                            @click="detectWithVision"
                            :disabled="detectingVision"
                            class="px-4 py-2 bg-purple-600 rounded-lg hover:bg-purple-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <span>🤖</span>
                            <span>{{ detectingVision ? 'در حال تشخیص با AI...' : 'تشخیص خودکار با AI' }}</span>
                        </button>

                        <button
                            @click="openSaveModal"
                            :disabled="saving || buildings.length === 0"
                            class="px-4 py-2 bg-blue-600 rounded-lg hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ saving ? 'در حال ذخیره...' : 'ذخیرهٔ جلسه' }}
                        </button>

                        <button
                            @click="resetAll"
                            class="px-4 py-2 bg-gray-700 rounded-lg hover:bg-gray-600"
                        >
                            شروع مجدد
                        </button>

                        <label class="px-4 py-2 bg-gray-700 rounded-lg hover:bg-gray-600 cursor-pointer">
                            تغییر تصویر
                            <input type="file" accept="image/*" class="hidden" @change="handleImageUpload">
                        </label>
                    </div>

                    <!-- نتایج تحلیل -->
                    <div v-if="analysis" class="bg-gray-800 p-4 rounded-xl">
                        <h2 class="text-lg font-bold mb-3">نتیجهٔ تحلیل</h2>

                        <div v-if="analysis.ok === false" class="text-red-400">
                            {{ analysis.message }}
                        </div>

                        <template v-else>
                            <div class="mb-4">
                                <p class="text-sm text-gray-300">{{ analysis.summary }}</p>
                                <p class="text-xs text-gray-400 mt-1">تعداد ساختمان‌ها: {{ analysis.building_count }}</p>
                            </div>

                            <div class="mb-4">
                                <h3 class="font-semibold text-orange-400 mb-2">نقاط ضعف</h3>
                                <ul class="space-y-2">
                                    <li
                                        v-for="(point, idx) in analysis.weak_points"
                                        :key="idx"
                                        class="p-2 rounded bg-gray-700 text-sm"
                                        :class="severityBorder(point.severity)"
                                    >
                                        <strong>{{ point.title }}</strong>
                                        <p class="text-gray-300 mt-1">{{ point.description }}</p>
                                    </li>
                                </ul>
                            </div>

                            <div>
                                <h3 class="font-semibold text-green-400 mb-2">پیشنهادهای ورود</h3>
                                <ul class="space-y-2">
                                    <li
                                        v-for="(entry, idx) in analysis.entry_suggestions"
                                        :key="idx"
                                        class="p-2 rounded bg-gray-700 text-sm"
                                    >
                                        <strong>ورود از سمت {{ entry.side }}</strong>
                                        <p class="text-gray-300 mt-1">{{ entry.reason }} (امتیاز: {{ entry.score }})</p>
                                    </li>
                                </ul>
                                <p v-if="analysis.entry_suggestions.length === 0" class="text-sm text-gray-400">
                                    اطلاعات کافی برای پیشنهاد ورود وجود ندارد.
                                </p>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- سایدبار: ساختمان‌ها و جلسات قبلی -->
                <div class="space-y-4">
                    <!-- لیست ساختمان‌ها -->
                    <div class="bg-gray-800 p-4 rounded-xl max-h-[60vh] overflow-y-auto">
                        <h2 class="font-bold mb-3">ساختمان‌های علامت‌گذاری‌شده</h2>
                        <ul v-if="buildings.length" class="space-y-2">
                            <li
                                v-for="(b, idx) in buildings"
                                :key="b.id"
                                class="flex items-center justify-between p-2 rounded bg-gray-700 text-sm"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-3 h-3 rounded-full"
                                        :style="{ backgroundColor: buildingColor(b.type) }"
                                    ></span>
                                    <span>{{ buildingLabel(b.type) }}</span>
                                    <span class="text-xs text-gray-400">({{ Math.round(b.x) }}%, {{ Math.round(b.y) }}%)</span>
                                </div>
                                <button @click="removeBuilding(idx)" class="text-red-400 hover:text-red-300 text-xs">
                                    حذف
                                </button>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-400">هنوز ساختمانی اضافه نشده است.</p>
                    </div>

                    <!-- جلسات قبلی -->
                    <div class="bg-gray-800 p-4 rounded-xl">
                        <h2 class="font-bold mb-3">جلسات ذخیره‌شده</h2>
                        <ul v-if="sessions.length" class="space-y-2">
                            <li
                                v-for="session in sessions"
                                :key="session.id"
                                class="flex items-center justify-between p-2 rounded bg-gray-700 text-sm"
                            >
                                <button
                                    @click="loadSession(session.id)"
                                    class="text-left hover:text-orange-400 truncate flex-1"
                                >
                                    {{ session.title || 'جلسه بدون نام' }}
                                </button>
                                <button @click="deleteSession(session.id)" class="text-red-400 hover:text-red-300 text-xs mr-2">
                                    حذف
                                </button>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-400">جلسهٔ ذخیره‌شده‌ای ندارید.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- مودال ذخیره -->
        <div v-if="showSaveModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-4">
            <div class="bg-gray-800 p-6 rounded-xl w-full max-w-md">
                <h3 class="text-lg font-bold mb-4">ذخیرهٔ جلسه</h3>
                <input
                    v-model="sessionTitle"
                    type="text"
                    class="w-full p-2 bg-gray-700 rounded mb-4 text-white"
                    placeholder="عنوان جلسه (اختیاری)"
                >
                <div class="flex gap-3 justify-end">
                    <button @click="showSaveModal = false" class="px-4 py-2 bg-gray-600 rounded hover:bg-gray-500">
                        انصراف
                    </button>
                    <button
                        @click="saveSession"
                        :disabled="saving"
                        class="px-4 py-2 bg-blue-600 rounded hover:bg-blue-500 disabled:opacity-50"
                    >
                        ذخیره
                    </button>
                </div>
            </div>
        </div>

        <BottomNav activeTab="strategy_lab" />
    </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'
import BottomNav from "@/Components/Dashboard/BottomNav.vue"

export default {
    name: "StrategyLabPage",
    components: {
        BottomNav,
        Link,
    },
    props: {
        sessions: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            buildingTypes: [
                { key: 'town_hall', label: 'تاون هال', color: '#f59e0b' },
                { key: 'clan_castle', label: 'کلن کسل', color: '#ef4444' },
                { key: 'barbarian_king', label: 'پادشاه', color: '#8b5cf6' },
                { key: 'archer_queen', label: 'ملکه', color: '#ec4899' },
                { key: 'grand_warden', label: 'واردن', color: '#3b82f6' },
                { key: 'royal_champion', label: 'قهرمان سلطنتی', color: '#14b8a6' },
                { key: 'cannon', label: 'توپ', color: '#6b7280' },
                { key: 'archer_tower', label: 'برج کماندار', color: '#a855f7' },
                { key: 'mortar', label: 'خمپاره‌انداز', color: '#84cc16' },
                { key: 'air_defense', label: 'دفاع هوایی', color: '#06b6d4' },
                { key: 'wizard_tower', label: 'برج جادوگر', color: '#f97316' },
                { key: 'air_sweeper', label: 'دستگاه باد', color: '#22d3ee' },
                { key: 'hidden_tesla', label: 'تسلای مخفی', color: '#eab308' },
                { key: 'bomb_tower', label: 'برج بمب', color: '#b91c1c' },
                { key: 'x_bow', label: 'X-Bow', color: '#6366f1' },
                { key: 'x_bow_air', label: 'X-Bow هوایی', color: '#4f46e5' },
                { key: 'inferno_tower_single', label: 'اینفرنو تک‌هدف', color: '#dc2626' },
                { key: 'inferno_tower_multi', label: 'اینفرنو چندهدف', color: '#b45309' },
                { key: 'eagle_artillery', label: 'عقاب', color: '#7c2d12' },
                { key: 'scattershot', label: 'Scattershot', color: '#065f46' },
                { key: 'monolith', label: 'Monolith', color: '#111827' },
                { key: 'builder_hut', label: 'کلبه بیلدر', color: '#10b981' },
                { key: 'trap', label: 'تله', color: '#78350f' },
            ],
            selectedType: 'town_hall',
            imageFile: null,
            imageUrl: null,
            originalImageUrl: null,
            buildings: [],
            nextBuildingId: 1,
            analysis: null,
            analyzing: false,
            detectingVision: false,
            saving: false,
            showSaveModal: false,
            sessionTitle: '',
            localSessions: this.sessions,
        }
    },
    computed: {
        selectedTypeLabel() {
            return this.buildingLabel(this.selectedType)
        },
    },
    mounted() {
        window.addEventListener('resize', this.redrawCanvas)
    },
    beforeUnmount() {
        window.removeEventListener('resize', this.redrawCanvas)
        if (this.imageUrl && this.imageUrl.startsWith('blob:')) {
            URL.revokeObjectURL(this.imageUrl)
        }
    },
    methods: {
        buildingLabel(type) {
            const found = this.buildingTypes.find(t => t.key === type)
            return found ? found.label : type
        },
        buildingColor(type) {
            const found = this.buildingTypes.find(t => t.key === type)
            return found ? found.color : '#9ca3af'
        },
        severityBorder(severity) {
            return {
                high: 'border-r-4 border-red-500',
                medium: 'border-r-4 border-yellow-500',
                low: 'border-r-4 border-green-500',
            }[severity] || ''
        },
        handleImageUpload(event) {
            const file = event.target.files[0]
            if (!file) return

            if (file.size > 5 * 1024 * 1024) {
                alert('حجم عکس باید کمتر از ۵ مگابایت باشد.')
                return
            }

            this.imageFile = file
            if (this.imageUrl && this.imageUrl.startsWith('blob:')) {
                URL.revokeObjectURL(this.imageUrl)
            }
            this.imageUrl = URL.createObjectURL(file)
            this.originalImageUrl = this.imageUrl
            this.buildings = []
            this.nextBuildingId = 1
            this.analysis = null
        },
        handleImageLoad() {
            this.redrawCanvas()
        },
        handleMapClick(event) {
            const img = this.$refs.mapImage
            if (!img) return

            const rect = img.getBoundingClientRect()
            const xPx = event.clientX - rect.left
            const yPx = event.clientY - rect.top

            const xPercent = (xPx / rect.width) * 100
            const yPercent = (yPx / rect.height) * 100

            this.buildings.push({
                id: this.nextBuildingId++,
                type: this.selectedType,
                x: xPercent,
                y: yPercent,
            })

            this.redrawCanvas()
        },
        removeBuilding(index) {
            this.buildings.splice(index, 1)
            this.redrawCanvas()
        },
        redrawCanvas() {
            const canvas = this.$refs.markerCanvas
            const img = this.$refs.mapImage
            if (!canvas || !img) return

            canvas.width = img.clientWidth
            canvas.height = img.clientHeight

            const ctx = canvas.getContext('2d')
            ctx.clearRect(0, 0, canvas.width, canvas.height)

            this.buildings.forEach(b => {
                const x = (b.x / 100) * canvas.width
                const y = (b.y / 100) * canvas.height
                const color = this.buildingColor(b.type)

                ctx.beginPath()
                ctx.arc(x, y, 8, 0, Math.PI * 2)
                ctx.fillStyle = color
                ctx.fill()
                ctx.strokeStyle = '#fff'
                ctx.lineWidth = 2
                ctx.stroke()

                ctx.fillStyle = '#fff'
                ctx.font = '10px sans-serif'
                ctx.fillText(this.buildingLabel(b.type), x + 10, y + 3)
            })
        },
        async analyze() {
            if (this.buildings.length === 0) return

            this.analyzing = true
            this.analysis = null

            try {
                const response = await window.axios.post('/api/strategy-lab/quick-analyze', {
                    buildings: this.buildings,
                })
                this.analysis = response.data
            } catch (error) {
                console.error(error)
                alert('خطا در تحلیل نقشه.')
            } finally {
                this.analyzing = false
            }
        },
        async detectWithVision() {
            if (!this.imageFile) {
                alert('ابتدا یک تصویر آپلود کنید.')
                return
            }

            this.detectingVision = true
            this.analysis = null

            const formData = new FormData()
            formData.append('image', this.imageFile)

            try {
                const response = await window.axios.post('/api/strategy-lab/detect-vision', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                })
                const data = response.data

                if (!data.ok) {
                    alert(data.message || 'خطا در تشخیص ساختمان‌ها.')
                    return
                }

                this.buildings = data.buildings || []
                this.nextBuildingId = this.buildings.length + 1
                this.analysis = data.analysis || null

                this.$nextTick(() => {
                    this.redrawCanvas()
                })

                if (this.buildings.length === 0) {
                    alert('هیچ ساختمانی تشخیص داده نشد. لطفاً ساختمان‌ها را دستی علامت‌گذاری کنید.')
                }
            } catch (error) {
                console.error(error)
                const message = error.response?.data?.message || 'خطا در ارتباط با AI Vision.'
                alert(message)
            } finally {
                this.detectingVision = false
            }
        },
        openSaveModal() {
            this.sessionTitle = ''
            this.showSaveModal = true
        },
        async saveSession() {
            if (!this.imageFile) {
                alert('تصویر نقشه یافت نشد.')
                return
            }

            this.saving = true

            const formData = new FormData()
            formData.append('image', this.imageFile)
            formData.append('buildings', JSON.stringify(this.buildings))
            if (this.sessionTitle) {
                formData.append('title', this.sessionTitle)
            }

            try {
                const response = await window.axios.post('/api/strategy-lab/sessions', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                })
                this.localSessions.unshift(response.data)
                this.showSaveModal = false
                alert('جلسه ذخیره شد.')
            } catch (error) {
                console.error(error)
                alert('خطا در ذخیرهٔ جلسه.')
            } finally {
                this.saving = false
            }
        },
        async loadSession(id) {
            try {
                const response = await window.axios.get(`/api/strategy-lab/sessions/${id}`)
                const data = response.data

                if (this.imageUrl && this.imageUrl.startsWith('blob:')) {
                    URL.revokeObjectURL(this.imageUrl)
                }

                this.imageUrl = data.image_url
                this.originalImageUrl = data.image_url
                this.imageFile = null
                this.buildings = data.buildings || []
                this.nextBuildingId = this.buildings.length + 1
                this.analysis = data.analysis || null

                this.$nextTick(() => {
                    this.redrawCanvas()
                })
            } catch (error) {
                console.error(error)
                alert('خطا در بارگذاری جلسه.')
            }
        },
        async deleteSession(id) {
            if (!confirm('آیا از حذف این جلسه مطمئن هستید؟')) return

            try {
                await window.axios.delete(`/api/strategy-lab/sessions/${id}`)
                this.localSessions = this.localSessions.filter(s => s.id !== id)
            } catch (error) {
                console.error(error)
                alert('خطا در حذف جلسه.')
            }
        },
        resetAll() {
            if (this.imageUrl && this.imageUrl.startsWith('blob:')) {
                URL.revokeObjectURL(this.imageUrl)
            }
            this.imageFile = null
            this.imageUrl = null
            this.originalImageUrl = null
            this.buildings = []
            this.nextBuildingId = 1
            this.analysis = null
        },
    },
}
</script>

<style scoped>
.min-h-screen {
    background: url('/847433.jpg') no-repeat center center;
    background-size: cover;
}
</style>
