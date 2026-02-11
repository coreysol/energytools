/**
 * DPP Map - Leaflet map and address search
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

    function initMap() {
        map = L.map('map').setView(DEFAULT_CENTER, DEFAULT_ZOOM);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
    }

    function errorMessageFromCode(code) {
        const messages = {
            rate_limited: 'Too many searches. Please wait a moment and try again.',
            address_not_found: 'Address not found. Try a different search or check the spelling.',
            service_unavailable: 'Geocoding service is temporarily unavailable. Please try again later.',
            missing_query: 'Please enter an address.'
        };
        return messages[code] || null;
    }

    function showSearchError(message, code) {
        const el = document.getElementById('search-error');
        if (!el) return;
        el.textContent = (code && errorMessageFromCode(code)) || message || 'Something went wrong. Please try again.';
        el.style.display = 'block';
    }

    function clearSearchError() {
        const el = document.getElementById('search-error');
        if (el) {
            el.textContent = '';
            el.style.display = 'none';
        }
    }

    function flyTo(lat, lng, zoom) {
        if (!map) return;
        map.flyTo([lat, lng], zoom || 12);
        if (addressMarker) {
            map.removeLayer(addressMarker);
        }
        addressMarker = L.marker([lat, lng]).addTo(map);
    }

    async function searchByAddress() {
        const input = document.getElementById('search-address');
        const btn = document.getElementById('btn-search');
        const q = (input && input.value) ? input.value.trim() : '';
        if (!q) {
            showSearchError('Please enter an address.', 'missing_query');
            return;
        }

        clearSearchError();
        if (btn) btn.disabled = true;

        try {
            const geoRes = await fetch(API_BASE + '/geocode.php?q=' + encodeURIComponent(q));
            const geoData = await geoRes.json();
            if (!geoRes.ok || geoData.error) {
                showSearchError(geoData.error || geoData.message, geoData.code);
                return;
            }
            const lat = parseFloat(geoData.lat);
            const lng = parseFloat(geoData.lng);
            if (isNaN(lat) || isNaN(lng)) {
                showSearchError('Invalid coordinates returned.', null);
                return;
            }
            flyTo(lat, lng);
        } catch (err) {
            showSearchError('Search failed. Please try again.', null);
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    function bindEvents() {
        const btn = document.getElementById('btn-search');
        const input = document.getElementById('search-address');
        if (btn) btn.addEventListener('click', searchByAddress);
        if (input) {
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchByAddress();
                }
            });
        }
    }

    function init() {
        initMap();
        bindEvents();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
