<?php

namespace App\Services\BaseClone\Games;

use App\Services\BaseClone\BuildingCatalog;

/**
 * قرارداد بازی‌هایی که خروجی «چیدمان روی شبکه» دارند (کلش آف کلنز: دهکدهٔ اصلی/بیلدر بیس).
 *
 * ویرایشگر چیدمان به کاتالوگ ساختمان‌ها (ابعاد، برچسب، رنگ) و ابعاد شبکه نیاز دارد؛
 * بازی‌های دک‌محور (کلش رویال) این قرارداد را پیاده نمی‌کنند و ویرایش چیدمان ندارند.
 */
interface LayoutGameAdapter extends GameAdapter
{
    /** کاتالوگ مرجع ساختمان‌های این دهکده (ابعاد/برچسب/رنگ/آیکون). */
    public function catalog(): BuildingCatalog;

    /** ابعاد شبکهٔ قابل ساخت (مثلاً ۴۴). */
    public function gridSize(): int;
}
