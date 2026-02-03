<?php
/**
 * DPP eligibility by utility + rate class
 * GET ?utility=...&rate_class=...  -> JSON { eligible, eligible_rate_classes, reference_link }
 */

header('Content-Type: application/json; charset=utf-8');

$utility = isset($_GET['utility']) ? trim((string) $_GET['utility']) : '';
$rateClass = isset($_GET['rate_class']) ? trim((string) $_GET['rate_class']) : 'residential';

if ($utility === '') {
    echo json_encode([
        'eligible' => false,
        'eligible_rate_classes' => [],
        'reference_link' => null,
        'eligibility_updated' => null,
    ]);
    exit;
}

$configFile = __DIR__ . '/../config/dpp_eligibility.json';
if (!is_readable($configFile)) {
    echo json_encode([
        'eligible' => false,
        'eligible_rate_classes' => [],
        'reference_link' => null,
        'eligibility_updated' => null,
    ]);
    exit;
}

$eligibilityUpdated = @filemtime($configFile) ? date('Y-m-d', filemtime($configFile)) : null;
$config = json_decode(file_get_contents($configFile), true);
$rateClasses = [];
$referenceLink = null;

if (is_array($config) && isset($config['utilities']) && is_array($config['utilities'])) {
    foreach ($config['utilities'] as $name => $data) {
        if (!is_array($data)) {
            continue;
        }
        if (strcasecmp(trim($name), $utility) === 0) {
            $rateClasses = isset($data['rate_classes']) && is_array($data['rate_classes'])
                ? $data['rate_classes'] : [];
            $referenceLink = isset($data['reference_link']) ? trim((string) $data['reference_link']) : null;
            break;
        }
    }
}

$eligible = in_array(strtolower($rateClass), array_map('strtolower', $rateClasses));

echo json_encode([
    'eligible' => $eligible,
    'eligible_rate_classes' => $rateClasses,
    'reference_link' => $referenceLink !== '' ? $referenceLink : null,
    'eligibility_updated' => $eligibilityUpdated,
]);
