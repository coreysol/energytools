<?php
/**
 * Pre-cache electric utility boundaries from ORNL OpenDataSoft into data/cache/territories/
 * so the map can serve territory lookups without calling ORNL at request time.
 *
 * Run from CLI from the dpp_map directory (or repo root):
 *   php scripts/precache_territories.php
 *   php scripts/precache_territories.php [west] [south] [east] [north] [step_degrees]
 *
 * Default: continental US bbox, 2° step. Uses same cache format as api/territories_loader.php.
 * ORNL has no strict rate limit like HIFLD; a short delay (e.g. 0.2s) per request is still recommended.
 *
 * After pre-caching, consider increasing TERRITORIES_CACHE_TTL in api/territories_loader.php
 * (e.g. 604800 = 7 days) so pre-cached data is used longer.
 */

$baseDir = dirname(__DIR__);
$cacheDir = $baseDir . '/data/cache/territories';
$ornlBase = 'https://ornl.opendatasoft.com/api/explore/v2.1/catalog/datasets/electric-retail-service-territories';

// Grid step (degrees). Must match api/territories_loader.php TERRITORIES_CACHE_GRID.
$gridStep = 2.0;

// Default: continental US (snap to grid so keys match loader)
$west = isset($argv[1]) ? (float) $argv[1] : -125.0;
$south = isset($argv[2]) ? (float) $argv[2] : 24.0;
$east = isset($argv[3]) ? (float) $argv[3] : -66.0;
$north = isset($argv[4]) ? (float) $argv[4] : 50.0;
$step = isset($argv[5]) ? (float) $argv[5] : $gridStep;

$west = floor($west / $gridStep) * $gridStep;
$south = floor($south / $gridStep) * $gridStep;
$east = ceil($east / $gridStep) * $gridStep;
$north = ceil($north / $gridStep) * $gridStep;

if ($step <= 0 || $west >= $east || $south >= $north) {
    fwrite(STDERR, "Usage: php precache_territories.php [west south east north] [step_degrees]\n");
    exit(1);
}

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

function fetch_territories_from_ornl_bbox($west, $south, $east, $north, $baseUrl) {
    $where = 'in_bbox(geo_point_2d,' . $west . ',' . $south . ',' . $east . ',' . $north . ')';
    $params = [
        'where' => $where,
        'limit' => 10000,
    ];
    $url = $baseUrl . '/exports/geojson?' . http_build_query($params);
    $opts = [
        'http' => [
            'method' => 'GET',
            'timeout' => 30,
        ],
    ];
    $raw = @file_get_contents($url, false, stream_context_create($opts));
    if ($raw === false) {
        return null;
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['features'])) {
        return null;
    }
    return $data['features'];
}

$count = 0;
$total = 0;
for ($s = $south; $s < $north; $s += $step) {
    for ($w = $west; $w < $east; $w += $step) {
        $e = $w + $step;
        $n = $s + $step;
        $total++;
        $key = sprintf('bbox_%.4f_%.4f_%.4f_%.4f', $w, $s, $e, $n);
        $cacheFile = $cacheDir . '/' . md5($key) . '.json';
        if (file_exists($cacheFile)) {
            echo "Skip (cached): $key\n";
            continue;
        }
        $features = fetch_territories_from_ornl_bbox($w, $s, $e, $n, $ornlBase);
        if ($features !== null) {
            file_put_contents($cacheFile, json_encode(['features' => $features]), LOCK_EX);
            $count++;
            echo "Cached: $key (" . count($features) . " features)\n";
        } else {
            echo "Fail: $key\n";
        }
        usleep(200000); // 0.2s between requests
    }
}

echo "Done. Cached $count new tiles (of $total total).\n";
