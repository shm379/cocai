<?php

use App\Services\BaseClone\ImageHasher;

function gradientImage(int $seed): string
{
    $img = imagecreatetruecolor(64, 64);
    for ($y = 0; $y < 64; $y++) {
        for ($x = 0; $x < 64; $x++) {
            $v = ($x * 4 + $seed * 37 * ($y % 5)) % 256;
            imagesetpixel($img, $x, $y, imagecolorallocate($img, $v, $v, $v));
        }
    }
    ob_start();
    imagepng($img);
    imagedestroy($img);

    return (string) ob_get_clean();
}

it('produces a 16-character hex hash', function () {
    $hash = (new ImageHasher)->hashBinary(gradientImage(1));

    expect($hash)->toMatch('/^[0-9a-f]{16}$/');
});

it('gives zero distance for identical images and a positive distance for different ones', function () {
    $hasher = new ImageHasher;

    $a = $hasher->hashBinary(gradientImage(1));
    $b = $hasher->hashBinary(gradientImage(1));
    $c = $hasher->hashBinary(gradientImage(9));

    expect(ImageHasher::distance($a, $b))->toBe(0)
        ->and(ImageHasher::distance($a, $c))->toBeGreaterThan(0)
        ->and(ImageHasher::similarity(0))->toBe(100)
        ->and(ImageHasher::similarity(64))->toBe(0);
});

it('returns null for non-image data', function () {
    expect((new ImageHasher)->hashBinary('not an image'))->toBeNull();
});
