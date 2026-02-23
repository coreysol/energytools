<?php
/**
 * Constants derived from Brattle Group (2023) "Real Reliability: The Value of Virtual Power"
 * Prepared for Google. Report uses 400 MW resource adequacy, 2022 dollars.
 * Where exact exhibit values are not available, values are derived from report ranges
 * (e.g. VPP net cost ~40-60% of gas peaker, ~60% of battery; $37/kW-yr societal value).
 */

// Base-case net cost per MW-year ($/MW/year) for providing resource adequacy
// Derived from report: VPP ~40-60% of gas, ~60% of battery; 60 GW over 10 yr → $15-35B savings
const BRATTLE_NET_COST_GAS_PEAKER_BASE = 90000;   // $/MW-year
const BRATTLE_NET_COST_BATTERY_BASE    = 75000;   // $/MW-year
const BRATTLE_NET_COST_VPP_BASE        = 42000;   // $/MW-year (~47% of gas, ~56% of battery)

// Incremental societal value for VPP ($/MW-year) when emissions and/or resilience included
// Report: ~$37/kW-yr total → $37,000/MW-yr; split for optional toggles
const BRATTLE_EMISSIONS_VALUE_PER_MW_YR  = 18000;  // $/MW-year (reduced GHG valued at carbon price)
const BRATTLE_RESILIENCE_VALUE_PER_MW_YR = 19000;  // $/MW-year (avoided outage value from BTM batteries)

// Carbon price used in report base case ($/metric ton CO2)
const BRATTLE_CARBON_PRICE_BASE = 100;

// Sensitivity multipliers (1.0 = base). Applied to net costs.
// T&D: High = VPP has more T&D deferral value (lower VPP net cost)
const BRATTLE_TD_VPP_MULTIPLIER = [
    'low'  => 1.15,
    'base' => 1.0,
    'high' => 0.82,
];

// Technology cost: 2030 trends = lower costs for VPP and battery
const BRATTLE_TECH_COST_MULTIPLIER = [
    'base'  => 1.0,
    '2030'  => 0.88,
];

// Renewables: BAU = lower renewables, different marginal value
const BRATTLE_RENEWABLES_MULTIPLIER = [
    'base' => 1.0,
    'bau'  => 1.08,
];

// Battery config (for utility-scale battery alternative only)
const BRATTLE_BATTERY_CONFIG_MULTIPLIER = [
    'base' => 1.0,
    'alt'  => 1.12,
];

// Ancillary services: when off, net cost is higher (no ancillary value subtracted)
const BRATTLE_ANCILLARY_MULTIPLIER_WITHOUT = 1.18;
const BRATTLE_ANCILLARY_MULTIPLIER_WITH    = 1.0;

// Carbon price scaling for emissions value (linear in $/metric ton vs base $100)
// So at $100, scale = 1.0; at $50, scale = 0.5
const BRATTLE_CARBON_PRICE_REF = 100;
