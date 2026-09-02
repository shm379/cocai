<?php

namespace App\Services\BaseClone;

/**
 * هش ادراکی تصویر (dHash ۶۴ بیتی) برای یافتن نقشه‌های تکراری/مشابه.
 *
 * تصویر به ۹×۸ خاکستری کوچک می‌شود و اختلاف روشنایی پیکسل‌های مجاور در هر سطر
 * یک بیت می‌سازد. فاصلهٔ همینگ دو هش معیار شباهت است (۰ = یکسان).
 */
class ImageHasher
{
    public const BITS = 64;

    /**
     * @return string|null  ۱۶ کاراکتر هگز یا null در صورت خطا
     */
    public function hashFile(string $path): ?string
    {
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $contents = @file_get_contents($path);
        if ($contents === false || $contents === '') {
            return null;
        }

        return $this->hashBinary($contents);
    }

    public function hashBinary(string $contents): ?string
    {
        $img = @imagecreatefromstring($contents);
        if ($img === false) {
            return null;
        }

        try {
            return $this->hashResource($img);
        } finally {
            imagedestroy($img);
        }
    }

    /**
     * @param  \GdImage  $img
     */
    public function hashResource($img): ?string
    {
        $w = 9;
        $h = 8;

        $small = imagecreatetruecolor($w, $h);
        if ($small === false) {
            return null;
        }

        imagecopyresampled($small, $img, 0, 0, 0, 0, $w, $h, imagesx($img), imagesy($img));

        $bits = '';
        for ($y = 0; $y < $h; $y++) {
            $prev = null;
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($small, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $lum = 0.299 * $r + 0.587 * $g + 0.114 * $b;

                if ($prev !== null) {
                    $bits .= $lum > $prev ? '1' : '0';
                }
                $prev = $lum;
            }
        }

        imagedestroy($small);

        $hex = '';
        for ($i = 0; $i < self::BITS; $i += 4) {
            $hex .= dechex(bindec(substr($bits, $i, 4)));
        }

        return $hex;
    }

    /**
     * فاصلهٔ همینگ دو هش هگز ۱۶ کاراکتری.
     */
    public static function distance(string $a, string $b): int
    {
        $a = str_pad(strtolower($a), 16, '0', STR_PAD_LEFT);
        $b = str_pad(strtolower($b), 16, '0', STR_PAD_LEFT);

        $distance = 0;
        for ($i = 0; $i < 16; $i++) {
            $x = hexdec($a[$i]) ^ hexdec($b[$i]);
            $distance += substr_count(decbin($x), '1');
        }

        return $distance;
    }

    /**
     * درصد شباهت بر اساس فاصلهٔ همینگ.
     */
    public static function similarity(int $distance): int
    {
        $distance = max(0, min(self::BITS, $distance));

        return (int) round((self::BITS - $distance) / self::BITS * 100);
    }
}
