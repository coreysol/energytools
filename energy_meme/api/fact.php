<?php
/**
 * Energy Meme Generator - Fact API
 *
 * Returns a random fact as JSON.
 * Query params:
 *   tone    (string) – "boost" or "motivate"; omit for any tone
 *   exclude (int)    – fact ID to exclude from random selection
 *
 * Response fields:
 *   id, tone, category, fact, explanation, source, source_url,
 *   has_image, _image_filename
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Access-Control-Allow-Origin: same-origin');

$facts_file = __DIR__ . '/../data/facts.json';

if (!file_exists($facts_file)) {
    http_response_code(500);
    echo json_encode(['error' => 'Facts data not found.']);
    exit;
}

$facts = json_decode(file_get_contents($facts_file), true) ?? [];

if (empty($facts)) {
    http_response_code(500);
    echo json_encode(['error' => 'No facts available.']);
    exit;
}

// Validate tone filter
$tone_param  = isset($_GET['tone']) ? trim($_GET['tone']) : null;
$valid_tones = ['boost', 'motivate'];
if ($tone_param !== null && !in_array($tone_param, $valid_tones, true)) {
    $tone_param = null;
}

// Exclude the current fact from the random pool
$exclude_id = isset($_GET['exclude']) ? (int)$_GET['exclude'] : null;

// Apply tone + exclude filters
$available = array_values(array_filter($facts, function ($f) use ($tone_param, $exclude_id) {
    $tone_match    = $tone_param === null || ($f['tone'] ?? '') === $tone_param;
    $exclude_match = $exclude_id === null || (int)$f['id'] !== $exclude_id;
    return $tone_match && $exclude_match;
}));

// If excluding left nothing (e.g. only one fact in this tone), relax exclude
if (empty($available)) {
    $available = array_values(array_filter($facts, function ($f) use ($tone_param) {
        return $tone_param === null || ($f['tone'] ?? '') === $tone_param;
    }));
}

// Final fallback: return any fact
if (empty($available)) {
    $available = $facts;
}

$fact = $available[array_rand($available)];

// Check for a fact-specific on-screen image
$has_image = false;
$img_file  = null;
foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
    $path = __DIR__ . '/../images/facts/' . $fact['id'] . '.' . $ext;
    if (file_exists($path)) {
        $has_image = true;
        $img_file  = $fact['id'] . '.' . $ext;
        break;
    }
}

echo json_encode([
    'id'              => (int)$fact['id'],
    'tone'            => $fact['tone'] ?? 'boost',
    'category'        => $fact['category'],
    'fact'            => $fact['fact'],
    'explanation'     => $fact['explanation'],
    'source'          => $fact['source'],
    'source_url'      => $fact['source_url'] ?? null,
    'has_image'       => $has_image,
    '_image_filename' => $img_file,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
