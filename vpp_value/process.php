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
    $params = array_merge(
        ['errors' => json_encode($validation['errors'])],
        [
            'include_resilience'   => $validation['sanitized']['include_resilience'] ? '1' : '',
            'include_emissions'   => $validation['sanitized']['include_emissions'] ? '1' : '',
            'include_ancillary'   => $validation['sanitized']['include_ancillary'] ? '1' : '',
            'comparison_baseline' => $validation['sanitized']['comparison_baseline'],
            'carbon_price'        => $validation['sanitized']['carbon_price'],
            'td_level'            => $validation['sanitized']['td_level'],
            'tech_cost'           => $validation['sanitized']['tech_cost'],
            'renewables'          => $validation['sanitized']['renewables'],
            'battery_config'      => $validation['sanitized']['battery_config'],
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
