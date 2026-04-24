<?php
/**
 * DPP Map - Interactive map for distributed power plant (DPP)
 * Search by address to zoom the map to that location.
 */
$pageTitle = 'Distributed Power Plant Map';
$pageSubtitle = 'Search by address to view a location on the map';

$energytools_page_title = $pageTitle;
$energytools_design_css_prefix = '../assets';
$energytools_extra_stylesheets = ['assets/map.css'];
$energytools_head_prefix_html = <<<'HTML'
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
HTML;
$energytools_brand_line = 'DPP Map';
$energytools_home_href = '../index.php';
require __DIR__ . '/../includes/design-head.php';
require __DIR__ . '/../includes/design-header.php';
?>
<main id="main">
    <div class="tool-shell tool-shell--wide">
        <div class="tool-shell__main tool-shell__main--flush">
            <div class="tool-shell__main" style="padding-bottom: 0;">
                <h1 class="tool-page-title"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="tool-page-lede"><?php echo htmlspecialchars($pageSubtitle, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <div class="search-controls" role="search">
                <div class="form-group form-group-address">
                    <label for="search-address">Address</label>
                    <input type="text" id="search-address" name="search-address" placeholder="Enter address or city, state" aria-label="Search by address" aria-autocomplete="list" aria-controls="address-suggest-list" aria-expanded="false" autocomplete="off">
                    <ul id="address-suggest-list" class="address-suggest-list" role="listbox" aria-label="Address suggestions" style="display: none;"></ul>
                </div>
                <div class="form-group">
                    <button type="button" id="btn-search" class="btn btn-primary" aria-label="Search">Search</button>
                </div>
            </div>

            <div id="search-error" class="search-error" style="display: none;"></div>

            <div class="map-layout">
                <div class="map-container">
                    <div id="map"></div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php
$energytools_footer_html = '<p>Search by address to zoom the map to that location.</p>';
$energytools_footer_append = '';
require __DIR__ . '/../includes/design-footer.php';
?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="assets/map.js"></script>
<?php require __DIR__ . '/../includes/design-close.php'; ?>
