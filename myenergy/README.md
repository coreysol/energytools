# My Energy — Scrollytelling Home Energy Story

A static scrollytelling page that visualizes 22 years of home energy data (2004–2025), combining monthly grid consumption with SolarEdge solar production data. Built with D3, Scrollama, and Vite. Deployed as plain static files — no server-side code.

## Quick start

```bash
npm install
npm run dev       # opens http://localhost:5173
```

## Project structure

```
myenergy/
├── index.html              Main page
├── src/
│   ├── main.js             Entry point, data loading, Scrollama init
│   ├── chart.js            D3 chart (dual-series area + line)
│   ├── annotations.js      Event markers and time-window highlights
│   ├── scroll.js           Scrollama step handlers
│   └── style.css           All styles
├── data/
│   ├── energy_story.json   Merged grid + solar data (committed)
│   ├── events.json         Narrative events (hand-editable)
│   ├── grid_monthly.json   Grid-only data (intermediate)
│   └── solar_monthly.json  Solar-only data (after SolarEdge fetch)
├── scripts/
│   ├── transform_csv.py    One-time: unpivot utility CSV → grid_monthly.json
│   ├── fetch_solar.py      One-time: SolarEdge API → solar_monthly.json
│   └── merge_data.py       One-time: grid + solar → energy_story.json
├── vite.config.js
└── package.json
```

## Data pipeline

The data pipeline runs on your machine only — not on every deploy. You run these scripts once (or again if you want a new "edition" with updated data).

### Step 1: Transform the utility CSV

Your utility data lives in `Utility-Analysis - Electricity.csv` (wide format: months as rows, years as columns). Transform it to long-format JSON:

```bash
python3 scripts/transform_csv.py
```

Output: `data/grid_monthly.json`

### Step 2: Fetch SolarEdge data (one-time)

Set your SolarEdge credentials as environment variables, then run:

```bash
export SOLAREDGE_API_KEY=your_api_key_here
export SOLAREDGE_SITE_ID=your_site_id_here
python3 scripts/fetch_solar.py
```

Output: `data/solar_monthly.json`

The API key is never committed or embedded in the site. The output JSON is committed to the repo so the site works without API access.

### Step 3: Merge into final dataset

```bash
python3 scripts/merge_data.py
```

Output: `data/energy_story.json` (this is what the site reads)

If you haven't run the SolarEdge fetch yet, this still works — solar values will be `null`.

## Editing events

Edit `data/events.json` directly. Each event has:

| Field         | Description                                          |
|---------------|------------------------------------------------------|
| `slug`        | Unique ID                                            |
| `title`       | Heading shown in the scroll step                     |
| `body`        | Narrative paragraph                                  |
| `anchor`      | Year-month (e.g. `"2012-06"`) — chart position       |
| `end`         | Optional end year-month for multi-month spans         |
| `sensitivity` | `"public"` (exact date shown) or `"soft"` (season/year only) |

Scroll order follows the array order. Add, remove, or reorder events freely.

## Building for production

```bash
npm run build
```

Output goes to `dist/`. This contains everything needed for DreamHost:

```
dist/
├── index.html
├── assets/
│   ├── index-*.js
│   └── index-*.css
├── energy_story.json
└── events.json
```

## Deploying to DreamHost

Upload the **contents** of `dist/` to your DreamHost directory:

```
public_html/energytools/myenergy/
```

No Node.js, PHP, or any server-side runtime needed. Just static files.

You can upload via SFTP, rsync, or the DreamHost file manager:

```bash
rsync -avz --delete dist/ user@server:~/energytools.example.com/energytools/myenergy/
```

## Creating a new edition

If you want to update the story with newer data:

1. Update the CSV with new months
2. Re-run `transform_csv.py`
3. Re-run `fetch_solar.py` (to get new solar months)
4. Re-run `merge_data.py`
5. Edit events in `events.json` if needed
6. `npm run build`
7. Upload `dist/` to DreamHost
