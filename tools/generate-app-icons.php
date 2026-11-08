<?php

declare(strict_types=1);

/**
 * Generates the PWA icons in public/app-icons/ (deliberately not "icons",
 * see CLAUDE.md): sky-300 drop on navy-900, like the logo in the sidebar.
 *
 * Usage: php tools/generate-app-icons.php
 */
$target = dirname(__DIR__).'/public/app-icons';

if (! is_dir($target)) {
    mkdir($target, 0755, true);
}

$render = function (int $size, float $dropScale, string $file) use ($target): void {
    $ss = 4; // supersampling for smooth edges
    $canvas = $size * $ss;
    $image = imagecreatetruecolor($canvas, $canvas);

    $navy = imagecolorallocate($image, 0x1B, 0x24, 0x47);
    $sky = imagecolorallocate($image, 0x84, 0xCE, 0xEB);
    $shine = imagecolorallocate($image, 0xD6, 0xEE, 0xF9);

    imagefill($image, 0, 0, $navy);

    // Drop from the app icon (24×24 viewBox): tip (12, 2.7), round body centre (12, 14.5), radius 7.
    $unit = $canvas * $dropScale / 24;
    $cx = $canvas / 2;
    $offsetY = $canvas / 2 - 11.1 * $unit; // centre the drop's bounding box (2.7 … 21.5) vertically
    $tipY = $offsetY + 2.7 * $unit;
    $bodyY = $offsetY + 14.5 * $unit;
    $radius = 7 * $unit;

    // Tangent points from the tip to the round body.
    $distance = $bodyY - $tipY;
    $cos = $radius / $distance;
    $sin = sqrt(1 - $cos * $cos);

    imagefilledellipse($image, (int) $cx, (int) $bodyY, (int) ($radius * 2), (int) ($radius * 2), $sky);
    imagefilledpolygon($image, [
        (int) $cx, (int) $tipY,
        (int) ($cx + $radius * $sin), (int) ($bodyY - $radius * $cos),
        (int) ($cx - $radius * $sin), (int) ($bodyY - $radius * $cos),
    ], $sky);

    // Small highlight.
    imagefilledellipse($image, (int) ($cx - $radius * 0.42), (int) ($bodyY + $radius * 0.05), (int) ($radius * 0.32), (int) ($radius * 0.62), $shine);

    $final = imagecreatetruecolor($size, $size);
    imagecopyresampled($final, $image, 0, 0, 0, 0, $size, $size, $canvas, $canvas);
    imagepng($final, "{$target}/{$file}", 9);
};

// "any" icons fill more of the square; maskable icons keep the drop inside the 80 % safe zone.
$render(192, 0.78, 'icon-192.png');
$render(512, 0.78, 'icon-512.png');
$render(512, 0.58, 'icon-maskable-512.png');
$render(180, 0.70, 'apple-touch-icon.png');
$render(32, 0.90, 'favicon-32.png');

echo "Icons erzeugt in {$target}\n";
