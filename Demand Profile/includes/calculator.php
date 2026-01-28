<?php
/**
 * Demand calculation engine
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/data_fetcher.php';

/**
 * Generate 15-minute interval demand data for a full year
 * 
 * @param array $inputs User inputs (zip_code, annual_kwh, has_ac, has_heating, has_wfh, has_ev)
 * @return array Array of 15-minute interval data points
 */
function generateDemandProfile($inputs) {
    $annual_kwh = (float)$inputs['annual_kwh'];
    $zip_code = $inputs['zip_code'];
    
    // Get climate data
    $climate = getClimateData($zip_code);
    
    // Get base load profile
    $base_profile = getBaseLoadProfile();
    
    // Calculate average daily kWh
    $avg_daily_kwh = $annual_kwh / DAYS_PER_YEAR;
    
    // Calculate average hourly kWh
    $avg_hourly_kwh = $avg_daily_kwh / HOURS_PER_DAY;
    
    // Calculate average kW (power) per hour
    $avg_hourly_kw = $avg_hourly_kwh; // For hourly average
    
    // Generate data for full year
    $data = [];
    $start_date = new DateTime('2024-01-01 00:00:00');
    
    for ($day = 0; $day < DAYS_PER_YEAR; $day++) {
        $current_date = clone $start_date;
        $current_date->modify("+{$day} days");
        
        $day_of_week = (int)$current_date->format('w'); // 0 = Sunday, 6 = Saturday
        $is_weekend = ($day_of_week == 0 || $day_of_week == 6);
        $season = getSeason($current_date);
        
        // Get appropriate load profile
        $hourly_profile = $is_weekend ? $base_profile['weekend'] : $base_profile['weekday'];
        
        // Generate 15-minute intervals for this day
        for ($hour = 0; $hour < HOURS_PER_DAY; $hour++) {
            for ($interval = 0; $interval < INTERVALS_PER_HOUR; $interval++) {
                $minute = $interval * INTERVAL_MINUTES;
                
                // Base load for this hour
                $base_kw = $avg_hourly_kw * $hourly_profile[$hour];
                
                // Apply seasonal adjustments
                $adjusted_kw = applySeasonalAdjustments(
                    $base_kw,
                    $season,
                    $hour,
                    $climate,
                    $inputs
                );
                
                // Apply work-from-home adjustments
                if ($inputs['has_wfh'] && !$is_weekend && $hour >= 9 && $hour < 17) {
                    $adjusted_kw *= 1.3; // 30% increase during work hours
                }
                
                // Apply EV charging adjustments
                if ($inputs['has_ev']) {
                    $adjusted_kw += getEVChargingLoad($hour, $is_weekend);
                }
                
                // Create timestamp
                $timestamp = clone $current_date;
                $timestamp->setTime($hour, $minute, 0);
                
                $data[] = [
                    'timestamp' => $timestamp,
                    'date' => $timestamp->format('Y-m-d'),
                    'time' => $timestamp->format('H:i'),
                    'hour' => $hour,
                    'minute' => $minute,
                    'kw' => max(0, $adjusted_kw) // Ensure non-negative
                ];
            }
        }
    }
    
    // Normalize to match annual kWh
    $total_kwh = 0;
    foreach ($data as $point) {
        $total_kwh += $point['kw'] * HOURS_PER_INTERVAL;
    }
    
    $normalization_factor = $annual_kwh / $total_kwh;
    
    foreach ($data as &$point) {
        $point['kw'] *= $normalization_factor;
    }
    
    return $data;
}

/**
 * Get season based on date
 * 
 * @param DateTime $date
 * @return string Season name
 */
function getSeason($date) {
    $month = (int)$date->format('n');
    
    if ($month >= 3 && $month <= 5) {
        return 'spring';
    } elseif ($month >= 6 && $month <= 8) {
        return 'summer';
    } elseif ($month >= 9 && $month <= 11) {
        return 'fall';
    } else {
        return 'winter';
    }
}

/**
 * Apply seasonal adjustments to base load
 * 
 * @param float $base_kw Base kW load
 * @param string $season Season name
 * @param int $hour Hour of day (0-23)
 * @param array $climate Climate data
 * @param array $inputs User inputs
 * @return float Adjusted kW
 */
function applySeasonalAdjustments($base_kw, $season, $hour, $climate, $inputs) {
    $adjusted = $base_kw;
    
    // Summer AC adjustments
    if ($season === 'summer' && $inputs['has_ac']) {
        // Peak cooling hours: 2 PM - 6 PM (hours 14-17)
        if ($hour >= 14 && $hour <= 17) {
            $cooling_factor = 1.0 + ($climate['cooling_degree_days'] / 2000) * 0.5;
            $adjusted += $base_kw * $cooling_factor;
        } elseif ($hour >= 12 && $hour <= 20) {
            // Moderate cooling during extended hot hours
            $cooling_factor = 1.0 + ($climate['cooling_degree_days'] / 2000) * 0.3;
            $adjusted += $base_kw * $cooling_factor * 0.5;
        }
    }
    
    // Winter heating adjustments
    if ($season === 'winter' && $inputs['has_heating']) {
        // Peak heating hours: early morning (5-8 AM) and evening (6-10 PM)
        if (($hour >= 5 && $hour <= 8) || ($hour >= 18 && $hour <= 21)) {
            $heating_factor = 1.0 + ($climate['heating_degree_days'] / 2000) * 0.6;
            $adjusted += $base_kw * $heating_factor;
        } elseif ($hour >= 0 && $hour <= 6) {
            // Overnight heating
            $heating_factor = 1.0 + ($climate['heating_degree_days'] / 2000) * 0.4;
            $adjusted += $base_kw * $heating_factor * 0.7;
        }
    }
    
    return $adjusted;
}

/**
 * Get EV charging load for a given hour
 * 
 * @param int $hour Hour of day (0-23)
 * @param bool $is_weekend Whether it's a weekend
 * @return float Additional kW from EV charging
 */
function getEVChargingLoad($hour, $is_weekend) {
    // Typical EV charging: overnight (10 PM - 6 AM) or evening (6 PM - 10 PM)
    // Level 2 charger: 7-11 kW, using 7 kW as average
    
    $ev_kw = 0;
    $charging_rate = 7.0; // kW
    
    // Evening charging (6 PM - 10 PM) - more common on weekdays
    if (!$is_weekend && $hour >= 18 && $hour < 22) {
        // 60% chance of charging during this window
        if (rand(1, 100) <= 60) {
            $ev_kw = $charging_rate;
        }
    }
    
    // Overnight charging (10 PM - 6 AM)
    if ($hour >= 22 || $hour < 6) {
        // 80% chance of charging overnight
        if (rand(1, 100) <= 80) {
            $ev_kw = $charging_rate;
        }
    }
    
    return $ev_kw;
}

/**
 * Aggregate data by season and hour for graph display
 * 
 * @param array $data Full year 15-minute interval data
 * @return array Seasonal hourly averages
 */
function aggregateSeasonalData($data) {
    $seasonal_data = [
        'spring' => array_fill(0, 24, []),
        'summer' => array_fill(0, 24, []),
        'fall' => array_fill(0, 24, []),
        'winter' => array_fill(0, 24, [])
    ];
    
    foreach ($data as $point) {
        $date = new DateTime($point['date']);
        $season = getSeason($date);
        $hour = $point['hour'];
        
        $seasonal_data[$season][$hour][] = $point['kw'];
    }
    
    // Calculate averages
    $averages = [];
    foreach ($seasonal_data as $season => $hours) {
        $averages[$season] = [];
        foreach ($hours as $hour => $values) {
            if (count($values) > 0) {
                $averages[$season][$hour] = array_sum($values) / count($values);
            } else {
                $averages[$season][$hour] = 0;
            }
        }
    }
    
    return $averages;
}
