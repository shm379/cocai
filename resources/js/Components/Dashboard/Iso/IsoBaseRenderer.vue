<template>
    <div
        ref="wrap"
        class="iso-wrap"
        :class="{ 'is-edit': mode === 'edit', 'is-panning': panning }"
        dir="ltr"
    >
        <svg
            ref="svg"
            class="iso-svg"
            role="application"
            :aria-label="ariaLabel"
            tabindex="0"
            @pointerdown="onPointerDown"
            @pointermove="onPointerMove"
            @pointerup="onPointerUp"
            @pointercancel="onPointerUp"
            @pointerover="onPointerOver"
            @pointerout="onPointerOut"
            @wheel.prevent="onWheel"
            @dblclick.prevent="onDblClick"
            @contextmenu.prevent
        >
            <g ref="viewport" data-iso-viewport>
                <IsoGround :geo="geo" :show-grid="gridVisible" :uid="uid" />
                <IsoItems
                    :items="items"
                    :geo="geo"
                    :mode="mode"
                    :sprites="spriteMap"
                    :wall-sprite="wallSprite"
                    :wall-middle="wallMiddle"
                    :show-levels="showLevels"
                    :uid="uid"
                    @sprite-error="onSpriteError"
                />
                <IsoOverlay
                    :geo="geo"
                    :selected="selectedBuilding"
                    :uncertain="uncertainBuildings"
                    :ghost="ghost"
                    :zoom="view.k"
                    :uid="uid"
                />
            </g>
        </svg>

        <!-- نوار ابزار شناور -->
        <div class="iso-toolbar" dir="rtl">
            <button type="button" class="iso-btn" title="بزرگ‌نمایی" aria-label="بزرگ‌نمایی" @click="zoomBy(1.25)">＋</button>
            <button type="button" class="iso-btn" title="کوچک‌نمایی" aria-label="کوچک‌نمایی" @click="zoomBy(1 / 1.25)">－</button>
            <button type="button" class="iso-btn" title="نمایش کامل نقشه" aria-label="نمایش کامل نقشه" @click="fit()">⤢</button>
            <button
                v-if="showExportButton"
                type="button"
                class="iso-btn"
                title="خروجی PNG"
                aria-label="خروجی PNG"
                :disabled="exporting"
                @click="exportPng()"
            >{{ exporting ? '⏳' : '🖼️' }}</button>
        </div>

        <!-- برچسب ساختمان زیر اشاره‌گر / انتخاب‌شده -->
        <div v-if="tipBuilding" class="iso-tip" dir="rtl">
            <span class="iso-tip-icon">{{ tipBuilding.icon || '🏠' }}</span>
            <span class="iso-tip-label">{{ tipBuilding.label || tipBuilding.type }}</span>
            <span v-if="tipBuilding.level" class="iso-tip-meta">سطح {{ fa(tipBuilding.level) }}</span>
            <span class="iso-tip-meta" dir="ltr">({{ tipBuilding.x }}, {{ tipBuilding.y }})</span>
            <span v-if="tipBuilding.placed === false" class="iso-tip-flag">جا نشد</span>
            <span v-else-if="isUncertain(tipBuilding)" class="iso-tip-flag">نامطمئن</span>
        </div>

        <div v-if="exporting" class="iso-busy" dir="rtl">در حال ساخت تصویر…</div>
    </div>
</template>

<script>
import IsoGround from './IsoGround.vue'
import IsoItems from './IsoItems.vue'
import IsoOverlay from './IsoOverlay.vue'
import { makeGeometry, sortItems, screenToTile, faDigits, clamp } from './iso'
import { spriteCandidates, resolveFirst, wallPostCandidates, wallMiddleCandidates } from './sprites'
import { exportPng as rasterize, deliverBlob } from './exportPng'

let uidCounter = 0

/** کش نتیجهٔ resolve برای هر مجموعهٔ کاندید (مشترک بین نمونه‌ها) */
const resolvedByKey = new Map()
/** مقدار نهایی resolve (همگام) تا با هر تغییر چیدمان در ویرایشگر، اسپرایت‌ها یک فریم ناپدید نشوند */
const resolvedValueByKey = new Map()

/**
 * رندرر ایزومتریک شبیه بازی برای چیدمان بیس (نمای «نمای بازی»).
 *
 * - SVG با یک transform روی <g data-iso-viewport> که در حین pan/zoom مستقیم (بدون Vue) نوشته می‌شود.
 * - لایه‌ها: زمین، آیتم‌ها (دیوار + ساختمان با ترتیب نقاش)، رویه (انتخاب/نامطمئن/ghost).
 * - props/emits طوری طراحی شده که ویرایشگر بتواند همین کامپوننت را در mode="edit" هدایت کند.
 */
export default {
    name: 'IsoBaseRenderer',
    components: { IsoGround, IsoItems, IsoOverlay },
    props: {
        layout: { type: Object, required: true },
        mode: {
            type: String,
            default: 'view',
            validator: v => ['view', 'edit'].includes(v),
        },
        selectedId: { type: Number, default: null },
        uncertainIds: { type: Array, default: () => [] },
        ghost: { type: Object, default: null },
        showGrid: { type: Boolean, default: undefined },
        fitOnMount: { type: Boolean, default: true },
        showLevels: { type: Boolean, default: false },
        showExportButton: { type: Boolean, default: false },
        exportName: { type: String, default: '' },
        maxZoom: { type: Number, default: 3 },
        minZoom: { type: Number, default: 0.3 },
    },
    emits: ['select', 'tile-down', 'tile-move', 'tile-up', 'building-down', 'zoom', 'export'],
    data() {
        return {
            uid: `iso${++uidCounter}`,
            view: { k: 1, tx: 0, ty: 0 },
            innerSelected: this.selectedId,
            hoverId: null,
            panning: false,
            exporting: false,
            spriteMap: {},
            wallSprite: null,
            wallMiddle: null,
            resolveToken: 0,
        }
    },
    computed: {
        geo() {
            return makeGeometry(this.layout?.grid_size || 44)
        },
        village() {
            return this.layout?.village === 'builder' ? 'builder' : 'home'
        },
        buildings() {
            return Array.isArray(this.layout?.buildings) ? this.layout.buildings : []
        },
        walls() {
            return Array.isArray(this.layout?.walls) ? this.layout.walls : []
        },
        items() {
            return sortItems(this.buildings, this.walls)
        },
        buildingById() {
            const map = new Map()
            for (const b of this.buildings) map.set(b.id, b)
            return map
        },
        selectedBuilding() {
            const id = this.selectedId ?? this.innerSelected
            return id === null || id === undefined ? null : (this.buildingById.get(id) || null)
        },
        uncertainSet() {
            return new Set((this.uncertainIds || []).map(Number))
        },
        uncertainBuildings() {
            return this.buildings.filter(b => this.isUncertain(b))
        },
        gridVisible() {
            return this.showGrid === undefined ? this.mode === 'edit' : !!this.showGrid
        },
        tipBuilding() {
            const id = this.hoverId ?? this.selectedId ?? this.innerSelected
            return id === null || id === undefined ? null : (this.buildingById.get(id) || null)
        },
        ariaLabel() {
            return `نقشهٔ چیدمان بیس، ${faDigits(this.geo.n)} در ${faDigits(this.geo.n)}`
        },
    },
    watch: {
        selectedId(v) {
            this.innerSelected = v
        },
        buildings: {
            handler() {
                this.resolveSprites()
            },
            deep: false,
        },
        village() {
            this.resolveSprites()
            this.resolveWallSprites()
        },
    },
    created() {
        // وضعیت لحظه‌ای viewport (غیرواکنشی؛ فقط روی attribute نوشته می‌شود)
        this._live = { k: 1, tx: 0, ty: 0 }
        this._pointers = new Map()
        this._gesture = null
        this._raf = null
        this._userMoved = false
        this._lastTile = null
        this._ro = null
    },
    mounted() {
        this.applyTransform()
        if (this.fitOnMount) this.fit()

        if (typeof ResizeObserver !== 'undefined') {
            this._ro = new ResizeObserver(() => {
                if (!this._userMoved) this.fit()
            })
            this._ro.observe(this.$refs.wrap)
        }

        this.resolveWallSprites()
        this.resolveSprites()
    },
    beforeUnmount() {
        if (this._ro) this._ro.disconnect()
        if (this._raf) cancelAnimationFrame(this._raf)
    },
    methods: {
        fa: faDigits,

        isUncertain(b) {
            return b.uncertain === true || b.placed === false || this.uncertainSet.has(Number(b.id))
        },

        /* ---------- اسپرایت‌ها ---------- */
        resolveSprites() {
            const token = ++this.resolveToken
            const next = {}
            const pending = []

            for (const b of this.buildings) {
                const candidates = spriteCandidates(b, this.village)
                if (!candidates.length) {
                    next[b.id] = null
                    continue
                }
                const key = candidates.join('|')
                if (!resolvedByKey.has(key)) {
                    resolvedByKey.set(key, resolveFirst(candidates))
                }
                if (resolvedValueByKey.has(key)) {
                    next[b.id] = resolvedValueByKey.get(key)
                    continue
                }
                pending.push(
                    resolvedByKey.get(key).then(res => {
                        resolvedValueByKey.set(key, res)
                        if (token !== this.resolveToken) return
                        this.spriteMap[b.id] = res
                    })
                )
            }

            this.spriteMap = next
            return Promise.all(pending)
        },
        resolveWallSprites() {
            const village = this.village
            resolveFirst(wallPostCandidates(village)).then(res => {
                if (village === this.village) this.wallSprite = res
            })
            resolveFirst(wallMiddleCandidates(village)).then(res => {
                if (village === this.village) this.wallMiddle = res
            })
        },
        onSpriteError(id) {
            this.spriteMap[id] = null
        },

        /* ---------- viewport ---------- */
        applyTransform() {
            const vp = this.$refs.viewport
            if (!vp) return
            const { k, tx, ty } = this._live
            vp.setAttribute('transform', `translate(${tx} ${ty}) scale(${k})`)
        },
        scheduleApply() {
            if (this._raf) return
            this._raf = requestAnimationFrame(() => {
                this._raf = null
                this.applyTransform()
            })
        },
        commitView() {
            this.view = { ...this._live }
            this.$emit('zoom', { ...this.view })
        },
        fitZoom() {
            const wrap = this.$refs.wrap
            if (!wrap) return 1
            const w = wrap.clientWidth || 1
            const h = wrap.clientHeight || 1
            return Math.min(w / this.geo.width, h / this.geo.height) * 0.98
        },
        clampZoom(k) {
            const lo = Math.min(this.minZoom, this.fitZoom() * 0.8)
            return clamp(k, lo, this.maxZoom)
        },
        clampPan() {
            const wrap = this.$refs.wrap
            if (!wrap) return
            const w = wrap.clientWidth
            const h = wrap.clientHeight
            const { k } = this._live
            const bw = this.geo.width * k
            const bh = this.geo.height * k
            // همیشه دست‌کم یک‌سوم نقشه داخل کادر بماند
            const margin = 0.34
            this._live.tx = clamp(this._live.tx, w - bw * (1 + margin) + bw * margin, bw * margin)
            this._live.ty = clamp(this._live.ty, h - bh * (1 + margin) + bh * margin, bh * margin)
            // اگر نقشه از کادر کوچک‌تر است، وسط‌چین
            if (bw <= w) this._live.tx = (w - bw) / 2
            if (bh <= h) this._live.ty = (h - bh) / 2
        },
        /** نمایش کل نقشه در کادر */
        fit() {
            const wrap = this.$refs.wrap
            if (!wrap) return
            const k = this.clampZoom(this.fitZoom())
            this._live.k = k
            this._live.tx = (wrap.clientWidth - this.geo.width * k) / 2
            this._live.ty = (wrap.clientHeight - this.geo.height * k) / 2
            this._userMoved = false
            this.applyTransform()
            this.commitView()
        },
        /** بزرگ‌نمایی حول نقطه‌ای از کادر (پیش‌فرض: مرکز) */
        zoomAt(factor, cx, cy) {
            const wrap = this.$refs.wrap
            if (!wrap) return
            if (cx === undefined) cx = wrap.clientWidth / 2
            if (cy === undefined) cy = wrap.clientHeight / 2
            const k0 = this._live.k
            const k1 = this.clampZoom(k0 * factor)
            if (k1 === k0) return
            this._live.tx = cx - (cx - this._live.tx) * (k1 / k0)
            this._live.ty = cy - (cy - this._live.ty) * (k1 / k0)
            this._live.k = k1
            this._userMoved = true
            this.clampPan()
            this.applyTransform()
            this.commitView()
        },
        zoomBy(factor) {
            this.zoomAt(factor)
        },
        /** مرکز کادر روی یک ساختمان */
        centerOn(id, zoom = null) {
            const b = this.buildingById.get(id)
            const wrap = this.$refs.wrap
            if (!b || !wrap) return
            const size = Math.max(1, Number(b.size) || 1)
            const cx = (b.x + size / 2 - (b.y + size / 2)) * 32 + this.geo.originX
            const cy = (b.x + size / 2 + b.y + size / 2) * 16 + this.geo.originY
            if (zoom) this._live.k = this.clampZoom(zoom)
            this._live.tx = wrap.clientWidth / 2 - cx * this._live.k
            this._live.ty = wrap.clientHeight / 2 - cy * this._live.k
            this._userMoved = true
            this.applyTransform()
            this.commitView()
        },

        /* ---------- تبدیل مختصات ---------- */
        /**
         * مختصات صفحه (clientX/Y) → خانهٔ شبکه.
         * @returns {{x:number,y:number,gx:number,gy:number,inside:boolean}}
         */
        screenToTile(clientX, clientY) {
            const rect = this.$refs.svg.getBoundingClientRect()
            const { k, tx, ty } = this._live
            const sx = (clientX - rect.left - tx) / k
            const sy = (clientY - rect.top - ty) / k
            return screenToTile(sx, sy, this.geo)
        },
        localPoint(e) {
            const rect = this.$refs.svg.getBoundingClientRect()
            return { x: e.clientX - rect.left, y: e.clientY - rect.top }
        },
        buildingIdFromEvent(e) {
            const el = e.target && e.target.closest ? e.target.closest('[data-id]') : null
            if (!el) return null
            const id = Number(el.getAttribute('data-id'))
            return Number.isFinite(id) ? id : null
        },

        /* ---------- رویدادهای اشاره‌گر ---------- */
        onPointerDown(e) {
            if (e.button !== undefined && e.button !== 0 && e.pointerType === 'mouse') return
            const svg = this.$refs.svg
            try { svg.setPointerCapture(e.pointerId) } catch (err) { /* بی‌اهمیت */ }

            const p = this.localPoint(e)
            this._pointers.set(e.pointerId, p)

            if (this._pointers.size === 2) {
                const pts = Array.from(this._pointers.values())
                this._gesture = {
                    type: 'pinch',
                    k0: this._live.k,
                    tx0: this._live.tx,
                    ty0: this._live.ty,
                    dist0: Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y) || 1,
                    mid0: { x: (pts[0].x + pts[1].x) / 2, y: (pts[0].y + pts[1].y) / 2 },
                }
                return
            }

            const tile = this.screenToTile(e.clientX, e.clientY)
            const id = this.buildingIdFromEvent(e)
            const payloadBase = { x: tile.x, y: tile.y, gx: tile.gx, gy: tile.gy, inside: tile.inside, pointerType: e.pointerType, event: e }

            if (id !== null) {
                this.$emit('building-down', { id, ...payloadBase })
            } else {
                this.$emit('tile-down', payloadBase)
            }

            this._gesture = {
                type: 'single',
                claimed: e.defaultPrevented,
                start: p,
                tx0: this._live.tx,
                ty0: this._live.ty,
                moved: false,
                downId: id,
                inside: tile.inside,
            }
        },
        onPointerMove(e) {
            const g = this._gesture
            if (this._pointers.has(e.pointerId)) {
                this._pointers.set(e.pointerId, this.localPoint(e))
            }

            if (g && g.type === 'pinch' && this._pointers.size >= 2) {
                const pts = Array.from(this._pointers.values())
                const dist = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y) || 1
                const mid = { x: (pts[0].x + pts[1].x) / 2, y: (pts[0].y + pts[1].y) / 2 }
                const k = this.clampZoom(g.k0 * (dist / g.dist0))
                // نقطهٔ جهانی زیر میانهٔ اولیه ثابت بماند، به‌علاوهٔ جابه‌جایی میانه
                const wx = (g.mid0.x - g.tx0) / g.k0
                const wy = (g.mid0.y - g.ty0) / g.k0
                this._live.k = k
                this._live.tx = mid.x - wx * k
                this._live.ty = mid.y - wy * k
                this._userMoved = true
                this.scheduleApply()
                return
            }

            if (g && g.type === 'single' && this._pointers.has(e.pointerId)) {
                const p = this.localPoint(e)
                const dx = p.x - g.start.x
                const dy = p.y - g.start.y
                if (!g.moved && Math.hypot(dx, dy) > 4) g.moved = true

                if (g.claimed) {
                    this.emitTileMove(e)
                    return
                }

                if (g.moved) {
                    this.panning = true
                    this._live.tx = g.tx0 + dx
                    this._live.ty = g.ty0 + dy
                    this._userMoved = true
                    this.scheduleApply()
                }
                return
            }

            // حرکت بدون کلیک (برای ابزار افزودن در حالت ویرایش)
            if (this.mode === 'edit' && this._pointers.size === 0) {
                this.emitTileMove(e)
            }
        },
        onPointerUp(e) {
            const g = this._gesture
            this._pointers.delete(e.pointerId)
            try { this.$refs.svg.releasePointerCapture(e.pointerId) } catch (err) { /* بی‌اهمیت */ }

            if (g && g.type === 'pinch') {
                if (this._pointers.size < 2) {
                    this._gesture = null
                    this.clampPan()
                    this.applyTransform()
                    this.commitView()
                }
                return
            }

            if (g && g.type === 'single') {
                this._gesture = null
                this.panning = false
                const tile = this.screenToTile(e.clientX, e.clientY)
                this.$emit('tile-up', { x: tile.x, y: tile.y, gx: tile.gx, gy: tile.gy, inside: tile.inside, pointerType: e.pointerType, event: e })

                if (!g.moved && !g.claimed) {
                    if (g.downId !== null) {
                        this.innerSelected = g.downId
                        this.$emit('select', g.downId)
                    } else if (g.inside || this.innerSelected !== null) {
                        this.innerSelected = null
                        this.$emit('select', null)
                    }
                } else if (g.moved && !g.claimed) {
                    this.clampPan()
                    this.applyTransform()
                    this.commitView()
                }
            }
        },
        emitTileMove(e) {
            const tile = this.screenToTile(e.clientX, e.clientY)
            const key = `${tile.x},${tile.y}`
            if (this._lastTile === key) return
            this._lastTile = key
            this.$emit('tile-move', { x: tile.x, y: tile.y, gx: tile.gx, gy: tile.gy, inside: tile.inside, pointerType: e.pointerType })
        },
        onPointerOver(e) {
            const id = this.buildingIdFromEvent(e)
            if (id !== null) this.hoverId = id
        },
        onPointerOut(e) {
            const id = this.buildingIdFromEvent(e)
            if (id !== null && id === this.hoverId) this.hoverId = null
        },
        onWheel(e) {
            const p = this.localPoint(e)
            const factor = Math.pow(1.1, -e.deltaY / 100)
            this.zoomAt(factor, p.x, p.y)
        },
        onDblClick(e) {
            const p = this.localPoint(e)
            this.zoomAt(1.6, p.x, p.y)
        },

        /* ---------- خروجی PNG ---------- */
        /**
         * ساخت PNG و تحویل به کاربر. نام فایل: base-<name>.png
         * @returns {Promise<Blob|null>}
         */
        async exportPng(name = '') {
            if (this.exporting) return null
            const slug = String(name || this.exportName || 'layout').replace(/[^\w\-]+/g, '-')
            this.exporting = true
            try {
                const coarse = typeof matchMedia === 'function' && matchMedia('(pointer: coarse)').matches
                const blob = await rasterize(this.$refs.svg, {
                    width: this.geo.width,
                    height: this.geo.height,
                    scale: coarse ? 1 : 1.5,
                    watermark: `CoCAI · ${slug}`,
                    keepOverlay: true,
                })
                this.$emit('export', blob)
                await deliverBlob(blob, `base-${slug}.png`, `CoCAI · ${slug}`)
                return blob
            } catch (err) {
                console.error('iso export failed', err)
                return null
            } finally {
                this.exporting = false
            }
        },
    },
}
</script>

<style scoped>
.iso-wrap {
    position: relative;
    width: 100%;
    height: 60vh;
    min-height: 280px;
    background: radial-gradient(ellipse at center, #10213a 0%, #0b1220 70%);
    border-radius: 1rem;
    overflow: hidden;
}
@media (min-width: 1024px) {
    .iso-wrap {
        height: 520px;
    }
}
.iso-svg {
    display: block;
    width: 100%;
    height: 100%;
    touch-action: none;
    user-select: none;
    -webkit-user-select: none;
    -webkit-tap-highlight-color: transparent;
    cursor: grab;
    outline: none;
}
/* حالت نمایش: کشیدن عمودی با یک انگشت صفحه را اسکرول می‌کند؛ افقی/دو انگشت نقشه را حرکت/زوم می‌کند */
.iso-wrap:not(.is-edit) .iso-svg {
    touch-action: pan-y;
}
.iso-svg:focus-visible {
    box-shadow: inset 0 0 0 3px #22d3ee;
}
.is-panning .iso-svg {
    cursor: grabbing;
}
.iso-toolbar {
    position: absolute;
    right: 10px;
    bottom: 10px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    z-index: 2;
}
.iso-btn {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(17, 24, 39, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #fff;
    font-size: 18px;
    font-weight: 900;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(6px);
    transition: background 0.15s;
}
.iso-btn:hover {
    background: rgba(31, 41, 55, 0.95);
}
.iso-btn:disabled {
    opacity: 0.5;
}
.iso-tip {
    position: absolute;
    left: 10px;
    bottom: 10px;
    max-width: calc(100% - 80px);
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 12px;
    background: rgba(17, 24, 39, 0.88);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    pointer-events: none;
    z-index: 2;
}
.iso-tip-icon {
    font-size: 16px;
}
.iso-tip-meta {
    color: #9ca3af;
    font-weight: 500;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
}
.iso-tip-flag {
    color: #fbbf24;
    font-size: 11px;
}
.iso-busy {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(11, 18, 32, 0.6);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    z-index: 3;
}
</style>
