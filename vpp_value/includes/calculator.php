<?php
/**
 * VPP Value Calculator logic based on Brattle (2023) "Real Reliability: The Value of Virtual Power"
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/brattle_constants.php';

/**
 * Compute net cost per MW-year for each resource and savings vs baseline.
 *
 * @param array $inputs Sanitized form inputs (include_resilience, include_emissions, comparison_baseline, carbon_price, td_level, tech_cost, renewables, battery_config, include_ancillary)
 * @return array ['savings_per_mw_year' => float, 'savings_per_mw_10yr' => float, 'net_cost_vpp' => float, 'net_cost_gas' => float, 'net_cost_battery' => float]
 */
function compute_savings_per_mw(array $inputs) {
    $ancillaryMult = !empty($inputs['include_ancillary'])
        ? BRATTLE_ANCILLARY_MULTIPLIER_WITH
        : BRATTLE_ANCILLARY_MULTIPLIER_WITHOUT;

    $gasNetCost = BRATTLE_NET_COST_GAS_PEAKER_BASE
        * BRATTLE_TECH_COST_MULTIPLIER[$inputs['tech_cost']]
        * BRATTLE_RENEWABLES_MULTIPLIER[$inputs['renewables']]
        * $ancillaryMult;

    $batteryNetCost = BRATTLE_NET_COST_BATTERY_BASE
        * BRATTLE_TECH_COST_MULTIPLIER[$inputs['tech_cost']]
        * BRATTLE_RENEWABLES_MULTIPLIER[$inputs['renewables']]
        * BRATTLE_BATTERY_CONFIG_MULTIPLIER[$inputs['battery_config']]
        * $ancillaryMult;

    $vppNetCostBeforeSocietal = BRATTLE_NET_COST_VPP_BASE
        * BRATTLE_TD_VPP_MULTIPLIER[$inputs['td_level']]
        * BRATTLE_TECH_COST_MULTIPLIER[$inputs['tech_cost']]
        * BRATTLE_RENEWABLES_MULTIPLIER[$inputs['renewables']]
        * $ancillaryMult;

    $societalValue = 0;
    if (!empty($inputs['include_resilience'])) {
        $societalValue += BRATTLE_RESILIENCE_VALUE_PER_MW_YR;
    }
    if (!empty($inputs['include_emissions'])) {
        $carbonScale = ($inputs['carbon_price'] ?? BRATTLE_CARBON_PRICE_BASE) / BRATTLE_CARBON_PRICE_REF;
        $societalValue += BRATTLE_EMISSIONS_VALUE_PER_MW_YR * $carbonScale;
    }

    $vppNetCost = max(0, $vppNetCostBeforeSocietal - $societalValue);

    $baseline = $inputs['comparison_baseline'] ?? 'average';
    if ($baseline === 'gas') {
        $alternativeNetCost = $gasNetCost;
    } elseif ($baseline === 'battery') {
        $alternativeNetCost = $batteryNetCost;
    } else {
        $alternativeNetCost = ($gasNetCost + $batteryNetCost) / 2;
    }

    $savingsPerMwYear = $alternativeNetCost - $vppNetCost;
    $savingsPerMw10yr = $savingsPerMwYear * 10;

    return [
        'savings_per_mw_year'   => $savingsPerMwYear,
        'savings_per_mw_10yr'  => $savingsPerMw10yr,
        'net_cost_vpp'         => $vppNetCost,
        'net_cost_gas'         => $gasNetCost,
        'net_cost_battery'    => $batteryNetCost,
    ];
}
