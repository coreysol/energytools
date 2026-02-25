<?php
/**
 * VPP Value Calculator logic based on Brattle (2023) "Real Reliability: The Value of Virtual Power"
 * Technical Appendix Table 4. Net cost = resource cost − system/societal benefits.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/brattle_constants.php';

/**
 * Compute net cost per MW-year for each resource and savings vs baseline.
 *
 * @param array $inputs Sanitized form inputs
 * @return array ['savings_per_mw_year', 'savings_per_mw_10yr', 'net_cost_vpp', 'net_cost_gas', 'net_cost_battery']
 */
function compute_savings_per_mw(array $inputs) {
    $ancillaryKey = !empty($inputs['include_ancillary']) ? 'with' : 'without';
    $tech = $inputs['tech_cost'] ?? 'base';
    $renewables = $inputs['renewables'] ?? 'base';
    $batteryConfig = $inputs['battery_config'] ?? 'base';

    $gasNetCost = BRATTLE_NET_COST_GAS_PEAKER_BASE
        * BRATTLE_TECH_COST_MULTIPLIER_GAS[$tech]
        * BRATTLE_RENEWABLES_MULTIPLIER_GAS[$renewables]
        * BRATTLE_ANCILLARY_MULTIPLIER_GAS[$ancillaryKey];

    $batteryNetCost = BRATTLE_NET_COST_BATTERY_BASE
        * BRATTLE_TECH_COST_MULTIPLIER_BATTERY[$tech]
        * BRATTLE_RENEWABLES_MULTIPLIER_BATTERY[$renewables]
        * BRATTLE_BATTERY_CONFIG_MULTIPLIER[$batteryConfig]
        * BRATTLE_ANCILLARY_MULTIPLIER_BATTERY[$ancillaryKey];

    $vppNetCostBeforeSocietal = BRATTLE_NET_COST_VPP_BASE
        * BRATTLE_TD_VPP_MULTIPLIER[$inputs['td_level'] ?? 'base']
        * BRATTLE_TECH_COST_MULTIPLIER_VPP[$tech]
        * BRATTLE_RENEWABLES_MULTIPLIER_VPP[$renewables]
        * BRATTLE_ANCILLARY_MULTIPLIER_VPP[$ancillaryKey];

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

    $out = [
        'savings_per_mw_year'   => $savingsPerMwYear,
        'savings_per_mw_10yr'  => $savingsPerMw10yr,
        'net_cost_vpp'         => $vppNetCost,
        'net_cost_gas'         => $gasNetCost,
        'net_cost_battery'    => $batteryNetCost,
    ];

    $peakMw = isset($inputs['peak_demand']) && isset($inputs['pct_peak_vpp'])
        && $inputs['peak_demand'] !== null && $inputs['pct_peak_vpp'] !== null
        ? (float) $inputs['peak_demand'] * ((float) $inputs['pct_peak_vpp'] / 100) : null;
    if ($peakMw !== null) {
        $out['vpp_mw'] = $peakMw;
        $out['total_savings_year'] = $savingsPerMwYear * $peakMw;
        $out['total_savings_10yr'] = $savingsPerMw10yr * $peakMw;
    } else {
        $out['vpp_mw'] = null;
        $out['total_savings_year'] = null;
        $out['total_savings_10yr'] = null;
    }

    return $out;
}
