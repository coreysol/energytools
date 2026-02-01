<?php
/**
 * Data fetcher for external APIs and static data
 */

require_once __DIR__ . '/config.php';

/**
 * Get climate zone data based on zip code
 * 
 * @param string $zip_code 5-digit zip code
 * @return array Climate data including temperature patterns
 */
function getClimateData($zip_code) {
    // TODO: Implement actual API integration or static data lookup
    // For now, return default climate zone data
    
    // Simple mapping: Use first digit of zip code as rough climate indicator
    $first_digit = (int)substr($zip_code, 0, 1);
    
    // Default to moderate climate (zone 5)
    $climate_zone = 5;
    
    if ($first_digit >= 0 && $first_digit <= 2) {
        $climate_zone = 1; // Northeast - Cold
    } elseif ($first_digit >= 3 && $first_digit <= 4) {
        $climate_zone = 2; // Southeast - Warm/Humid
    } elseif ($first_digit >= 5 && $first_digit <= 6) {
        $climate_zone = 3; // Midwest - Moderate
    } elseif ($first_digit >= 7 && $first_digit <= 8) {
        $climate_zone = 4; // Mountain/West - Dry
    } else {
        $climate_zone = 5; // West Coast - Moderate
    }
    
    // Return typical temperature patterns by season
    return [
        'zone' => $climate_zone,
        'spring_avg_temp' => 60 + ($climate_zone * 5),
        'summer_avg_temp' => 75 + ($climate_zone * 5),
        'fall_avg_temp' => 60 + ($climate_zone * 5),
        'winter_avg_temp' => 40 + ($climate_zone * 5),
        'cooling_degree_days' => max(0, ($climate_zone - 2) * 500),
        'heating_degree_days' => max(0, (6 - $climate_zone) * 500)
    ];
}

/**
 * Get base residential load profile
 * Returns hourly load factors (0-1) representing typical residential patterns
 * 
 * @return array Hourly load factors for weekdays and weekends
 */
function getBaseLoadProfile() {
    // Typical residential hourly load pattern (as percentage of daily average)
    // Values represent relative load throughout the day
    // Peak is typically in evening (6-9 PM)
    
    return [
        'weekday' => [
            0 => 0.4,  1 => 0.35, 2 => 0.35, 3 => 0.35, 4 => 0.35, 5 => 0.4,
            6 => 0.5,  7 => 0.7,  8 => 0.8,  9 => 0.6,  10 => 0.55, 11 => 0.55,
            12 => 0.6, 13 => 0.6, 14 => 0.65, 15 => 0.7, 16 => 0.75, 17 => 0.85,
            18 => 1.0, 19 => 1.1, 20 => 1.0, 21 => 0.9, 22 => 0.75, 23 => 0.6
        ],
        'weekend' => [
            0 => 0.4,  1 => 0.35, 2 => 0.35, 3 => 0.35, 4 => 0.35, 5 => 0.4,
            6 => 0.45, 7 => 0.5,  8 => 0.6,  9 => 0.7,  10 => 0.75, 11 => 0.8,
            12 => 0.85, 13 => 0.85, 14 => 0.9, 15 => 0.95, 16 => 1.0, 17 => 1.05,
            18 => 1.1, 19 => 1.15, 20 => 1.05, 21 => 0.95, 22 => 0.8, 23 => 0.65
        ]
    ];
}

/**
 * Cache data to reduce API calls
 * 
 * @param string $key Cache key
 * @param mixed $data Data to cache
 * @param int $expiry Expiry time in seconds
 */
function cacheData($key, $data, $expiry = CACHE_EXPIRY) {
    if (!CACHE_ENABLED) return;
    
    $cache_file = CACHE_DIR . '/' . md5($key) . '.cache';
    $cache_data = [
        'data' => $data,
        'expiry' => time() + $expiry
    ];
    
    file_put_contents($cache_file, serialize($cache_data));
}

/**
 * Get cached data
 * 
 * @param string $key Cache key
 * @return mixed|false Cached data or false if not found/expired
 */
function getCachedData($key) {
    if (!CACHE_ENABLED) return false;
    
    $cache_file = CACHE_DIR . '/' . md5($key) . '.cache';
    
    if (!file_exists($cache_file)) {
        return false;
    }
    
    $cache_data = unserialize(file_get_contents($cache_file));
    
    if ($cache_data['expiry'] < time()) {
        unlink($cache_file);
        return false;
    }
    
    return $cache_data['data'];
}
