<?php
/**
 * DPP Map - Interactive map for distributed power plant (DPP)
 * Search by address to zoom the map to that location.
 */
$pageTitle = 'Distributed Power Plant Map';
$pageSubtitle = 'Search by address to view a location on the map';
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
                <div class="form-group form-group-address">
                    <label for="search-address">Address</label>
                    <input type="text" id="search-address" name="search-address" placeholder="Enter address or city, state" aria-label="Search by address" aria-autocomplete="list" aria-controls="address-suggest-list" aria-expanded="false" autocomplete="off">
                    <ul id="address-suggest-list" class="address-suggest-list" role="listbox" aria-label="Address suggestions" style="display: none;"></ul>
                </div>
                <div class="form-group">
                    <button type="button" id="btn-search" class="btn-primary" aria-label="Search">Search</button>
                </div>
            </div>

            <div id="search-error" class="search-error" style="display: none;"></div>

            <div class="map-layout">
                <div class="map-container">
                    <div id="map"></div>
                </div>
            </div>
        </main>

        <footer>
            <p>Search by address to zoom the map to that location.</p>
        </footer>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="assets/map.js"></script>
</body>
</html>
