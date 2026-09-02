<template>
    <div class="w-full" :class="iso ? 'iso-outer' : ''">
        <div :class="iso ? 'iso-inner' : 'w-full'">
            <svg
                :viewBox="`0 0 ${gridSize} ${gridSize}`"
                class="w-full h-auto block rounded-lg"
                preserveAspectRatio="xMidYMid meet"
            >
                <!-- زمین -->
                <rect x="0" y="0" :width="gridSize" :height="gridSize" fill="#3f6212" />

                <!-- خطوط شبکه -->
                <g stroke="#4d7c0f" stroke-width="0.04">
                    <template v-for="i in gridSize + 1" :key="'g' + i">
                        <line :x1="i - 1" y1="0" :x2="i - 1" :y2="gridSize" />
                        <line x1="0" :y1="i - 1" :x2="gridSize" :y2="i - 1" />
                    </template>
                </g>

                <!-- دیوارها -->
                <rect
                    v-for="(w, i) in walls"
                    :key="'w' + i"
                    :x="w[0] + 0.1"
                    :y="w[1] + 0.1"
                    width="0.8"
                    height="0.8"
                    rx="0.12"
                    fill="#e7e5e4"
                    stroke="#57534e"
                    stroke-width="0.08"
                />

                <!-- ساختمان‌ها -->
                <g v-for="b in placedBuildings" :key="b.id">
                    <rect
                        :x="b.x + 0.12"
                        :y="b.y + 0.12"
                        :width="b.size - 0.24"
                        :height="b.size - 0.24"
                        rx="0.35"
                        :fill="b.color"
                        stroke="#111827"
                        stroke-width="0.12"
                        opacity="0.94"
                    >
                        <title>{{ tooltip(b) }}</title>
                    </rect>
                    <text
                        v-if="showIcons"
                        :x="b.x + b.size / 2"
                        :y="b.y + b.size / 2"
                        text-anchor="middle"
                        dominant-baseline="central"
                        :font-size="b.size >= 3 ? 1.5 : 1.05"
                        :transform="iso ? isoTextTransform(b) : undefined"
                        style="pointer-events: none"
                    >{{ b.icon }}</text>
                </g>
            </svg>
        </div>
    </div>
</template>

<script>
/**
 * رندر چیدمان ۴۴×۴۴ بازسازی‌شده به‌صورت SVG.
 * حالت iso همان نمای لوزی‌شکل بازی را با transform ساده شبیه‌سازی می‌کند.
 */
export default {
    name: 'BaseLayoutGrid',
    props: {
        layout: {
            type: Object,
            required: true,
        },
        iso: {
            type: Boolean,
            default: false,
        },
        showIcons: {
            type: Boolean,
            default: true,
        },
    },
    computed: {
        gridSize() {
            return this.layout?.grid_size || 44
        },
        walls() {
            return Array.isArray(this.layout?.walls) ? this.layout.walls : []
        },
        placedBuildings() {
            return (this.layout?.buildings || []).filter(b => b.placed !== false)
        },
    },
    methods: {
        tooltip(b) {
            const level = b.level ? ` (سطح ${b.level})` : ''
            return `${b.label}${level} — خانهٔ (${b.x}, ${b.y}) ابعاد ${b.size}×${b.size}`
        },
        isoTextTransform(b) {
            const cx = b.x + b.size / 2
            const cy = b.y + b.size / 2
            return `translate(${cx} ${cy}) rotate(-45) scale(1 2) translate(${-cx} ${-cy})`
        },
    },
}
</script>

<style scoped>
.iso-outer {
    aspect-ratio: 2 / 1;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: visible;
}
.iso-inner {
    width: 70.7%;
    transform: scaleY(0.5) rotate(45deg);
    transform-origin: center;
}
</style>
