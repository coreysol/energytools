<?php
/**
 * Input validation functions
 */

require_once __DIR__ . '/config.php';

/**
 * Validate zip code (5-digit US zip code)
 * 
 * @param string $zip Zip code to validate
 * @return bool|string True if valid, error message if invalid
 */
function validateZipCode($zip) {
    // Remove any whitespace
    $zip = trim($zip);
    
    // Check if empty
    if (empty($zip)) {
        return 'Zip code is required';
    }
    
    // Check format: 5 digits
    if (!preg_match('/^\d{5}$/', $zip)) {
        return 'Zip code must be exactly 5 digits';
    }
    
    // Basic range check (US zip codes are 00501-99950)
    $zipNum = (int)$zip;
    if ($zipNum < 501 || $zipNum > 99950) {
        return 'Invalid zip code range';
    }
    
    return true;
}

/**
 * Validate annual kWh usage
 * 
 * @param mixed $kwh Annual kWh usage
 * @return bool|string True if valid, error message if invalid
 */
function validateAnnualKWh($kwh) {
    // Remove any whitespace
    $kwh = trim($kwh);
    
    // Check if empty
    if (empty($kwh)) {
        return 'Annual kWh usage is required';
    }
    
    // Must be numeric
    if (!is_numeric($kwh)) {
        return 'Annual kWh usage must be a number';
    }
    
    // Convert to float
    $kwh = (float)$kwh;
    
    // Must be positive
    if ($kwh <= 0) {
        return 'Annual kWh usage must be greater than 0';
    }
    
    // Reasonable range check (1,000 - 100,000 kWh)
    if ($kwh < 1000) {
        return 'Annual kWh usage seems too low (minimum 1,000 kWh)';
    }
    
    if ($kwh > 100000) {
        return 'Annual kWh usage seems too high (maximum 100,000 kWh)';
    }
    
    return true;
}

/**
 * Validate checkbox input (boolean)
 * 
 * @param mixed $value Checkbox value
 * @return bool True if checked, false otherwise
 */
function validateCheckbox($value) {
    return isset($value) && ($value === '1' || $value === 'on' || $value === true || $value === 'yes');
}

/**
 * Sanitize input string
 * 
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate all form inputs
 * 
 * @param array $inputs Form inputs array
 * @return array Array with 'valid' (bool) and 'errors' (array)
 */
function validateAllInputs($inputs) {
    $errors = [];
    $valid = true;
    
    // Validate zip code
    $zipResult = validateZipCode($inputs['zip_code'] ?? '');
    if ($zipResult !== true) {
        $errors['zip_code'] = $zipResult;
        $valid = false;
    }
    
    // Validate annual kWh
    $kwhResult = validateAnnualKWh($inputs['annual_kwh'] ?? '');
    if ($kwhResult !== true) {
        $errors['annual_kwh'] = $kwhResult;
        $valid = false;
    }
    
    // Checkboxes are optional, just validate they're boolean
    // (They default to false if not set, which is fine)
    
    return [
        'valid' => $valid,
        'errors' => $errors
    ];
}
