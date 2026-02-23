<?php
/**
 * Configuration for VPP Value Calculator
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('America/Los_Angeles');

define('BASE_DIR', __DIR__ . '/..');
define('INCLUDES_DIR', __DIR__);
define('ASSETS_DIR', BASE_DIR . '/assets');

// Web path to this app (no trailing slash). Enables correct asset and redirect URLs when run as a subdirectory (e.g. /energytools/vpp_value).
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$script_dir = $script_name !== '' ? str_replace('\\', '/', dirname($script_name)) : '';
define('BASE_PATH', $script_dir !== '' ? rtrim($script_dir, '/') : '');
