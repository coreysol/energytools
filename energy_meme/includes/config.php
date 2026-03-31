<?php
/**
 * Energy Meme Generator - Configuration
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('America/New_York');

$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$script_dir  = $script_name !== '' ? str_replace('\\', '/', dirname($script_name)) : '';
define('BASE_PATH', $script_dir !== '' ? rtrim($script_dir, '/') : '');
