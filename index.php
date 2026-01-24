<?php
/**
 * Main input form page
 */

require_once __DIR__ . '/includes/config.php';

// Initialize variables
$zip_code = '';
$annual_kwh = '';
$has_ac = false;
$has_heating = false;
$has_wfh = false;
$has_ev = false;
$errors = [];

// Check if form was submitted with errors (redirected back)
if (isset($_GET['errors'])) {
    $errors = json_decode(urldecode($_GET['errors']), true) ?? [];
}

// Restore form values if redirected back
if (isset($_GET['zip_code'])) {
    $zip_code = htmlspecialchars($_GET['zip_code']);
}
if (isset($_GET['annual_kwh'])) {
    $annual_kwh = htmlspecialchars($_GET['annual_kwh']);
}
if (isset($_GET['has_ac'])) {
    $has_ac = true;
}
if (isset($_GET['has_heating'])) {
    $has_heating = true;
}
if (isset($_GET['has_wfh'])) {
    $has_wfh = true;
}
if (isset($_GET['has_ev'])) {
    $has_ev = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demand Profile Generator</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Demand Profile Generator</h1>
            <p class="subtitle">Generate residential electricity demand profiles based on your home characteristics</p>
        </header>

        <main>
            <form method="POST" action="process.php" id="demandForm">
                <div class="form-group">
                    <label for="zip_code">Zip Code <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="zip_code" 
                        name="zip_code" 
                        value="<?php echo $zip_code; ?>"
                        placeholder="12345"
                        maxlength="5"
                        pattern="[0-9]{5}"
                        required
                    >
                    <?php if (isset($errors['zip_code'])): ?>
                        <span class="error"><?php echo htmlspecialchars($errors['zip_code']); ?></span>
                    <?php endif; ?>
                    <small>Enter your 5-digit US zip code</small>
                </div>

                <div class="form-group">
                    <label for="annual_kwh">Annual kWh Usage <span class="required">*</span></label>
                    <input 
                        type="number" 
                        id="annual_kwh" 
                        name="annual_kwh" 
                        value="<?php echo $annual_kwh; ?>"
                        placeholder="12000"
                        min="1000"
                        max="100000"
                        step="1"
                        required
                    >
                    <?php if (isset($errors['annual_kwh'])): ?>
                        <span class="error"><?php echo htmlspecialchars($errors['annual_kwh']); ?></span>
                    <?php endif; ?>
                    <small>Enter your total annual electricity consumption in kilowatt-hours</small>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input 
                            type="checkbox" 
                            id="has_ac" 
                            name="has_ac" 
                            value="1"
                            <?php echo $has_ac ? 'checked' : ''; ?>
                        >
                        <span>Does your home have air conditioning?</span>
                    </label>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input 
                            type="checkbox" 
                            id="has_heating" 
                            name="has_heating" 
                            value="1"
                            <?php echo $has_heating ? 'checked' : ''; ?>
                        >
                        <span>Is your home heated with electricity?</span>
                    </label>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input 
                            type="checkbox" 
                            id="has_wfh" 
                            name="has_wfh" 
                            value="1"
                            <?php echo $has_wfh ? 'checked' : ''; ?>
                        >
                        <span>Does at least one member of your household work from home?</span>
                    </label>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input 
                            type="checkbox" 
                            id="has_ev" 
                            name="has_ev" 
                            value="1"
                            <?php echo $has_ev ? 'checked' : ''; ?>
                        >
                        <span>Do you charge an electric vehicle at home?</span>
                    </label>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn-primary">Generate Demand Profile</button>
                </div>
            </form>
        </main>

        <footer>
            <p>This tool generates demand profiles based on typical residential patterns and your inputs.</p>
        </footer>
    </div>

    <script src="assets/js/form-validation.js"></script>
</body>
</html>
