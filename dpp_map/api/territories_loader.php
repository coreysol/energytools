<?php
/**
 * Load utility territory features: local file and/or HIFLD fetch by bbox (cached).
 * Used by utility-at-point and utility-search.
 */

$TERRITORIES_CACHE_DIR = __DIR__ . '/../data/cache/territories';
$TERRITORIES_CACHE_TTL = 86400; // 24 hours
$HIFLD_LAYER_URL = 'https://maps.nccs.nasa.gov/mapping/rest/services/hifld_open/energy/MapServer/26/query';

/**
 * Get territory features for a bounding box. Tries cache then HIFLD. Returns array of GeoJSON features.
 */
function get_territories_for_bbox($west, $south, $east, $north) {
    global $TERRITORIES_CACHE_DIR, $TERRITORIES_CACHE_TTL, $HIFLD_LAYER_URL;
    $key = sprintf('bbox_%.4f_%.4f_%.4f_%.4f', $west, $south, $east, $north);
    $cacheFile = $TERRITORIES_CACHE_DIR . '/' . md5($key) . '.json';
    if (!is_dir($TERRITORIES_CACHE_DIR)) {
        @mkdir($TERRITORIES_CACHE_DIR, 0755, true);
    }
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $TERRITORIES_CACHE_TTL) {
        $raw = file_get_contents($cacheFile);
        $data = json_decode($raw, true);
        if (is_array($data) && isset($data['features'])) {
            return $data['features'];
        }
    }
    $features = fetch_territories_from_hifld_bbox($west, $south, $east, $north, $HIFLD_LAYER_URL);
    if ($features !== null) {
        file_put_contents($cacheFile, json_encode(['features' => $features]), LOCK_EX);
        return $features;
    }
    return null;
}

/**
 * Fetch territory features from HIFLD by bbox. Returns array of features or null on failure.
 */
function fetch_territories_from_hifld_bbox($west, $south, $east, $north, $baseUrl) {
    $geometry = json_encode([
        'xmin' => $west,
        'ymin' => $south,
        'xmax' => $east,
        'ymax' => $north,
    ]);
    $params = [
        'where' => '1=1',
        'geometry' => $geometry,
        'geometryType' => 'esriGeometryEnvelope',
        'inSR' => '4326',
        'spatialRel' => 'esriSpatialRelIntersects',
        'returnGeometry' => 'true',
        'outFields' => '*',
        'outSR' => '4326',
        'f' => 'geojson',
    ];
    $url = $baseUrl . '?' . http_build_query($params);
    $opts = [
        'http' => [
            'method' => 'GET',
            'timeout' => 15,
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

/**
 * Fetch territory features from HIFLD by name (LIKE). Returns array of features or null.
 */
function fetch_territories_from_hifld_by_name($q, $baseUrl, $limit = 100) {
    $safe = str_replace("'", "''", $q);
    $where = "UPPER(NAME) LIKE UPPER('%" . $safe . "%')";
    $params = [
        'where' => $where,
        'returnGeometry' => 'true',
        'outFields' => '*',
        'outSR' => '4326',
        'f' => 'geojson',
        'returnIdsOnly' => 'false',
    ];
    $url = $baseUrl . '?' . http_build_query($params);
    $opts = [
        'http' => [
            'method' => 'GET',
            'timeout' => 20,
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
    return array_slice($data['features'], 0, $limit);
}

/**
 * Load features from local territories.json.
 */
function get_local_territories() {
    $file = __DIR__ . '/../data/territories.json';
    if (!is_readable($file)) {
        return [];
    }
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data) || !isset($data['features'])) {
        return [];
    }
    return $data['features'];
}

/**
 * Extract utility name from feature properties (NAME or name).
 */
function feature_utility_name($feature) {
    $p = isset($feature['properties']) ? $feature['properties'] : [];
    return isset($p['NAME']) ? $p['NAME'] : (isset($p['name']) ? $p['name'] : '');
}

/**
 * Extract geometry. Handles Polygon and MultiPolygon.
 */
function feature_geometry($feature) {
    return isset($feature['geometry']) ? $feature['geometry'] : null;
}
