<?php
/**
 * Geocode proxy - Nominatim (OpenStreetMap)
 * GET ?q=address  -> JSON { lat, lng, display_name } or { error, code }
 * Caches results by normalized query string. Rate limit: 1 request per second per IP.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, max-age=300');

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
if ($q === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing query parameter q.', 'code' => 'missing_query']);
    exit;
}

$q = preg_replace('/\s+/', ' ', $q);
$cacheDir = __DIR__ . '/../data/cache';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

// Rate limit: 1 request per second per IP (Nominatim policy)
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
        http_response_code(429);
        header('Retry-After: 1');
        echo json_encode([
            'error' => 'Too many requests. Please wait a moment and try again.',
            'code' => 'rate_limited',
        ]);
        exit;
    }
}
file_put_contents($rateLimitFile, (string) $now, LOCK_EX);

$cacheKey = 'geocode_' . md5(mb_strtolower($q, 'UTF-8'));
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
    'limit' => 1,
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

if ($raw === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Geocoding service unavailable.', 'code' => 'service_unavailable']);
    exit;
}

$data = json_decode($raw, true);
if (!is_array($data) || empty($data)) {
    echo json_encode(['error' => 'Address not found.', 'code' => 'address_not_found']);
    exit;
}

$first = $data[0];
$lat = isset($first['lat']) ? $first['lat'] : null;
$lon = isset($first['lon']) ? $first['lon'] : null;
$displayName = isset($first['display_name']) ? $first['display_name'] : '';

if ($lat === null || $lon === null) {
    echo json_encode(['error' => 'Address not found.', 'code' => 'address_not_found']);
    exit;
}

$out = [
    'lat' => (float) $lat,
    'lng' => (float) $lon,
    'display_name' => $displayName,
];
file_put_contents($cacheFile, json_encode($out), LOCK_EX);
echo json_encode($out);
