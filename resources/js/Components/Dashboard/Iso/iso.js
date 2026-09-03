/**
 * توابع خالص هندسهٔ ایزومتریک (۲:۱) برای رندر چیدمان بیس.
 *
 * شبکهٔ N×N روی یک لوزی ترسیم می‌شود: هر خانه یک لوزی ۶۴×۳۲ است.
 * نقطهٔ شبکهٔ (gx, gy) گوشهٔ خانه‌هاست؛ خانهٔ (x, y) لوزی بین
 * نقاط (x,y)، (x+1,y)، (x+1,y+1) و (x,y+1) است.
 */

export const TILE_W = 64
export const TILE_H = 32
export const HW = TILE_W / 2
export const HH = TILE_H / 2

/** فضای بالای لوزی برای اسپرایت‌های بلند ۴×۴ که بالاتر از ردیف اول کشیده می‌شوند */
export const PAD_TOP = 176
export const PAD_BOTTOM = 32

/**
 * هندسهٔ صحنه برای شبکهٔ N×N.
 * @param {number} n
 * @returns {{n:number, originX:number, originY:number, width:number, height:number}}
 */
export function makeGeometry(n) {
    const size = Math.max(1, Number(n) || 44)
    return {
        n: size,
        originX: size * HW,
        originY: PAD_TOP,
        width: size * TILE_W,
        height: size * TILE_H + PAD_TOP + PAD_BOTTOM,
    }
}

/**
 * نقطهٔ شبکه → مختصات صحنه (واحد SVG).
 */
export function toScreen(gx, gy, geo) {
    return {
        sx: (gx - gy) * HW + geo.originX,
        sy: (gx + gy) * HH + geo.originY,
    }
}

/**
 * مختصات صحنه → مختصات پیوستهٔ شبکه.
 */
export function screenToGrid(sx, sy, geo) {
    const px = sx - geo.originX
    const py = sy - geo.originY
    return {
        gx: (px / HW + py / HH) / 2,
        gy: (py / HH - px / HW) / 2,
    }
}

/**
 * مختصات صحنه → خانهٔ شبکه (عدد صحیح) یا null اگر بیرون نقشه باشد.
 */
export function screenToTile(sx, sy, geo) {
    const { gx, gy } = screenToGrid(sx, sy, geo)
    const x = Math.floor(gx)
    const y = Math.floor(gy)
    const inside = x >= 0 && y >= 0 && x < geo.n && y < geo.n
    return { x, y, gx, gy, inside }
}

/**
 * رشتهٔ points یک چهارضلعی روی شبکه (از گوشهٔ (x,y) با ابعاد w×h).
 */
export function quadPoints(x, y, w, h, geo, dy = 0) {
    const a = toScreen(x, y, geo)
    const b = toScreen(x + w, y, geo)
    const c = toScreen(x + w, y + h, geo)
    const d = toScreen(x, y + h, geo)
    return `${a.sx},${a.sy + dy} ${b.sx},${b.sy + dy} ${c.sx},${c.sy + dy} ${d.sx},${d.sy + dy}`
}

/** لوزی یک خانه */
export function tilePoints(x, y, geo) {
    return quadPoints(x, y, 1, 1, geo)
}

/** لوزی ردپای ساختمان s×s */
export function footprintPoints(x, y, size, geo, dy = 0) {
    return quadPoints(x, y, size, size, geo, dy)
}

/** لوزی کل نقشه */
export function boardPoints(geo) {
    return quadPoints(0, 0, geo.n, geo.n, geo)
}

/** مرکز ردپا */
export function footprintCenter(x, y, size, geo) {
    return toScreen(x + size / 2, y + size / 2, geo)
}

/** گوشهٔ پایینی ردپا (لنگر اسپرایت) */
export function footprintBottom(x, y, size, geo) {
    return toScreen(x + size, y + size, geo)
}

/** گوشهٔ بالایی ردپا (جای نشان «؟») */
export function footprintTop(x, y, geo) {
    return toScreen(x, y, geo)
}

/**
 * عمق نقاشی (الگوریتم نقاش): مجموع مختصات مرکز ردپا (x + y + size).
 *
 * با این کلید، همسایه‌های شرقی/جنوبیِ یک ساختمان بزرگ‌تر (مثلاً دیوارهای دور تاون‌هال ۴×۴)
 * همیشه بعد از آن و همسایه‌های شمالی/غربی قبل از آن کشیده می‌شوند؛ همسایه‌های قطری
 * (تساوی) روی صفحه هم‌پوشانی ندارند. دیوارها به‌طور خودکار x + y + 1 می‌گیرند.
 */
export function depthOf(x, y, size) {
    return x + y + size
}

/**
 * ماسک همسایگی دیوار (۴ بیت: N=1, E=2, S=4, W=8) — فقط اتصال‌های عمودی/افقی.
 */
export function wallMask(x, y, wallSet) {
    let m = 0
    if (wallSet.has(`${x},${y - 1}`)) m |= 1
    if (wallSet.has(`${x + 1},${y}`)) m |= 2
    if (wallSet.has(`${x},${y + 1}`)) m |= 4
    if (wallSet.has(`${x - 1},${y}`)) m |= 8
    return m
}

/**
 * فهرست مرتب‌شدهٔ آیتم‌های قابل رندر (ساختمان + دیوار) بر اساس عمق.
 * @param {Array} buildings
 * @param {Array<Array<number>>} walls
 */
export function sortItems(buildings, walls) {
    const wallSet = new Set(walls.map(([x, y]) => `${x},${y}`))
    const items = []

    for (const b of buildings) {
        const size = Math.max(1, Number(b.size) || 1)
        items.push({
            kind: 'building',
            key: `b${b.id}`,
            id: b.id,
            x: Number(b.x) || 0,
            y: Number(b.y) || 0,
            size,
            depth: depthOf(Number(b.x) || 0, Number(b.y) || 0, size),
            building: b,
        })
    }

    for (const [x, y] of walls) {
        items.push({
            kind: 'wall',
            key: `w${x}_${y}`,
            id: null,
            x,
            y,
            size: 1,
            depth: depthOf(x, y, 1),
            mask: wallMask(x, y, wallSet),
        })
    }

    items.sort((a, b) => {
        if (a.depth !== b.depth) return a.depth - b.depth
        if (a.x !== b.x) return a.x - b.x
        if (a.y !== b.y) return a.y - b.y
        return (a.id ?? 0) - (b.id ?? 0)
    })

    return items
}

/**
 * روشن/تیره کردن رنگ هگز بر حسب درصد (مثبت = روشن‌تر).
 */
export function shade(hex, percent) {
    const rgb = hexToRgb(hex)
    if (!rgb) return hex
    const [h, s, l] = rgbToHsl(rgb.r, rgb.g, rgb.b)
    const nl = Math.max(0, Math.min(1, l + percent / 100))
    const [r, g, b] = hslToRgb(h, s, nl)
    return rgbToHex(r, g, b)
}

export function hexToRgb(hex) {
    if (typeof hex !== 'string') return null
    let h = hex.trim().replace('#', '')
    if (h.length === 3) h = h.split('').map(c => c + c).join('')
    if (!/^[0-9a-fA-F]{6}$/.test(h)) return null
    const n = parseInt(h, 16)
    return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 }
}

export function rgbToHex(r, g, b) {
    const to = v => Math.round(Math.max(0, Math.min(255, v))).toString(16).padStart(2, '0')
    return `#${to(r)}${to(g)}${to(b)}`
}

function rgbToHsl(r, g, b) {
    r /= 255; g /= 255; b /= 255
    const max = Math.max(r, g, b)
    const min = Math.min(r, g, b)
    let h = 0
    let s = 0
    const l = (max + min) / 2
    if (max !== min) {
        const d = max - min
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min)
        switch (max) {
            case r: h = (g - b) / d + (g < b ? 6 : 0); break
            case g: h = (b - r) / d + 2; break
            default: h = (r - g) / d + 4
        }
        h /= 6
    }
    return [h, s, l]
}

function hslToRgb(h, s, l) {
    if (s === 0) {
        const v = l * 255
        return [v, v, v]
    }
    const q = l < 0.5 ? l * (1 + s) : l + s - l * s
    const p = 2 * l - q
    const hue = t => {
        if (t < 0) t += 1
        if (t > 1) t -= 1
        if (t < 1 / 6) return p + (q - p) * 6 * t
        if (t < 1 / 2) return q
        if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6
        return p
    }
    return [hue(h + 1 / 3) * 255, hue(h) * 255, hue(h - 1 / 3) * 255]
}

/** ارتفاع جعبهٔ جایگزین بر حسب ابعاد ردپا */
export const BOX_HEIGHT = { 1: 24, 2: 40, 3: 56, 4: 72 }
/** اندازهٔ ایموجی جایگزین بر حسب ابعاد ردپا */
export const EMOJI_SIZE = { 1: 18, 2: 26, 3: 38, 4: 50 }

/**
 * هندسهٔ جعبهٔ ایزومتریک جایگزین (وقتی اسپرایت نداریم).
 * @returns {{top:string,left:string,right:string,center:{sx:number,sy:number},height:number}}
 */
export function boxGeometry(x, y, size, geo) {
    const inset = 0.12
    const H = BOX_HEIGHT[size] || Math.round(size * 18)
    const ix = x + inset
    const iy = y + inset
    const iw = size - inset * 2

    const gT = toScreen(ix, iy, geo)
    const gR = toScreen(ix + iw, iy, geo)
    const gB = toScreen(ix + iw, iy + iw, geo)
    const gL = toScreen(ix, iy + iw, geo)

    const top = `${gT.sx},${gT.sy - H} ${gR.sx},${gR.sy - H} ${gB.sx},${gB.sy - H} ${gL.sx},${gL.sy - H}`
    const left = `${gL.sx},${gL.sy - H} ${gB.sx},${gB.sy - H} ${gB.sx},${gB.sy} ${gL.sx},${gL.sy}`
    const right = `${gB.sx},${gB.sy - H} ${gR.sx},${gR.sy - H} ${gR.sx},${gR.sy} ${gB.sx},${gB.sy}`
    const c = toScreen(ix + iw / 2, iy + iw / 2, geo)

    return {
        top,
        left,
        right,
        height: H,
        center: { sx: c.sx, sy: c.sy - H },
        topRight: { sx: gR.sx, sy: gR.sy - H },
    }
}

let faFormatter = null

/** اعداد فارسی */
export function faDigits(value) {
    if (value === null || value === undefined || value === '') return ''
    try {
        if (!faFormatter && typeof Intl !== 'undefined') {
            faFormatter = new Intl.NumberFormat('fa-IR', { useGrouping: false })
        }
        if (faFormatter && typeof value === 'number') return faFormatter.format(value)
    } catch (e) {
        // ادامه با جایگزینی دستی
    }
    return String(value).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d])
}

export function clamp(v, min, max) {
    return Math.min(max, Math.max(min, v))
}
