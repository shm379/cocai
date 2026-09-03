<template>
    <g class="iso-ground-layer">
        <defs>
            <pattern
                :id="`${uid}-grass`"
                patternUnits="userSpaceOnUse"
                :width="tileW"
                :height="tileH"
                :x="geo.originX - tileW / 2"
                :y="geo.originY"
            >
                <rect x="0" y="0" :width="tileW" :height="tileH" fill="#3f6212" />
                <polygon :points="`${tileW / 2},0 ${tileW},${tileH / 2} ${tileW / 2},${tileH} 0,${tileH / 2}`" fill="#4d7c0f" />
            </pattern>
        </defs>

        <!-- ضخامت خاک زیر لوزی -->
        <polygon :points="soilPoints" fill="#3b2a14" />
        <polygon :points="soilSidePoints" fill="#2a1d0e" />

        <!-- چمن -->
        <polygon :points="board" :fill="`url(#${uid}-grass)`" />

        <!-- خطوط شبکه (فقط حالت ویرایش) -->
        <g v-if="showGrid" class="iso-edit-only" stroke="rgba(255,255,255,0.16)" stroke-width="1" fill="none">
            <line v-for="l in gridLines" :key="l.key" :x1="l.x1" :y1="l.y1" :x2="l.x2" :y2="l.y2" />
        </g>

        <!-- حاشیهٔ لوزی -->
        <polygon :points="board" fill="none" stroke="#1a2e05" stroke-width="3" stroke-linejoin="round" />
    </g>
</template>

<script>
import { TILE_W, TILE_H, boardPoints, toScreen } from './iso'

/**
 * لایهٔ زمین: لوزی چمن با الگوی شطرنجی ایزومتریک، ضخامت خاک و خطوط شبکه.
 */
export default {
    name: 'IsoGround',
    props: {
        geo: { type: Object, required: true },
        showGrid: { type: Boolean, default: false },
        uid: { type: String, required: true },
    },
    computed: {
        tileW() {
            return TILE_W
        },
        tileH() {
            return TILE_H
        },
        board() {
            return boardPoints(this.geo)
        },
        soilPoints() {
            const g = this.geo
            const d = 14
            const t = toScreen(0, 0, g)
            const r = toScreen(g.n, 0, g)
            const b = toScreen(g.n, g.n, g)
            const l = toScreen(0, g.n, g)
            return `${t.sx},${t.sy + d} ${r.sx},${r.sy + d} ${b.sx},${b.sy + d} ${l.sx},${l.sy + d}`
        },
        soilSidePoints() {
            const g = this.geo
            const d = 14
            const r = toScreen(g.n, 0, g)
            const b = toScreen(g.n, g.n, g)
            const l = toScreen(0, g.n, g)
            return `${l.sx},${l.sy} ${b.sx},${b.sy} ${r.sx},${r.sy} ${r.sx},${r.sy + d} ${b.sx},${b.sy + d} ${l.sx},${l.sy + d}`
        },
        gridLines() {
            const g = this.geo
            const lines = []
            for (let i = 0; i <= g.n; i++) {
                const a = toScreen(i, 0, g)
                const b = toScreen(i, g.n, g)
                lines.push({ key: `v${i}`, x1: a.sx, y1: a.sy, x2: b.sx, y2: b.sy })
                const c = toScreen(0, i, g)
                const d = toScreen(g.n, i, g)
                lines.push({ key: `h${i}`, x1: c.sx, y1: c.sy, x2: d.sx, y2: d.sy })
            }
            return lines
        },
    },
}
</script>
