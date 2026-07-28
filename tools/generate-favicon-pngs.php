<?php

declare(strict_types=1);

/**
 * Regenera PNG de favicon / PWA desde public/img/favicon-source.jpg
 * Uso: php tools/generate-favicon-pngs.php
 */

$root = dirname(__DIR__);
$srcPath = $root . '/public/img/favicon-source.jpg';
$publicImg = $root . '/public/img';

if (! is_file($srcPath)) {
    fwrite(STDERR, "Missing source: {$srcPath}\n");
    exit(1);
}

$src = imagecreatefromjpeg($srcPath);
if ($src === false) {
    fwrite(STDERR, "Cannot read JPEG\n");
    exit(1);
}

if (! is_dir($publicImg)) {
    mkdir($publicImg, 0775, true);
}

$sizes = [
    32 => 'favicon-32.png',
    180 => 'apple-touch-icon.png',
    192 => 'icon-192.png',
    512 => 'icon-512.png',
];

foreach ($sizes as $size => $name) {
    $dst = imagecreatetruecolor($size, $size);
    imagealphablending($dst, true);
    imagesavealpha($dst, true);
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefilledrectangle($dst, 0, 0, $size, $size, $white);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $size, $size, imagesx($src), imagesy($src));
    imagepng($dst, $publicImg . '/' . $name, 6);
    imagedestroy($dst);
    echo $name . " OK\n";
}

imagedestroy($src);
echo "done\n";
