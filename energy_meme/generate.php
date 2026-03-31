<?php
/**
 * Energy Meme Generator - PHP GD image renderer
 *
 * Generates and caches 1200×630 shareable PNG images for each fact.
 * Background resolution priority:
 *   1. images/facts/{id}.*   (fact-specific image)
 *   2. backgrounds/{hint}.*  (category/hint background)
 *   3. backgrounds/default.* (fallback)
 *   4. Built-in dark gradient (no file needed)
 *
 * Place a TTF font at assets/fonts/font.ttf for best quality.
 * Common system fonts (DejaVu, Liberation, Arial) are tried automatically.
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

// ── GD check ─────────────────────────────────────────────────────
if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'PHP GD extension is not available.';
    exit;
}

// ── Input validation ─────────────────────────────────────────────
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Invalid or missing fact ID.';
    exit;
}

// ── Cache ─────────────────────────────────────────────────────────
$cache_dir  = __DIR__ . '/cache/images';
$cache_file = $cache_dir . '/fact_' . $id . '.png';

if (!is_dir($cache_dir)) {
    @mkdir($cache_dir, 0755, true);
}

if (file_exists($cache_file)) {
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=86400');
    readfile($cache_file);
    exit;
}

// ── Load fact ────────────────────────────────────────────────────
$facts_file = __DIR__ . '/data/facts.json';
if (!file_exists($facts_file)) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Facts data not found.';
    exit;
}

$facts = json_decode(file_get_contents($facts_file), true) ?? [];
$fact  = null;
foreach ($facts as $f) {
    if ((int)$f['id'] === $id) { $fact = $f; break; }
}

if ($fact === null) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'Fact not found.';
    exit;
}

// ── Canvas ───────────────────────────────────────────────────────
$width   = 1200;
$height  = 630;
$padding = 64;

$im = imagecreatetruecolor($width, $height);

// ── Background ───────────────────────────────────────────────────
$hint = $fact['background'] ?? null;

function find_bg_image($id, $hint) {
    $exts = ['jpg', 'jpeg', 'png', 'webp'];
    // 1. Fact-specific
    foreach ($exts as $e) {
        $p = __DIR__ . '/images/facts/' . $id . '.' . $e;
        if (file_exists($p)) return $p;
    }
    // 2. Named background
    if ($hint) {
        foreach ($exts as $e) {
            $p = __DIR__ . '/backgrounds/' . $hint . '.' . $e;
            if (file_exists($p)) return $p;
        }
    }
    // 3. Default background
    foreach ($exts as $e) {
        $p = __DIR__ . '/backgrounds/default.' . $e;
        if (file_exists($p)) return $p;
    }
    return null;
}

function load_image_resource($path) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg'], true)) return @imagecreatefromjpeg($path);
    if ($ext === 'png')  return @imagecreatefrompng($path);
    if ($ext === 'webp' && function_exists('imagecreatefromwebp')) return @imagecreatefromwebp($path);
    return null;
}

$bg_path    = find_bg_image($id, $hint);
$drew_image = false;

if ($bg_path) {
    $bg_src = load_image_resource($bg_path);
    if ($bg_src) {
        $src_w = imagesx($bg_src);
        $src_h = imagesy($bg_src);
        // Cover-fit: scale so the image fills the canvas
        $scale = max($width / $src_w, $height / $src_h);
        $new_w = (int)($src_w * $scale);
        $new_h = (int)($src_h * $scale);
        $off_x = (int)(($width  - $new_w) / 2);
        $off_y = (int)(($height - $new_h) / 2);
        imagecopyresampled($im, $bg_src, $off_x, $off_y, 0, 0, $new_w, $new_h, $src_w, $src_h);
        imagedestroy($bg_src);
        $drew_image = true;
    }
}

if (!$drew_image) {
    // Dark navy gradient fallback
    for ($y = 0; $y < $height; $y++) {
        $t = $y / $height;
        $r = (int)(18 + $t * 12);
        $g = (int)(28 + $t * 16);
        $b = (int)(55 + $t * 25);
        imagefilledrectangle($im, 0, $y, $width - 1, $y,
            imagecolorallocate($im, $r, $g, $b));
    }
}

// ── Dark overlay ─────────────────────────────────────────────────
// Alpha: 0=opaque, 127=transparent. 55% opaque ≈ alpha 57.
imagealphablending($im, true);
$overlay = imagecolorallocatealpha($im, 0, 0, 0, $drew_image ? 57 : 90);
imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, $overlay);

// ── Colors ───────────────────────────────────────────────────────
$col_white  = imagecolorallocate($im, 255, 255, 255);
$col_orange = imagecolorallocate($im, 247, 148, 29);
$col_grey   = imagecolorallocate($im, 185, 185, 185);

// ── Font ─────────────────────────────────────────────────────────
$font_candidates = [
    __DIR__ . '/assets/fonts/font.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
    '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
    '/usr/share/fonts/dejavu/DejaVuSans.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
    '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
    '/usr/share/fonts/truetype/freefont/FreeSans.ttf',
    '/usr/share/fonts/TTF/DejaVuSans-Bold.ttf',
    '/usr/share/fonts/TTF/DejaVuSans.ttf',
    '/Library/Fonts/Arial Bold.ttf',
    '/Library/Fonts/Arial.ttf',
    '/System/Library/Fonts/Supplemental/Arial.ttf',
    '/usr/share/fonts/truetype/ubuntu/Ubuntu-B.ttf',
    '/usr/share/fonts/truetype/ubuntu/Ubuntu-R.ttf',
];

$font = null;
foreach ($font_candidates as $fc) {
    if (file_exists($fc)) { $font = $fc; break; }
}

// ── Text rendering ───────────────────────────────────────────────
if ($font && function_exists('imagettftext')) {

    /**
     * Word-wrap $text to fit within $max_w pixels at the given font size.
     * Returns array of lines.
     */
    function ttf_wrap($font, $size, $max_w, $text) {
        $words = preg_split('/\s+/', trim($text));
        $lines = [];
        $cur   = '';
        foreach ($words as $word) {
            $test = $cur === '' ? $word : "$cur $word";
            $bbox = imagettfbbox($size, 0, $font, $test);
            if ($bbox && abs($bbox[4] - $bbox[0]) > $max_w && $cur !== '') {
                $lines[] = $cur;
                $cur = $word;
            } else {
                $cur = $test;
            }
        }
        if ($cur !== '') $lines[] = $cur;
        return $lines;
    }

    $text_w = $width - ($padding * 2);

    // --- Category label (top-left) ---
    $cat_size = 17;
    $cat_y    = $padding + $cat_size;
    imagettftext($im, $cat_size, 0, $padding, $cat_y,
        $col_orange, $font, mb_strtoupper($fact['category']));

    // --- Fact text (vertically centered) ---
    // Dynamically reduce font size if the text is very long
    $fact_size = 38;
    $max_lines = 5;
    do {
        $fact_lines = ttf_wrap($font, $fact_size, $text_w, $fact['fact']);
        if (count($fact_lines) <= $max_lines || $fact_size <= 20) break;
        $fact_size -= 4;
    } while (true);

    $line_h  = (int)($fact_size * 1.45);
    $block_h = count($fact_lines) * $line_h;

    // Center within the space between category text and source area
    $area_top    = $cat_y + 28;
    $area_bottom = $height - $padding - 36;
    $start_y     = max(
        $area_top + $fact_size,
        (int)(($area_top + $area_bottom - $block_h) / 2) + $fact_size
    );

    foreach ($fact_lines as $i => $line) {
        imagettftext($im, $fact_size, 0, $padding,
            $start_y + $i * $line_h, $col_white, $font, $line);
    }

    // --- Source credit (bottom-left) ---
    $src_size  = 13;
    $src_text  = 'Source: ' . $fact['source'];
    if (!empty($fact['source_url'])) {
        $url = $fact['source_url'];
        if (mb_strlen($url) > 55) $url = mb_substr($url, 0, 52) . '...';
        $src_text .= '  ·  ' . $url;
    }
    // Wrap source to avoid overflowing into branding
    $src_max_w  = $text_w - 140;
    $src_lines  = ttf_wrap($font, $src_size, $src_max_w, $src_text);
    $src_line_h = $src_size + 5;
    $src_start  = $height - $padding - (count($src_lines) - 1) * $src_line_h;
    foreach ($src_lines as $si => $sl) {
        imagettftext($im, $src_size, 0, $padding,
            $src_start + $si * $src_line_h, $col_grey, $font, $sl);
    }

    // --- Branding (bottom-right) ---
    $brand    = 'energytools';
    $brand_sz = 13;
    $bbox     = imagettfbbox($brand_sz, 0, $font, $brand);
    $bw       = abs($bbox[4] - $bbox[0]);
    imagettftext($im, $brand_sz, 0, $width - $padding - $bw,
        $height - $padding, $col_grey, $font, $brand);

} else {
    // ── Built-in bitmap font fallback ────────────────────────────
    // Font 5: ~9px wide chars, 15px tall
    $char_w   = 9;
    $line_h   = 18;
    $max_chars = (int)(($width - $padding * 2) / $char_w);

    imagestring($im, 3, $padding, $padding,
        strtoupper($fact['category']), $col_orange);

    $words = preg_split('/\s+/', $fact['fact']);
    $lines = [];
    $cur   = '';
    foreach ($words as $word) {
        $test = $cur === '' ? $word : "$cur $word";
        if (strlen($test) > $max_chars && $cur !== '') {
            $lines[] = $cur;
            $cur = $word;
        } else {
            $cur = $test;
        }
    }
    if ($cur !== '') $lines[] = $cur;

    $block_h = count($lines) * $line_h;
    $start_y = (int)(($height - $block_h) / 2);
    foreach ($lines as $i => $line) {
        imagestring($im, 4, $padding, $start_y + $i * $line_h,
            $line, $col_white);
    }

    imagestring($im, 2, $padding, $height - $padding - $line_h,
        'Source: ' . $fact['source'], $col_grey);

    $brand   = 'energytools';
    $brand_x = $width - $padding - (strlen($brand) * 7);
    imagestring($im, 2, $brand_x, $height - $padding - $line_h,
        $brand, $col_grey);
}

// ── Save and serve ───────────────────────────────────────────────
header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');

if (@imagepng($im, $cache_file, 6)) {
    imagedestroy($im);
    readfile($cache_file);
} else {
    // Cache write failed (permissions); output directly
    imagepng($im);
    imagedestroy($im);
}
