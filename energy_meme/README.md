# Energy Facts & Memes — Customization Guide

This tool displays shareable energy facts in two moods — **Inspire me** (positive progress) and **Motivate me** (gaps still to close) — plus an **I'm feeling lucky** button that alternates between the two. Each fact can have its own on-screen image and a downloadable PNG is generated automatically via PHP GD.

---

## Directory structure

```
energy_meme/
  data/
    facts.json          ← All fact content lives here
  backgrounds/          ← Background images for the downloadable PNG
  images/
    facts/              ← Optional per-fact images shown in the web card
  assets/
    fonts/              ← Optional TTF font for the downloadable PNG
  cache/
    images/             ← Auto-generated PNG cache (do not edit)
```

---

## Adding and editing facts (`data/facts.json`)

The file is a JSON array. Each fact is an object with the following fields:

```json
{
  "id": 17,
  "tone": "boost",
  "category": "Solar Energy",
  "fact": "The one-sentence statement displayed prominently on the card.",
  "explanation": "The longer educational context shown below the quote — 2–4 sentences works well.",
  "source": "Author / Organisation, Report Title Year",
  "source_url": "https://link-to-the-source.org",
  "background": "solar"
}
```

### Field reference

| Field | Required | Notes |
|---|---|---|
| `id` | Yes | Unique integer. Never reuse a deleted ID — cached images are keyed to it. |
| `tone` | Yes | `"boost"` for inspiring/positive facts. `"motivate"` for challenging/gap facts. |
| `category` | Yes | Short label shown in the orange badge (e.g. `"Solar Energy"`, `"Policy"`). |
| `fact` | Yes | The headline statement. Aim for 1–2 sentences and under ~200 characters for best image layout. |
| `explanation` | Yes | Educational context. 2–4 sentences. Shown below the quote on the card. |
| `source` | Yes | Attribution text (e.g. `"IEA, World Energy Outlook 2023"`). |
| `source_url` | No | If provided, the source renders as a clickable link on the card and is printed in the downloadable image. |
| `background` | No | Base name of a file in `backgrounds/` to use as the image background for the downloadable PNG (e.g. `"solar"` → looks for `backgrounds/solar.jpg`). Omit or set to `null` for no category background. |

### Tips

- Keep IDs sequential. The next available ID in the current dataset is **17**.
- Aim for a roughly equal number of `boost` and `motivate` facts so both buttons feel equally rich.
- The `fact` text is word-wrapped in the downloadable image. Very long facts will shrink the font automatically, but under ~180 characters gives the best result.
- After adding facts, **clear the image cache** if you've changed any existing fact text (see Cache section below).

---

## Background images for the downloadable PNG

Place image files in `backgrounds/`. The generator resolves the background in this priority order for each fact:

1. `images/facts/{id}.jpg` — fact-specific image (see next section)
2. `backgrounds/{background}.jpg` — matched by the `"background"` field in the fact
3. `backgrounds/default.jpg` — catch-all fallback
4. Built-in dark gradient — used automatically if no image files are found

### Recommended specs

- **Format:** JPG or PNG (WebP also supported if PHP was compiled with WebP support)
- **Size:** 1200 × 630 px minimum (standard social share / OG image ratio)
- **Content:** Dark or high-contrast images work best — the generator applies a semi-transparent dark overlay before drawing text

### Naming convention

| Filename | Used when |
|---|---|
| `backgrounds/default.jpg` | Any fact with no `background` field or whose named background file is missing |
| `backgrounds/solar.jpg` | Facts with `"background": "solar"` |
| `backgrounds/wind.jpg` | Facts with `"background": "wind"` |
| `backgrounds/lucky-boost.jpg` | **Lucky button only** — inspiring facts (overrides all other backgrounds) |
| `backgrounds/lucky-motivate.jpg` | **Lucky button only** — motivating facts (overrides all other backgrounds) |

You can add as many named backgrounds as you like — just match the filename to the `"background"` value in the fact.

---

## Per-fact images shown in the web card

These are **separate from the downloadable PNG backgrounds**. They appear as a photo in the on-screen fact card (not the generated image).

Place files in `images/facts/` named after the fact ID:

```
images/facts/1.jpg
images/facts/5.png
images/facts/12.webp
```

The filename must be the fact's `id` with a supported extension (`.jpg`, `.jpeg`, `.png`, `.webp`). No changes to `facts.json` are needed — the app detects them automatically.

**Recommended specs:** Any aspect ratio; displayed at full card width with `max-height: 380px` and `object-fit: cover`. Landscape images work best.

---

## Improving downloadable image quality (optional font setup)

By default the image generator searches for common system fonts (DejaVu, Liberation, Arial). For the best and most consistent results, place any TTF font at:

```
assets/fonts/font.ttf
```

This path is checked first. Any free TrueType font works — [Inter](https://rsms.me/inter/), [Source Sans](https://fonts.google.com/specimen/Source+Sans+3), and [Open Sans](https://fonts.google.com/specimen/Open+Sans) are good options. Download the `.ttf` file and drop it in the folder.

---

## Clearing the image cache

Downloadable PNGs are cached in `cache/images/` as `fact_{id}.png`. The cache is not automatically invalidated when you edit `facts.json`.

**When to clear the cache:**
- After changing the `fact` text, `category`, or `source` of an existing fact
- After adding or replacing a background image
- After installing a new font

**How to clear:** Delete the files in `cache/images/`. The next request for each fact will regenerate the image. You can delete individual files (e.g. `fact_3.png`) to regenerate only specific facts.

```bash
# Clear all cached images
rm cache/images/*.png

# Clear one specific fact
rm cache/images/fact_3.png
```

---

## Tone categories at a glance

Current facts are organised into these categories:

| Category | Examples |
|---|---|
| Solar Energy | Cost declines, installed capacity, potential |
| Wind Energy | U.S. capacity milestones, turbine scale |
| Grid & Storage | Battery growth, virtual power plants |
| Climate | Temperature records, emissions trends, job creation |
| Policy | IRA investment, international commitments |
| Demand & Efficiency | LED adoption, heat pumps, building retrofits |

You are not limited to these — the `category` field is free text and will render on the badge exactly as written.
