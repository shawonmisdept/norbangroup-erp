<?php

/**
 * Generate PWA icons for employee portal (run once: php scripts/generate-pwa-icons.php)
 */
$dir = __DIR__ . '/../public/pwa';

if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}

foreach ([192, 512] as $size) {
    $image = imagecreatetruecolor($size, $size);
    $navy = imagecolorallocate($image, 30, 58, 95);
    $gold = imagecolorallocate($image, 201, 168, 76);
    $white = imagecolorallocate($image, 255, 255, 255);

    imagefilledrectangle($image, 0, 0, $size, $size, $navy);
    imagefilledellipse($image, (int) ($size / 2), (int) ($size / 2), (int) ($size * 0.55), (int) ($size * 0.55), $gold);

    $font = 5;
    $text = 'E';
    $x = (int) (($size - imagefontwidth($font) * strlen($text)) / 2);
    $y = (int) (($size - imagefontheight($font)) / 2);
    imagestring($image, $font, max(0, $x), max(0, $y), $text, $white);

    imagepng($image, "{$dir}/icon-{$size}.png");
    imagedestroy($image);

    echo "Created icon-{$size}.png\n";
}
