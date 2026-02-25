<?php
/**
 * Constants from Brattle Group (2023) "Real Reliability: The Value of Virtual Power"
 * Volume II: Technical Appendix, Table 4. All values converted from $million/year (400 MW)
 * to $/MW-year by multiplying by 1e6/400 = 2500.
 * Prepared for Google. 2022 dollars.
 */

// Base-case net cost per MW-year ($/MW/year), Table 4 "Net Resource Adequacy Cost (System)" base case
const BRATTLE_NET_COST_GAS_PEAKER_BASE = 103500;   // $41.40M / 400 MW
const BRATTLE_NET_COST_BATTERY_BASE    = 68675;    // $27.47M / 400 MW
const BRATTLE_NET_COST_VPP_BASE        = 42850;    // $17.14M / 400 MW

// VPP societal benefits (Table 4 "Societal Cost Impact" for VPP base case; negative = benefit)
// Emissions: -$14.76M → $36,900/MW-yr. Resilience: -$0.16M → $400/MW-yr. Total ~$37,300/MW-yr.
const BRATTLE_EMISSIONS_VALUE_PER_MW_YR  = 36900;  // $/MW-year at $100/metric ton
const BRATTLE_RESILIENCE_VALUE_PER_MW_YR = 400;    // $/MW-year (Table 4)

// Carbon price base case ($/metric ton CO2), Technical Appendix Section I/II
const BRATTLE_CARBON_PRICE_BASE = 100;
const BRATTLE_CARBON_PRICE_REF  = 100;

// T&D sensitivity: Table 4 "Higher T&D Cost" and "Lower T&D Cost" (VPP only)
// High T&D: VPP net $6.64M vs base $17.14M → 6.64/17.14 = 0.387
// Low T&D:  VPP net $27.64M vs base $17.14M → 27.64/17.14 = 1.612
const BRATTLE_TD_VPP_MULTIPLIER = [
    'low'  => 1.612,
    'base' => 1.0,
    'high' => 0.387,
];

// 2030 technology cost: Table 4 "2030 Tech Cost Trend" (each resource different)
// Gas 37.67/41.40=0.910, Battery 7.90/27.47=0.288, VPP 13.88/17.14=0.810
const BRATTLE_TECH_COST_MULTIPLIER_GAS     = ['base' => 1.0, '2030' => 0.910];
const BRATTLE_TECH_COST_MULTIPLIER_BATTERY = ['base' => 1.0, '2030' => 0.288];
const BRATTLE_TECH_COST_MULTIPLIER_VPP     = ['base' => 1.0, '2030' => 0.810];

// BAU renewables: Table 4 "BAU Renewables Deployment"
// Gas 40.07/41.40=0.968, Battery 26.76/27.47=0.974, VPP 15.46/17.14=0.902
const BRATTLE_RENEWABLES_MULTIPLIER_GAS     = ['base' => 1.0, 'bau' => 0.968];
const BRATTLE_RENEWABLES_MULTIPLIER_BATTERY = ['base' => 1.0, 'bau' => 0.974];
const BRATTLE_RENEWABLES_MULTIPLIER_VPP     = ['base' => 1.0, 'bau' => 0.902];

// Energy only (no ancillary): Table 4 "Energy Value Only"
// Gas 41.61/41.40=1.005, Battery 42.06/27.47=1.531, VPP 17.89/17.14=1.044
const BRATTLE_ANCILLARY_MULTIPLIER_GAS     = ['with' => 1.0, 'without' => 1.005];
const BRATTLE_ANCILLARY_MULTIPLIER_BATTERY = ['with' => 1.0, 'without' => 1.531];
const BRATTLE_ANCILLARY_MULTIPLIER_VPP     = ['with' => 1.0, 'without' => 1.044];

// Alternative battery (4-hr only): Table 4 "4-hr Storage". Battery net $15.01M vs base $27.47M → 15.01/27.47 = 0.546
const BRATTLE_BATTERY_CONFIG_MULTIPLIER = [
    'base' => 1.0,
    'alt'  => 0.546,
];
