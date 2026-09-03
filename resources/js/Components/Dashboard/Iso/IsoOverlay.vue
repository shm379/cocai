<template>
    <g class="iso-overlay-layer" style="pointer-events: none">
        <!-- ساختمان‌های نامطمئن: حلقهٔ چشمک‌زن + نشان «؟» -->
        <g v-for="b in uncertain" :key="'u' + b.id">
            <polygon
                :points="fp(b)"
                fill="rgba(245,158,11,0.14)"
                stroke="#f59e0b"
                stroke-width="3"
                stroke-dasharray="10 6"
                stroke-linejoin="round"
                vector-effect="non-scaling-stroke"
                class="iso-pulse"
            />
            <g :transform="`translate(${badge(b).sx} ${badge(b).sy}) scale(${badgeScale})`">
                <circle r="12" fill="#f59e0b" stroke="#111827" stroke-width="1.5" />
                <text text-anchor="middle" dominant-baseline="central" font-size="15" font-weight="900" fill="#111827" y="1">؟</text>
            </g>
        </g>

        <!-- انتخاب -->
        <polygon
            v-if="selected"
            :points="fp(selected)"
            fill="rgba(34,211,238,0.15)"
            stroke="#22d3ee"
            stroke-width="3"
            stroke-linejoin="round"
            vector-effect="non-scaling-stroke"
            class="iso-edit-only"
        />

        <!-- پیش‌نمایش جابه‌جایی/افزودن -->
        <g v-if="ghost" class="iso-edit-only" opacity="0.85">
            <polygon
                :points="fp(ghost)"
                :fill="ghost.valid === false ? 'rgba(239,68,68,0.35)' : 'rgba(34,197,94,0.35)'"
                :stroke="ghost.valid === false ? '#dc2626' : '#16a34a'"
                stroke-width="3"
                stroke-linejoin="round"
                vector-effect="non-scaling-stroke"
            />
            <g opacity="0.6">
                <polygon :points="ghostBox.left" :fill="shade(ghost.color || '#6b7280', -18)" stroke="#111827" stroke-width="1.5" />
                <polygon :points="ghostBox.right" :fill="shade(ghost.color || '#6b7280', -35)" stroke="#111827" stroke-width="1.5" />
                <polygon :points="ghostBox.top" :fill="ghost.color || '#6b7280'" stroke="#111827" stroke-width="1.5" />
                <text :x="ghostBox.center.sx" :y="ghostBox.center.sy" text-anchor="middle" dominant-baseline="central" :font-size="emojiSize">{{ ghost.icon || '🏠' }}</text>
            </g>
        </g>
    </g>
</template>

<script>
import { footprintPoints, footprintTop, boxGeometry, shade, EMOJI_SIZE } from './iso'

/**
 * لایهٔ رویی: حلقهٔ انتخاب، نشان ساختمان‌های نامطمئن و پیش‌نمایش (ghost) ویرایشگر.
 * این لایه کوچک است تا در حین درگ فقط همین چند گره دوباره رندر شود.
 */
export default {
    name: 'IsoOverlay',
    props: {
        geo: { type: Object, required: true },
        selected: { type: Object, default: null },
        uncertain: { type: Array, default: () => [] },
        ghost: { type: Object, default: null },
        /** مقیاس فعلی viewport؛ برای ثابت‌ماندن اندازهٔ نشان «؟» روی صفحه */
        zoom: { type: Number, default: 1 },
        uid: { type: String, required: true },
    },
    computed: {
        badgeScale() {
            const k = Number(this.zoom) || 1
            return Math.min(6, Math.max(1, 0.9 / k))
        },
        ghostBox() {
            const g = this.ghost
            return boxGeometry(Number(g.x) || 0, Number(g.y) || 0, Math.max(1, Number(g.size) || 1), this.geo)
        },
        emojiSize() {
            return EMOJI_SIZE[Math.max(1, Number(this.ghost?.size) || 1)] || 30
        },
    },
    methods: {
        shade,
        fp(b) {
            return footprintPoints(Number(b.x) || 0, Number(b.y) || 0, Math.max(1, Number(b.size) || 1), this.geo)
        },
        badge(b) {
            const t = footprintTop(Number(b.x) || 0, Number(b.y) || 0, this.geo)
            return { sx: t.sx, sy: t.sy - 8 }
        },
    },
}
</script>

<style scoped>
.iso-pulse {
    animation: iso-pulse 1.2s ease-in-out infinite alternate;
}
@keyframes iso-pulse {
    from { opacity: 0.35; }
    to { opacity: 1; }
}
@media (prefers-reduced-motion: reduce) {
    .iso-pulse {
        animation: none;
        opacity: 1;
    }
}
</style>
