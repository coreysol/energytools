<?php
/**
 * Utility at point - (lat, lng) -> utility name/id + geometry
 * Fetches territory data on demand (HIFLD bbox) with cache; falls back to local file.
 * GET ?lat=...&lng=...  -> JSON { utility, utility_id, bbox, geometry, geometries }
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/territories_loader.php';

$lat = isset($_GET['lat']) ? (float) $_GET['lat'] : null;
$lng = isset($_GET['lng']) ? (float) $_GET['lng'] : null;

if ($lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid lat and lng required.', 'code' => 'invalid_params']);
    exit;
}

$margin = 0.4;
$west = $lng - $margin;
$south = $lat - $margin;
$east = $lng + $margin;
$north = $lat + $margin;

$features = get_territories_for_bbox($west, $south, $east, $north);
$territorySource = 'hifld';
if ($features === null) {
    $features = get_local_territories();
    $territorySource = 'local';
}

$point = [$lng, $lat];

foreach ($features as $feature) {
    $geom = feature_geometry($feature);
    if (!$geom) {
        continue;
    }
    $type = isset($geom['type']) ? $geom['type'] : '';
    $contained = false;
    $geometries = [];

    if ($type === 'Polygon' && isset($geom['coordinates'][0])) {
        $contained = pointInPolygon($point, $geom['coordinates'][0]);
        if ($contained) {
            $geometries = [$geom];
        }
    } elseif ($type === 'MultiPolygon' && isset($geom['coordinates'])) {
        foreach ($geom['coordinates'] as $poly) {
            if (isset($poly[0]) && pointInPolygon($point, $poly[0])) {
                $contained = true;
            }
            if (isset($poly[0])) {
                $geometries[] = ['type' => 'Polygon', 'coordinates' => $poly];
            }
        }
        if ($contained) {
            // Keep all polygons for this utility so we draw full service area
        }
    }

    if ($contained) {
        $props = isset($feature['properties']) ? $feature['properties'] : [];
        $name = feature_utility_name($feature);
        $id = isset($props['id']) ? $props['id'] : (isset($props['OBJECTID']) ? (string) $props['OBJECTID'] : null);
        $bbox = null;
        if (count($geometries) === 1) {
            $bbox = bboxFromRing($geometries[0]['coordinates'][0]);
        } elseif (count($geometries) > 1) {
            $bbox = bboxFromGeometries($geometries);
        }
        echo json_encode([
            'utility' => $name,
            'utility_id' => $id,
            'bbox' => $bbox,
            'geometry' => count($geometries) === 1 ? $geometries[0] : null,
            'geometries' => count($geometries) > 1 ? $geometries : (count($geometries) === 1 ? [$geometries[0]] : []),
            'territory_source' => $territorySource,
        ]);
        exit;
    }
}

echo json_encode(['utility' => null, 'utility_id' => null, 'bbox' => null, 'geometry' => null, 'geometries' => [], 'territory_source' => $territorySource, 'code' => 'no_utility_at_point']);

function pointInPolygon(array $point, array $ring) {
    $n = count($ring);
    if ($n < 3) {
        return false;
    }
    $x = $point[0];
    $y = $point[1];
    $inside = false;
    for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
        $xi = $ring[$i][0];
        $yi = $ring[$i][1];
        $xj = $ring[$j][0];
        $yj = $ring[$j][1];
        $dy = $yj - $yi;
        if ($dy !== 0.0 && (($yi > $y) !== ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / $dy + $xi)) {
            $inside = !$inside;
        }
    }
    return $inside;
}

function bboxFromRing(array $ring) {
    $lngs = array_column($ring, 0);
    $lats = array_column($ring, 1);
    if (empty($lngs) || empty($lats)) {
        return null;
    }
    return [
        'west' => min($lngs),
        'south' => min($lats),
        'east' => max($lngs),
        'north' => max($lats),
    ];
}

function bboxFromGeometries(array $geometries) {
    $west = $south = PHP_FLOAT_MAX;
    $east = $north = -PHP_FLOAT_MAX;
    foreach ($geometries as $g) {
        if (!isset($g['coordinates'][0])) {
            continue;
        }
        $b = bboxFromRing($g['coordinates'][0]);
        if ($b) {
            $west = min($west, $b['west']);
            $south = min($south, $b['south']);
            $east = max($east, $b['east']);
            $north = max($north, $b['north']);
        }
    }
    if ($west === PHP_FLOAT_MAX) {
        return null;
    }
    return ['west' => $west, 'south' => $south, 'east' => $east, 'north' => $north];
}
