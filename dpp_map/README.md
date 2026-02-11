# DPP Map – Distributed Power Plant Map

Interactive map that lets you search by **address** and zooms the map to that location with a marker.

## Requirements

- PHP 7.4+
- Web server with PHP (e.g. Apache, nginx+php-fpm)

## Setup

1. Point your web server document root (or a vhost) at the `dpp_map` folder so that `index.php` and the `api/` and `assets/` paths are served correctly.
2. **Geocoding** uses [Nominatim](https://nominatim.openstreetmap.org/) (no API key). Results are cached under `data/cache/`. Nominatim allows 1 request per second; the geocode API enforces this per IP and returns 429 with `Retry-After: 1` when exceeded.

## File layout

- `index.php` – Main page (address search + map)
- `api/geocode.php` – Nominatim proxy (caches by query)
- `assets/map.js` – Map and address search logic
- `assets/map.css` – Styles
- `data/cache/` – Geocode and rate-limit data (created automatically)

## Styling

`assets/map.css` uses the same visual style as `maint_calc/mcalc.css` (header, buttons, container) so the DPP map matches the rest of energytools.
