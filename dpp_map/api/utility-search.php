<?php
/**
 * Utility search - name query -> list of matches with bbox and geometries
 * Fetches from HIFLD by name (or local file); returns all polygons per utility for drawing.
 * GET ?q=...  -> JSON { matches: [ { utility, utility_id, bbox, geometries }, ... ] }
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/territories_loader.php';

$HIFLD_LAYER_URL = 'https://maps.nccs.nasa.gov/mapping/rest/services/hifld_open/energy/MapServer/26/query';

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
if ($q === '') {
    echo json_encode(['matches' => []]);
    exit;
}

$features = fetch_territories_from_hifld_by_name($q, $HIFLD_LAYER_URL, 50);
if ($features === null) {
    $features = get_local_territories();
}

$qLower = mb_strtolower($q, 'UTF-8');
$byName = [];

foreach ($features as $feature) {
    $name = feature_utility_name($feature);
    if ($name === '' || mb_strpos(mb_strtolower($name, 'UTF-8'), $qLower) === false) {
        continue;
    }
    $geom = feature_geometry($feature);
    $geometries = [];
    if ($geom) {
        if (isset($geom['type']) && $geom['type'] === 'Polygon' && isset($geom['coordinates'][0])) {
            $geometries[] = $geom;
        } elseif (isset($geom['type']) && $geom['type'] === 'MultiPolygon' && isset($geom['coordinates'])) {
            foreach ($geom['coordinates'] as $poly) {
                $geometries[] = ['type' => 'Polygon', 'coordinates' => $poly];
            }
        }
    }
    $props = isset($feature['properties']) ? $feature['properties'] : [];
    $id = isset($props['id']) ? $props['id'] : (isset($props['OBJECTID']) ? (string) $props['OBJECTID'] : null);
    if (!isset($byName[$name])) {
        $byName[$name] = [
            'utility' => $name,
            'utility_id' => $id,
            'bbox' => null,
            'geometries' => [],
        ];
    }
    foreach ($geometries as $g) {
        $byName[$name]['geometries'][] = $g;
        if (isset($g['coordinates'][0])) {
            $ring = $g['coordinates'][0];
            $lngs = array_column($ring, 0);
            $lats = array_column($ring, 1);
            if (!empty($lngs) && !empty($lats)) {
                $w = min($lngs);
                $s = min($lats);
                $e = max($lngs);
                $n = max($lats);
                if ($byName[$name]['bbox'] === null) {
                    $byName[$name]['bbox'] = ['west' => $w, 'south' => $s, 'east' => $e, 'north' => $n];
                } else {
                    $b = $byName[$name]['bbox'];
                    $byName[$name]['bbox'] = [
                        'west' => min($b['west'], $w),
                        'south' => min($b['south'], $s),
                        'east' => max($b['east'], $e),
                        'north' => max($b['north'], $n),
                    ];
                }
            }
        }
    }
}

$matches = array_values($byName);
echo json_encode(['matches' => $matches]);
