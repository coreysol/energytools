<?php
/**
 * Process VPP Value Calculator form and redirect to results
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/validator.php';
require_once __DIR__ . '/includes/calculator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

$validation = validateVppInputs($_POST);

if (!$validation['valid']) {
    $s = $validation['sanitized'];
    $params = array_merge(
        ['errors' => json_encode($validation['errors'])],
        [
            'include_resilience'   => $s['include_resilience'] ? '1' : '',
            'include_emissions'   => $s['include_emissions'] ? '1' : '',
            'include_ancillary'   => $s['include_ancillary'] ? '1' : '',
            'comparison_baseline' => $s['comparison_baseline'],
            'carbon_price'        => $s['carbon_price'],
            'td_level'            => $s['td_level'],
            'tech_cost'           => $s['tech_cost'],
            'renewables'          => $s['renewables'],
            'battery_config'      => $s['battery_config'],
            'peak_demand'         => $s['peak_demand'] !== null ? (string) $s['peak_demand'] : '',
            'pct_peak_vpp'        => $s['pct_peak_vpp'] !== null ? (string) $s['pct_peak_vpp'] : '',
        ]
    );
    header('Location: ' . BASE_PATH . '/index.php?' . http_build_query($params));
    exit;
}

$inputs = $validation['sanitized'];
$result = compute_savings_per_mw($inputs);

session_start();
$_SESSION['vpp_value_inputs'] = $inputs;
$_SESSION['vpp_value_result'] = $result;

header('Location: ' . BASE_PATH . '/results.php');
exit;
