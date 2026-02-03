/**
 * DPP Map - Leaflet map, search, and eligibility panel
 * API base is relative to current page (dpp_map/)
 */

(function () {
    'use strict';

    const API_BASE = 'api';

    // Map init: US center, zoom for continental view
    const DEFAULT_CENTER = [39.8283, -98.5795];
    const DEFAULT_ZOOM = 4;

    let map = null;
    let addressMarker = null;
    let utilityBoundaryLayer = null;

    function initMap() {
        map = L.map('map').setView(DEFAULT_CENTER, DEFAULT_ZOOM);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
    }

    function setPanelPlaceholder(message) {
        document.getElementById('panel-placeholder').classList.add('visible');
        document.getElementById('panel-placeholder').textContent = message || 'Search by address or utility to see eligibility.';
        document.getElementById('panel-content').classList.remove('visible');
        document.getElementById('panel-loading').style.display = 'none';
        document.getElementById('panel-error').style.display = 'none';
    }

    function setPanelLoading(show) {
        document.getElementById('panel-placeholder').classList.remove('visible');
        document.getElementById('panel-content').classList.remove('visible');
        document.getElementById('panel-loading').style.display = show ? 'block' : 'none';
        document.getElementById('panel-error').style.display = 'none';
        const btnAddr = document.getElementById('btn-search-address');
        const btnUtil = document.getElementById('btn-search-utility');
        if (btnAddr) btnAddr.disabled = !!show;
        if (btnUtil) btnUtil.disabled = !!show;
    }

    function userMessageFromCode(code) {
        var messages = {
            rate_limited: 'Too many searches. Please wait a moment and try again.',
            address_not_found: 'Address not found. Try a different search or check the spelling.',
            service_unavailable: 'Geocoding service is temporarily unavailable. Please try again later.',
            no_utility_at_point: 'We don\'t have boundary data for this location yet, or the address is outside known utility areas.',
            missing_query: 'Please enter an address or utility name.'
        };
        return messages[code] || null;
    }

    function setPanelError(message, code) {
        document.getElementById('panel-placeholder').classList.remove('visible');
        document.getElementById('panel-content').classList.remove('visible');
        document.getElementById('panel-loading').style.display = 'none';
        var el = document.getElementById('panel-error');
        el.textContent = (code && userMessageFromCode(code)) || message || 'Something went wrong. Please try again.';
        el.style.display = 'block';
    }

    function setPanelResult(utilityName, eligible, eligibleRateClasses, referenceLink, eligibilityUpdated, territorySource) {
        document.getElementById('panel-placeholder').classList.remove('visible');
        document.getElementById('panel-loading').style.display = 'none';
        document.getElementById('panel-error').style.display = 'none';

        document.getElementById('panel-utility').textContent = utilityName ? 'Utility: ' + utilityName : 'Utility: —';
        var eligEl = document.getElementById('panel-eligibility');
        eligEl.className = 'eligibility-result ' + (eligible ? 'eligible' : 'not-eligible');
        eligEl.textContent = eligible
            ? 'You can register for a DPP for your rate class.'
            : 'DPP registration is not available for your rate class at this utility.';
        if (eligibleRateClasses && eligibleRateClasses.length) {
            eligEl.textContent += ' Eligible rate classes: ' + eligibleRateClasses.join(', ') + '.';
        }

        var refEl = document.getElementById('panel-reference');
        if (referenceLink) {
            refEl.innerHTML = '<a href="' + escapeHtml(referenceLink) + '" target="_blank" rel="noopener noreferrer">Learn more</a>';
            refEl.style.display = 'block';
        } else {
            refEl.innerHTML = '';
            refEl.style.display = 'none';
        }

        var freshEl = document.getElementById('panel-freshness');
        if (freshEl) {
            var parts = [];
            if (eligibilityUpdated) parts.push('Eligibility data updated: ' + eligibilityUpdated);
            if (territorySource) parts.push('Boundary data: ' + (territorySource === 'hifld' ? 'HIFLD (live)' : 'Local'));
            freshEl.textContent = parts.join(' · ');
            freshEl.style.display = parts.length ? 'block' : 'none';
        }

        document.getElementById('panel-content').classList.add('visible');
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function flyTo(lat, lng, zoom) {
        if (!map) return;
        map.flyTo([lat, lng], zoom || 12);
        if (addressMarker) {
            map.removeLayer(addressMarker);
        }
        addressMarker = L.marker([lat, lng]).addTo(map);
    }

    var boundaryStyle = {
        color: '#e57a00',
        weight: 2.5,
        opacity: 0.9,
        fillColor: '#f7941d',
        fillOpacity: 0.15
    };

    function drawUtilityBoundary(geometryOrGeometries) {
        if (!map) return;
        if (utilityBoundaryLayer) {
            map.removeLayer(utilityBoundaryLayer);
            utilityBoundaryLayer = null;
        }
        var geometries = Array.isArray(geometryOrGeometries)
            ? geometryOrGeometries
            : (geometryOrGeometries && geometryOrGeometries.coordinates ? [geometryOrGeometries] : []);
        if (geometries.length === 0) return;
        utilityBoundaryLayer = L.layerGroup();
        geometries.forEach(function (geom) {
            if (!geom || !geom.coordinates) return;
            var feature = { type: 'Feature', geometry: geom, properties: {} };
            L.geoJSON(feature, { style: boundaryStyle }).addTo(utilityBoundaryLayer);
        });
        utilityBoundaryLayer.addTo(map);
    }

    function clearUtilityBoundary() {
        if (map && utilityBoundaryLayer) {
            map.removeLayer(utilityBoundaryLayer);
            utilityBoundaryLayer = null;
        }
    }

    function flyToBounds(bounds) {
        if (!map || !bounds) return;
        const b = L.latLngBounds([bounds.south, bounds.west], [bounds.north, bounds.east]);
        map.flyToBounds(b, { padding: [30, 30], maxZoom: 10 });
    }

    // Address search: geocode -> utility at point -> eligibility
    async function searchByAddress() {
        const input = document.getElementById('search-address');
        const q = (input && input.value) ? input.value.trim() : '';
        if (!q) {
            setPanelError('Please enter an address.');
            return;
        }

        setPanelLoading(true);
        try {
            const geoRes = await fetch(API_BASE + '/geocode.php?q=' + encodeURIComponent(q));
            const geoData = await geoRes.json();
            if (!geoRes.ok || geoData.error) {
                setPanelError(geoData.error || geoData.message, geoData.code);
                setPanelLoading(false);
                return;
            }
            var lat = parseFloat(geoData.lat);
            var lng = parseFloat(geoData.lng);
            if (isNaN(lat) || isNaN(lng)) {
                setPanelError('Invalid coordinates returned.', null);
                setPanelLoading(false);
                return;
            }
            flyTo(lat, lng);

            var utilRes = await fetch(API_BASE + '/utility-at-point.php?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng));
            var utilData = await utilRes.json();
            var utilityName = utilData.utility || null;
            var utilityId = utilData.utility_id || null;
            var territorySource = utilData.territory_source || null;
            if (utilData.geometries && utilData.geometries.length > 0) {
                drawUtilityBoundary(utilData.geometries);
            } else if (utilData.geometry) {
                drawUtilityBoundary(utilData.geometry);
            } else {
                clearUtilityBoundary();
            }

            var rateClass = (document.getElementById('rate-class') && document.getElementById('rate-class').value) || 'residential';
            var eligible = false;
            var eligibleRateClasses = [];
            var referenceLink = null;
            var eligibilityUpdated = null;

            if (utilityName || utilityId) {
                var eligRes = await fetch(API_BASE + '/eligibility.php?utility=' + encodeURIComponent(utilityName || utilityId || '') + '&rate_class=' + encodeURIComponent(rateClass));
                var eligData = await eligRes.json();
                eligible = eligData.eligible === true;
                eligibleRateClasses = Array.isArray(eligData.eligible_rate_classes) ? eligData.eligible_rate_classes : [];
                referenceLink = eligData.reference_link || null;
                eligibilityUpdated = eligData.eligibility_updated || null;
            }

            setPanelResult(utilityName || 'Unknown', eligible, eligibleRateClasses, referenceLink, eligibilityUpdated, territorySource);
        } catch (err) {
            setPanelError('Search failed. Please try again.', null);
        } finally {
            setPanelLoading(false);
        }
    }

    // Utility search: get bbox and zoom, show eligibility
    async function searchByUtility() {
        const input = document.getElementById('search-utility');
        const q = (input && input.value) ? input.value.trim() : '';
        if (!q) {
            setPanelError('Please enter a utility name.');
            return;
        }
        setPanelLoading(true);
        try {
            const res = await fetch(API_BASE + '/utility-search.php?q=' + encodeURIComponent(q));
            const data = await res.json();
            if (!res.ok || data.error) {
                setPanelError(data.error || data.message || 'Utility not found.');
                setPanelLoading(false);
                return;
            }
            var matches = data.matches || [];
            if (matches.length === 0) {
                setPanelError('No matching utility found.', null);
                setPanelLoading(false);
                return;
            }
            var first = matches[0];
            if (first.bbox && first.bbox.south != null && first.bbox.north != null && first.bbox.west != null && first.bbox.east != null) {
                flyToBounds({ south: first.bbox.south, north: first.bbox.north, west: first.bbox.west, east: first.bbox.east });
            }
            if (first.geometries && first.geometries.length > 0) {
                drawUtilityBoundary(first.geometries);
            } else if (first.geometry) {
                drawUtilityBoundary(first.geometry);
            } else {
                clearUtilityBoundary();
            }
            var utilityName = first.utility || first.name || q;
            var rateClass = (document.getElementById('rate-class') && document.getElementById('rate-class').value) || 'residential';
            var eligRes = await fetch(API_BASE + '/eligibility.php?utility=' + encodeURIComponent(utilityName) + '&rate_class=' + encodeURIComponent(rateClass));
            var eligData = await eligRes.json().catch(function () { return {}; });
            var eligible = eligData.eligible === true;
            var eligibleRateClasses = Array.isArray(eligData.eligible_rate_classes) ? eligData.eligible_rate_classes : [];
            var referenceLink = eligData.reference_link || null;
            var eligibilityUpdated = eligData.eligibility_updated || null;
            var territorySource = (first.geometries && first.geometries.length > 0) ? 'hifld' : 'local';
            setPanelResult(utilityName, eligible, eligibleRateClasses, referenceLink, eligibilityUpdated, territorySource);
        } catch (err) {
            setPanelError('Search failed. Please try again.', null);
        } finally {
            setPanelLoading(false);
        }
    }

    var suggestTimeout = null;
    var suggestHighlight = -1;

    function showSuggestions(suggestions) {
        var list = document.getElementById('utility-suggest-list');
        var input = document.getElementById('search-utility');
        if (!list || !input) return;
        list.innerHTML = '';
        if (!suggestions || suggestions.length === 0) {
            list.style.display = 'none';
            input.setAttribute('aria-expanded', 'false');
            return;
        }
        suggestions.forEach(function (s, i) {
            var li = document.createElement('li');
            li.setAttribute('role', 'option');
            li.setAttribute('id', 'suggest-' + i);
            li.textContent = s.name;
            li.tabIndex = -1;
            li.addEventListener('click', function () {
                input.value = s.name;
                list.style.display = 'none';
                input.setAttribute('aria-expanded', 'false');
                searchByUtility();
            });
            li.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    input.value = s.name;
                    list.style.display = 'none';
                    input.setAttribute('aria-expanded', 'false');
                    searchByUtility();
                }
            });
            list.appendChild(li);
        });
        list.style.display = 'block';
        input.setAttribute('aria-expanded', 'true');
        suggestHighlight = -1;
    }

    function hideSuggestions() {
        var list = document.getElementById('utility-suggest-list');
        var input = document.getElementById('search-utility');
        if (list) list.style.display = 'none';
        if (input) input.setAttribute('aria-expanded', 'false');
        suggestHighlight = -1;
    }

    function fetchSuggestions(q) {
        if (q.length < 2) {
            showSuggestions([]);
            return;
        }
        fetch(API_BASE + '/utility-suggest.php?q=' + encodeURIComponent(q))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var suggestions = data.suggestions || [];
                showSuggestions(suggestions);
            })
            .catch(function () { showSuggestions([]); });
    }

    function bindEvents() {
        document.getElementById('btn-search-address').addEventListener('click', searchByAddress);
        document.getElementById('btn-search-utility').addEventListener('click', searchByUtility);
        document.getElementById('search-address').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); searchByAddress(); }
        });
        var utilInput = document.getElementById('search-utility');
        var suggestList = document.getElementById('utility-suggest-list');
        utilInput.addEventListener('input', function () {
            clearTimeout(suggestTimeout);
            var q = utilInput.value.trim();
            suggestTimeout = setTimeout(function () { fetchSuggestions(q); }, 300);
        });
        utilInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                hideSuggestions();
                searchByUtility();
            } else if (e.key === 'Escape') {
                hideSuggestions();
            } else if (e.key === 'ArrowDown' && suggestList && suggestList.style.display === 'block') {
                e.preventDefault();
                var opts = suggestList.querySelectorAll('[role="option"]');
                suggestHighlight = Math.min(suggestHighlight + 1, opts.length - 1);
                if (opts[suggestHighlight]) opts[suggestHighlight].focus();
            } else if (e.key === 'ArrowUp' && suggestList && suggestList.style.display === 'block') {
                e.preventDefault();
                var opts = suggestList.querySelectorAll('[role="option"]');
                suggestHighlight = Math.max(suggestHighlight - 1, -1);
                if (opts[suggestHighlight]) opts[suggestHighlight].focus();
            }
        });
        utilInput.addEventListener('blur', function () {
            setTimeout(hideSuggestions, 200);
        });
        document.addEventListener('click', function (e) {
            if (suggestList && !utilInput.contains(e.target) && !suggestList.contains(e.target)) {
                hideSuggestions();
            }
        });
    }

    function init() {
        initMap();
        bindEvents();
        setPanelPlaceholder();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
