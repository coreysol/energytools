#!/usr/bin/env python3
"""
One-time script: merge grid_monthly.json + solar_monthly.json into energy_story.json.

If solar_monthly.json doesn't exist yet, creates energy_story.json with
solar_kwh = null for all months (you can re-run after fetching solar data).

Usage:
    python3 scripts/merge_data.py

Output: data/energy_story.json
"""

import json
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
GRID_PATH = ROOT / 'data' / 'grid_monthly.json'
SOLAR_PATH = ROOT / 'data' / 'solar_monthly.json'
OUT_PATH = ROOT / 'data' / 'energy_story.json'

SEASON_MAP = {
    12: 'winter', 1: 'winter', 2: 'winter',
    3: 'spring', 4: 'spring', 5: 'spring',
    6: 'summer', 7: 'summer', 8: 'summer',
    9: 'fall', 10: 'fall', 11: 'fall',
}


def main():
    with open(GRID_PATH, encoding='utf-8') as f:
        grid_data = json.load(f)

    solar_lookup = {}
    if SOLAR_PATH.exists():
        with open(SOLAR_PATH, encoding='utf-8') as f:
            for rec in json.load(f):
                solar_lookup[rec['year_month']] = rec['solar_kwh']
        print(f'Loaded {len(solar_lookup)} solar records')
    else:
        print('No solar_monthly.json found — merging grid only (solar_kwh = null)')

    merged = []
    for rec in grid_data:
        ym = rec['year_month']
        month = int(ym.split('-')[1])
        solar = solar_lookup.get(ym)

        entry = {
            'year_month': ym,
            'grid_kwh': rec['grid_kwh'],
            'solar_kwh': solar,
            'season': SEASON_MAP[month],
        }
        merged.append(entry)

    merged.sort(key=lambda r: r['year_month'])

    OUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    with open(OUT_PATH, 'w', encoding='utf-8') as f:
        json.dump(merged, f, indent=2)

    solar_count = sum(1 for r in merged if r['solar_kwh'] is not None)
    print(f'Wrote {len(merged)} records ({solar_count} with solar) to {OUT_PATH}')


if __name__ == '__main__':
    main()
