# DPP Map – Distributed Power Plant Map

Interactive map that lets you search by **address** and zooms the map to that location with a marker.

## Requirements

- PHP 7.4+
- Web server with PHP (e.g. Apache, nginx+php-fpm)

## Setup

1. Point your web server document root (or a vhost) at the `dpp_map` folder so that `index.php` and the `api/` and `assets/` paths are served correctly.
2. **Geocoding** uses [Nominatim](https://nominatim.openstreetmap.org/) (no API key). Results are cached under `data/cache/`. Nominatim allows 1 request per second; the geocode API enforces this per IP and returns 429 with `Retry-After: 1` when exceeded.

## Utility boundaries (red polygon)

The map shows the electric utility service territory containing the searched address. Data is loaded from [ORNL OpenDataSoft](https://ornl.opendatasoft.com/explore/dataset/electric-retail-service-territories) (Electric Retail Service Territories) by bounding box, with fallback to `data/territories.json`. Responses are cached under `data/cache/territories/`.

**Note:** The ORNL API exposes utility centroids (points) only, not polygon boundaries. When using ORNL, the map shows the utility name for the nearest utility to the searched point but does not draw a red polygon. Local `data/territories.json` and pre-cached tiles use the same format; if you have polygon data in local or cache, the red outline will be drawn.

**Pre-caching for better performance:** Run the pre-cache script so lookups are served from disk instead of ORNL at request time:

```bash
cd dpp_map
php scripts/precache_territories.php
```

Optional args: `[west south east north] [step_degrees]`. Default is continental US with a 2° grid (~195 tiles, ~3+ minutes at 1 req/sec). After pre-caching, you can increase `TERRITORIES_CACHE_TTL` in `api/territories_loader.php` (e.g. to 604800 for 7 days).

## File layout

- `index.php` – Main page (address search + map)
- `api/geocode.php` – Nominatim proxy (caches by query)
- `api/geocode-suggest.php` – Address autocomplete (Nominatim)
- `api/utility-at-point.php` – (lat, lng) → utility boundary geometry
- `api/territories_loader.php` – HIFLD + local territory loading
- `assets/map.js` – Map and address search logic
- `assets/map.css` – Styles
- `data/cache/` – Geocode and rate-limit data (created automatically)
- `data/cache/territories/` – Cached ORNL boundary tiles (or pre-cached by script)
- `data/territories.json` – Local fallback boundaries
- `scripts/precache_territories.php` – Pre-fill boundary cache from ORNL OpenDataSoft

## Styling

`assets/map.css` uses the same visual style as `maint_calc/mcalc.css` (header, buttons, container) so the DPP map matches the rest of energytools.
