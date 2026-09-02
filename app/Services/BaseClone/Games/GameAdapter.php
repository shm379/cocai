<?php

namespace App\Services\BaseClone\Games;

use Illuminate\Http\UploadedFile;

/**
 * قرارداد هر «موتور» بازی برای بازسازی از روی تصویر.
 *
 * هر بازی سوپرسل یک آداپتور دارد: کلش آف کلنز (دهکدهٔ اصلی/بیلدر بیس) چیدمان
 * ۴۴×۴۴ می‌سازد، کلش رویال دک ۸ کارتی را می‌خواند و لینک کپی واقعی می‌سازد.
 */
interface GameAdapter
{
    /** شناسهٔ یکتا (مثلاً coc_home). */
    public function key(): string;

    /** نام فارسی برای UI. */
    public function label(): string;

    /** متادیتای UI: آیکون، رنگ، توضیح کوتاه، نوع خروجی. */
    public function meta(): array;

    public function isConfigured(): bool;

    /**
     * تحلیل تصویر و تولید خروجی.
     *
     * @param  string|null  $hash  هش ادراکی تصویر (برای تطبیق با آرشیو)
     * @return array{ok: bool, layout?: array, copy_link?: ?string, th_level?: ?int, matched_map_id?: ?int, match_distance?: ?int, matches?: array, message?: string}
     */
    public function analyze(UploadedFile $image, ?string $hash): array;
}
