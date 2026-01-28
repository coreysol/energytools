<?php
/**
 * Process form submission and generate demand profile
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/validator.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Get and sanitize inputs
$inputs = [
    'zip_code' => sanitizeInput($_POST['zip_code'] ?? ''),
    'annual_kwh' => sanitizeInput($_POST['annual_kwh'] ?? ''),
    'has_ac' => validateCheckbox($_POST['has_ac'] ?? false),
    'has_heating' => validateCheckbox($_POST['has_heating'] ?? false),
    'has_wfh' => validateCheckbox($_POST['has_wfh'] ?? false),
    'has_ev' => validateCheckbox($_POST['has_ev'] ?? false)
];

// Validate inputs
$validation = validateAllInputs($inputs);

if (!$validation['valid']) {
    // Redirect back to form with errors
    $errorParams = http_build_query([
        'errors' => json_encode($validation['errors']),
        'zip_code' => $inputs['zip_code'],
        'annual_kwh' => $inputs['annual_kwh'],
        'has_ac' => $inputs['has_ac'] ? '1' : '',
        'has_heating' => $inputs['has_heating'] ? '1' : '',
        'has_wfh' => $inputs['has_wfh'] ? '1' : '',
        'has_ev' => $inputs['has_ev'] ? '1' : ''
    ]);
    header('Location: index.php?' . $errorParams);
    exit;
}

// Store validated inputs in session for results page
session_start();
$_SESSION['demand_profile_inputs'] = $inputs;
$_SESSION['demand_profile_annual_kwh'] = (float)$inputs['annual_kwh'];

// Generate demand profile data
require_once __DIR__ . '/includes/calculator.php';
require_once __DIR__ . '/includes/csv_generator.php';

try {
    // Generate 15-minute interval data
    $demand_data = generateDemandProfile($inputs);
    
    // Generate CSV file
    $csv_filepath = generateGreenButtonCSV($demand_data);
    
    // Aggregate data for graphs
    $seasonal_data = aggregateSeasonalData($demand_data);
    
    // Store in session
    $_SESSION['demand_profile_data'] = $demand_data;
    $_SESSION['demand_profile_seasonal'] = $seasonal_data;
    $_SESSION['demand_profile_csv'] = $csv_filepath;
    
    header('Location: results.php');
    exit;
    
} catch (Exception $e) {
    // Handle error
    $errorParams = http_build_query([
        'errors' => json_encode(['general' => 'Error generating demand profile: ' . $e->getMessage()]),
        'zip_code' => $inputs['zip_code'],
        'annual_kwh' => $inputs['annual_kwh'],
        'has_ac' => $inputs['has_ac'] ? '1' : '',
        'has_heating' => $inputs['has_heating'] ? '1' : '',
        'has_wfh' => $inputs['has_wfh'] ? '1' : '',
        'has_ev' => $inputs['has_ev'] ? '1' : ''
    ]);
    header('Location: index.php?' . $errorParams);
    exit;
}
