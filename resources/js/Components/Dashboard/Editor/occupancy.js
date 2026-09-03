/**
 * توابع خالص اشغال شبکه برای ویرایشگر چیدمان (آینهٔ قوانین LayoutGridMapper/LayoutEditValidator).
 *
 * شبکهٔ N×N در یک Int32Array نگه داشته می‌شود:
 *   0  = خالی
 *  -1  = دیوار
 *  >0  = شناسهٔ ساختمانی که آن خانه را اشغال کرده
 */

export const FREE = 0
export const WALL = -1

export function cellKey(x, y) {
    return `${x},${y}`
}

/**
 * آیا ردپای s×s از (x,y) داخل شبکهٔ N×N است؟
 */
export function inBounds(n, x, y, size = 1) {
    return Number.isInteger(x) && Number.isInteger(y) && x >= 0 && y >= 0 && x + size <= n && y + size <= n
}

/**
 * ساخت نقشهٔ اشغال از روی ساختمان‌های جای‌گذاری‌شده و دیوارها.
 * @param {number} n
 * @param {Array} buildings
 * @param {Array<Array<number>>} walls
 * @param {(b:Object)=>number} sizeOf
 * @returns {Int32Array}
 */
export function buildOccupancy(n, buildings, walls, sizeOf) {
    const occ = new Int32Array(n * n)

    for (const b of buildings) {
        if (b.placed === false) continue
        const size = sizeOf(b)
        const bx = Number(b.x)
        const by = Number(b.y)
        for (let y = by; y < by + size; y++) {
            if (y < 0 || y >= n) continue
            for (let x = bx; x < bx + size; x++) {
                if (x < 0 || x >= n) continue
                occ[y * n + x] = Number(b.id)
            }
        }
    }

    for (const w of walls) {
        const x = Number(w[0])
        const y = Number(w[1])
        if (!inBounds(n, x, y, 1)) continue
        const idx = y * n + x
        if (occ[idx] === FREE) occ[idx] = WALL
    }

    return occ
}

/**
 * برخورد ردپای s×s از (x,y) با چه چیزی است؟
 * @returns {null|{kind:'bounds'|'wall'|'building', id?:number}}
 */
export function footprintCollision(occ, n, x, y, size, excludeId = null) {
    if (!inBounds(n, x, y, size)) return { kind: 'bounds' }
    for (let yy = y; yy < y + size; yy++) {
        for (let xx = x; xx < x + size; xx++) {
            const v = occ[yy * n + xx]
            if (v === FREE) continue
            if (v === WALL) return { kind: 'wall' }
            if (excludeId !== null && v === Number(excludeId)) continue
            return { kind: 'building', id: v }
        }
    }
    return null
}

export function isFree(occ, n, x, y, size, excludeId = null) {
    return footprintCollision(occ, n, x, y, size, excludeId) === null
}

/**
 * نزدیک‌ترین جای خالی برای ردپای s×s حول (x,y) با جست‌وجوی مارپیچی (فاصلهٔ چبیشف).
 * @returns {{x:number,y:number}|null}
 */
export function findFreeSpot(occ, n, x, y, size, excludeId = null, radius = 6) {
    const cx = Math.min(Math.max(0, x), Math.max(0, n - size))
    const cy = Math.min(Math.max(0, y), Math.max(0, n - size))
    for (let r = 0; r <= radius; r++) {
        for (let dy = -r; dy <= r; dy++) {
            for (let dx = -r; dx <= r; dx++) {
                if (Math.max(Math.abs(dx), Math.abs(dy)) !== r) continue
                const nx = cx + dx
                const ny = cy + dy
                if (isFree(occ, n, nx, ny, size, excludeId)) return { x: nx, y: ny }
            }
        }
    }
    return null
}

/**
 * خانه‌های روی پاره‌خط (برزنهام) از (x0,y0) تا (x1,y1) شامل هر دو سر.
 * برای پرکردن خلأ بین دو رویداد حرکت هنگام نقاشی دیوار.
 */
export function lineCells(x0, y0, x1, y1) {
    const cells = []
    let dx = Math.abs(x1 - x0)
    let dy = -Math.abs(y1 - y0)
    const sx = x0 < x1 ? 1 : -1
    const sy = y0 < y1 ? 1 : -1
    let err = dx + dy
    let x = x0
    let y = y0
    for (let guard = 0; guard < 4096; guard++) {
        cells.push([x, y])
        if (x === x1 && y === y1) break
        const e2 = 2 * err
        if (e2 >= dy) { err += dy; x += sx }
        if (e2 <= dx) { err += dx; y += sy }
    }
    return cells
}
