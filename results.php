<?php
/**
 * Results display page
 */

require_once __DIR__ . '/includes/config.php';

session_start();

// Check if we have valid inputs
if (!isset($_SESSION['demand_profile_inputs'])) {
    header('Location: index.php');
    exit;
}

$inputs = $_SESSION['demand_profile_inputs'];
$annual_kwh = $_SESSION['demand_profile_annual_kwh'] ?? 0;
$seasonal_data = $_SESSION['demand_profile_seasonal'] ?? null;
$csv_filepath = $_SESSION['demand_profile_csv'] ?? null;

// If data not generated, redirect back
if ($seasonal_data === null) {
    header('Location: index.php');
    exit;
}

// Prepare data for charts
$seasons = ['spring', 'summer', 'fall', 'winter'];
$season_labels = [
    'spring' => 'Spring',
    'summer' => 'Summer',
    'fall' => 'Fall',
    'winter' => 'Winter'
];

// Find maximum kW value across all seasons for consistent Y-axis scaling
$max_kw = 0;
foreach ($seasons as $season) {
    if (isset($seasonal_data[$season])) {
        $season_max = max($seasonal_data[$season]);
        if ($season_max > $max_kw) {
            $max_kw = $season_max;
        }
    }
}
// Round up to next nice number (add 10% padding and round up)
$max_kw = ceil($max_kw * 1.1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demand Profile Results</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Demand Profile Results</h1>
        </header>

        <main class="results-container">
            <div class="results-header">
                <h2>Your Demand Profile</h2>
                <p>Based on your inputs:</p>
                <ul>
                    <li>Zip Code: <?php echo htmlspecialchars($inputs['zip_code']); ?></li>
                    <li>Annual kWh: <?php echo number_format($annual_kwh, 0); ?> kWh</li>
                    <li>Air Conditioning: <?php echo $inputs['has_ac'] ? 'Yes' : 'No'; ?></li>
                    <li>Electric Heating: <?php echo $inputs['has_heating'] ? 'Yes' : 'No'; ?></li>
                    <li>Work From Home: <?php echo $inputs['has_wfh'] ? 'Yes' : 'No'; ?></li>
                    <li>EV Charging: <?php echo $inputs['has_ev'] ? 'Yes' : 'No'; ?></li>
                </ul>
            </div>

            <div class="charts-container">
                <?php foreach ($seasons as $season): ?>
                    <div class="chart-wrapper">
                        <h3><?php echo $season_labels[$season]; ?> Demand Profile</h3>
                        <canvas id="chart-<?php echo $season; ?>"></canvas>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($csv_filepath): ?>
            <div class="download-section">
                <h3>Download Your Data</h3>
                <p>Download the complete 15-minute interval data in Green Button CSV format:</p>
                <a href="download.php" class="download-btn">Download CSV File</a>
            </div>
            <?php endif; ?>

            <div style="margin-top: 30px; text-align: center;">
                <a href="index.php" class="btn-primary" style="display: inline-block; text-decoration: none;">Generate New Profile</a>
            </div>
        </main>

        <footer>
            <p>Results are based on typical residential load patterns and your inputs.</p>
        </footer>
    </div>

    <script>
        // Chart configuration
        // X-axis: Hours of day (0-23, starting at midnight on left)
        // Y-axis: kW usage
        const chartOptions = {
            type: 'line',
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Hour of Day'
                        },
                        min: 0,
                        max: 23,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                return value + ':00';
                            }
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'kW Usage'
                        },
                        beginAtZero: true,
                        max: <?php echo $max_kw; ?>
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Hour ' + context.parsed.x + ': ' + context.parsed.y.toFixed(2) + ' kW';
                            }
                        }
                    }
                }
            }
        };

        // Create charts for each season
        <?php foreach ($seasons as $season): ?>
        const data<?php echo ucfirst($season); ?> = {
            labels: [<?php for ($hour = 0; $hour < 24; $hour++): ?><?php echo $hour; ?><?php echo $hour < 23 ? ',' : ''; ?><?php endfor; ?>],
            datasets: [{
                label: 'Average kW',
                data: [
                    <?php 
                    $hour_data = $seasonal_data[$season];
                    for ($hour = 0; $hour < 24; $hour++): 
                        $kw = $hour_data[$hour] ?? 0;
                    ?>
                    <?php echo number_format($kw, 2); ?><?php echo $hour < 23 ? ',' : ''; ?>
                    <?php endfor; ?>
                ],
                borderColor: '<?php 
                    $colors = ['spring' => 'rgb(76, 175, 80)', 'summer' => 'rgb(255, 152, 0)', 'fall' => 'rgb(156, 39, 176)', 'winter' => 'rgb(33, 150, 243)'];
                    echo $colors[$season];
                ?>',
                backgroundColor: '<?php echo $colors[$season]; ?>33',
                borderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5
            }]
        };

        new Chart(
            document.getElementById('chart-<?php echo $season; ?>'),
            {
                ...chartOptions,
                data: data<?php echo ucfirst($season); ?>
            }
        );
        <?php endforeach; ?>
    </script>
</body>
</html>
