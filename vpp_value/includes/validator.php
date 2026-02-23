<?php
/**
 * Input validation for VPP Value Calculator
 */

require_once __DIR__ . '/config.php';

function validateCheckbox($value) {
    return isset($value) && ($value === '1' || $value === 'on' || $value === true || $value === 'yes');
}

function sanitizeInput($input) {
    if (!is_string($input)) {
        return '';
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

$VALID_BASELINES = ['gas', 'battery', 'average'];
$VALID_TD_LEVELS = ['base', 'high', 'low'];
$VALID_TECH_COSTS = ['base', '2030'];
$VALID_RENEWABLES = ['base', 'bau'];
$VALID_BATTERY_CONFIGS = ['base', 'alt'];

function validateCarbonPrice($value) {
    if (!is_numeric($value)) {
        return [false, 'Carbon price must be a number'];
    }
    $n = (float) $value;
    if ($n < 0 || $n > 500) {
        return [false, 'Carbon price must be between 0 and 500 $/metric ton'];
    }
    return [true, $n];
}

function validateSelect($value, array $allowed, $fieldName) {
    $v = is_string($value) ? trim($value) : '';
    if (in_array($v, $allowed, true)) {
        return [true, $v];
    }
    return [false, $fieldName . ' must be one of: ' . implode(', ', $allowed)];
}

/**
 * Validate all VPP form inputs.
 *
 * @param array $inputs Raw POST inputs
 * @return array ['valid' => bool, 'errors' => array, 'sanitized' => array]
 */
function validateVppInputs(array $inputs) {
    $errors = [];
    $sanitized = [
        'include_resilience'   => validateCheckbox($inputs['include_resilience'] ?? false),
        'include_emissions'    => validateCheckbox($inputs['include_emissions'] ?? false),
        'include_ancillary'   => validateCheckbox($inputs['include_ancillary'] ?? false),
        'comparison_baseline' => $inputs['comparison_baseline'] ?? 'average',
        'carbon_price'        => 100,
        'td_level'            => $inputs['td_level'] ?? 'base',
        'tech_cost'           => $inputs['tech_cost'] ?? 'base',
        'renewables'          => $inputs['renewables'] ?? 'base',
        'battery_config'      => $inputs['battery_config'] ?? 'base',
    ];

    global $VALID_BASELINES, $VALID_TD_LEVELS, $VALID_TECH_COSTS, $VALID_RENEWABLES, $VALID_BATTERY_CONFIGS;

    list($ok, $result) = validateSelect($sanitized['comparison_baseline'], $VALID_BASELINES, 'Comparison baseline');
    if (!$ok) {
        $errors['comparison_baseline'] = $result;
    } else {
        $sanitized['comparison_baseline'] = $result;
    }

    list($ok, $result) = validateCarbonPrice($inputs['carbon_price'] ?? 100);
    if (!$ok) {
        $errors['carbon_price'] = $result;
    } else {
        $sanitized['carbon_price'] = $result;
    }

    list($ok, $result) = validateSelect($sanitized['td_level'], $VALID_TD_LEVELS, 'T&D cost level');
    if (!$ok) {
        $errors['td_level'] = $result;
    } else {
        $sanitized['td_level'] = $result;
    }

    list($ok, $result) = validateSelect($sanitized['tech_cost'], $VALID_TECH_COSTS, 'Technology cost scenario');
    if (!$ok) {
        $errors['tech_cost'] = $result;
    } else {
        $sanitized['tech_cost'] = $result;
    }

    list($ok, $result) = validateSelect($sanitized['renewables'], $VALID_RENEWABLES, 'Renewables deployment');
    if (!$ok) {
        $errors['renewables'] = $result;
    } else {
        $sanitized['renewables'] = $result;
    }

    list($ok, $result) = validateSelect($sanitized['battery_config'], $VALID_BATTERY_CONFIGS, 'Battery configuration');
    if (!$ok) {
        $errors['battery_config'] = $result;
    } else {
        $sanitized['battery_config'] = $result;
    }

    return [
        'valid'    => count($errors) === 0,
        'errors'   => $errors,
        'sanitized' => $sanitized,
    ];
}
