# My Energy — Scrollytelling Home Energy Story

A static scrollytelling page that visualizes 22 years of home energy data (2004–2025), combining monthly grid consumption with SolarEdge solar production data. No build step — the repo is the deployable site.

## Project structure

```
myenergy/
├── index.html              Main page
├── css/
│   └── style.css           All styles
├── js/
│   ├── app.js              Application code (chart, scrolling, stats)
│   └── vendor/
│       ├── d3.v7.min.js    D3.js (vendored)
│       └── scrollama.min.js Scrollama (vendored)
├── data/
│   ├── energy_story.json   Merged grid + solar data
│   ├── events.json         Narrative events (hand-editable)
│   ├── grid_monthly.json   Grid-only data (intermediate)
│   └── solar_monthly.json  Solar-only data
├── scripts/
│   ├── transform_csv.py    One-time: unpivot utility CSV → grid_monthly.json
│   ├── fetch_solar.py      One-time: SolarEdge API → solar_monthly.json
│   └── merge_data.py       One-time: grid + solar → energy_story.json
└── README.md
```

## Local preview

Open `index.html` directly in a browser, or use any static server:

```bash
python3 -m http.server 8000
```

Then visit `http://localhost:8000`.

## Deploying to DreamHost

The repo **is** the site. Upload the contents of this folder to your DreamHost directory:

```
public_html/energytools/myenergy/
```

Or pull directly from GitHub on the server:

```bash
cd ~/energytools.example.com/energytools/myenergy
git pull
```

No Node.js, no build step, no server-side runtime needed.

## Data pipeline

These scripts run on your machine only — not on the server. Run them once (or again to update the data for a new "edition").

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

## Updating vendor libraries

D3 and Scrollama are vendored in `js/vendor/`. To update them, download the new minified files and replace:

- D3: https://d3js.org/ → download `d3.min.js` → save as `js/vendor/d3.v7.min.js`
- Scrollama: https://github.com/russellsamora/scrollama → `build/scrollama.min.js` → save as `js/vendor/scrollama.min.js`
