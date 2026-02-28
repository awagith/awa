<?php
// Simple WEBP placeholders generator using GD; run from project root.
// Creates hero.webp, side1.webp, side2.webp under pub/media/import/home/

declare(strict_types=1);

function ensureDir(string $dir): void {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function createWebp(int $width, int $height, string $bgHex, string $text, string $targetPath): bool {
    if (!function_exists('imagecreatetruecolor') || !function_exists('imagewebp')) {
        fwrite(STDERR, "GD com suporte a WEBP não disponível.\n");
        return false;
    }
    $im = imagecreatetruecolor($width, $height);
    [$r, $g, $b] = sscanf($bgHex, '#%02x%02x%02x');
    $bg = imagecolorallocate($im, $r, $g, $b);
    imagefilledrectangle($im, 0, 0, $width, $height, $bg);

    $white = imagecolorallocate($im, 255, 255, 255);
    $fontSize = 5; // built-in bitmap font
    $textWidth = imagefontwidth($fontSize) * strlen($text);
    $textHeight = imagefontheight($fontSize);
    $x = (int)(($width - $textWidth) / 2);
    $y = (int)(($height - $textHeight) / 2);
    imagestring($im, $fontSize, $x, $y, $text, $white);

    $ok = imagewebp($im, $targetPath, 80);
    imagedestroy($im);
    return $ok;
}

$base = __DIR__ . '/../pub/media/import/home';
ensureDir($base);

$files = [
    ['w' => 1600, 'h' => 520, 'bg' => '#ff6f00', 'text' => 'Banner Principal', 'name' => 'hero.webp'],
    ['w' => 800, 'h' => 520, 'bg' => '#1f1f1f', 'text' => 'Banner Lateral 1', 'name' => 'side1.webp'],
    ['w' => 800, 'h' => 520, 'bg' => '#333333', 'text' => 'Banner Lateral 2', 'name' => 'side2.webp'],
];

$created = 0;
foreach ($files as $f) {
    $target = $base . '/' . $f['name'];
    if (file_exists($target)) {
        echo "[SKIP] {$f['name']} já existe\n";
        continue;
    }
    if (createWebp($f['w'], $f['h'], $f['bg'], $f['text'], $target)) {
        echo "[OK] {$f['name']} criado em {$target}\n";
        $created++;
    } else {
        echo "[ERR] Falha ao criar {$f['name']}\n";
    }
}

echo "Total criados: {$created}\n";
