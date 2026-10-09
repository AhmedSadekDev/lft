<?php
$img = imagecreatefrompng(__DIR__ . '/../public/assets/media/logo.png');
$w = imagesx($img);
$h = imagesy($img);
$minX = $w; $minY = $h; $maxX = 0; $maxY = 0;
for ($x=0; $x<$w; $x++) {
    for ($y=0; $y<$h; $y++) {
        $alpha = (imagecolorat($img, $x, $y) >> 24) & 0x7F;
        if ($alpha < 120) {
            if ($x < $minX) $minX = $x;
            if ($x > $maxX) $maxX = $x;
            if ($y < $minY) $minY = $y;
            if ($y > $maxY) $maxY = $y;
        }
    }
}
echo "Total size: {$w}x{$h}\n";
echo "Content bounds: minX=$minX, minY=$minY, maxX=$maxX, maxY=$maxY\n";
echo "Content size: " . ($maxX - $minX + 1) . "x" . ($maxY - $minY + 1) . "\n";
echo "Padding: left=$minX, top=$minY, right=" . ($w - 1 - $maxX) . ", bottom=" . ($h - 1 - $maxY) . "\n";
