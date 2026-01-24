<?php
/**
 * CSV file generator in Green Button format
 */

require_once __DIR__ . '/config.php';

/**
 * Generate CSV file in Green Button format
 * 
 * @param array $data Array of 15-minute interval data points
 * @return string Path to generated CSV file
 */
function generateGreenButtonCSV($data) {
    // Generate unique filename
    $filename = 'demand_profile_' . date('Y-m-d_His') . '.csv';
    $filepath = TEMP_CSV_DIR . '/' . $filename;
    
    // Open file for writing
    $file = fopen($filepath, 'w');
    
    if (!$file) {
        throw new Exception('Could not create CSV file');
    }
    
    // Write header (Green Button format)
    fputcsv($file, ['Date', 'Time', 'Usage'], CSV_DELIMITER, CSV_ENCLOSURE);
    
    // Write data rows
    foreach ($data as $point) {
        // Convert kW to kWh for 15-minute interval
        $kwh = $point['kw'] * HOURS_PER_INTERVAL;
        
        // Format row: Date, Time, Usage (kWh)
        fputcsv($file, [
            $point['date'],
            $point['time'],
            number_format($kwh, 6, '.', '') // 6 decimal places for precision
        ], CSV_DELIMITER, CSV_ENCLOSURE);
    }
    
    fclose($file);
    
    return $filepath;
}

/**
 * Serve CSV file for download
 * 
 * @param string $filepath Path to CSV file
 * @param string $filename Download filename
 */
function serveCSVDownload($filepath, $filename = null) {
    if (!file_exists($filepath)) {
        header('HTTP/1.0 404 Not Found');
        exit('File not found');
    }
    
    if ($filename === null) {
        $filename = basename($filepath);
    }
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Output file
    readfile($filepath);
    exit;
}

/**
 * Clean up old CSV files (older than 1 hour)
 */
function cleanupOldCSVFiles() {
    $files = glob(TEMP_CSV_DIR . '/*.csv');
    $now = time();
    
    foreach ($files as $file) {
        if (is_file($file)) {
            // Delete files older than 1 hour
            if ($now - filemtime($file) >= 3600) {
                unlink($file);
            }
        }
    }
}
