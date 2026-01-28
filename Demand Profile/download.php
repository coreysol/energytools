<?php
/**
 * CSV file download handler
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/csv_generator.php';

session_start();

// Check if CSV file exists in session
if (!isset($_SESSION['demand_profile_csv'])) {
    header('HTTP/1.0 404 Not Found');
    exit('CSV file not found. Please generate a new profile.');
}

$csv_filepath = $_SESSION['demand_profile_csv'];

// Generate download filename
$filename = 'demand_profile_' . date('Y-m-d') . '.csv';

// Serve the file
serveCSVDownload($csv_filepath, $filename);
