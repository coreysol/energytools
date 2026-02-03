# DPP Map – Distributed Power Plant Eligibility Map

Interactive map to see whether you can register for a distributed power plant (DPP) in your area. Search by **address** or **electric utility**; eligibility is determined by utility and rate class.

## Requirements

- PHP 7.4+ (for Excel import with PhpSpreadsheet: PHP 8.1+)
- Web server with PHP (e.g. Apache, nginx+php-fpm)

## Setup

1. Point your web server document root (or a vhost) at the `dpp_map` folder so that `index.php` and the `api/` and `assets/` paths are served correctly.
2. **Geocoding** uses [Nominatim](https://nominatim.openstreetmap.org/) (no API key). Results are cached under `data/cache/`. Nominatim allows 1 request per second; the geocode API enforces this per IP and returns 429 with `Retry-After: 1` when exceeded.
3. **Utility territories**: The app fetches territory data **on demand** from HIFLD (Electric Retail Service Territories MapServer), with a cache under `data/cache/territories/`. If HIFLD is unavailable, it falls back to `data/territories.json`. You can still maintain a local file for offline or custom boundaries.
4. **DPP eligibility**: Maintain `config/dpp_eligibility.json` (utility → rate classes + reference link). You can import from CSV or Excel (see below).

## Import eligibility from CSV or Excel

- **CSV**: Columns `utility`, `rate_classes` (comma-separated: residential, commercial, industrial), `reference_link`. Run:
  ```bash
  php scripts/import_eligibility.php scripts/eligibility_sample.csv
  ```
- **Excel (.xlsx)**: Same column names. Install PhpSpreadsheet first:
  ```bash
  composer require phpoffice/phpspreadsheet
  php scripts/import_eligibility.php path/to/eligibility.xlsx
  ```

The script writes `config/dpp_eligibility.json`. The map reads this file for the eligibility panel and “Learn more” link.

## File layout

- `index.php` – Main page (map + search + eligibility panel)
- `api/geocode.php` – Nominatim proxy (caches by query)
- `api/utility-at-point.php` – (lat, lng) → utility + geometry/geometries (HIFLD or local)
- `api/utility-search.php` – name query → list of utilities + bbox + geometries
- `api/utility-suggest.php` – name query → suggestions for typeahead (max 15)
- `api/eligibility.php` – (utility, rate_class) → eligible + reference_link + eligibility_updated
- `config/dpp_eligibility.json` – Eligibility matrix (utility → rate_classes, reference_link)
- `data/territories.json` – GeoJSON of utility service territories
- `data/cache/` – Geocode and rate-limit data (created automatically)
- `data/cache/territories/` – HIFLD territory cache (created automatically)
- `api/territories_loader.php` – Shared loader for HIFLD fetch and local fallback
- `scripts/import_eligibility.php` – CSV/Excel → `config/dpp_eligibility.json`

## Styling

`assets/map.css` uses the same visual style as `maint_calc/mcalc.css` (header, buttons, container) so the DPP map matches the rest of energytools.
