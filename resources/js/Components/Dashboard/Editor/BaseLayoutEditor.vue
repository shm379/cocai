<template>
    <div class="space-y-3" dir="rtl">
        <!-- نوار ابزار -->
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-1 bg-gray-900 rounded-xl p-1 border border-white/10" role="group" aria-label="ابزار ویرایش">
                <button
                    v-for="t in tools"
                    :key="t.key"
                    type="button"
                    class="min-h-[44px] min-w-[44px] px-3 rounded-lg text-[11px] font-bold transition"
                    :class="tool === t.key ? 'bg-fuchsia-600 text-white' : 'text-gray-300 hover:text-white hover:bg-white/5'"
                    :title="t.title"
                    :aria-pressed="tool === t.key ? 'true' : 'false'"
                    @click="setTool(t.key)"
                >{{ t.icon }} <span class="hidden sm:inline">{{ t.label }}</span></button>
            </div>

            <div class="flex items-center gap-1">
                <button type="button" class="tb" :disabled="!hist.undo" title="برگشت (Ctrl+Z)" aria-label="برگشت" @click="undo">↩️</button>
                <button type="button" class="tb" :disabled="!hist.redo" title="جلو (Ctrl+Y)" aria-label="جلو" @click="redo">↪️</button>
                <button type="button" class="tb" title="نمایش کامل نقشه" aria-label="نمایش کامل نقشه" @click="fit">⤢</button>
                <button type="button" class="tb" :disabled="exporting" title="خروجی PNG" aria-label="خروجی PNG" @click="exportPng">{{ exporting ? '⏳' : '🖼️' }}</button>
            </div>

            <div class="mr-auto flex items-center gap-2">
                <button
                    type="button"
                    class="min-h-[44px] px-4 rounded-xl bg-white/[0.06] hover:bg-white/10 border border-white/10 text-xs font-bold text-gray-100 transition"
                    @click="cancel"
                >✖ بستن</button>
                <button
                    type="button"
                    class="min-h-[44px] px-5 rounded-xl bg-gradient-to-r from-emerald-600 to-cyan-600 hover:from-emerald-500 hover:to-cyan-500 text-white text-xs font-black shadow transition disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!dirty || saving"
                    @click="save"
                >{{ saving ? '⏳ در حال ذخیره…' : (dirty ? '💾 ذخیرهٔ تغییرات' : '✔ ذخیره شده') }}</button>
            </div>
        </div>

        <!-- خط وضعیت -->
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-400">
            <span>🏠 {{ fa(counts.placed) }} ساختمان</span>
            <span v-if="counts.unplaced">· <span class="text-red-200">{{ fa(counts.unplaced) }} جا‌نشده</span></span>
            <span>· 🧱 {{ fa(counts.walls) }}/{{ fa(limits.walls) }} دیوار</span>
            <span>· <span :class="counts.uncertain ? 'text-amber-200' : 'text-emerald-200'">؟ {{ fa(counts.uncertain) }} نامطمئن</span></span>
            <span v-if="dirty" class="text-fuchsia-200 font-bold">· ذخیره‌نشده</span>
            <span class="text-gray-500">· نسخهٔ {{ fa(version) }}</span>
            <span v-if="toolHint" class="text-cyan-200">· {{ toolHint }}</span>
        </div>

        <!-- اعلان‌ها -->
        <transition-group name="toast" tag="div" class="space-y-1">
            <div
                v-for="t in toasts"
                :key="t.id"
                class="rounded-xl px-3 py-2 text-xs font-bold border"
                :class="t.kind === 'error' ? 'bg-red-500/15 border-red-400/40 text-red-100' : (t.kind === 'warn' ? 'bg-amber-500/15 border-amber-400/40 text-amber-100' : 'bg-emerald-500/15 border-emerald-400/40 text-emerald-100')"
                role="status"
            >{{ t.text }}</div>
        </transition-group>

        <!-- تعارض نسخه -->
        <div v-if="conflict" class="rounded-2xl bg-amber-500/10 border border-amber-400/40 p-3 space-y-2">
            <p class="text-sm font-black text-amber-100">⚠️ این چیدمان در جای دیگری تغییر کرده است (نسخهٔ {{ fa(conflict.current_version) }}).</p>
            <p class="text-[11px] text-amber-100/80">می‌توانید نسخهٔ جدید را بارگذاری کنید (پیش‌نویس فعلی از بین می‌رود) یا با پیش‌نویس خود ادامه دهید و روی آن بنویسید.</p>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="min-h-[44px] px-4 rounded-xl bg-amber-500/30 hover:bg-amber-500/50 border border-amber-400/50 text-xs font-bold text-amber-50" @click="reloadFromConflict">🔄 بارگذاری نسخهٔ جدید</button>
                <button type="button" class="min-h-[44px] px-4 rounded-xl bg-white/[0.06] hover:bg-white/10 border border-white/10 text-xs font-bold text-gray-100" @click="keepDraftAfterConflict">✍️ ادامه با پیش‌نویس من</button>
            </div>
        </div>

        <!-- خطاهای اعتبارسنجی سرور -->
        <div v-if="serverErrors.length" class="rounded-2xl bg-red-500/10 border border-red-400/40 p-3 space-y-1.5">
            <p class="text-sm font-black text-red-100">⛔ سرور این چیدمان را نپذیرفت:</p>
            <ul class="space-y-1">
                <li v-for="(e, i) in serverErrors" :key="i" class="flex items-center gap-2 text-[11px] text-red-100">
                    <span class="flex-1">{{ e.label }}: {{ e.message }}</span>
                    <button v-if="e.id" type="button" class="min-h-[36px] px-3 rounded-lg bg-red-500/20 hover:bg-red-500/40 border border-red-400/40 font-bold" @click="jumpTo(e.id)">پرش</button>
                </li>
            </ul>
        </div>

        <!-- بدنه -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 items-start">
            <div class="lg:col-span-2 bg-gray-950 rounded-2xl p-2 border border-white/10">
                <IsoBaseRenderer
                    ref="renderer"
                    :layout="draftLayout"
                    mode="edit"
                    :selected-id="selectedId"
                    :ghost="ghost"
                    :show-levels="true"
                    :export-name="clone.slug"
                    @select="onSelect"
                    @tile-down="onTileDown"
                    @tile-move="onTileMove"
                    @tile-up="onTileUp"
                    @building-down="onBuildingDown"
                    @zoom="onZoom"
                />
            </div>

            <div class="bg-gray-950 rounded-2xl border border-white/10 overflow-hidden">
                <div class="flex border-b border-white/10" role="tablist">
                    <button
                        v-for="tb in tabs"
                        :key="tb.key"
                        type="button"
                        role="tab"
                        class="flex-1 min-h-[44px] text-[11px] font-bold transition"
                        :class="tab === tb.key ? 'bg-white/[0.06] text-white border-b-2 border-fuchsia-500' : 'text-gray-400 hover:text-white'"
                        :aria-selected="tab === tb.key ? 'true' : 'false'"
                        @click="tab = tb.key"
                    >{{ tb.label }}</button>
                </div>
                <div class="p-3 lg:max-h-[520px] lg:overflow-y-auto">
                    <UncertainPanel
                        v-if="tab === 'uncertain'"
                        :items="uncertainList"
                        :selected-id="selectedId"
                        @select="jumpTo"
                        @confirm="confirmBuilding"
                        @remove="removeBuilding"
                        @place="placeNearest"
                    />
                    <BuildingPalette
                        v-else-if="tab === 'add'"
                        :catalog="catalog"
                        :counts="countsByType"
                        :active-type="addType"
                        :limit="limits.buildings"
                        :limit-reached="draft.buildings.length >= limits.buildings"
                        @pick="pickAddType"
                    />
                    <InspectorPanel
                        v-else-if="tab === 'inspector'"
                        :building="selectedBuilding"
                        :catalog="catalog"
                        :error="selectedBuilding ? (errorById[selectedBuilding.id] || '') : ''"
                        @nudge="nudge"
                        @level="setLevel"
                        @retype="retype"
                        @confirm="selectedId !== null && confirmBuilding(selectedId)"
                        @remove="selectedId !== null && removeBuilding(selectedId)"
                        @place="selectedId !== null && placeNearest(selectedId)"
                        @deselect="selectedId = null"
                        @center="selectedId !== null && centerOn(selectedId)"
                    />
                    <div v-else class="space-y-2">
                        <p class="text-[11px] text-gray-400">تصویر اصلی برای مقایسه</p>
                        <img :src="clone.image_url" alt="تصویر اصلی" class="w-full rounded-xl object-contain max-h-[460px] bg-gray-900">
                    </div>
                </div>
            </div>
        </div>

        <div class="sr-only" aria-live="polite">{{ announce }}</div>
    </div>
</template>

<script>
import { router } from '@inertiajs/vue3'
import IsoBaseRenderer from '../Iso/IsoBaseRenderer.vue'
import UncertainPanel from './UncertainPanel.vue'
import BuildingPalette from './BuildingPalette.vue'
import InspectorPanel from './InspectorPanel.vue'
import History, { snapshot, restore } from './history'
import { buildOccupancy, footprintCollision, isFree, findFreeSpot, lineCells, inBounds, FREE } from './occupancy'
import { faDigits, clamp } from '../Iso/iso'

const MIN_PRECISION_ZOOM = 0.8
const NUDGE_KEYS = {
    ArrowUp: { dx: 0, dy: -1 },
    ArrowDown: { dx: 0, dy: 1 },
    ArrowLeft: { dx: -1, dy: 0 },
    ArrowRight: { dx: 1, dy: 0 },
}

/**
 * ویرایشگر چیدمان بیس (فقط مالک).
 *
 * ابزارها: انتخاب/جابه‌جایی (درگ با snap و برخورد ردپا)، افزودن از پالت، دیوار، پاک‌کن،
 * برگشت/جلو (اسنپ‌شات، سقف ۵۰)، پنل نامطمئن‌ها، جهت‌نما و ذخیره با کنترل نسخه (۴۰۹).
 *
 * پیش‌نویس (draft) فقط buildings و walls را نگه می‌دارد؛ بقیهٔ کلیدهای layout دست‌نخورده می‌ماند.
 */
export default {
    name: 'BaseLayoutEditor',
    components: { IsoBaseRenderer, UncertainPanel, BuildingPalette, InspectorPanel },
    props: {
        /** خروجی toPublicArray(true): slug, game, layout, image_url */
        clone: { type: Object, required: true },
    },
    emits: ['saved', 'close', 'dirty'],
    data() {
        const layout = this.clone.layout || {}
        return {
            draft: this.cloneDraft(layout),
            original: '',
            version: Number(layout.version) || 1,
            catalog: null,
            catalogError: '',
            selectedId: null,
            tool: 'select',
            addType: null,
            drag: null,
            stroke: null,
            hoverTile: null,
            hist: { undo: false, redo: false },
            dirty: false,
            saving: false,
            exporting: false,
            conflict: null,
            serverErrors: [],
            errorById: {},
            toasts: [],
            announce: '',
            tab: 'uncertain',
            viewK: 1,
            tools: [
                { key: 'select', icon: '🖐️', label: 'انتخاب', title: 'انتخاب و جابه‌جایی' },
                { key: 'add', icon: '➕', label: 'افزودن', title: 'افزودن ساختمان از پالت' },
                { key: 'wall', icon: '🧱', label: 'دیوار', title: 'نقاشی دیوار' },
                { key: 'erase', icon: '🧹', label: 'پاک‌کن', title: 'پاک‌کردن دیوار / حذف ساختمان' },
            ],
        }
    },
    computed: {
        n() {
            return Math.max(1, Number(this.clone.layout?.grid_size) || Number(this.catalog?.grid_size) || 44)
        },
        limits() {
            return {
                buildings: Number(this.catalog?.limits?.buildings) || 300,
                walls: Number(this.catalog?.limits?.walls) || 400,
            }
        },
        tabs() {
            return [
                { key: 'uncertain', label: `؟ نامطمئن (${faDigits(this.uncertainList.length)})` },
                { key: 'add', label: '➕ افزودن' },
                { key: 'inspector', label: '🔎 جزئیات' },
                { key: 'image', label: '🖼️ تصویر' },
            ]
        },
        /** ساختمان‌های پیش‌نویس با متادیتای کاتالوگ (ابعاد/برچسب/رنگ/آیکون) */
        decorated() {
            return this.draft.buildings.map(b => this.decorate(b))
        },
        buildingById() {
            const m = new Map()
            for (const b of this.decorated) m.set(b.id, b)
            return m
        },
        selectedBuilding() {
            return this.selectedId === null ? null : (this.buildingById.get(this.selectedId) || null)
        },
        draftLayout() {
            return { ...this.clone.layout, buildings: this.decorated, walls: this.draft.walls }
        },
        occupancy() {
            return buildOccupancy(this.n, this.draft.buildings, this.draft.walls, b => this.sizeOf(b))
        },
        uncertainList() {
            const order = { core: 0, defense: 1, resource: 2, army: 3, hero: 4, other: 5 }
            return this.decorated
                .filter(b => b.uncertain === true || b.placed === false)
                .sort((a, b) => (order[a.category] ?? 9) - (order[b.category] ?? 9) || a.id - b.id)
        },
        counts() {
            let placed = 0
            let unplaced = 0
            let uncertain = 0
            for (const b of this.draft.buildings) {
                if (b.placed === false) unplaced++
                else placed++
                if (b.uncertain === true || b.placed === false) uncertain++
            }
            return { placed, unplaced, uncertain, walls: this.draft.walls.length }
        },
        countsByType() {
            const m = {}
            for (const b of this.draft.buildings) m[b.type] = (m[b.type] || 0) + 1
            return m
        },
        /** پیش‌نمایش جابه‌جایی یا افزودن */
        ghost() {
            if (this.drag && this.drag.moved) {
                const b = this.buildingById.get(this.drag.id)
                if (!b) return null
                return { type: b.type, size: b.size, x: this.drag.x, y: this.drag.y, valid: this.drag.valid, icon: b.icon, color: b.color }
            }
            if (this.tool === 'add' && this.addType && this.hoverTile && this.hoverTile.inside) {
                const meta = this.catalog?.types?.[this.addType]
                if (!meta) return null
                const pos = this.addPosition(this.hoverTile, meta.size)
                return {
                    type: this.addType,
                    size: meta.size,
                    x: pos.x,
                    y: pos.y,
                    valid: isFree(this.occupancy, this.n, pos.x, pos.y, meta.size),
                    icon: meta.icon,
                    color: meta.color,
                }
            }
            return null
        },
        toolHint() {
            if (this.tool === 'add') return this.addType ? 'روی نقشه بزنید تا اضافه شود' : 'یک ساختمان از پالت انتخاب کنید'
            if (this.tool === 'wall') return this.viewK < MIN_PRECISION_ZOOM ? 'برای دقت بیشتر زوم کنید' : 'روی خانه‌ها بکشید تا دیوار کشیده شود'
            if (this.tool === 'erase') return 'روی دیوار بکشید یا روی ساختمان بزنید'
            return ''
        },
    },
    watch: {
        draft: {
            handler() {
                this.dirty = snapshot(this.draft) !== this.original
            },
            deep: true,
        },
        dirty(v) {
            this.$emit('dirty', v)
        },
        'clone.layout_version'() {
            // مالک از بیرون نسخهٔ جدیدی گرفته (مثلاً پس از ذخیره)؛ نسخهٔ داخلی همگام می‌شود
            const v = Number(this.clone.layout?.version) || 1
            if (!this.dirty) this.version = v
        },
    },
    created() {
        this._history = new History(50)
        this.original = snapshot(this.draft)
        this._toastSeq = 0
    },
    mounted() {
        this.loadCatalog()
        this._onKey = e => this.onKeyDown(e)
        this._onBeforeUnload = e => {
            if (!this.dirty) return
            e.preventDefault()
            e.returnValue = ''
        }
        window.addEventListener('keydown', this._onKey)
        window.addEventListener('beforeunload', this._onBeforeUnload)
        this._offInertia = router.on('before', event => {
            if (this.dirty && !window.confirm('تغییرات ذخیره‌نشده از بین می‌رود. ادامه می‌دهید؟')) {
                event.preventDefault()
            }
        })
    },
    beforeUnmount() {
        window.removeEventListener('keydown', this._onKey)
        window.removeEventListener('beforeunload', this._onBeforeUnload)
        if (typeof this._offInertia === 'function') this._offInertia()
        for (const t of this.toasts) clearTimeout(t.timer)
    },
    methods: {
        fa: faDigits,

        /* ---------- داده ---------- */
        cloneDraft(layout) {
            const buildings = Array.isArray(layout.buildings) ? layout.buildings : []
            const walls = Array.isArray(layout.walls) ? layout.walls : []
            return {
                buildings: buildings.map(b => ({ ...b, id: Number(b.id), x: Number(b.x) || 0, y: Number(b.y) || 0 })),
                walls: walls.map(([x, y]) => [Number(x), Number(y)]),
            }
        },
        sizeOf(b) {
            const meta = this.catalog?.types?.[b.type]
            return Math.max(1, Number(meta?.size) || Number(b.size) || 1)
        },
        decorate(b) {
            const meta = this.catalog?.types?.[b.type]
            return {
                ...b,
                size: this.sizeOf(b),
                label: meta?.label || b.label || b.type,
                color: meta?.color || b.color || '#6b7280',
                icon: meta?.icon || b.icon || '🏠',
                category: meta?.category || b.category || 'other',
                sprite: meta?.sprite ?? b.sprite ?? null,
            }
        },
        async loadCatalog() {
            try {
                const { data } = await window.axios.get('/api/base-clones/catalog', { params: { game: this.clone.game || 'coc_home' } })
                if (data && data.ok) this.catalog = data
                else this.catalogError = data?.message || 'کاتالوگ بارگذاری نشد.'
            } catch (err) {
                this.catalogError = err?.response?.data?.message || 'کاتالوگ بارگذاری نشد.'
                this.toast(this.catalogError, 'error')
            }
        },

        /* ---------- تاریخچه ---------- */
        pushHistory() {
            this._history.push(snapshot(this.draft))
            this.syncHist()
        },
        syncHist() {
            this.hist = { undo: this._history.canUndo, redo: this._history.canRedo }
        },
        undo() {
            const s = this._history.undo(snapshot(this.draft))
            if (s === null) return
            this.applySnapshot(s)
            this.say('برگشت انجام شد')
        },
        redo() {
            const s = this._history.redo(snapshot(this.draft))
            if (s === null) return
            this.applySnapshot(s)
            this.say('جلو انجام شد')
        },
        applySnapshot(s) {
            this.draft = restore(s)
            this.syncHist()
            this.drag = null
            this.stroke = null
            if (this.selectedId !== null && !this.draft.buildings.some(b => b.id === this.selectedId)) this.selectedId = null
        },

        /* ---------- ابزارها ---------- */
        setTool(key) {
            this.tool = key
            this.drag = null
            this.stroke = null
            if (key !== 'add') this.addType = null
            if (key === 'add') this.tab = 'add'
            if ((key === 'wall' || key === 'add') && this.viewK < MIN_PRECISION_ZOOM) this.ensurePrecisionZoom()
        },
        pickAddType(type) {
            this.addType = type
            if (type) {
                this.tool = 'add'
                this.selectedId = null
                if (this.viewK < MIN_PRECISION_ZOOM) this.ensurePrecisionZoom()
            } else if (this.tool === 'add') {
                this.tool = 'select'
            }
        },
        ensurePrecisionZoom() {
            const r = this.$refs.renderer
            if (!r || !this.viewK) return
            r.zoomAt(MIN_PRECISION_ZOOM / this.viewK)
            this.toast(this.isCoarsePointer() ? 'برای دقت بیشتر زوم شد؛ با دو انگشت جابه‌جا/زوم کنید' : 'برای دقت بیشتر زوم شد', 'info')
        },
        fit() {
            this.$refs.renderer?.fit()
        },
        centerOn(id) {
            this.$refs.renderer?.centerOn(id, Math.max(this.viewK, 0.7))
        },
        jumpTo(id) {
            this.selectedId = id
            this.tool = 'select'
            this.addType = null
            this.centerOn(id)
            if (this.isMobile()) this.tab = 'inspector'
        },
        isMobile() {
            return typeof window !== 'undefined' && window.innerWidth < 1024
        },
        isCoarsePointer() {
            return typeof matchMedia === 'function' && matchMedia('(pointer: coarse)').matches
        },
        onZoom({ k }) {
            this.viewK = Number(k) || 1
            // پایان pinch: هر درگ/نقاشی نیمه‌کاره لغو می‌شود
            this.drag = null
            this.stroke = null
        },

        /* ---------- رویدادهای رندرر ---------- */
        onSelect(id) {
            if (this.tool !== 'select') return
            this.selectedId = id
            if (id !== null) {
                if (this.tab !== 'uncertain' || this.isMobile()) this.tab = 'inspector'
            }
        },
        onBuildingDown({ id, x, y, inside, pointerType, event }) {
            if (this.tool === 'erase') {
                event.preventDefault()
                this.removeBuilding(id)
                return
            }
            if (this.tool === 'wall') {
                event.preventDefault()
                this.beginStroke(x, y, inside)
                return
            }
            if (this.tool === 'add') {
                event.preventDefault()
                this.hoverTile = { x, y, inside }
                return
            }
            // انتخاب: در لمس، اول ضربه برای انتخاب (رندرر خودش select می‌فرستد)، بعد کشیدن
            if (pointerType === 'touch' && this.selectedId !== id) return
            const b = this.buildingById.get(id)
            if (!b) return
            event.preventDefault()
            this.selectedId = id
            if (this.tab !== 'uncertain' || this.isMobile()) this.tab = 'inspector'
            this.drag = {
                id,
                offX: x - b.x,
                offY: y - b.y,
                startX: b.x,
                startY: b.y,
                x: b.x,
                y: b.y,
                valid: true,
                moved: false,
            }
        },
        onTileDown({ x, y, inside, event }) {
            if (this.tool === 'wall' || this.tool === 'erase') {
                if (!inside) return
                event.preventDefault()
                this.beginStroke(x, y, inside)
                return
            }
            if (this.tool === 'add') {
                event.preventDefault()
                this.hoverTile = { x, y, inside }
            }
        },
        onTileMove({ x, y, inside }) {
            this.hoverTile = { x, y, inside }
            if (this.drag) {
                const b = this.buildingById.get(this.drag.id)
                if (!b) return
                const nx = clamp(x - this.drag.offX, 0, this.n - b.size)
                const ny = clamp(y - this.drag.offY, 0, this.n - b.size)
                if (nx !== this.drag.x || ny !== this.drag.y || !this.drag.moved) {
                    this.drag.x = nx
                    this.drag.y = ny
                    this.drag.moved = this.drag.moved || nx !== this.drag.startX || ny !== this.drag.startY
                    this.drag.valid = isFree(this.occupancy, this.n, nx, ny, b.size, b.id)
                }
                return
            }
            if (this.stroke) {
                const cx = clamp(x, 0, this.n - 1)
                const cy = clamp(y, 0, this.n - 1)
                const [lx, ly] = this.stroke.last
                for (const [px, py] of lineCells(lx, ly, cx, cy)) this.paintCell(px, py)
                this.stroke.last = [cx, cy]
            }
        },
        onTileUp({ x, y, inside }) {
            if (this.drag) {
                const d = this.drag
                this.drag = null
                const b = this.buildingById.get(d.id)
                if (!b || !d.moved) return
                if (!d.valid) {
                    this.toast('جای انتخاب‌شده خالی نیست', 'warn')
                    this.say('جای انتخاب‌شده خالی نیست')
                    return
                }
                if (d.x === d.startX && d.y === d.startY && b.placed !== false) return
                this.moveBuilding(d.id, d.x, d.y)
                return
            }
            if (this.stroke) {
                this.stroke = null
                return
            }
            if (this.tool === 'add' && this.addType) {
                if (!inside) return
                this.hoverTile = { x, y, inside }
                const g = this.ghost
                if (!g) return
                if (!g.valid) {
                    this.toast('جای انتخاب‌شده خالی نیست', 'warn')
                    return
                }
                this.addBuilding(this.addType, g.x, g.y)
            }
        },

        /* ---------- عملیات روی پیش‌نویس ---------- */
        addPosition(tile, size) {
            const half = Math.floor(size / 2)
            return {
                x: clamp(tile.x - half, 0, this.n - size),
                y: clamp(tile.y - half, 0, this.n - size),
            }
        },
        moveBuilding(id, x, y) {
            const b = this.draft.buildings.find(it => it.id === id)
            if (!b) return
            this.pushHistory()
            b.x = x
            b.y = y
            b.placed = true
            b.uncertain = false
            b.source = 'user'
            this.clearError(id)
            this.say(`${this.labelOf(b)} به خانهٔ (${faDigits(x)}، ${faDigits(y)}) منتقل شد`)
        },
        nudge({ dx, dy }, steps = 1) {
            const b = this.selectedBuilding
            if (!b) return
            const nx = clamp(b.x + dx * steps, 0, this.n - b.size)
            const ny = clamp(b.y + dy * steps, 0, this.n - b.size)
            if (nx === b.x && ny === b.y && b.placed !== false) return
            const hit = footprintCollision(this.occupancy, this.n, nx, ny, b.size, b.id)
            if (hit) {
                const msg = hit.kind === 'wall' ? 'دیوار سر راه است' : (hit.kind === 'building' ? `هم‌پوشانی با ${this.labelOf(this.buildingById.get(hit.id) || {})}` : 'خارج از نقشه')
                this.toast(msg, 'warn')
                this.say(msg)
                return
            }
            this.moveBuilding(b.id, nx, ny)
        },
        addBuilding(type, x, y) {
            const meta = this.catalog?.types?.[type]
            if (!meta) return
            if (this.draft.buildings.length >= this.limits.buildings) {
                this.toast(`حداکثر ${faDigits(this.limits.buildings)} ساختمان مجاز است`, 'warn')
                return
            }
            this.pushHistory()
            const id = this.draft.buildings.reduce((m, b) => Math.max(m, Number(b.id) || 0), 0) + 1
            this.draft.buildings.push({
                id,
                type,
                x,
                y,
                level: null,
                placed: true,
                uncertain: false,
                user_fixed: false,
                source: 'user',
            })
            this.say(`${meta.label} در (${faDigits(x)}، ${faDigits(y)}) اضافه شد`)
        },
        removeBuilding(id) {
            const idx = this.draft.buildings.findIndex(b => b.id === id)
            if (idx === -1) return
            this.pushHistory()
            const [b] = this.draft.buildings.splice(idx, 1)
            if (this.selectedId === id) this.selectedId = null
            this.clearError(id)
            this.say(`${this.labelOf(b)} حذف شد`)
        },
        /** تأیید یک‌طرفه است: سرور user_fixed را چسبنده نگه می‌دارد و لغو آن ذخیره نمی‌شود */
        confirmBuilding(id) {
            const b = this.draft.buildings.find(it => it.id === id)
            if (!b || b.placed === false || b.user_fixed) return
            this.pushHistory()
            b.user_fixed = true
            b.uncertain = false
            this.say(`${this.labelOf(b)} تأیید شد`)
        },
        /** قرار دادن ساختمان جا‌نشده در نزدیک‌ترین خانهٔ خالی حول مختصات ذخیره‌شده */
        placeNearest(id) {
            const b = this.draft.buildings.find(it => it.id === id)
            if (!b) return
            const size = this.sizeOf(b)
            const spot = findFreeSpot(this.occupancy, this.n, b.x, b.y, size, b.id, 8)
            if (!spot) {
                this.toast('جای خالی نزدیک پیدا نشد؛ آن را دستی بکشید', 'warn')
                this.selectedId = id
                this.tab = 'inspector'
                this.centerOn(id)
                return
            }
            this.moveBuilding(id, spot.x, spot.y)
            this.selectedId = id
            this.centerOn(id)
        },
        setLevel(level) {
            const b = this.selectedBuilding && this.draft.buildings.find(it => it.id === this.selectedId)
            if (!b) return
            const v = level === null ? null : clamp(Number(level) || 1, 1, 50)
            if ((b.level ?? null) === v) return
            this.pushHistory()
            b.level = v
        },
        retype(type) {
            const b = this.selectedBuilding && this.draft.buildings.find(it => it.id === this.selectedId)
            const meta = this.catalog?.types?.[type]
            if (!b || !meta || b.type === type) return
            this.pushHistory()
            b.type = type
            b.uncertain = false
            b.source = 'user'
            delete b.sprite
            const size = Number(meta.size) || 1
            // ردپای جدید باید جا شود؛ وگرنه نزدیک‌ترین جای خالی، وگرنه «جا‌نشده»
            const occ = buildOccupancy(this.n, this.draft.buildings.filter(it => it.id !== b.id), this.draft.walls, it => this.sizeOf(it))
            if (b.placed !== false && isFree(occ, this.n, b.x, b.y, size)) return
            const spot = findFreeSpot(occ, this.n, b.x, b.y, size, null, 6)
            if (spot) {
                b.x = spot.x
                b.y = spot.y
                b.placed = true
                this.toast(`${meta.label} به نزدیک‌ترین جای خالی منتقل شد`, 'info')
            } else {
                // مختصات ذخیره‌شده باید داخل نقشه بماند؛ اعتبارسنج سرور «جا‌نشده» را هم بررسی می‌کند
                b.x = clamp(Number(b.x) || 0, 0, this.n - size)
                b.y = clamp(Number(b.y) || 0, 0, this.n - size)
                b.placed = false
                this.toast(`${meta.label} با این ابعاد جا نمی‌شود؛ جای خالی انتخاب کنید`, 'warn')
            }
        },

        /* ---------- دیوار ---------- */
        beginStroke(x, y, inside) {
            if (!inside) return
            this.stroke = { last: [x, y], snap: snapshot(this.draft), changed: false, capWarned: false }
            this.paintCell(x, y)
        },
        paintCell(x, y) {
            if (!this.stroke || !inBounds(this.n, x, y, 1)) return
            const idx = y * this.n + x
            const v = this.occupancy[idx]
            if (this.tool === 'wall') {
                if (v !== FREE) return
                if (this.draft.walls.length >= this.limits.walls) {
                    if (!this.stroke.capWarned) {
                        this.stroke.capWarned = true
                        this.toast(`حداکثر ${faDigits(this.limits.walls)} دیوار`, 'warn')
                    }
                    return
                }
                this.commitStroke()
                this.draft.walls.push([x, y])
            } else if (this.tool === 'erase') {
                const i = this.draft.walls.findIndex(w => w[0] === x && w[1] === y)
                if (i === -1) return
                this.commitStroke()
                this.draft.walls.splice(i, 1)
            }
        },
        /** اولین تغییر هر ضربه یک مدخل تاریخچه می‌سازد */
        commitStroke() {
            if (this.stroke.changed) return
            this.stroke.changed = true
            this._history.push(this.stroke.snap)
            this.syncHist()
        },

        /* ---------- صفحه‌کلید ---------- */
        onKeyDown(e) {
            const t = e.target
            const tag = t && t.tagName ? t.tagName.toLowerCase() : ''
            if (['input', 'select', 'textarea'].includes(tag) || (t && t.isContentEditable)) return
            const mod = e.ctrlKey || e.metaKey

            if (mod && (e.key === 'z' || e.key === 'Z')) {
                e.preventDefault()
                e.shiftKey ? this.redo() : this.undo()
                return
            }
            if (mod && (e.key === 'y' || e.key === 'Y')) {
                e.preventDefault()
                this.redo()
                return
            }
            if (e.key === 'Escape') {
                if (this.drag || this.stroke) {
                    this.drag = null
                    this.stroke = null
                } else if (this.tool !== 'select') {
                    this.setTool('select')
                } else {
                    this.selectedId = null
                }
                return
            }
            if (this.selectedId === null) return
            if (NUDGE_KEYS[e.key]) {
                e.preventDefault()
                this.nudge(NUDGE_KEYS[e.key], e.shiftKey ? 4 : 1)
                return
            }
            if (e.key === 'Delete' || e.key === 'Backspace') {
                e.preventDefault()
                this.removeBuilding(this.selectedId)
            }
        },

        /* ---------- ذخیره ---------- */
        payload() {
            return {
                version: this.version,
                buildings: this.draft.buildings.map(b => ({
                    id: Number(b.id),
                    type: b.type,
                    x: clamp(Number(b.x) || 0, 0, this.n - this.sizeOf(b)),
                    y: clamp(Number(b.y) || 0, 0, this.n - this.sizeOf(b)),
                    level: b.level === null || b.level === undefined || b.level === '' ? null : Number(b.level),
                    placed: b.placed !== false,
                    user_fixed: !!b.user_fixed,
                })),
                walls: this.draft.walls.map(([x, y]) => [Number(x), Number(y)]),
            }
        },
        async save() {
            if (this.saving || !this.dirty) return
            this.saving = true
            this.conflict = null
            this.serverErrors = []
            this.errorById = {}
            try {
                const { data } = await window.axios.put(`/api/base-clones/${this.clone.slug}/layout`, this.payload())
                if (data && data.ok && data.clone) {
                    this.applySaved(data.clone)
                    this.toast(data.message || 'چیدمان ذخیره شد.', 'ok')
                    this.say('ذخیره شد')
                } else {
                    this.toast(data?.message || 'ذخیره ناموفق بود.', 'error')
                }
            } catch (err) {
                const res = err?.response
                const data = res?.data || {}
                if (res?.status === 409) {
                    this.conflict = { current_version: Number(data.current_version) || this.version + 1, clone: data.clone || null }
                    this.toast(data.message || 'این چیدمان در جای دیگری تغییر کرده است.', 'warn')
                } else if (res?.status === 422) {
                    this.serverErrors = this.parseErrors(data.errors || {})
                    this.toast(data.message || 'چیدمان نامعتبر است.', 'error')
                    const first = this.serverErrors.find(e => e.id)
                    if (first) this.jumpTo(first.id)
                } else if (res?.status === 403 || res?.status === 401) {
                    this.toast(data.message || 'اجازهٔ ویرایش ندارید.', 'error')
                } else if (res?.status === 429) {
                    this.toast('درخواست‌ها زیاد است؛ کمی بعد دوباره تلاش کنید.', 'warn')
                } else {
                    this.toast(data.message || 'خطا در ارتباط با سرور.', 'error')
                }
            } finally {
                this.saving = false
            }
        },
        /** پس از ذخیرهٔ موفق یا بارگذاری نسخهٔ جدید */
        applySaved(cloneData) {
            const layout = cloneData.layout || {}
            this.draft = this.cloneDraft(layout)
            this.original = snapshot(this.draft)
            this.version = Number(layout.version) || this.version + 1
            this._history.clear()
            this.syncHist()
            this.dirty = false
            this.conflict = null
            this.serverErrors = []
            this.errorById = {}
            this.drag = null
            this.stroke = null
            if (this.selectedId !== null && !this.draft.buildings.some(b => b.id === this.selectedId)) this.selectedId = null
            this.$emit('saved', cloneData)
        },
        reloadFromConflict() {
            if (this.conflict?.clone) {
                this.applySaved(this.conflict.clone)
                this.toast('نسخهٔ جدید بارگذاری شد.', 'ok')
                return
            }
            // بدون داده: از API بگیر
            window.axios.get(`/api/base-clones/${this.clone.slug}`).then(({ data }) => {
                if (data?.ok && data.clone) {
                    this.applySaved(data.clone)
                    this.toast('نسخهٔ جدید بارگذاری شد.', 'ok')
                }
            }).catch(() => this.toast('بارگذاری نسخهٔ جدید ناموفق بود.', 'error'))
        },
        keepDraftAfterConflict() {
            if (!this.conflict) return
            this.version = this.conflict.current_version
            this.conflict = null
            this.toast('اکنون می‌توانید پیش‌نویس خود را روی نسخهٔ جدید ذخیره کنید.', 'info')
        },
        /**
         * نگاشت خطاهای ۴۲۲ (کلیدهای buildings.<i>[.field] / walls.<j> / buildings / walls) به فهرست خوانا.
         */
        parseErrors(errors) {
            const out = []
            const byId = {}
            for (const [key, val] of Object.entries(errors)) {
                const message = Array.isArray(val) ? val.join(' ') : String(val)
                let m = key.match(/^buildings\.(\d+)(?:\.(\w+))?$/)
                if (m) {
                    const b = this.draft.buildings[Number(m[1])]
                    const id = b ? b.id : null
                    const label = b ? `${this.labelOf(b)} (#${id})` : `ساختمان ${m[1]}`
                    out.push({ id, label, message })
                    if (id !== null && !byId[id]) byId[id] = message
                    continue
                }
                m = key.match(/^walls\.(\d+)(?:\.\d+)?$/)
                if (m) {
                    const w = this.draft.walls[Number(m[1])]
                    out.push({ id: null, label: w ? `دیوار (${w[0]}, ${w[1]})` : `دیوار ${m[1]}`, message })
                    continue
                }
                out.push({ id: null, label: key === 'buildings' ? 'ساختمان‌ها' : (key === 'walls' ? 'دیوارها' : key), message })
            }
            this.errorById = byId
            return out
        },
        clearError(id) {
            if (this.errorById[id]) {
                const next = { ...this.errorById }
                delete next[id]
                this.errorById = next
                this.serverErrors = this.serverErrors.filter(e => e.id !== id)
            }
        },

        /* ---------- خروجی / بستن ---------- */
        async exportPng() {
            const r = this.$refs.renderer
            if (!r || this.exporting) return
            this.exporting = true
            try {
                await r.exportPng(this.clone.slug || 'layout')
            } finally {
                this.exporting = false
            }
        },
        cancel() {
            if (this.dirty && !window.confirm('تغییرات ذخیره‌نشده از بین می‌رود. بسته شود؟')) return
            this.dirty = false
            this.$emit('close')
        },

        /* ---------- کمکی ---------- */
        labelOf(b) {
            return this.catalog?.types?.[b.type]?.label || b.label || b.type || 'ساختمان'
        },
        say(text) {
            this.announce = ''
            this.$nextTick(() => { this.announce = text })
        },
        toast(text, kind = 'info') {
            const id = ++this._toastSeq
            const timer = setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id)
            }, kind === 'error' ? 6000 : 3000)
            this.toasts = [...this.toasts.slice(-2), { id, text, kind, timer }]
        },
    },
}
</script>

<style scoped>
.tb {
    min-width: 44px;
    min-height: 44px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #f3f4f6;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s;
}
.tb:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.12);
}
.tb:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
.toast-enter-active,
.toast-leave-active {
    transition: all 0.2s ease;
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
