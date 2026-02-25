<?php
/**
 * VPP Value Calculator - Results page with bar chart and methods
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/brattle_constants.php';

session_start();

if (!isset($_SESSION['vpp_value_inputs']) || !isset($_SESSION['vpp_value_result'])) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

$inputs = $_SESSION['vpp_value_inputs'];
$result = $_SESSION['vpp_value_result'];

$savingsYear = $result['savings_per_mw_year'];
$savings10yr = $result['savings_per_mw_10yr'];
$baseline = $inputs['comparison_baseline'];

// Chart: which resources to show
$chartLabels = [];
$chartValues = [];
$chartColors = [];

$chartLabels[] = 'VPP';
$chartValues[] = round($result['net_cost_vpp'], 0);
$chartColors[] = 'rgba(247, 148, 29, 0.8)';

if ($baseline === 'gas' || $baseline === 'average') {
    $chartLabels[] = 'Gas peaker';
    $chartValues[] = round($result['net_cost_gas'], 0);
    $chartColors[] = 'rgba(100, 100, 100, 0.8)';
}
if ($baseline === 'battery' || $baseline === 'average') {
    $chartLabels[] = 'Utility-scale battery';
    $chartValues[] = round($result['net_cost_battery'], 0);
    $chartColors[] = 'rgba(33, 150, 243, 0.8)';
}

$baselineLabel = $baseline === 'gas' ? 'gas peaker' : ($baseline === 'battery' ? 'utility-scale battery' : 'average of gas peaker and battery');
$base = BASE_PATH . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo htmlspecialchars($base); ?>">
    <title>VPP Value Calculator – Results</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_PATH); ?>/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>VPP Value Calculator – Results</h1>
            <p class="subtitle">Savings per MW of VPP deployed (utility/ratepayer perspective)</p>
        </header>
        <main class="results-container">
            <div class="results-header">
                <h2>Your results</h2>
                <p>Comparison baseline: <strong><?php echo htmlspecialchars($baselineLabel); ?></strong>. Resource adequacy net costs in 2022 $/MW/year. Results are based on the model in the Brattle report and do not reflect actual value calculated with specificity for a particular utility area.</p>
            </div>

            <div class="savings-result">
                <div class="primary">$<?php echo number_format($savingsYear, 0); ?> per MW of VPP deployed saved per year</div>
                <div class="secondary">Over 10 years: $<?php echo number_format($savings10yr, 0); ?> per MW (undiscounted)</div>
                <div class="secondary" style="margin-top: 6px;">Use this value as a multiplier: multiply by your planned VPP capacity (MW) to estimate total savings.</div>
            </div>

            <div class="chart-wrapper">
                <h3>Net cost comparison ($/MW/year)</h3>
                <div class="chart-container">
                    <canvas id="vppChart"></canvas>
                </div>
                <p style="margin-top: 12px; color: #666; font-size: 0.95em;">Lower bar = lower net cost. Savings = difference between the alternative and VPP.</p>
            </div>

            <div class="selected-inputs-ref">
                <strong>Selected inputs:</strong>
                Resilience <?php echo !empty($inputs['include_resilience']) ? 'on' : 'off'; ?>;
                Emissions <?php echo !empty($inputs['include_emissions']) ? 'on' : 'off'; ?>;
                Comparison baseline: <?php echo htmlspecialchars($baselineLabel); ?>;
                Carbon price: $<?php echo (int) $inputs['carbon_price']; ?>/metric ton;
                T&amp;D: <?php echo ucfirst(htmlspecialchars($inputs['td_level'])); ?>;
                Tech cost: <?php echo $inputs['tech_cost'] === '2030' ? '2030 trends' : 'Base'; ?>;
                Renewables: <?php echo $inputs['renewables'] === 'bau' ? 'Business-as-usual' : 'Base'; ?>;
                Battery config: <?php echo $inputs['battery_config'] === 'alt' ? 'Alternative' : 'Base'; ?>;
                Ancillary services <?php echo !empty($inputs['include_ancillary']) ? 'on' : 'off'; ?>.
            </div>
            <div style="margin-top: 30px; text-align: center;">
                <a href="<?php echo htmlspecialchars(BASE_PATH); ?>/index.php" class="btn-primary">New calculation</a>
            </div>

            <div class="methods-container">
                <div class="methods-content">
                    <h2>Methods and assumptions</h2>

                    <section class="method-section">
                        <h3>Source</h3>
                        <p>This calculator is based on <a href="https://www.brattle.com/wp-content/uploads/2023/04/Real-Reliability-The-Value-of-Virtual-Power_5.3.2023.pdf" target="_blank" rel="noopener noreferrer">The Brattle Group (2023), <em>Real Reliability: The Value of Virtual Power</em>, prepared for Google</a>. The report models the net cost of providing 400 MW of resource adequacy from a residential VPP (smart thermostats, smart water heating, home EV managed charging, behind-the-meter battery demand response) compared to a natural gas peaker and a transmission-connected utility-scale battery.</p>
                    </section>

                    <section class="method-section">
                        <h3>Net cost definition</h3>
                        <p>Net cost = all-in cost (CapEx, O&M, fuel where applicable) minus market value (energy, ancillary services, T&amp;D deferral, and optionally emissions and resilience). Lower net cost means the resource is more attractive. When &ldquo;Include emissions benefits&rdquo; or &ldquo;Include resilience benefits&rdquo; is on, the report&rsquo;s incremental societal value is subtracted from the VPP net cost (reducing it or making it negative).</p>
                    </section>

                    <section class="method-section">
                        <h3>Savings per MW</h3>
                        <p>Savings per MW per year = (alternative net cost per MW-year) − (VPP net cost per MW-year). The alternative is the one you selected: gas peaker only, utility-scale battery only, or the average of both. The result is in 2022 dollars per MW of VPP capacity; multiply by your planned VPP size (MW) to estimate total savings.</p>
                        <div class="formula">
                            <code>Savings ($/MW/year) = Alternative net cost − VPP net cost</code>
                        </div>
                    </section>

                    <section class="method-section">
                        <h3>Base-case constants (Technical Appendix Table 4)</h3>
                        <p>Base-case net costs per MW-year are from Brattle Volume II Technical Appendix, Table 4, &ldquo;Net Resource Adequacy Cost (System)&rdquo; base case, converted from $million/year for 400 MW to $/MW-year.</p>
                        <ul>
                            <li>Gas peaker (base): <?php echo number_format(\BRATTLE_NET_COST_GAS_PEAKER_BASE); ?> $/MW-year</li>
                            <li>Utility-scale battery (base): <?php echo number_format(\BRATTLE_NET_COST_BATTERY_BASE); ?> $/MW-year</li>
                            <li>VPP (base): <?php echo number_format(\BRATTLE_NET_COST_VPP_BASE); ?> $/MW-year</li>
                            <li>Incremental emissions value (when included): <?php echo number_format(\BRATTLE_EMISSIONS_VALUE_PER_MW_YR); ?> $/MW-year at $<?php echo \BRATTLE_CARBON_PRICE_BASE; ?>/metric ton (Table 4 VPP emissions); scales with carbon price</li>
                            <li>Incremental resilience value (when included): <?php echo number_format(\BRATTLE_RESILIENCE_VALUE_PER_MW_YR); ?> $/MW-year (Table 4 VPP resilience)</li>
                        </ul>
                    </section>

                    <section class="method-section">
                        <h3>Sensitivity options</h3>
                        <ul>
                            <li><strong>T&amp;D cost level:</strong> Base = report base; High = more T&amp;D deferral value for VPP (lower VPP net cost); Low = less.</li>
                            <li><strong>Technology cost scenario:</strong> Base = report base-case costs; 2030 trends = assumed future cost declines (lower net costs).</li>
                            <li><strong>Renewables deployment:</strong> Base = report&rsquo;s 50% renewables illustrative system; Business-as-usual = sensitivity with lower renewables.</li>
                            <li><strong>Battery configuration:</strong> Applies to the utility-scale battery alternative only: Base = 4‑hour/6‑hour mix; Alternative = 4‑hour storage only (Table 4 &ldquo;4-hr Storage&rdquo; column; does not fully meet RA in report).</li>
                            <li><strong>Include ancillary services value:</strong> When on, net cost subtracts value from providing spinning reserves etc. (report base case). When off, &ldquo;energy only&rdquo; sensitivity—higher net costs.</li>
                        </ul>
                    </section>

                    <section class="method-section">
                        <h3>Report reference</h3>
                        <p class="data-sources-note"><a href="https://www.brattle.com/wp-content/uploads/2023/04/Real-Reliability-The-Value-of-Virtual-Power_5.3.2023.pdf" target="_blank" rel="noopener noreferrer">Brattle Group (2023). <em>Real Reliability: The Value of Virtual Power</em>, prepared for Google</a> (summary). <a href="https://www.brattle.com/wp-content/uploads/2023/04/Real-Reliability-The-Value-of-Virtual-Power-Technical-Appendix_5.3.2023.pdf" target="_blank" rel="noopener noreferrer">Technical Appendix</a>, Table 4: annual costs, benefits, and net costs by scenario. All constants in this calculator are from Table 4 or derived from it.</p>
                    </section>
                </div>
            </div>
        </main>

        <footer>
            <p>Based on <a href="https://www.brattle.com/wp-content/uploads/2023/04/Real-Reliability-The-Value-of-Virtual-Power_5.3.2023.pdf" target="_blank" rel="noopener noreferrer">Brattle Group (2023), &ldquo;Real Reliability: The Value of Virtual Power,&rdquo; prepared for Google</a>.</p>
        </footer>
    </div>

    <script>
        (function() {
            var labels = <?php echo json_encode($chartLabels); ?>;
            var values = <?php echo json_encode($chartValues); ?>;
            var colors = <?php echo json_encode($chartColors); ?>;

            new Chart(document.getElementById('vppChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Net cost ($/MW/year)',
                        data: values,
                        backgroundColor: colors,
                        borderColor: colors.map(function(c) { return c.replace('0.8)', '1)'); }),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Net cost ($/MW/year)' }
                        },
                        x: {
                            title: { display: true, text: 'Resource' }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    return '$' + ctx.parsed.y.toLocaleString() + ' /MW/year';
                                }
                            }
                        }
                    }
                }
            });
        })();
    </script>
</body>
</html>
