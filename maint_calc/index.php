<?php
// Turning off database setup below in favor of static file-based values but can be re-enabled in the future if needed
/**
include('database.php');

// Pull state-specific values from database
$sql = "SELECT state,production_factor,kwh_cost,srecs,repair_cost FROM mCalc_main order by state";
$result = mysqli_query($link,$sql) or die("Unable to select: ".mysqli_error($link));
mysqli_close($link);

while($row = mysqli_fetch_assoc($result)) {
	$dbStateProdFactors[$row['state']] = $row['production_factor'];
	$dbStateKwhCosts[$row['state']] = $row['kwh_cost'];
	$dbStateSrecs[$row['state']] = $row['srecs'];
	$dbStateRepairCost[$row['state']] = $row['repair_cost'];
}
**/

// Setup associative array of state-based values
include('state_info.php');

// Initialize arrays
$dbStateProdFactors = [];
$dbStateKwhCosts = [];
$dbStateSrecs = [];
$dbStateRepairCost = [];

foreach ($record as $row) {
	$dbStateProdFactors[$row['state']] = $row['production_factor'];
	$dbStateKwhCosts[$row['state']] = $row['kwh_cost'];
	$dbStateSrecs[$row['state']] = $row['srecs'];
	$dbStateRepairCost[$row['state']] = $row['repair_cost'];
}

// User input from the URL if provided, otherwise from form or default values if not
// Use filter_input for secure input handling
$getState = filter_input(INPUT_GET, 'state', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$postState = filter_input(INPUT_POST, 'chosenState', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if (!empty($getState) && strlen($getState) == 2) {
	$chosenState = $getState;
}
elseif (!empty($postState) && strlen($postState) == 2) {
	$chosenState = $postState;
}
else {
	$chosenState = 'US';
}

// Validate chosenState exists in our data, fallback to US if not
if (!isset($dbStateKwhCosts[$chosenState])) {
	$chosenState = 'US';
}

// Sanitize and validate numeric inputs with reasonable bounds
$brokenModuleCount = filter_input(INPUT_POST, 'brokenModuleCount', FILTER_VALIDATE_INT);
$brokenModuleCount = ($brokenModuleCount !== false && $brokenModuleCount > 0 && $brokenModuleCount <= 1000) ? $brokenModuleCount : 1;

$brokenModuleSize = filter_input(INPUT_POST, 'brokenModuleSize', FILTER_VALIDATE_INT);
$brokenModuleSize = ($brokenModuleSize !== false && $brokenModuleSize > 0 && $brokenModuleSize <= 1000) ? $brokenModuleSize : 400;

$stateKwhCosts = filter_input(INPUT_POST, 'stateKwhCosts', FILTER_VALIDATE_FLOAT);
$stateKwhCosts = ($stateKwhCosts !== false && $stateKwhCosts > 0 && $stateKwhCosts <= 10) ? $stateKwhCosts : $dbStateKwhCosts[$chosenState];

$repairCost = filter_input(INPUT_POST, 'repairCost', FILTER_VALIDATE_FLOAT);
$repairCost = ($repairCost !== false && $repairCost > 0 && $repairCost <= 100000) ? $repairCost : $dbStateRepairCost[$chosenState];

// These get calculated based on state-specific values and user-supplied input
// Note: htmlspecialchars is not needed for numeric calculations - use it on output only
$brokenSystemSize = $brokenModuleCount * $brokenModuleSize;

$avgDayKwh = round(($brokenSystemSize * $dbStateProdFactors[$chosenState] / 365), 2);
$avgDayLostRevenue = round((($brokenSystemSize * $dbStateProdFactors[$chosenState] * $stateKwhCosts) / 365), 2);
$avgDayRecover = ($avgDayLostRevenue > 0) ? round(($repairCost / $avgDayLostRevenue), 0) : 0;

$avgMonthKwh = round(($brokenSystemSize * $dbStateProdFactors[$chosenState] / 12), 2);
$avgMonthLostRevenue = round((($brokenSystemSize * $dbStateProdFactors[$chosenState] * $stateKwhCosts) / 12), 2);
$avgMonthRecover = ($avgMonthLostRevenue > 0) ? round(($repairCost / $avgMonthLostRevenue), 0) : 0;

$avgYearKwh = round(($brokenSystemSize * $dbStateProdFactors[$chosenState]), 2);
$avgYearLostRevenue = round(($brokenSystemSize * $dbStateProdFactors[$chosenState] * $stateKwhCosts), 2);
$avgYearRecover = ($avgYearLostRevenue > 0) ? round(($repairCost / $avgYearLostRevenue), 0) : 0;

// Use htmlspecialchars for all output to prevent XSS
$safeChosenState = htmlspecialchars($chosenState, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Cost Calculator</title>
    <link rel="stylesheet" href="mcalc.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Maintenance Cost Calculator</h1>
            <p class="subtitle">Calculate the cost of delayed solar panel repairs</p>
        </header>

        <main>
<?php if (!empty($_POST['showCalc'])): ?>
            <form method="POST" action="index.php">
                <div class="form-group">
                    <label for="chosenState">What state do you live in?</label>
                    <select name="chosenState" id="chosenState">
                        <option value="<?php echo $safeChosenState; ?>"><?php echo $safeChosenState; ?></option>
<?php foreach($dbStateKwhCosts as $key => $value): ?>
                        <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?></option>
<?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="brokenModuleCount">How many panels aren't working?</label>
                    <input type="number" id="brokenModuleCount" name="brokenModuleCount" min="1" max="1000" placeholder="<?php echo (int)$brokenModuleCount; ?>">
                </div>

                <div class="form-group">
                    <label for="brokenModuleSize">What's the size of each panel in Watts?</label>
                    <input type="number" id="brokenModuleSize" name="brokenModuleSize" min="1" max="1000" placeholder="<?php echo (int)$brokenModuleSize; ?>">
                </div>

                <div class="form-group">
                    <label for="stateKwhCosts">How much does electricity cost ($/kWh)?</label>
                    <input type="number" step="0.0001" min="0.0001" max="10" id="stateKwhCosts" name="stateKwhCosts" value="<?php echo htmlspecialchars((string)(float)$stateKwhCosts, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="form-group">
                    <label for="repairCost">How much will it cost to fix the problem? ($)</label>
                    <input type="number" min="1" max="100000" id="repairCost" name="repairCost" value="<?php echo htmlspecialchars((string)(int)$repairCost, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <input type="hidden" name="showResults" value="showResults">
                
                <div class="form-group">
                    <button type="submit" class="btn-primary">Calculate</button>
                </div>
            </form>
            <script>
            (function() {
                var stateData = <?php
                    $stateFormData = [];
                    foreach ($dbStateKwhCosts as $st => $kwh) {
                        $stateFormData[$st] = [
                            'kwh_cost' => (float) $kwh,
                            'repair_cost' => (int) ($dbStateRepairCost[$st] ?? 500),
                        ];
                    }
                    echo json_encode($stateFormData);
                ?>;
                var select = document.getElementById('chosenState');
                var kwhInput = document.getElementById('stateKwhCosts');
                var repairInput = document.getElementById('repairCost');
                if (select && kwhInput && repairInput) {
                    select.addEventListener('change', function() {
                        var state = this.value;
                        if (stateData[state]) {
                            kwhInput.value = stateData[state].kwh_cost;
                            repairInput.value = stateData[state].repair_cost;
                        }
                    });
                }
            })();
            </script>
            <a href="index.php?state=<?php echo urlencode($chosenState); ?>" class="back-link">← Back to instructions</a>

<?php elseif (!empty($_POST['showResults'])): ?>
            <div class="results-container">
                <div class="results-header">
                    <h2>Your Results</h2>
                </div>
                
                <div class="results-grid">
                    <div class="result-card">
                        <h3>Daily</h3>
                        <p>
                            <span class="label">Average energy produced:</span>
                            <span class="value"><?php echo htmlspecialchars($avgDayKwh, ENT_QUOTES, 'UTF-8'); ?> kWh</span>
                        </p>
                        <p>
                            <span class="label">Average lost revenue:</span>
                            <span class="value">$<?php echo htmlspecialchars($avgDayLostRevenue, ENT_QUOTES, 'UTF-8'); ?></span>
                        </p>
                        <p>
                            <span class="label">Time to recover repair cost:</span>
                            <span class="value"><?php echo htmlspecialchars($avgDayRecover, ENT_QUOTES, 'UTF-8'); ?> days</span>
                        </p>
                    </div>
                    
                    <div class="result-card">
                        <h3>Monthly</h3>
                        <p>
                            <span class="label">Average energy produced:</span>
                            <span class="value"><?php echo htmlspecialchars($avgMonthKwh, ENT_QUOTES, 'UTF-8'); ?> kWh</span>
                        </p>
                        <p>
                            <span class="label">Average lost revenue:</span>
                            <span class="value">$<?php echo htmlspecialchars($avgMonthLostRevenue, ENT_QUOTES, 'UTF-8'); ?></span>
                        </p>
                        <p>
                            <span class="label">Time to recover repair cost:</span>
                            <span class="value"><?php echo htmlspecialchars($avgMonthRecover, ENT_QUOTES, 'UTF-8'); ?> months</span>
                        </p>
                    </div>
                    
                    <div class="result-card">
                        <h3>Yearly</h3>
                        <p>
                            <span class="label">Average energy produced:</span>
                            <span class="value"><?php echo htmlspecialchars($avgYearKwh, ENT_QUOTES, 'UTF-8'); ?> kWh</span>
                        </p>
                        <p>
                            <span class="label">Average lost revenue:</span>
                            <span class="value">$<?php echo htmlspecialchars($avgYearLostRevenue, ENT_QUOTES, 'UTF-8'); ?></span>
                        </p>
                        <p>
                            <span class="label">Time to recover repair cost:</span>
                            <span class="value"><?php echo htmlspecialchars($avgYearRecover, ENT_QUOTES, 'UTF-8'); ?> years</span>
                        </p>
                    </div>
                </div>

<?php if (!empty($dbStateSrecs[$chosenState])): ?>
                <div class="srec-notice">
                    ** You're in a state with <a href="https://www.solarunitedneighbors.org/learn-the-issues/solar-incentives/solar-renewable-energy-credits-srecs/" target="_blank" rel="noopener noreferrer">SRECs</a>. Lost revenue estimates do not include SREC value.
                </div>
<?php endif; ?>

                <div class="actions">
                    <form method="POST" action="index.php" style="display: inline;">
                        <input type="hidden" name="showCalc" value="showCalc">
                        <input type="hidden" name="chosenState" value="<?php echo $safeChosenState; ?>">
                        <button type="submit" class="btn-primary">Recalculate</button>
                    </form>
                    <a href="index.php?state=<?php echo urlencode($chosenState); ?>" class="btn-secondary">Back to instructions</a>
                </div>
            </div>

<?php else: ?>
            <section class="info-section">
                <h2>Do you have modules in your solar array that aren't working?</h2>
                <p>Use the calculator to see how much lost electricity savings you're missing out on and how that compares to the cost of repairs.</p>
                <p class="note">
                    <strong>NOTE:</strong> Energy production estimates (kWh) are based on conservative assumptions for your state. Your system may produce more or less energy than this estimate but for smaller numbers of modules that are broken, the difference is likely minimal.
                </p>
            </section>

            <form method="POST" action="index.php">
                <input type="hidden" name="showCalc" value="showCalc">
                <input type="hidden" name="chosenState" value="<?php echo $safeChosenState; ?>">
                <div class="form-group">
                    <button type="submit" class="btn-primary">Go to Calculator</button>
                </div>
            </form>
<?php endif; ?>
        </main>

        <footer>
            <p>This tool generates estimates based on typical solar production patterns and your inputs.</p>
            <p class="data-source-note">Electricity cost (kWh) by state: U.S. Energy Information Administration (EIA), State Electricity Profiles, 2024 average retail price (residential), converted to $/kWh. <a href="https://www.eia.gov/electricity/state/" target="_blank" rel="noopener noreferrer">eia.gov/electricity/state</a>. Profile year 2024; release November 10, 2025.</p>
            <p class="data-source-note">Energy production factor by state: Derived from solar resource and utility-scale PV capacity factor data. EIA state capacity factors (e.g. AZ 29.1%, UT 29.0%, CA 28.4%); NREL ATB capacity factors by resource class (GHI). State factors scaled to relative solar resource (Southwest highest, Northeast/Pacific NW lower). US = 1.20 baseline. <a href="https://www.eia.gov/todayinenergy/detail.php?id=39832" target="_blank" rel="noopener noreferrer">EIA Today in Energy</a>; <a href="https://atb.nrel.gov/electricity/2024/utility-scale_pv" target="_blank" rel="noopener noreferrer">NREL ATB</a>.</p>
        </footer>
    </div>
</body>
</html>
