<?php
/**
 * Geocode suggest - Nominatim (OpenStreetMap), multiple results for autocomplete
 * GET ?q=address  -> JSON array of { lat, lng, display_name } (max 5). Empty array on no results.
 * Rate limit: 1 request per second per IP (shared with geocode.php via same rate_limit dir).
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, max-age=300');

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
if ($q === '') {
    echo json_encode([]);
    exit;
}

$q = preg_replace('/\s+/', ' ', $q);
$cacheDir = __DIR__ . '/../data/cache';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

$rateLimitDir = $cacheDir . '/rate_limit';
if (!is_dir($rateLimitDir)) {
    @mkdir($rateLimitDir, 0755, true);
}
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rateLimitFile = $rateLimitDir . '/' . md5($clientIp) . '.txt';
$now = time();
if (file_exists($rateLimitFile)) {
    $last = (int) file_get_contents($rateLimitFile);
    if ($now - $last < 1) {
        echo json_encode([]);
        exit;
    }
}
file_put_contents($rateLimitFile, (string) $now, LOCK_EX);

$cacheKey = 'geocode_suggest_' . md5(mb_strtolower($q, 'UTF-8'));
$cacheFile = $cacheDir . '/' . $cacheKey . '.json';
if (file_exists($cacheFile)) {
    $age = time() - filemtime($cacheFile);
    if ($age < 86400) {
        echo file_get_contents($cacheFile);
        exit;
    }
}

$url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
    'q' => $q,
    'format' => 'json',
    'limit' => 5,
]);
$opts = [
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: EnergyTools-DPP-Map/1.0 (contact required)\r\n",
        'timeout' => 10,
    ],
];
$ctx = stream_context_create($opts);
$raw = @file_get_contents($url, false, $ctx);

if ($raw === false || !is_array($data = json_decode($raw, true))) {
    echo json_encode([]);
    exit;
}

$out = [];
foreach (array_slice($data, 0, 5) as $item) {
    $lat = isset($item['lat']) ? $item['lat'] : null;
    $lon = isset($item['lon']) ? $item['lon'] : null;
    $displayName = isset($item['display_name']) ? $item['display_name'] : '';
    if ($lat !== null && $lon !== null) {
        $out[] = [
            'lat' => (float) $lat,
            'lng' => (float) $lon,
            'display_name' => $displayName,
        ];
    }
}
file_put_contents($cacheFile, json_encode($out), LOCK_EX);
echo json_encode($out);
