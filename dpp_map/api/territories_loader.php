<?php
/**
 * Load utility territory features: ORNL OpenDataSoft (Explore API v2.1) by bbox, with cache.
 * Fallback: local data/territories.json.
 * Used by utility-at-point for territory-at-point lookup.
 *
 * ORNL dataset: electric-retail-service-territories (same data as HIFLD; ORNL exposes centroid points only in API).
 */

$TERRITORIES_CACHE_DIR = __DIR__ . '/../data/cache/territories';
$TERRITORIES_CACHE_TTL = 86400; // 24 hours (increase to 604800 or more when using pre-cached data)
$TERRITORIES_CACHE_GRID = 2.0;  // degrees; must match scripts/precache_territories.php grid
$ORNL_API_BASE = 'https://ornl.opendatasoft.com/api/explore/v2.1/catalog/datasets/electric-retail-service-territories';

/**
 * Snap bbox to grid so cache keys align with pre-cache script. Returns [west, south, east, north].
 */
function territories_snap_bbox_to_grid($west, $south, $east, $north) {
    global $TERRITORIES_CACHE_GRID;
    $g = $TERRITORIES_CACHE_GRID;
    return [
        floor($west / $g) * $g,
        floor($south / $g) * $g,
        ceil($east / $g) * $g,
        ceil($north / $g) * $g,
    ];
}

/**
 * Get territory features for a bounding box. Tries cache then ORNL. Returns array of GeoJSON features.
 * Bbox is snapped to TERRITORIES_CACHE_GRID so pre-cached tiles are used.
 */
function get_territories_for_bbox($west, $south, $east, $north) {
    global $TERRITORIES_CACHE_DIR, $TERRITORIES_CACHE_TTL, $ORNL_API_BASE;
    $snapped = territories_snap_bbox_to_grid($west, $south, $east, $north);
    $west = $snapped[0];
    $south = $snapped[1];
    $east = $snapped[2];
    $north = $snapped[3];
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
    $features = fetch_territories_from_ornl_bbox($west, $south, $east, $north);
    if ($features !== null) {
        file_put_contents($cacheFile, json_encode(['features' => $features]), LOCK_EX);
        return $features;
    }
    return null;
}

/**
 * Fetch territory features from ORNL OpenDataSoft by bbox.
 * Uses exports/geojson with where=in_bbox(geo_point_2d, west, south, east, north).
 * Returns array of GeoJSON features (geometry may be Point only; ORNL API does not expose polygons).
 */
function fetch_territories_from_ornl_bbox($west, $south, $east, $north) {
    global $ORNL_API_BASE;
    // ODSQL in_bbox(geo_field, west, south, east, north) - bbox in WGS84
    $where = 'in_bbox(geo_point_2d,' . $west . ',' . $south . ',' . $east . ',' . $north . ')';
    $params = [
        'where' => $where,
        'limit' => 10000,
    ];
    $url = $ORNL_API_BASE . '/exports/geojson?' . http_build_query($params);
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
 * Extract geometry. Handles Polygon, MultiPolygon, and Point.
 */
function feature_geometry($feature) {
    return isset($feature['geometry']) ? $feature['geometry'] : null;
}
