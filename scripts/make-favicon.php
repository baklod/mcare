<?php

$srcPath = dirname(__DIR__).'/public/assets/images/logoicon.png';
$src = imagecreatefrompng($srcPath);

if ($src === false) {
    fwrite(STDERR, "Could not read {$srcPath}\n");
    exit(1);
}

$sw = imagesx($src);
$sh = imagesy($src);

$minX = $sw;
$minY = $sh;
$maxX = 0;
$maxY = 0;

for ($y = 0; $y < $sh; $y++) {
    for ($x = 0; $x < $sw; $x++) {
        $rgb = imagecolorat($src, $x, $y);
        $a = ($rgb >> 24) & 0x7F;
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $isBg = ($a > 100) || ($r < 18 && $g < 18 && $b < 18);
        if (! $isBg) {
            $minX = min($minX, $x);
            $minY = min($minY, $y);
            $maxX = max($maxX, $x);
            $maxY = max($maxY, $y);
        }
    }
}

$contentW = $maxX - $minX + 1;
$contentH = $maxY - $minY + 1;
$cx = (int) round(($minX + $maxX) / 2);
$cy = (int) round(($minY + $maxY) / 2);
$side = min($sw, $sh, max($contentW, $contentH));
$sx = max(0, min($sw - $side, $cx - intdiv($side, 2)));
$sy = max(0, min($sh - $side, $cy - intdiv($side, 2)));

$makeSquarePng = function (int $size, string $dest) use ($src, $sx, $sy, $side): void {
    $canvas = imagecreatetruecolor($size, $size);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $bg = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefilledrectangle($canvas, 0, 0, $size, $size, $bg);
    imagealphablending($canvas, true);
    imagecopyresampled($canvas, $src, 0, 0, $sx, $sy, $size, $size, $side, $side);
    imagepng($canvas, $dest, 6);
    imagedestroy($canvas);
};

$root = dirname(__DIR__).'/public';
$makeSquarePng(16, $root.'/favicon-16.png');
$makeSquarePng(32, $root.'/favicon-32.png');
$makeSquarePng(48, $root.'/favicon.png');
$makeSquarePng(180, $root.'/assets/images/favicon-180.png');

$png = file_get_contents($root.'/favicon-32.png');
$info = getimagesize($root.'/favicon-32.png');
$w = $info[0] >= 256 ? 0 : $info[0];
$h = $info[1] >= 256 ? 0 : $info[1];
$ico = pack('vvv', 0, 1, 1)
    .pack('CCCCvvVV', $w, $h, 0, 0, 1, 32, strlen($png), 22)
    .$png;
file_put_contents($root.'/favicon.ico', $ico);

imagedestroy($src);

echo "Wrote cropped square favicons from {$sw}x{$sh} source (crop {$side}x{$side} at {$sx},{$sy}).\n";
