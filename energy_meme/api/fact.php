<?php
/**
 * Energy Meme Generator - Fact API
 *
 * Returns a fact as JSON.
 * Query params:
 *   id         (int)    – return a specific fact by ID (for permalinks)
 *   tone       (string) – "boost" or "motivate"; omit for any tone
 *   exclude    (int)    – fact ID to exclude from random selection
 *   lucky_tone (string) – "boost" or "motivate"; activates lucky backgrounds
 *
 * Response fields:
 *   id, tone, category, fact, explanation, source, source_url,
 *   background_url
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

// Fetch a specific fact by ID (permalink / popstate)
if (isset($_GET['id'])) {
    $requested_id = (int)$_GET['id'];
    $fact = null;
    foreach ($facts as $f) {
        if ((int)$f['id'] === $requested_id) { $fact = $f; break; }
    }
    if (!$fact) {
        http_response_code(404);
        echo json_encode(['error' => 'Fact not found.']);
        exit;
    }
    $lucky_tone     = null; // permalinks never carry lucky backgrounds
    $background_url = resolve_background($fact['id'], $fact['background'] ?? null, $lucky_tone);
    echo json_encode([
        'id'             => (int)$fact['id'],
        'tone'           => $fact['tone'] ?? 'boost',
        'category'       => $fact['category'],
        'fact'           => $fact['fact'],
        'explanation'    => $fact['explanation'],
        'source'         => $fact['source'],
        'source_url'     => $fact['source_url'] ?? null,
        'background_url' => $background_url,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

// Optional lucky_tone param — when set, inserts lucky backgrounds into the priority chain
$lucky_tone = isset($_GET['lucky_tone']) ? trim($_GET['lucky_tone']) : null;
if ($lucky_tone !== null && !in_array($lucky_tone, ['boost', 'motivate'], true)) {
    $lucky_tone = null;
}

/**
 * Resolve the background image path using the same priority as generate.php:
 *   1. images/facts/{id}.*          (fact-specific)
 *   2. backgrounds/lucky-{tone}.*   (lucky mode only, when $lucky_tone is set)
 *   3. backgrounds/{hint}.*         (category/named background)
 *   4. backgrounds/default.*        (catch-all fallback)
 *   5. null                         (no image — generator uses gradient)
 */
function resolve_background($fact_id, $bg_hint, $lucky_tone) {
    $base = __DIR__ . '/..';
    $exts = ['jpg', 'jpeg', 'png', 'webp'];

    // 1. Fact-specific image
    foreach ($exts as $e) {
        if (file_exists("$base/images/facts/{$fact_id}.{$e}"))
            return "images/facts/{$fact_id}.{$e}";
    }
    // 2. Lucky background
    if ($lucky_tone) {
        foreach ($exts as $e) {
            if (file_exists("$base/backgrounds/lucky-{$lucky_tone}.{$e}"))
                return "backgrounds/lucky-{$lucky_tone}.{$e}";
        }
    }
    // 3. Named category background
    if ($bg_hint) {
        foreach ($exts as $e) {
            if (file_exists("$base/backgrounds/{$bg_hint}.{$e}"))
                return "backgrounds/{$bg_hint}.{$e}";
        }
    }
    // 4. Default background
    foreach ($exts as $e) {
        if (file_exists("$base/backgrounds/default.{$e}"))
            return "backgrounds/default.{$e}";
    }
    return null;
}

$background_url = resolve_background(
    $fact['id'],
    $fact['background'] ?? null,
    $lucky_tone
);

echo json_encode([
    'id'             => (int)$fact['id'],
    'tone'           => $fact['tone'] ?? 'boost',
    'category'       => $fact['category'],
    'fact'           => $fact['fact'],
    'explanation'    => $fact['explanation'],
    'source'         => $fact['source'],
    'source_url'     => $fact['source_url'] ?? null,
    'background_url' => $background_url,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
