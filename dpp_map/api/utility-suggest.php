<?php
/**
 * Utility name suggestions for typeahead
 * GET ?q=...  -> JSON { suggestions: [ { name, id }, ... ] }  (max 15)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/territories_loader.php';

$HIFLD_LAYER_URL = 'https://maps.nccs.nasa.gov/mapping/rest/services/hifld_open/energy/MapServer/26/query';

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
if ($q === '' || mb_strlen($q) < 2) {
    echo json_encode(['suggestions' => []]);
    exit;
}

$features = fetch_territories_from_hifld_by_name($q, $HIFLD_LAYER_URL, 30);
if ($features === null) {
    $features = get_local_territories();
}

$qLower = mb_strtolower($q, 'UTF-8');
$seen = [];
$suggestions = [];
$max = 15;

foreach ($features as $feature) {
    $name = feature_utility_name($feature);
    if ($name === '' || mb_strpos(mb_strtolower($name, 'UTF-8'), $qLower) === false) {
        continue;
    }
    if (isset($seen[$name])) {
        continue;
    }
    $seen[$name] = true;
    $props = isset($feature['properties']) ? $feature['properties'] : [];
    $id = isset($props['id']) ? $props['id'] : (isset($props['OBJECTID']) ? (string) $props['OBJECTID'] : null);
    $suggestions[] = ['name' => $name, 'id' => $id];
    if (count($suggestions) >= $max) {
        break;
    }
}

echo json_encode(['suggestions' => $suggestions]);
