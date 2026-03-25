#!/usr/bin/env python3
"""
One-time script: unpivot the wide-format utility CSV into long-format JSON.

Input:  Utility-Analysis - Electricity.csv (months as rows, years as columns)
Output: data/grid_monthly.json
"""

import csv
import json
from pathlib import Path

MONTH_NAMES = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
]
MONTH_MAP = {name: i + 1 for i, name in enumerate(MONTH_NAMES)}

ROOT = Path(__file__).resolve().parent.parent
CSV_PATH = ROOT / 'Utility-Analysis - Electricity.csv'
OUT_PATH = ROOT / 'data' / 'grid_monthly.json'


def parse_kwh(raw: str) -> int | None:
    """Parse a kWh value, handling quoted numbers with commas and blanks."""
    s = raw.strip().strip('"')
    if not s:
        return None
    s = s.replace(',', '')
    return int(s)


def main():
    records = []

    with open(CSV_PATH, newline='', encoding='utf-8') as f:
        reader = csv.reader(f)
        header = next(reader)  # Month, 2004, 2005, ...
        years = header[1:]     # ['2004', '2005', ..., '2026']

        for row in reader:
            month_name = row[0].strip()
            if month_name not in MONTH_MAP:
                break  # stop at summary rows (Kwh, Average, blanks)

            month_num = MONTH_MAP[month_name]

            for col_idx, year_str in enumerate(years):
                year = int(year_str)
                if year >= 2026:
                    continue  # skip partial 2026

                raw_val = row[col_idx + 1] if col_idx + 1 < len(row) else ''
                kwh = parse_kwh(raw_val)
                if kwh is None:
                    continue

                year_month = f'{year}-{month_num:02d}'
                records.append({
                    'year_month': year_month,
                    'grid_kwh': kwh,
                })

    records.sort(key=lambda r: r['year_month'])

    OUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    with open(OUT_PATH, 'w', encoding='utf-8') as f:
        json.dump(records, f, indent=2)

    print(f'Wrote {len(records)} records to {OUT_PATH}')


if __name__ == '__main__':
    main()
