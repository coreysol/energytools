<?php
/**
 * Calculation Methods Documentation Page
 */

require_once __DIR__ . '/includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculation Methods - Demand Profile Generator</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Calculation Methods</h1>
            <p class="subtitle">How we build your demand profile</p>
        </header>

        <main class="methods-container">
            <div class="methods-content">
                <h2>Calculation Methods Summary</h2>
                
                <section class="method-section">
                    <h3>Base Load Profile</h3>
                    <p>
                        The tool starts with a standard residential hourly load pattern that varies by day type:
                    </p>
                    <ul>
                        <li><strong>Weekday pattern:</strong> Lower overnight (0.35–0.4× average), morning rise (0.5–0.8×), moderate daytime (0.55–0.7×), evening peak (1.0–1.1× around 6–9 PM)</li>
                        <li><strong>Weekend pattern:</strong> Similar shape but higher daytime usage (0.7–1.15×) and a more gradual evening peak</li>
                    </ul>
                    <p>
                        The base hourly kW is calculated as:
                    </p>
                    <div class="formula">
                        <code>Base kW (hour) = (Annual kWh ÷ 365 days ÷ 24 hours) × Hourly Load Factor</code>
                    </div>
                </section>

                <section class="method-section">
                    <h3>Climate and Location Influence</h3>
                    <p>
                        Your zip code maps to a climate zone (1–5) that estimates:
                    </p>
                    <ul>
                        <li><strong>Cooling Degree Days (CDD):</strong> Higher in warmer regions, used to scale summer AC load</li>
                        <li><strong>Heating Degree Days (HDD):</strong> Higher in colder regions, used to scale winter heating load</li>
                    </ul>
                </section>

                <section class="method-section">
                    <h3>Seasonal Adjustments</h3>
                    
                    <h4>Air Conditioning (Summer Only)</h4>
                    <ul>
                        <li><strong>Peak cooling hours (2–6 PM):</strong> Additional load = Base kW × (1.0 + (CDD ÷ 2000) × 0.5)</li>
                        <li><strong>Extended hot hours (12–8 PM):</strong> Additional load = Base kW × (1.0 + (CDD ÷ 2000) × 0.3) × 0.5</li>
                    </ul>

                    <h4>Electric Heating (Winter Only)</h4>
                    <ul>
                        <li><strong>Peak heating hours (5–8 AM, 6–10 PM):</strong> Additional load = Base kW × (1.0 + (HDD ÷ 2000) × 0.6)</li>
                        <li><strong>Overnight heating (12–6 AM):</strong> Additional load = Base kW × (1.0 + (HDD ÷ 2000) × 0.4) × 0.7</li>
                    </ul>
                </section>

                <section class="method-section">
                    <h3>Work-From-Home Adjustments</h3>
                    <p>
                        If work-from-home is selected:
                    </p>
                    <ul>
                        <li><strong>Weekdays, 9 AM–5 PM:</strong> Base load × 1.3 (30% increase)</li>
                        <li>Accounts for computers, lighting, and additional HVAC during work hours</li>
                    </ul>
                </section>

                <section class="method-section">
                    <h3>Electric Vehicle Charging</h3>
                    <p>
                        If EV charging is selected:
                    </p>
                    <ul>
                        <li><strong>Evening charging (6–10 PM, weekdays):</strong> 60% probability of adding 7 kW</li>
                        <li><strong>Overnight charging (10 PM–6 AM):</strong> 80% probability of adding 7 kW</li>
                        <li>Represents typical Level 2 charging patterns</li>
                    </ul>
                </section>

                <section class="method-section">
                    <h3>Final Normalization</h3>
                    <p>
                        After applying all adjustments, the profile is normalized to match your annual kWh:
                    </p>
                    <div class="formula">
                        <code>Normalization Factor = Your Annual kWh ÷ Calculated Total kWh</code><br>
                        <code>Final kW (each interval) = Adjusted kW × Normalization Factor</code>
                    </div>
                    <p>
                        This ensures the total annual energy matches your input while preserving the relative demand patterns.
                    </p>
                </section>

                <div style="margin-top: 40px; text-align: center;">
                    <a href="index.php" class="btn-primary" style="display: inline-block; text-decoration: none;">Back to Form</a>
                </div>
            </div>
        </main>

        <footer>
            <p>This tool generates demand profiles based on typical residential patterns and your inputs.</p>
        </footer>
    </div>
</body>
</html>
