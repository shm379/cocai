/**
 * نگاشت نوع ساختمان → آدرس اسپرایت.
 *
 * ترتیب جست‌وجو برای هر ساختمان:
 *   1. building.sprite (اگر بک‌اند فرستاده باشد)
 *   2. /images/coc/buildings/{home|builder}/<type>.png
 *   3. /images/coc/units/<PascalCase>.png (آرشیو قدیمی clasher.us)
 * اگر هیچ‌کدام بارگذاری نشد، رندرر جعبهٔ رنگی + ایموجی می‌کشد.
 */

export const BUILDINGS_BASE = '/images/coc/buildings'
export const UNITS_BASE = '/images/coc/units'

/** اسپرایت پایهٔ دیوار (یک ستون) — آرشیو قدیمی */
export const WALL_SPRITE = `${UNITS_BASE}/Wall.png`

/**
 * کاندیدهای ستون دیوار به ترتیب اولویت.
 * @param {string} village 'home' | 'builder'
 */
export function wallPostCandidates(village = 'home') {
    const vil = village === 'builder' ? 'builder' : 'home'
    return [`${BUILDINGS_BASE}/walls/${vil}.png`, WALL_SPRITE]
}

/**
 * کاندیدهای قطعهٔ میانی دیوار (بین دو ستون). اگر نبود، از خود ستون با مقیاس کوچک‌تر استفاده می‌شود.
 */
export function wallMiddleCandidates(village = 'home') {
    const vil = village === 'builder' ? 'builder' : 'home'
    return [`${BUILDINGS_BASE}/walls/${vil}_middle.png`]
}

/** استثناهای نام‌گذاری فایل‌های قدیمی */
const UNIT_FILE_OVERRIDES = {
    x_bow: 'XBow',
    inferno_tower: 'Inferno_Tower',
    builder_hut: 'Builders_Hut',
    barbarian_king: 'Barbarian_King_Altar',
    archer_queen: 'Archer_Queen_Altar',
    grand_warden: 'Grand_Warden_Altar',
    royal_champion: 'Royal_Champion',
    minion_prince: 'Minion_Prince',
    battle_machine: 'Battle_Machine_Altar',
    battle_copter: 'Battle_Copter',
    town_hall: 'Town_Hall',
    builder_hall: 'Builder_Hall',
    otto_outpost: 'OTTOs_Outpost',
    multi_archer_tower: 'MultiArcher_Tower',
    multi_gear_tower: 'MultiGear_Tower',
}

/** انواعی که فایل سطح‌دار دارند (Town_Hall15.png …) */
const LEVELED_TYPES = new Set(['town_hall', 'builder_hall'])

/** پیش‌فرض لنگر و مقیاس اسپرایت‌ها */
export const DEFAULT_SPRITE_META = Object.freeze({
    scale: 1,
    anchorY: 0.92,
    dy: 0,
})

/**
 * snake_case → Pascal_Case (archer_tower → Archer_Tower)
 */
export function toPascalFile(type) {
    return String(type || '')
        .split('_')
        .filter(Boolean)
        .map(p => p.charAt(0).toUpperCase() + p.slice(1))
        .join('_')
}

/**
 * نام فایل قدیمی برای یک نوع.
 */
export function unitFileName(type, level = null) {
    const base = UNIT_FILE_OVERRIDES[type] || toPascalFile(type)
    if (level && LEVELED_TYPES.has(type)) {
        return `${base}${level}`
    }
    return base
}

/**
 * فهرست آدرس‌های کاندید برای یک ساختمان (به ترتیب اولویت).
 * @param {object} building
 * @param {string} village 'home' | 'builder'
 * @returns {string[]}
 */
export function spriteCandidates(building, village = 'home') {
    if (!building || !building.type) return []

    const type = String(building.type)
    const level = Number(building.level) || null
    const vil = village === 'builder' ? 'builder' : 'home'
    const list = []

    // آرت مخصوص سطح (مثلاً town_hall_9.png) بر آدرس عمومی بک‌اند مقدم است؛
    // بک‌اند همیشه فایل سطحِ نوع (TH17) را در `sprite` می‌گذارد.
    if (level && LEVELED_TYPES.has(type)) {
        list.push(`${BUILDINGS_BASE}/${vil}/${type}_${level}.png`)
    }

    if (typeof building.sprite === 'string' && building.sprite.trim()) {
        list.push(building.sprite.trim())
    }

    list.push(`${BUILDINGS_BASE}/${vil}/${type}.png`)

    if (level && LEVELED_TYPES.has(type)) {
        list.push(`${UNITS_BASE}/${unitFileName(type, level)}.png`)
    }
    list.push(`${UNITS_BASE}/${unitFileName(type)}.png`)

    return Array.from(new Set(list))
}

/**
 * متادیتای اسپرایت (مقیاس/لنگر) با اعمال override از building.sprite_meta.
 */
export function spriteMeta(building) {
    const meta = { ...DEFAULT_SPRITE_META }
    const override = building && typeof building.sprite_meta === 'object' ? building.sprite_meta : null
    if (override) {
        if (Number.isFinite(Number(override.scale)) && Number(override.scale) > 0) meta.scale = Number(override.scale)
        if (Number.isFinite(Number(override.anchorY))) meta.anchorY = Number(override.anchorY)
        if (Number.isFinite(Number(override.anchor_y))) meta.anchorY = Number(override.anchor_y)
        if (Number.isFinite(Number(override.dy))) meta.dy = Number(override.dy)
    }
    return meta
}

/**
 * کش سراسری بارگذاری تصاویر: url → Promise<{w,h}|null>
 * (بین کامپوننت‌ها مشترک است تا ۴۰۴های تکراری نداشته باشیم)
 */
const cache = new Map()

/**
 * بارگذاری یک تصویر؛ در صورت خطا null برمی‌گرداند (هرگز reject نمی‌کند).
 * @param {string} src
 * @returns {Promise<{w:number,h:number}|null>}
 */
export function loadSprite(src) {
    if (!src) return Promise.resolve(null)
    if (cache.has(src)) return cache.get(src)
    if (typeof Image === 'undefined') return Promise.resolve(null)

    const p = new Promise(resolve => {
        const img = new Image()
        img.decoding = 'async'
        img.onload = () => resolve({ w: img.naturalWidth || img.width, h: img.naturalHeight || img.height })
        img.onerror = () => resolve(null)
        img.src = src
    })
    cache.set(src, p)
    return p
}

/**
 * اولین کاندید قابل بارگذاری را برمی‌گرداند.
 * @param {string[]} candidates
 * @returns {Promise<{src:string,w:number,h:number}|null>}
 */
export async function resolveFirst(candidates) {
    for (const src of candidates) {
        const dims = await loadSprite(src)
        if (dims && dims.w > 0 && dims.h > 0) {
            return { src, ...dims }
        }
    }
    return null
}
