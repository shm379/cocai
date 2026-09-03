// فهرست مشترک بخش‌های داشبورد — استفاده‌شده در BottomNav، MoreSheet و SideDrawer
// (بدون کلاس Tailwind؛ فقط شناسه، برچسب و آیکون)

// ۴ بخش اصلی نوار پایین (به‌همراه دکمهٔ «بیشتر» می‌شود ۵ آیتم ثابت)
export const PRIMARY_SECTIONS = [
    { id: 'profile',  label: 'پیشرفت',     shortLabel: 'پیشرفت',  icon: '🏰' },
    { id: 'strategy', label: 'وار و حمله',  shortLabel: 'وار',     icon: '⚔️' },
    { id: 'cloner',   label: 'کلونر AI',    shortLabel: 'کلونر',   icon: '🧬', primary: true },
    { id: 'th_maps',  label: 'نقشه‌ها',     shortLabel: 'نقشه‌ها', icon: '🗺️' },
]

// بخش‌های باقی‌مانده که در شیت «بیشتر» و دراور کناری نمایش داده می‌شوند
export const SECONDARY_SECTIONS = [
    { id: 'heroes',       label: 'هیرو و پت',    icon: '👑' },
    { id: 'clanOverview', label: 'کلن و رید',    icon: '🛡️' },
    { id: 'troops',       label: 'لَب و نیروها', icon: '🧪' },
    { id: 'favorites',    label: 'نشان‌شده‌ها',  icon: '❤️' },
    { id: 'builderBase',  label: 'بیلدر بیس',    icon: '🔨' },
    { id: 'assistant',    label: 'مربی AI',      icon: '🤖' },
]

export const ALL_SECTIONS = [...PRIMARY_SECTIONS, ...SECONDARY_SECTIONS]

// لینک‌های خارج از تب‌ها
export const STRATEGY_LAB_LINK = { id: 'strategy-lab', label: 'آزمایشگاه بیس', icon: '🧪', href: '/dashboard/strategy-lab' }
export const PROFILE_LINK      = { id: 'profile-page', label: 'پروفایل',       icon: '👤', href: '/profile' }
