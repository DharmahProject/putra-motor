<?php

require_once __DIR__ . '/includes/auth.php';

if (!function_exists('imagecreatetruecolor')) {
    http_response_code(500);
    exit('Captcha tidak tersedia');
}

$code = auth_captcha_create();
$width = 168;
$height = 52;
$image = imagecreatetruecolor($width, $height);
$background = imagecolorallocate($image, 247, 239, 225);
$ink = imagecolorallocate($image, 61, 42, 18);
$noise = imagecolorallocate($image, 168, 135, 74);
imagefilledrectangle($image, 0, 0, $width, $height, $background);

for ($line = 0; $line < 7; $line++) {
    imageline(
        $image,
        random_int(0, $width),
        random_int(0, $height),
        random_int(0, $width),
        random_int(0, $height),
        $noise
    );
}

foreach (str_split($code) as $index => $character) {
    $glyph = imagecreatetruecolor(30, 34);
    imagealphablending($glyph, false);
    imagesavealpha($glyph, true);
    $clear = imagecolorallocatealpha($glyph, 0, 0, 0, 127);
    imagefilledrectangle($glyph, 0, 0, 29, 33, $clear);
    imagestring($glyph, 5, 7, 9, $character, $ink);
    $rotated = imagerotate($glyph, random_int(-16, 16), $clear);
    imagealphablending($image, true);
    imagecopy($image, $rotated, 8 + ($index * 32), random_int(4, 12), 0, 0, imagesx($rotated), imagesy($rotated));
    imagedestroy($glyph);
    imagedestroy($rotated);
}

for ($dot = 0; $dot < 80; $dot++) {
    imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), $noise);
}

header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
imagepng($image);
imagedestroy($image);
