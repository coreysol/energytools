<?php
/**
 * Energy Tools - Landing page
 * Lists available tools with brief descriptions
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Energy Tools</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Energy Tools</h1>
            <p class="subtitle">Free tools for people working in the renewable energy space</p>
        </header>

        <main>
            <section class="tools-list">
                <a href="demand_profile/" class="tool-card">
                    <h2>Demand Profile Generator</h2>
                    <p>Generate residential electricity demand profiles based on your home characteristics—zip code, annual usage, and options like AC, electric heating, work-from-home, and EV charging. Download hourly load shapes as CSV.</p>
                    <span class="tool-link">Open Demand Profile Generator →</span>
                </a>

                <a href="maint_calc/" class="tool-card">
                    <h2>Maintenance Cost Calculator</h2>
                    <p>Estimate the cost of delayed solar panel repairs. Enter your state, number of broken modules, and repair cost to see daily, monthly, and yearly lost energy and revenue so you can compare against repair cost.</p>
                    <span class="tool-link">Open Maintenance Cost Calculator →</span>
                </a>
            </section>
        </main>

        <footer>
            <p>These tools use representative patterns and assumptions. They are not a substitute for utility or engineering data where precision is required.</p>
        </footer>
    </div>
</body>
</html>
