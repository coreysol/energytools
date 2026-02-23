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
];

$errors = [];
if (isset($_GET['errors'])) {
    $errors = json_decode(urldecode($_GET['errors']), true) ?? [];
}
foreach (['include_resilience', 'include_emissions', 'include_ancillary', 'comparison_baseline', 'carbon_price', 'td_level', 'tech_cost', 'renewables', 'battery_config'] as $k) {
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
            <p class="subtitle">Estimate utility savings per MW of virtual power plants deployed (<a href="https://www.brattle.com/wp-content/uploads/2023/04/Real-Reliability-The-Value-of-Virtual-Power_5.3.2023.pdf" target="_blank" rel="noopener noreferrer">Brattle report, 2023</a>)</p>
        </header>

        <main>
            <form method="POST" action="<?php echo htmlspecialchars(BASE_PATH); ?>/process.php" id="vppForm">
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="include_resilience" name="include_resilience" value="1" <?php echo $defaults['include_resilience'] ? 'checked' : ''; ?>>
                        <span>Include resilience benefits <span class="tooltip-trigger" title="When on, adds the report's estimated value of avoided distribution outages from behind-the-meter batteries (backup during outages). Default: off (utility-only perspective).">?</span></span>
                    </label>
                    <small>Default: off (From Brattle report (2023)—utility perspective)</small>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="include_emissions" name="include_emissions" value="1" <?php echo $defaults['include_emissions'] ? 'checked' : ''; ?>>
                        <span>Include emissions benefits <span class="tooltip-trigger" title="When on, adds the value of reduced GHG emissions at the report's carbon price. Default: off (utility-only perspective).">?</span></span>
                    </label>
                    <small>Default: off (From Brattle report (2023))</small>
                </div>

                <div class="form-group">
                    <label for="comparison_baseline" class="label-with-tooltip">Comparison baseline
                        <span class="tooltip-trigger" title="Gas peaker = compare to a new gas combustion turbine; Battery = compare to transmission-connected utility-scale battery; Average = compare to the average of both.">?</span>
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
                        <span class="tooltip-trigger" title="Social cost of carbon in $/metric ton CO₂. Report base case: $100. Used when 'Include emissions benefits' is on.">?</span>
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
                        <span class="tooltip-trigger" title="Base = report base; High/Low = sensitivity cases for value of deferred T&D investment from distributed VPP resources.">?</span>
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
                        <span class="tooltip-trigger" title="Base = report base-case technology costs; 2030 trends = sensitivity with assumed future cost declines.">?</span>
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
                        <span class="tooltip-trigger" title="Base = report's 50% renewables illustrative system; Business-as-usual = sensitivity with lower renewables.">?</span>
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
                        <span class="tooltip-trigger" title="Applies to the utility-scale battery baseline: Base = 4-hour/6-hour mix in report; Alternative = different duration mix from sensitivity.">?</span>
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
                        <span>Include ancillary services value <span class="tooltip-trigger" title="When on, net cost subtracts value from providing spinning reserves etc. Report base case includes this. Turn off for 'energy only' sensitivity.">?</span></span>
                    </label>
                    <small>Default: on (From Brattle report (2023))</small>
                </div>

                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn-primary">Calculate savings per MW</button>
                </div>
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
