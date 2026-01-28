<?php
/**
 * Configuration file for Demand Profile Generator
 */

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('America/Los_Angeles');

// Base URL (adjust for your environment)
define('BASE_URL', 'http://localhost:8000');

// Directories
define('BASE_DIR', __DIR__ . '/..');
define('TEMP_DIR', BASE_DIR . '/temp');
define('INCLUDES_DIR', BASE_DIR . '/includes');
define('ASSETS_DIR', BASE_DIR . '/assets');

// File paths
define('TEMP_CSV_DIR', TEMP_DIR);

// CSV settings
define('CSV_DELIMITER', ',');
define('CSV_ENCLOSURE', '"');

// Time interval settings (in minutes)
define('INTERVAL_MINUTES', 15);
define('MINUTES_PER_HOUR', 60);
define('HOURS_PER_DAY', 24);
define('DAYS_PER_YEAR', 365);

// Calculation constants
define('INTERVALS_PER_HOUR', MINUTES_PER_HOUR / INTERVAL_MINUTES); // 4
define('INTERVALS_PER_DAY', INTERVALS_PER_HOUR * HOURS_PER_DAY); // 96
define('INTERVALS_PER_YEAR', INTERVALS_PER_DAY * DAYS_PER_YEAR); // 35,040

// kWh conversion (for 15-minute intervals)
define('HOURS_PER_INTERVAL', INTERVAL_MINUTES / MINUTES_PER_HOUR); // 0.25

// API settings (to be configured)
define('WEATHER_API_KEY', ''); // Add your API key if using weather API
define('WEATHER_API_URL', ''); // Add API URL if using weather API

// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_DIR', BASE_DIR . '/cache');
define('CACHE_EXPIRY', 3600); // 1 hour in seconds

// Ensure temp directory exists
if (!file_exists(TEMP_DIR)) {
    mkdir(TEMP_DIR, 0755, true);
}

// Ensure cache directory exists
if (CACHE_ENABLED && !file_exists(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0755, true);
}
