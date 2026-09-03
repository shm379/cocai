/**
 * تاریخچهٔ برگشت/جلو (undo/redo) با اسنپ‌شات JSON — سقف پیش‌فرض ۵۰ مورد.
 *
 * هر اسنپ‌شات رشتهٔ JSON از {buildings, walls} است (۱۰ تا ۲۰ کیلوبایت)؛
 * ۵۰ اسنپ‌شات در حافظه مشکلی ایجاد نمی‌کند و پیاده‌سازی را ساده و بی‌خطا نگه می‌دارد.
 */
export default class History {
    constructor(cap = 50) {
        this.cap = Math.max(1, cap)
        this.undoStack = []
        this.redoStack = []
    }

    get canUndo() {
        return this.undoStack.length > 0
    }

    get canRedo() {
        return this.redoStack.length > 0
    }

    /** ثبت وضعیت قبل از یک تغییر */
    push(snapshot) {
        this.undoStack.push(snapshot)
        if (this.undoStack.length > this.cap) this.undoStack.shift()
        this.redoStack = []
    }

    /** @returns {string|null} اسنپ‌شاتی که باید بازگردانده شود */
    undo(current) {
        if (!this.canUndo) return null
        this.redoStack.push(current)
        return this.undoStack.pop()
    }

    /** @returns {string|null} */
    redo(current) {
        if (!this.canRedo) return null
        this.undoStack.push(current)
        return this.redoStack.pop()
    }

    clear() {
        this.undoStack = []
        this.redoStack = []
    }
}

/** اسنپ‌شات پیش‌نویس (فقط بخش‌های قابل ویرایش) */
export function snapshot(draft) {
    return JSON.stringify({
        buildings: draft.buildings,
        walls: draft.walls,
    })
}

/** بازگردانی اسنپ‌شات به شیء تازه */
export function restore(snap) {
    const parsed = JSON.parse(snap)
    return {
        buildings: Array.isArray(parsed.buildings) ? parsed.buildings : [],
        walls: Array.isArray(parsed.walls) ? parsed.walls : [],
    }
}
