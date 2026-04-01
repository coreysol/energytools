<?php
/**
 * Energy Meme Generator - Main page
 *
 * Two-mood interface: "I need a boost!" (positive facts) or "Motivation!"
 * (challenging facts). A specific fact can be linked via ?id=N.
 */

require_once __DIR__ . '/includes/config.php';

// Load facts
$facts_file = __DIR__ . '/data/facts.json';
if (!file_exists($facts_file)) die('Facts data file not found.');
$facts = json_decode(file_get_contents($facts_file), true) ?? [];
if (empty($facts)) die('No facts available.');

// Look up a specific fact for OG meta tags only (?id= in URL).
// The card is never pre-rendered; it always requires a button click.
$requested_id   = isset($_GET['id']) ? (int)$_GET['id'] : null;
$preloaded_fact = null;

if ($requested_id !== null) {
    foreach ($facts as $f) {
        if ((int)$f['id'] === $requested_id) { $preloaded_fact = $f; break; }
    }
}


// Absolute URLs for OG meta
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = $protocol . '://' . $host . BASE_PATH . '/';

$og_title = 'Energy Facts & Memes';
$og_desc  = 'Inspiring and motivating facts from the clean energy transition.';
$og_img   = $base_url . 'assets/img/og-default.png'; // optional static fallback
if ($preloaded_fact) {
    $og_desc = $preloaded_fact['fact'];
    $og_img  = $base_url . 'generate.php?id=' . $preloaded_fact['id'];
}
$og_url    = $preloaded_fact
    ? $base_url . '?id=' . $preloaded_fact['id']
    : $base_url;

$base_href = BASE_PATH . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= htmlspecialchars($base_href) ?>">
    <title>Energy Facts &amp; Memes</title>
    <meta name="description" content="<?= htmlspecialchars(mb_substr($og_desc, 0, 160)) ?>">

    <meta property="og:type"        content="website">
    <meta property="og:title"       content="<?= htmlspecialchars($og_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($og_desc) ?>">
    <meta property="og:image"       content="<?= htmlspecialchars($og_img) ?>">
    <meta property="og:url"         content="<?= htmlspecialchars($og_url) ?>">

    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= htmlspecialchars($og_title) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($og_desc) ?>">
    <meta name="twitter:image"       content="<?= htmlspecialchars($og_img) ?>">

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container" id="fact-app"
         data-base="<?= htmlspecialchars($base_href) ?>"
         data-current-id="0"
         data-current-tone="">

        <header>
            <h1>Energy Facts</h1>
            <p class="subtitle">Shareable facts from the clean energy transition</p>
        </header>

        <main>

            <!-- ── Mood selector ─────────────────────────────────── -->
            <div class="tone-selector">
                <button
                    class="btn-tone btn-tone--boost"
                    id="btn-boost"
                    onclick="selectTone('boost')"
                    aria-pressed="false">
                    <span class="tone-label">Inspire me!</span>
                    <span class="tone-sub">Rainbow cats dancing in unison</span>
                </button>

                <button
                    class="btn-tone btn-tone--lucky"
                    id="btn-lucky"
                    onclick="selectLucky()"
                    aria-pressed="false">
                    <span class="tone-label">I'm feeling lucky</span>
                </button>

                <button
                    class="btn-tone btn-tone--motivate"
                    id="btn-motivate"
                    onclick="selectTone('motivate')"
                    aria-pressed="false">
                    <span class="tone-label">Motivate me!</span>
                    <span class="tone-sub">Miles to go before we sleep</span>
                </button>
            </div>

            <!-- ── Welcome prompt (hidden once a fact is shown) ──── -->
            <p class="welcome-note" id="welcome-note">
                Choose a mood above to get started.
            </p>

            <!-- ── Fact card (always hidden on page load) ─────────── -->
            <div id="fact-container" class="fact-container fact-container--hidden">

                <div class="meme-card" id="meme-card">

                    <div class="card-meta">
                        <span class="category-badge" id="fact-category"></span>
                        <span class="tone-badge" id="fact-tone-badge"></span>
                    </div>

                    <blockquote class="fact-text" id="fact-text"></blockquote>

                    <div class="fact-image fact-image--hidden" id="fact-image-wrap" aria-hidden="true">
                        <img src="" alt="" loading="lazy">
                    </div>

                    <div class="explanation" id="fact-explanation"></div>

                    <div class="fact-source" id="fact-source"></div>

                </div><!-- .meme-card -->

                <div class="actions">
                    <a class="btn-ghost" id="btn-download" href="#" download="">
                        Download Image
                    </a>

                    <button class="btn-ghost" id="btn-copy" onclick="copyFact()">Copy Text</button>

                    <button class="btn-ghost btn-icon" id="btn-share-x" onclick="shareX()" title="Share on X (Twitter)" aria-label="Share on X">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.737-8.835L1.254 2.25H8.08l4.213 5.567zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </button>

                    <button class="btn-ghost btn-icon" id="btn-share-li" onclick="shareLinkedIn()" title="Share on LinkedIn" aria-label="Share on LinkedIn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                    </button>

                    <button class="btn-ghost btn-icon" id="btn-share-fb" onclick="shareFacebook()" title="Share on Facebook" aria-label="Share on Facebook">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M24 12.073C24 5.404 18.627 0 12 0S0 5.404 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.886v2.268h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/>
                        </svg>
                    </button>

                    <button class="btn-ghost btn-icon" id="btn-share-ig" onclick="shareInstagram()" title="Share on Instagram" aria-label="Share on Instagram">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                        </svg>
                    </button>
                </div>

                <p class="share-note">
                    Permanent link: <a href="#" id="fact-permalink"></a>
                </p>

            </div><!-- #fact-container -->

        </main>

        <footer>
            <p>Facts sourced from peer-reviewed research and public reports. &nbsp;&middot;&nbsp; <a href="../">&#8592; All Energy Tools</a></p>
        </footer>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
