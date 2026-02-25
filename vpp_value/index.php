<?php
/**
 * VPP Value Calculator - Main form
 * Based on Brattle Group (2023) "Real Reliability: The Value of Virtual Power"
 */

require_once __DIR__ . '/includes/config.php';

$defaults = [
    'include_resilience'   => false,
    'include_emissions'    => false,
    'include_ancillary'    => true,
    'comparison_baseline'  => 'average',
    'carbon_price'        => '100',
    'td_level'            => 'base',
    'tech_cost'           => 'base',
    'renewables'          => 'base',
    'battery_config'      => 'base',
    'peak_demand'          => '',
    'pct_peak_vpp'         => '',
];

$errors = [];
if (isset($_GET['errors'])) {
    $errors = json_decode(urldecode($_GET['errors']), true) ?? [];
}
foreach (['include_resilience', 'include_emissions', 'include_ancillary', 'comparison_baseline', 'carbon_price', 'td_level', 'tech_cost', 'renewables', 'battery_config', 'peak_demand', 'pct_peak_vpp'] as $k) {
    if (isset($_GET[$k])) {
        if (in_array($k, ['include_resilience', 'include_emissions', 'include_ancillary'], true)) {
            $defaults[$k] = (bool) $_GET[$k];
        } else {
            $defaults[$k] = $_GET[$k];
        }
    }
}
$base = BASE_PATH . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo htmlspecialchars($base); ?>">
    <title>VPP Value Calculator</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_PATH); ?>/assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>VPP Value Calculator</h1>
            <p class="subtitle">Estimate utility savings per MW of virtual power plants deployed. (<a href="https://www.brattle.com/wp-content/uploads/2023/04/Real-Reliability-The-Value-of-Virtual-Power_5.3.2023.pdf" target="_blank" rel="noopener noreferrer">Brattle report, 2023</a>)</p>
        </header>
        <main>
            <form method="POST" action="<?php echo htmlspecialchars(BASE_PATH); ?>/process.php" id="vppForm">
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="include_resilience" name="include_resilience" value="1" <?php echo $defaults['include_resilience'] ? 'checked' : ''; ?>>
                        <span>Include resilience benefits <span class="tooltip-trigger" title="When on, subtracts the report's incremental resilience value from VPP net cost (value of avoided distribution outages from behind-the-meter batteries providing backup during outages). Technical Appendix Table 4 reports this as part of VPP 'Societal Cost Impact' (~$400/MW-year in base case). Default: off for utility-only perspective.">?</span></span>
                    </label>
                    <small>Default: off (From Brattle report (2023)—utility perspective)</small>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="include_emissions" name="include_emissions" value="1" <?php echo $defaults['include_emissions'] ? 'checked' : ''; ?>>
                        <span>Include emissions benefits <span class="tooltip-trigger" title="When on, subtracts the report's incremental emissions value from VPP net cost (reduced GHG emissions valued at the carbon price). Technical Appendix Table 4 reports this in VPP 'Societal Cost Impact' (~$36,900/MW-year at $100/metric ton); the calculator scales this value linearly with carbon price. Default: off for utility-only perspective.">?</span></span>
                    </label>
                    <small>Default: off (From Brattle report (2023))</small>
                </div>

                <div class="form-group">
                    <label for="comparison_baseline" class="label-with-tooltip">Comparison baseline
                        <span class="tooltip-trigger" title="Table 4 'Net Resource Adequacy Cost (System)' base case: Gas peaker = compare to a new gas combustion turbine; Utility-scale battery = compare to transmission-connected battery; Average = compare to the average of both. Savings = (selected alternative net cost per MW) − (VPP net cost per MW).">?</span>
                    </label>
                    <select id="comparison_baseline" name="comparison_baseline">
                        <option value="gas" <?php echo $defaults['comparison_baseline'] === 'gas' ? 'selected' : ''; ?>>Gas peaker</option>
                        <option value="battery" <?php echo $defaults['comparison_baseline'] === 'battery' ? 'selected' : ''; ?>>Utility-scale battery</option>
                        <option value="average" <?php echo $defaults['comparison_baseline'] === 'average' ? 'selected' : ''; ?>>Average of both</option>
                    </select>
                    <?php if (isset($errors['comparison_baseline'])): ?>
                        <span class="error"><?php echo htmlspecialchars($errors['comparison_baseline']); ?></span>
                    <?php endif; ?>
                    <small>Default: Average (From Brattle report (2023))</small>
                </div>

                <div class="accordion" id="additional-inputs-accordion">
                    <button type="button" class="accordion-header" id="accordion-toggle" aria-expanded="false" aria-controls="accordion-panel">
                        <span class="accordion-icon" aria-hidden="true">+</span>
                        <span class="accordion-label">Additional inputs to customize</span>
                    </button>
                    <div class="accordion-panel" id="accordion-panel" role="region" aria-labelledby="accordion-toggle" hidden>
                <div class="form-group">
                    <label for="carbon_price" class="label-with-tooltip">Carbon price ($/metric ton CO₂)
                        <span class="tooltip-trigger" title="Social cost of carbon in $/metric ton CO₂. Technical Appendix base case: $100. Table 4 VPP emissions benefit is at this price; when 'Include emissions benefits' is on, the calculator scales that benefit proportionally (e.g. $200/ton → 2× the base emissions value).">?</span>
                    </label>
                    <input type="number" id="carbon_price" name="carbon_price" value="<?php echo htmlspecialchars($defaults['carbon_price']); ?>"
                           min="0" max="500" step="1">
                    <?php if (isset($errors['carbon_price'])): ?>
                        <span class="error"><?php echo htmlspecialchars($errors['carbon_price']); ?></span>
                    <?php endif; ?>
                    <small>Default: 100 (From Brattle report (2023))</small>
                </div>

                <div class="form-group">
                    <label for="td_level" class="label-with-tooltip">T&amp;D cost level
                        <span class="tooltip-trigger" title="Table 4 T&amp;D sensitivity (VPP only): Base = report base. Higher T&amp;D Cost = more value from deferred distribution investment (lower VPP net cost). Lower T&amp;D Cost = less deferral value (higher VPP net cost). Does not change gas or battery net costs.">?</span>
                    </label>
                    <select id="td_level" name="td_level">
                        <option value="base" <?php echo $defaults['td_level'] === 'base' ? 'selected' : ''; ?>>Base</option>
                        <option value="high" <?php echo $defaults['td_level'] === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="low" <?php echo $defaults['td_level'] === 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                    <?php if (isset($errors['td_level'])): ?>
                        <span class="error"><?php echo htmlspecialchars($errors['td_level']); ?></span>
                    <?php endif; ?>
                    <small>Default: Base (From Brattle report (2023))</small>
                </div>

                <div class="form-group">
                    <label for="tech_cost" class="label-with-tooltip">Technology cost scenario
                        <span class="tooltip-trigger" title="Table 4 '2030 Tech Cost Trend' sensitivity: Base = report base-case technology costs. 2030 trends = assumed future cost declines; each resource has a different multiplier (gas ~0.91×, battery ~0.29×, VPP ~0.81×). Use this to approximate a 2030 cost view, as in the DOE Liftoff report, but it is a simple sensitivity, not DOE's full scenario modeling.">?</span>
                    </label>
                    <select id="tech_cost" name="tech_cost">
                        <option value="base" <?php echo $defaults['tech_cost'] === 'base' ? 'selected' : ''; ?>>Base</option>
                        <option value="2030" <?php echo $defaults['tech_cost'] === '2030' ? 'selected' : ''; ?>>2030 trends</option>
                    </select>
                    <?php if (isset($errors['tech_cost'])): ?>
                        <span class="error"><?php echo htmlspecialchars($errors['tech_cost']); ?></span>
                    <?php endif; ?>
                    <small>Default: Base (From Brattle report (2023))</small>
                </div>

                <div class="form-group">
                    <label for="renewables" class="label-with-tooltip">Renewables deployment
                        <span class="tooltip-trigger" title="Table 4 'BAU Renewables Deployment' sensitivity: Base = report's 50% renewables illustrative system. Business-as-usual = lower renewables deployment; all three resources (gas, battery, VPP) have slightly lower net costs in this case (~97% for gas, ~97% for battery, ~90% for VPP).">?</span>
                    </label>
                    <select id="renewables" name="renewables">
                        <option value="base" <?php echo $defaults['renewables'] === 'base' ? 'selected' : ''; ?>>Base</option>
                        <option value="bau" <?php echo $defaults['renewables'] === 'bau' ? 'selected' : ''; ?>>Business-as-usual</option>
                    </select>
                    <?php if (isset($errors['renewables'])): ?>
                        <span class="error"><?php echo htmlspecialchars($errors['renewables']); ?></span>
                    <?php endif; ?>
                    <small>Default: Base (From Brattle report (2023))</small>
                </div>

                <div class="form-group">
                    <label for="battery_config" class="label-with-tooltip">Battery configuration (for alternative)
                        <span class="tooltip-trigger" title="Table 4 battery sensitivity (utility-scale battery alternative only): Base = 4-hour/6-hour duration mix in report. Alternative = '4-hr Storage' column—4-hour storage only; lower net cost per MW but report notes this configuration does not fully meet resource adequacy in their analysis.">?</span>
                    </label>
                    <select id="battery_config" name="battery_config">
                        <option value="base" <?php echo $defaults['battery_config'] === 'base' ? 'selected' : ''; ?>>Base</option>
                        <option value="alt" <?php echo $defaults['battery_config'] === 'alt' ? 'selected' : ''; ?>>Alternative</option>
                    </select>
                    <?php if (isset($errors['battery_config'])): ?>
                        <span class="error"><?php echo htmlspecialchars($errors['battery_config']); ?></span>
                    <?php endif; ?>
                    <small>Default: Base (From Brattle report (2023))</small>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="include_ancillary" name="include_ancillary" value="1" <?php echo $defaults['include_ancillary'] ? 'checked' : ''; ?>>
                        <span>Include ancillary services value <span class="tooltip-trigger" title="Table 4 base case includes value from providing ancillary services (e.g. spinning reserves). When off = 'Energy Value Only' sensitivity: net cost does not subtract ancillary value, so net costs rise (e.g. battery ~1.53×, VPP ~1.04×). Use off to approximate energy-only valuation.">?</span></span>
                    </label>
                    <small>Default: on (From Brattle report (2023))</small>
                </div>

                <div class="form-group">
                    <label for="peak_demand" class="label-with-tooltip">Peak demand expected (MW)
                        <span class="tooltip-trigger" title="Optional. Your utility's expected peak demand in MW. If provided with % of peak below, total savings are calculated. DOE Liftoff assumes U.S. peak demand ~800–900 GW by 2030; you can enter your system peak or use that range for context.">?</span>
                    </label>
                    <input type="number" id="peak_demand" name="peak_demand" value="<?php echo htmlspecialchars($defaults['peak_demand']); ?>"
                           min="0" step="0.1" placeholder="Leave blank for $/MW only">
                    <?php if (isset($errors['peak_demand'])): ?>
                        <span class="error"><?php echo htmlspecialchars($errors['peak_demand']); ?></span>
                    <?php endif; ?>
                    <small>Optional. Provide with % below to see total savings.</small>
                </div>

                <div class="form-group">
                    <label for="pct_peak_vpp" class="label-with-tooltip">% of peak demand to be covered by VPPs
                        <span class="tooltip-trigger" title="Optional. Percentage of peak demand you expect to meet with VPPs. VPP capacity (MW) = peak demand × this % ÷ 100. DOE Liftoff uses 10–20% of peak by 2030; provide this together with peak demand to see total savings.">?</span>
                    </label>
                    <input type="number" id="pct_peak_vpp" name="pct_peak_vpp" value="<?php echo htmlspecialchars($defaults['pct_peak_vpp']); ?>"
                           min="0" max="100" step="0.1" placeholder="Leave blank for $/MW only">
                    <?php if (isset($errors['pct_peak_vpp'])): ?>
                        <span class="error"><?php echo htmlspecialchars($errors['pct_peak_vpp']); ?></span>
                    <?php endif; ?>
                    <small>Optional. 0–100. Provide with peak demand above to see total savings.</small>
                </div>

                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn-primary">Calculate savings per MW</button>
                </div>
                <p class="form-note">Methods and assumptions will be displayed on the results page. Results are based on the model in the Brattle report and do not reflect actual value calculated with specificity for a particular utility area.</p>
            </form>
        </main>

        <footer>
            <p>Based on <a href="https://www.brattle.com/wp-content/uploads/2023/04/Real-Reliability-The-Value-of-Virtual-Power_5.3.2023.pdf" target="_blank" rel="noopener noreferrer">Brattle Group (2023), &ldquo;Real Reliability: The Value of Virtual Power,&rdquo; prepared for Google</a>.</p>
        </footer>
    </div>
    <script src="<?php echo htmlspecialchars(BASE_PATH); ?>/assets/js/tooltips.js"></script>
    <script src="<?php echo htmlspecialchars(BASE_PATH); ?>/assets/js/accordion.js"></script>
</body>
</html>
