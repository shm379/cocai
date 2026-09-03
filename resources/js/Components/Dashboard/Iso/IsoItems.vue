<template>
    <g class="iso-items-layer">
        <defs>
            <!-- ستون دیوار (اسپرایت یا جعبهٔ جایگزین) -->
            <g :id="`${uid}-wall-post`">
                <image
                    v-if="wallSprite"
                    :href="wallSprite.src"
                    :x="-wallPost.w / 2"
                    :y="tileH - wallPost.h * wallAnchor"
                    :width="wallPost.w"
                    :height="wallPost.h"
                    preserveAspectRatio="xMidYMax"
                />
                <g v-else>
                    <polygon :points="wallBox.post.left" fill="#a8a29e" stroke="#57534e" stroke-width="1" />
                    <polygon :points="wallBox.post.right" fill="#78716c" stroke="#57534e" stroke-width="1" />
                    <polygon :points="wallBox.post.top" fill="#e7e5e4" stroke="#57534e" stroke-width="1" />
                </g>
            </g>
            <!-- اتصال به همسایهٔ شرقی -->
            <g :id="`${uid}-wall-link-e`">
                <image
                    v-if="wallMiddle"
                    :href="wallMiddle.src"
                    :x="wallLinkE.cx - wallMid.w / 2"
                    :y="wallLinkE.by - wallMid.h * wallAnchor"
                    :width="wallMid.w"
                    :height="wallMid.h"
                    preserveAspectRatio="xMidYMax"
                />
                <image
                    v-else-if="wallSprite"
                    :href="wallSprite.src"
                    :x="wallLinkE.cx - wallLink.w / 2"
                    :y="wallLinkE.by - wallLink.h * wallAnchor"
                    :width="wallLink.w"
                    :height="wallLink.h"
                    preserveAspectRatio="xMidYMax"
                />
                <g v-else>
                    <polygon :points="wallBox.linkE.left" fill="#a8a29e" stroke="#57534e" stroke-width="1" />
                    <polygon :points="wallBox.linkE.right" fill="#78716c" stroke="#57534e" stroke-width="1" />
                    <polygon :points="wallBox.linkE.top" fill="#d6d3d1" stroke="#57534e" stroke-width="1" />
                </g>
            </g>
            <!-- اتصال به همسایهٔ جنوبی (قطعهٔ میانی به‌صورت آینه‌ای) -->
            <g :id="`${uid}-wall-link-s`">
                <image
                    v-if="wallMiddle"
                    :href="wallMiddle.src"
                    :x="-wallMid.w / 2"
                    :y="wallLinkS.by - wallMid.h * wallAnchor"
                    :width="wallMid.w"
                    :height="wallMid.h"
                    preserveAspectRatio="xMidYMax"
                    :transform="`translate(${wallLinkS.cx} 0) scale(-1 1)`"
                />
                <image
                    v-else-if="wallSprite"
                    :href="wallSprite.src"
                    :x="wallLinkS.cx - wallLink.w / 2"
                    :y="wallLinkS.by - wallLink.h * wallAnchor"
                    :width="wallLink.w"
                    :height="wallLink.h"
                    preserveAspectRatio="xMidYMax"
                />
                <g v-else>
                    <polygon :points="wallBox.linkS.left" fill="#a8a29e" stroke="#57534e" stroke-width="1" />
                    <polygon :points="wallBox.linkS.right" fill="#78716c" stroke="#57534e" stroke-width="1" />
                    <polygon :points="wallBox.linkS.top" fill="#d6d3d1" stroke="#57534e" stroke-width="1" />
                </g>
            </g>
            <!-- ۱۶ حالت ماسک همسایگی (N=1, E=2, S=4, W=8). اتصال‌های N و W را همسایه می‌کشد. -->
            <g v-for="m in 16" :key="'m' + m" :id="`${uid}-wall-${m - 1}`">
                <use :href="`#${uid}-wall-post`" />
                <use v-if="(m - 1) & 2" :href="`#${uid}-wall-link-e`" />
                <use v-if="(m - 1) & 4" :href="`#${uid}-wall-link-s`" />
            </g>
        </defs>

        <template v-for="it in items" :key="it.key">
            <!-- دیوار -->
            <use
                v-if="it.kind === 'wall'"
                :href="`#${uid}-wall-${it.mask}`"
                :x="wallPos(it).sx"
                :y="wallPos(it).sy"
                aria-hidden="true"
            />

            <!-- ساختمان -->
            <g
                v-else
                class="iso-b"
                :class="{ 'is-unplaced': it.building.placed === false }"
                :opacity="it.building.placed === false ? 0.45 : 1"
                :data-id="it.id"
                role="button"
                :aria-label="ariaLabel(it.building)"
            >
                <title>{{ tooltip(it.building) }}</title>

                <!-- ناحیهٔ کلیک: فقط لوزی ردپا (نه جعبهٔ بلند اسپرایت) -->
                <polygon :points="footprint(it)" fill="transparent" stroke="none" pointer-events="all" />

                <template v-if="spriteOf(it)">
                    <image
                        pointer-events="none"
                        :href="spriteOf(it).src"
                        :x="spriteBox(it).x"
                        :y="spriteBox(it).y"
                        :width="spriteBox(it).w"
                        :height="spriteBox(it).h"
                        preserveAspectRatio="xMidYMax"
                        @error="$emit('sprite-error', it.id)"
                    />
                </template>
                <template v-else>
                    <g pointer-events="none">
                    <polygon :points="box(it).left" :fill="shadeColor(it.building.color, -18)" stroke="#111827" stroke-width="1.5" stroke-linejoin="round" />
                    <polygon :points="box(it).right" :fill="shadeColor(it.building.color, -35)" stroke="#111827" stroke-width="1.5" stroke-linejoin="round" />
                    <polygon :points="box(it).top" :fill="it.building.color || '#6b7280'" :stroke="shadeColor(it.building.color, 25)" stroke-width="1.5" stroke-linejoin="round" />
                    <polygon :points="box(it).top" fill="none" stroke="#111827" stroke-width="0.75" stroke-linejoin="round" opacity="0.6" />
                    <text
                        :x="box(it).center.sx"
                        :y="box(it).center.sy"
                        text-anchor="middle"
                        dominant-baseline="central"
                        :font-size="emojiSize(it.size)"
                        style="pointer-events: none"
                    >{{ it.building.icon || '🏠' }}</text>
                    </g>
                </template>

                <g v-if="showLevels && it.building.level" style="pointer-events: none">
                    <rect :x="badgePos(it).x - 14" :y="badgePos(it).y - 11" width="28" height="22" rx="11" fill="#111827" stroke="#f59e0b" stroke-width="1.5" />
                    <text :x="badgePos(it).x" :y="badgePos(it).y + 1" text-anchor="middle" dominant-baseline="central" font-size="13" font-weight="700" fill="#fde68a">{{ fa(it.building.level) }}</text>
                </g>
            </g>
        </template>
    </g>
</template>

<script>
import {
    TILE_W,
    TILE_H,
    toScreen,
    footprintPoints,
    footprintCenter,
    footprintBottom,
    boxGeometry,
    shade,
    EMOJI_SIZE,
    faDigits,
} from './iso'
import { spriteMeta } from './sprites'

const WALL_POST_SCALE = 1.0
const WALL_MID_SCALE = 0.8
const WALL_LINK_SCALE = 0.62
const WALL_ANCHOR_Y = 0.92

/**
 * لایهٔ آیتم‌ها: دیوارها (با اتصال خودکار) و ساختمان‌ها (اسپرایت یا جعبهٔ رنگی + ایموجی)
 * به ترتیب عمق (الگوریتم نقاش).
 */
export default {
    name: 'IsoItems',
    props: {
        items: { type: Array, required: true },
        geo: { type: Object, required: true },
        mode: { type: String, default: 'view' },
        /** id ساختمان → {src,w,h} | null */
        sprites: { type: Object, default: () => ({}) },
        wallSprite: { type: Object, default: null },
        wallMiddle: { type: Object, default: null },
        showLevels: { type: Boolean, default: false },
        uid: { type: String, required: true },
    },
    emits: ['sprite-error'],
    computed: {
        tileH() {
            return TILE_H
        },
        wallAnchor() {
            return WALL_ANCHOR_Y
        },
        wallPost() {
            const ratio = this.wallSprite ? this.wallSprite.h / this.wallSprite.w : 1
            // اسپرایت قدیمی (Wall.png) صفحهٔ کامل خانه را دارد → به عرض خانه؛ ستون‌های باریک جدید → ۰٫۷ خانه
            const scale = ratio > 1.15 ? WALL_POST_SCALE * 0.7 : WALL_POST_SCALE
            const w = TILE_W * scale
            return { w, h: w * ratio }
        },
        wallMid() {
            const w = TILE_W * WALL_MID_SCALE
            const ratio = this.wallMiddle ? this.wallMiddle.h / this.wallMiddle.w : 1
            return { w, h: w * ratio }
        },
        wallLink() {
            const w = TILE_W * WALL_LINK_SCALE
            const ratio = this.wallSprite ? this.wallSprite.h / this.wallSprite.w : 1
            return { w, h: w * ratio }
        },
        /** مختصات محلی اتصال شرقی (مبدأ = گوشهٔ بالای لوزی خانه) */
        wallLinkE() {
            // مرکز خانه (0,16)، مرکز همسایهٔ شرقی (32,32) → میانه (16,24)
            return { cx: 16, by: 24 + (TILE_H / 2) * WALL_LINK_SCALE }
        },
        wallLinkS() {
            // مرکز همسایهٔ جنوبی (-32,32) → میانه (-16,24)
            return { cx: -16, by: 24 + (TILE_H / 2) * WALL_LINK_SCALE }
        },
        /** هندسهٔ جایگزین دیوار (وقتی اسپرایت در دسترس نیست) */
        wallBox() {
            const local = { n: 1, originX: 0, originY: 0 }
            const post = this.localBox(local, 0.5, 0.5, 0.55, 26)
            const linkE = this.localBox(local, 0.75, 0.5, 0.35, 20)
            const linkS = this.localBox(local, 0.5, 0.75, 0.35, 20)
            return { post, linkE, linkS }
        },
    },
    methods: {
        fa: faDigits,
        shadeColor(color, pct) {
            return shade(color || '#6b7280', pct)
        },
        emojiSize(size) {
            return EMOJI_SIZE[size] || 30
        },
        spriteOf(it) {
            return this.sprites[it.id] || null
        },
        wallPos(it) {
            return toScreen(it.x, it.y, this.geo)
        },
        footprint(it) {
            return footprintPoints(it.x, it.y, it.size, this.geo)
        },
        box(it) {
            return boxGeometry(it.x, it.y, it.size, this.geo)
        },
        spriteBox(it) {
            const sp = this.spriteOf(it)
            const meta = spriteMeta(it.building)
            const w = it.size * TILE_W * 0.92 * meta.scale
            const h = w * (sp.h / sp.w)
            const c = footprintCenter(it.x, it.y, it.size, this.geo)
            const b = footprintBottom(it.x, it.y, it.size, this.geo)
            return {
                x: c.sx - w / 2,
                y: b.sy - h * meta.anchorY + meta.dy,
                w,
                h,
            }
        },
        badgePos(it) {
            const r = toScreen(it.x + it.size, it.y, this.geo)
            return { x: r.sx - 10, y: r.sy + 4 }
        },
        tooltip(b) {
            const level = b.level ? ` (سطح ${faDigits(b.level)})` : ''
            const flag = b.placed === false ? ' — جا نشد' : (b.uncertain ? ' — نامطمئن' : '')
            return `${b.label || b.type}${level} — خانهٔ (${faDigits(b.x)}، ${faDigits(b.y)})${flag}`
        },
        ariaLabel(b) {
            const level = b.level ? `، سطح ${faDigits(b.level)}` : ''
            const flag = b.placed === false || b.uncertain ? '، نامطمئن' : ''
            return `${b.label || b.type}${level}، خانهٔ ${faDigits(b.x)} و ${faDigits(b.y)}${flag}`
        },
        /**
         * جعبهٔ ایزومتریک کوچک در مختصات محلی خانه: مرکز (cx,cy) بر حسب خانه، عرض w خانه، ارتفاع h پیکسل.
         */
        localBox(local, cx, cy, w, h) {
            const half = w / 2
            const T = toScreen(cx - half, cy - half, local)
            const R = toScreen(cx + half, cy - half, local)
            const B = toScreen(cx + half, cy + half, local)
            const L = toScreen(cx - half, cy + half, local)
            return {
                top: `${T.sx},${T.sy - h} ${R.sx},${R.sy - h} ${B.sx},${B.sy - h} ${L.sx},${L.sy - h}`,
                left: `${L.sx},${L.sy - h} ${B.sx},${B.sy - h} ${B.sx},${B.sy} ${L.sx},${L.sy}`,
                right: `${B.sx},${B.sy - h} ${R.sx},${R.sy - h} ${R.sx},${R.sy} ${B.sx},${B.sy}`,
            }
        },
    },
}
</script>

<style scoped>
.iso-b {
    cursor: pointer;
}
.iso-b:hover image,
.iso-b:hover polygon {
    filter: brightness(1.15);
}
</style>
