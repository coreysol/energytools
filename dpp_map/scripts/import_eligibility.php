<?php
/**
 * Import DPP eligibility from Excel (or CSV) into config/dpp_eligibility.json
 * Expected columns: utility, rate_classes (comma-separated: residential, commercial, industrial), reference_link
 * Row 1 = headers. Run from CLI: php scripts/import_eligibility.php [path/to/file.xlsx or file.csv]
 */

$baseDir = dirname(__DIR__);
$configFile = $baseDir . '/config/dpp_eligibility.json';
$inputFile = isset($argv[1]) ? $argv[1] : '';

if ($inputFile === '' || !is_readable($inputFile)) {
    echo "Usage: php import_eligibility.php <path/to/eligibility.xlsx or .csv>\n";
    echo "Columns: utility, rate_classes (e.g. residential,commercial), reference_link\n";
    exit(1);
}

$ext = strtolower(pathinfo($inputFile, PATHINFO_EXTENSION));
$utilities = [];

if ($ext === 'csv') {
    $utilities = parseCsv($inputFile);
} elseif ($ext === 'xlsx' || $ext === 'xls') {
    $autoload = $baseDir . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        echo "For Excel support, run in dpp_map: composer require phpoffice/phpspreadsheet\n";
        echo "Or export your Excel sheet to CSV and run: php import_eligibility.php file.csv\n";
        exit(1);
    }
    require $autoload;
    $utilities = parseExcel($inputFile);
} else {
    echo "Unsupported format. Use .csv or .xlsx\n";
    exit(1);
}

if (empty($utilities)) {
    echo "No rows parsed. Check column names: utility, rate_classes, reference_link\n";
    exit(1);
}

$configDir = dirname($configFile);
if (!is_dir($configDir)) {
    mkdir($configDir, 0755, true);
}

$config = ['utilities' => $utilities];
$json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if (file_put_contents($configFile, $json, LOCK_EX) === false) {
    echo "Failed to write " . $configFile . "\n";
    exit(1);
}

echo "Wrote " . count($utilities) . " utilities to " . $configFile . "\n";

function parseCsv($path) {
    $utilities = [];
    $h = fopen($path, 'r');
    if (!$h) {
        return $utilities;
    }
    $headers = fgetcsv($h, 0, ',', '"', '\\');
    if (!$headers) {
        fclose($h);
        return $utilities;
    }
    $headers = array_map('trim', $headers);
    $idxUtility = findColumn($headers, ['utility', 'name', 'utility_name']);
    $idxRateClasses = findColumn($headers, ['rate_classes', 'rate classes']);
    $idxRef = findColumn($headers, ['reference_link', 'reference_link', 'reference link', 'url']);
    if ($idxUtility === null) {
        fclose($h);
        return $utilities;
    }
    while (($row = fgetcsv($h, 0, ',', '"', '\\')) !== false) {
        $utility = isset($row[$idxUtility]) ? trim($row[$idxUtility]) : '';
        if ($utility === '') {
            continue;
        }
        $rateClasses = [];
        if ($idxRateClasses !== null && isset($row[$idxRateClasses])) {
            $rateClasses = array_map('trim', array_filter(explode(',', $row[$idxRateClasses])));
        }
        $ref = ($idxRef !== null && isset($row[$idxRef])) ? trim($row[$idxRef]) : '';
        $utilities[$utility] = [
            'rate_classes' => $rateClasses,
            'reference_link' => $ref,
        ];
    }
    fclose($h);
    return $utilities;
}

function parseExcel($path) {
    $utilities = [];
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
    } catch (Exception $e) {
        echo "Excel error: " . $e->getMessage() . "\n";
        return $utilities;
    }
    if (empty($rows)) {
        return $utilities;
    }
    $headers = array_map('trim', $rows[0]);
    $idxUtility = findColumn($headers, ['utility', 'name', 'utility_name']);
    $idxRateClasses = findColumn($headers, ['rate_classes', 'rate classes']);
    $idxRef = findColumn($headers, ['reference_link', 'reference link', 'url']);
    if ($idxUtility === null) {
        return $utilities;
    }
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        $utility = isset($row[$idxUtility]) ? trim((string) $row[$idxUtility]) : '';
        if ($utility === '') {
            continue;
        }
        $rateClasses = [];
        if ($idxRateClasses !== null && isset($row[$idxRateClasses])) {
            $rateClasses = array_map('trim', array_filter(explode(',', (string) $row[$idxRateClasses])));
        }
        $ref = ($idxRef !== null && isset($row[$idxRef])) ? trim((string) $row[$idxRef]) : '';
        $utilities[$utility] = [
            'rate_classes' => $rateClasses,
            'reference_link' => $ref,
        ];
    }
    return $utilities;
}

function findColumn($headers, array $candidates) {
    foreach ($candidates as $c) {
        $cLower = strtolower($c);
        foreach ($headers as $i => $h) {
            if (strtolower($h) === $cLower) {
                return $i;
            }
        }
    }
    return null;
}
