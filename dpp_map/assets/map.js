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

    let suggestTimeout = null;
    let suggestHighlight = -1;

    function showAddressSuggestions(suggestions) {
        const list = document.getElementById('address-suggest-list');
        const input = document.getElementById('search-address');
        if (!list || !input) return;
        list.innerHTML = '';
        if (!suggestions || suggestions.length === 0) {
            list.style.display = 'none';
            input.setAttribute('aria-expanded', 'false');
            return;
        }
        suggestions.forEach(function (s, i) {
            const li = document.createElement('li');
            li.setAttribute('role', 'option');
            li.setAttribute('id', 'address-suggest-' + i);
            li.setAttribute('data-lat', String(s.lat));
            li.setAttribute('data-lng', String(s.lng));
            li.textContent = s.display_name;
            li.tabIndex = -1;
            li.addEventListener('click', function () {
                input.value = s.display_name;
                list.style.display = 'none';
                input.setAttribute('aria-expanded', 'false');
                flyTo(s.lat, s.lng);
                clearSearchError();
            });
            li.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    input.value = s.display_name;
                    list.style.display = 'none';
                    input.setAttribute('aria-expanded', 'false');
                    flyTo(s.lat, s.lng);
                    clearSearchError();
                }
            });
            list.appendChild(li);
        });
        list.style.display = 'block';
        input.setAttribute('aria-expanded', 'true');
        suggestHighlight = -1;
    }

    function hideAddressSuggestions() {
        const list = document.getElementById('address-suggest-list');
        const input = document.getElementById('search-address');
        if (list) list.style.display = 'none';
        if (input) input.setAttribute('aria-expanded', 'false');
        suggestHighlight = -1;
    }

    function fetchAddressSuggestions(q) {
        if (q.length < 2) {
            showAddressSuggestions([]);
            return;
        }
        fetch(API_BASE + '/geocode-suggest.php?q=' + encodeURIComponent(q))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                const suggestions = Array.isArray(data) ? data : [];
                showAddressSuggestions(suggestions);
            })
            .catch(function () { showAddressSuggestions([]); });
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
        const suggestList = document.getElementById('address-suggest-list');
        if (btn) btn.addEventListener('click', searchByAddress);
        if (input) {
            input.addEventListener('input', function () {
                clearTimeout(suggestTimeout);
                const q = input.value.trim();
                suggestTimeout = setTimeout(function () { fetchAddressSuggestions(q); }, 300);
            });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (suggestList && suggestList.style.display === 'block') {
                        const opts = suggestList.querySelectorAll('[role="option"]');
                        if (opts.length && suggestHighlight >= 0 && opts[suggestHighlight]) {
                            const lat = parseFloat(opts[suggestHighlight].dataset.lat);
                            const lng = parseFloat(opts[suggestHighlight].dataset.lng);
                            input.value = opts[suggestHighlight].textContent;
                            hideAddressSuggestions();
                            if (!isNaN(lat) && !isNaN(lng)) flyTo(lat, lng);
                            clearSearchError();
                            return;
                        }
                    }
                    searchByAddress();
                } else if (e.key === 'Escape') {
                    hideAddressSuggestions();
                } else if (e.key === 'ArrowDown' && suggestList && suggestList.style.display === 'block') {
                    e.preventDefault();
                    const opts = suggestList.querySelectorAll('[role="option"]');
                    suggestHighlight = Math.min(suggestHighlight + 1, opts.length - 1);
                    if (opts[suggestHighlight]) opts[suggestHighlight].focus();
                } else if (e.key === 'ArrowUp' && suggestList && suggestList.style.display === 'block') {
                    e.preventDefault();
                    const opts = suggestList.querySelectorAll('[role="option"]');
                    suggestHighlight = Math.max(suggestHighlight - 1, -1);
                    if (opts[suggestHighlight]) opts[suggestHighlight].focus();
                }
            });
            input.addEventListener('blur', function () {
                setTimeout(hideAddressSuggestions, 200);
            });
        }
        document.addEventListener('click', function (e) {
            if (input && suggestList && !input.contains(e.target) && !suggestList.contains(e.target)) {
                hideAddressSuggestions();
            }
        });
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
