<?php
/**
 * Energy Tools - Landing page
 */
$energytools_page_title = 'Energy Tools';
$energytools_design_css_prefix = 'assets';
$energytools_brand_line = 'Free tools for renewables';
$energytools_home_href = 'index.php';
require __DIR__ . '/includes/design-head.php';
require __DIR__ . '/includes/design-header.php';
?>
<main id="main" class="wrap" style="padding-block: 2rem 3rem;">
    <h1 class="section__title" style="margin-bottom: 0.75rem;">Energy Tools</h1>
    <p class="hub-intro">Free tools for people working in the renewable energy space.</p>

    <div class="work-stack" style="margin-top: 2rem;">
        <a class="work-card" href="demand_profile/">
            <h2 class="work-card__title">Demand Profile Generator</h2>
            <p>Generate residential electricity demand profiles based on your home characteristics—zip code, annual usage, and options like AC, electric heating, work-from-home, and EV charging. Download hourly load shapes as CSV.</p>
            <span class="hub-tool-cta">Open Demand Profile Generator</span>
        </a>

        <a class="work-card" href="energy_meme/">
            <h2 class="work-card__title">Energy Facts &amp; Memes</h2>
            <p>Discover shareable facts and inspiration from renewable energy research. Each fact includes an educational explanation and source. Download a shareable image, copy the text, or post directly to social media.</p>
            <span class="hub-tool-cta">Open Energy Facts &amp; Memes</span>
        </a>

        <a class="work-card" href="myenergy/">
            <h2 class="work-card__title">Our Energy Story</h2>
            <p>A scrollytelling narrative of 22 years of electricity in one home. Combines utility records with SolarEdge monitoring data.</p>
            <span class="hub-tool-cta">Open Home Energy Story</span>
        </a>

        <a class="work-card" href="maint_calc/">
            <h2 class="work-card__title">Residential Solar Maintenance Cost Calculator</h2>
            <p>Estimate the cost of delayed solar panel repairs. Enter your state, number of broken modules, and repair cost to see daily, monthly, and yearly lost energy and revenue so you can compare against repair cost.</p>
            <span class="hub-tool-cta">Open Maintenance Cost Calculator</span>
        </a>

        <a class="work-card" href="vpp_value/">
            <h2 class="work-card__title">VPP Value Calculator</h2>
            <p>Estimate utility savings per megawatt of virtual power plants (VPPs) deployed. Based on the Brattle Group report &ldquo;Real Reliability: The Value of Virtual Power.&rdquo; Compare VPP net cost to gas peakers and utility-scale batteries; optional sensitivities for carbon price, T&amp;D, and more.</p>
            <span class="hub-tool-cta">Open VPP Value Calculator</span>
        </a>
    </div>
</main>
<?php require __DIR__ . '/includes/design-footer.php'; ?>
<?php require __DIR__ . '/includes/design-close.php'; ?>
