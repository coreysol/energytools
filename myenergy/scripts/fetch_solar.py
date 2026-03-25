#!/usr/bin/env python3
"""
One-time script: fetch monthly solar production from SolarEdge API.

Requires environment variables:
    SOLAREDGE_API_KEY   – your SolarEdge API key
    SOLAREDGE_SITE_ID   – your site ID (visible in the monitoring portal URL)

Usage:
    export SOLAREDGE_API_KEY=xxxxx
    export SOLAREDGE_SITE_ID=12345
    python3 scripts/fetch_solar.py

Output: data/solar_monthly.json
"""

import json
import os
import ssl
import sys
import urllib.request
import urllib.error
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
OUT_PATH = ROOT / 'data' / 'solar_monthly.json'

API_BASE = 'https://monitoringapi.solaredge.com'

# SolarEdge API limits date ranges to 1 year per request for monthly data,
# so we paginate year by year.
START_YEAR = 2012
END_YEAR = 2025

def _make_ssl_context() -> ssl.SSLContext:
    """Build an SSL context, falling back gracefully on macOS where the
    Python installer doesn't configure system certificates by default."""
    try:
        import certifi
        return ssl.create_default_context(cafile=certifi.where())
    except ImportError:
        pass

    ctx = ssl.create_default_context()
    try:
        ctx.load_default_certs()
        urllib.request.urlopen('https://monitoringapi.solaredge.com', context=ctx, timeout=5)
        return ctx
    except Exception:
        pass

    print('  ⚠ System SSL certificates not found; using unverified HTTPS.')
    print('    To fix permanently, run:')
    print('    /Applications/Python\\ 3.13/Install\\ Certificates.command')
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE
    return ctx


SSL_CTX = None  # lazily initialized


def fetch_year(site_id: str, api_key: str, year: int) -> list[dict]:
    """Fetch monthly energy for a single calendar year."""
    global SSL_CTX
    if SSL_CTX is None:
        SSL_CTX = _make_ssl_context()

    start = f'{year}-01-01'
    end = f'{year}-12-31'
    url = (
        f'{API_BASE}/site/{site_id}/energy'
        f'?timeUnit=MONTH&startDate={start}&endDate={end}&api_key={api_key}'
    )

    print(f'  Fetching {year} ...', end=' ', flush=True)

    try:
        req = urllib.request.Request(url)
        with urllib.request.urlopen(req, timeout=30, context=SSL_CTX) as resp:
            body = json.loads(resp.read().decode())
    except urllib.error.HTTPError as e:
        print(f'HTTP {e.code}')
        raise SystemExit(f'SolarEdge API error for {year}: {e.code} {e.reason}')

    values = body.get('energy', {}).get('values', [])
    records = []
    for entry in values:
        date_str = entry.get('date', '')  # "2012-01-01 00:00:00"
        wh = entry.get('value')
        if wh is None:
            continue
        year_month = date_str[:7]  # "2012-01"
        kwh = round(wh / 1000.0, 1)
        records.append({'year_month': year_month, 'solar_kwh': kwh})

    print(f'{len(records)} months')
    return records


def main():
    api_key = os.environ.get('SOLAREDGE_API_KEY')
    site_id = os.environ.get('SOLAREDGE_SITE_ID')

    if not api_key or not site_id:
        print('Error: set SOLAREDGE_API_KEY and SOLAREDGE_SITE_ID environment variables.')
        print('Example:')
        print('  export SOLAREDGE_API_KEY=your_key_here')
        print('  export SOLAREDGE_SITE_ID=12345')
        sys.exit(1)

    print(f'Fetching SolarEdge data for site {site_id} ({START_YEAR}-{END_YEAR})')

    all_records = []
    for year in range(START_YEAR, END_YEAR + 1):
        records = fetch_year(site_id, api_key, year)
        all_records.extend(records)

    all_records.sort(key=lambda r: r['year_month'])

    OUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    with open(OUT_PATH, 'w', encoding='utf-8') as f:
        json.dump(all_records, f, indent=2)

    print(f'\nWrote {len(all_records)} records to {OUT_PATH}')


if __name__ == '__main__':
    main()
