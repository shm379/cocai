/**
 * خروجی PNG از SVG رندرر ایزومتریک.
 *
 * مراحل: کلون SVG → حذف لایه‌های فقط-ویرایش → تبدیل همهٔ <image>ها به data: URI
 * → سریال‌سازی → رسم روی canvas → Blob.
 */

const MAX_SIDE = 4096

/**
 * تبدیل آدرس تصویر به data URI (هم‌مبدأ؛ در خطا همان آدرس برمی‌گردد).
 */
const dataUriCache = new Map()

async function toDataUri(href) {
    if (!href || href.startsWith('data:')) return href
    if (dataUriCache.has(href)) return dataUriCache.get(href)

    const p = (async () => {
        try {
            const res = await fetch(href, { credentials: 'same-origin' })
            if (!res.ok) return null
            const blob = await res.blob()
            return await new Promise((resolve, reject) => {
                const fr = new FileReader()
                fr.onload = () => resolve(fr.result)
                fr.onerror = reject
                fr.readAsDataURL(blob)
            })
        } catch (e) {
            return null
        }
    })()
    dataUriCache.set(href, p)
    return p
}

/**
 * @param {SVGSVGElement} svgEl
 * @param {object} options
 * @param {number} options.width      عرض صحنه (واحد SVG)
 * @param {number} options.height     ارتفاع صحنه
 * @param {number} [options.scale=1]  ضریب رزولوشن
 * @param {string} [options.background='#0b1220']
 * @param {string} [options.watermark]
 * @param {boolean} [options.keepOverlay=true] نگه‌داشتن حلقه‌های «نامطمئن»
 * @returns {Promise<Blob>}
 */
export async function exportPng(svgEl, options = {}) {
    const {
        width,
        height,
        scale = 1,
        background = '#0b1220',
        watermark = '',
        keepOverlay = true,
    } = options

    if (!svgEl || !width || !height) {
        throw new Error('svg or dimensions missing')
    }

    const clone = svgEl.cloneNode(true)
    clone.setAttribute('xmlns', 'http://www.w3.org/2000/svg')
    clone.setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink')
    clone.setAttribute('viewBox', `0 0 ${width} ${height}`)
    clone.setAttribute('width', String(width))
    clone.setAttribute('height', String(height))
    clone.removeAttribute('style')
    clone.removeAttribute('class')

    const viewport = clone.querySelector('[data-iso-viewport]')
    if (viewport) viewport.setAttribute('transform', 'translate(0 0) scale(1)')

    clone.querySelectorAll('.iso-edit-only').forEach(el => el.remove())
    if (!keepOverlay) {
        clone.querySelectorAll('.iso-overlay-layer').forEach(el => el.remove())
    }

    // تبدیل تصاویر به data URI (برای Safari و برای جلوگیری از taint شدن canvas)
    const images = Array.from(clone.querySelectorAll('image'))
    await Promise.all(images.map(async img => {
        const href = img.getAttribute('href') || img.getAttributeNS('http://www.w3.org/1999/xlink', 'href')
        const uri = await toDataUri(href)
        if (uri) {
            img.setAttribute('href', uri)
            img.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', uri)
        } else {
            img.remove()
        }
    }))

    const xml = new XMLSerializer().serializeToString(clone)
    const svgUrl = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(xml)

    const img = new Image()
    img.decoding = 'async'
    await new Promise((resolve, reject) => {
        img.onload = resolve
        img.onerror = () => reject(new Error('svg rasterize failed'))
        img.src = svgUrl
    })

    let k = Math.max(0.1, Number(scale) || 1)
    const longSide = Math.max(width, height) * k
    if (longSide > MAX_SIDE) k = MAX_SIDE / Math.max(width, height)

    const canvas = document.createElement('canvas')
    canvas.width = Math.round(width * k)
    canvas.height = Math.round(height * k)
    const ctx = canvas.getContext('2d')
    ctx.fillStyle = background
    ctx.fillRect(0, 0, canvas.width, canvas.height)
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height)

    if (watermark) {
        const fontPx = Math.max(18, Math.round(28 * k))
        ctx.font = `bold ${fontPx}px system-ui, -apple-system, "Segoe UI", Roboto, sans-serif`
        ctx.fillStyle = 'rgba(255,255,255,0.55)'
        ctx.textAlign = 'right'
        ctx.textBaseline = 'bottom'
        ctx.direction = 'ltr'
        ctx.fillText(watermark, canvas.width - fontPx * 0.8, canvas.height - fontPx * 0.5)
    }

    const blob = await new Promise((resolve, reject) => {
        canvas.toBlob(b => (b ? resolve(b) : reject(new Error('toBlob failed'))), 'image/png')
    })
    return blob
}

/**
 * تحویل فایل به کاربر: روی موبایل از Web Share (اگر فایل پشتیبانی شود)، وگرنه دانلود.
 * @param {Blob} blob
 * @param {string} filename
 * @param {string} [title]
 */
export async function deliverBlob(blob, filename, title = '') {
    const file = typeof File !== 'undefined' ? new File([blob], filename, { type: 'image/png' }) : null
    const coarse = typeof matchMedia === 'function' && matchMedia('(pointer: coarse)').matches

    if (file && coarse && navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
        try {
            await navigator.share({ files: [file], title: title || filename })
            return 'shared'
        } catch (e) {
            if (e && e.name === 'AbortError') return 'aborted'
            // در خطاهای دیگر به دانلود برمی‌گردیم
        }
    }

    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    a.rel = 'noopener'
    document.body.appendChild(a)
    a.click()
    a.remove()
    setTimeout(() => URL.revokeObjectURL(url), 4000)
    return 'downloaded'
}
