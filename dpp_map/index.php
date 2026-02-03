<?php
/**
 * DPP Map - Interactive map for distributed power plant (DPP) eligibility
 * Search by address or electric utility; view eligibility by utility and rate class.
 */
$pageTitle = 'Distributed Power Plant Map';
$pageSubtitle = 'Find out if you can register for a distributed power plant in your area';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <link rel="stylesheet" href="assets/map.css">
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="subtitle"><?php echo htmlspecialchars($pageSubtitle, ENT_QUOTES, 'UTF-8'); ?></p>
        </header>

        <main>
            <div class="search-controls" role="search">
                <div class="form-group">
                    <label for="search-address">Search by address</label>
                    <input type="text" id="search-address" name="search-address" placeholder="Enter address or city, state" aria-label="Search by address" autocomplete="off">
                </div>
                <div class="form-group form-group-utility">
                    <label for="search-utility">Search by utility</label>
                    <input type="text" id="search-utility" name="search-utility" placeholder="Enter electric utility name" aria-label="Search by utility name" aria-autocomplete="list" aria-controls="utility-suggest-list" aria-expanded="false" autocomplete="off">
                    <ul id="utility-suggest-list" class="utility-suggest-list" role="listbox" aria-label="Utility suggestions" style="display: none;"></ul>
                </div>
                <div class="form-group">
                    <label for="rate-class">Rate class</label>
                    <select id="rate-class" name="rate-class" aria-label="Rate class">
                        <option value="residential">Residential</option>
                        <option value="commercial">Commercial</option>
                        <option value="industrial">Industrial</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="button" id="btn-search-address" class="btn-primary" aria-label="Search by address">Search address</button>
                </div>
                <div class="form-group">
                    <button type="button" id="btn-search-utility" class="btn-primary" aria-label="Search by utility">Search utility</button>
                </div>
            </div>

            <div class="map-layout">
                <div class="map-container">
                    <div id="map"></div>
                </div>
                <aside class="side-panel" role="region" aria-label="Eligibility results">
                    <h2>Eligibility</h2>
                    <div id="panel-placeholder" class="panel-placeholder">
                        Search by address or utility to see if you can register for a distributed power plant in your area.
                    </div>
                    <div id="panel-content" class="panel-content">
                        <div class="utility-name" id="panel-utility"></div>
                        <div id="panel-eligibility" class="eligibility-result"></div>
                        <div class="reference-link" id="panel-reference"></div>
                        <div id="panel-freshness" class="panel-freshness" style="display: none;" aria-live="polite"></div>
                    </div>
                    <div id="panel-loading" class="loading" style="display: none;" aria-live="polite">Searching…</div>
                    <div id="panel-error" class="error" style="display: none;"></div>
                </aside>
            </div>
        </main>

        <footer>
            <p>Eligibility is determined by your electric utility and rate class. Data is for reference only.</p>
        </footer>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="assets/map.js"></script>
</body>
</html>
