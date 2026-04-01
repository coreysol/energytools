/**
 * Energy Meme Generator - Client-side logic
 *
 * Handles: tone-based fact selection, DOM update, pushState URL sync,
 *          copy-to-clipboard, X share, LinkedIn share, download link update.
 */

(function () {
    'use strict';

    const app = document.getElementById('fact-app');
    if (!app) return;

    const BASE = app.dataset.base || './';

    let currentId      = parseInt(app.dataset.currentId, 10) || 0;
    let currentTone    = app.dataset.currentTone || '';
    let luckyNextTone  = 'boost'; // alternates each lucky press
    let isLuckyMode    = false;   // true while a lucky-sourced fact is displayed

    // ── DOM refs ──────────────────────────────────────────────────
    const welcomeNote   = document.getElementById('welcome-note');
    const factContainer = document.getElementById('fact-container');
    const catEl         = document.getElementById('fact-category');
    const toneBadgeEl   = document.getElementById('fact-tone-badge');
    const textEl        = document.getElementById('fact-text');
    const imgWrap       = document.getElementById('fact-image-wrap');
    const imgEl         = imgWrap ? imgWrap.querySelector('img') : null;
    const explEl        = document.getElementById('fact-explanation');
    const sourceEl      = document.getElementById('fact-source');
    const dlBtn         = document.getElementById('btn-download');
    const copyBtn       = document.getElementById('btn-copy');
    const permalink     = document.getElementById('fact-permalink');
    const boostBtn      = document.getElementById('btn-boost');
    const motivateBtn   = document.getElementById('btn-motivate');
    const luckyBtn      = document.getElementById('btn-lucky');
    const memeCard      = document.getElementById('meme-card');

    // ── Update card DOM with a fact object ─────────────────────── 
    function updateCard(fact) {
        currentId   = fact.id;
        currentTone = fact.tone;
        app.dataset.currentId   = fact.id;
        app.dataset.currentTone = fact.tone;

        // Category + tone badge
        if (catEl) catEl.textContent = fact.category;
        if (toneBadgeEl) {
            toneBadgeEl.textContent = fact.tone === 'motivate' ? 'Motivating' : 'Inspiring';
            toneBadgeEl.className   = 'tone-badge tone-badge--' + fact.tone;
        }

        // Fact text
        if (textEl) textEl.textContent = fact.fact;

        // Explanation
        if (explEl) explEl.textContent = fact.explanation;

        // Source
        if (sourceEl) {
            sourceEl.innerHTML = '';
            if (fact.source_url) {
                const a = document.createElement('a');
                a.href        = fact.source_url;
                a.target      = '_blank';
                a.rel         = 'noopener noreferrer';
                a.textContent = fact.source;
                sourceEl.appendChild(a);
            } else {
                sourceEl.textContent = fact.source;
            }
        }

        // Fact-specific image
        if (imgWrap && imgEl) {
            if (fact.has_image && fact._image_filename) {
                imgEl.src = BASE + 'images/facts/' + fact._image_filename;
                imgEl.alt = fact.category;
                imgWrap.classList.remove('fact-image--hidden');
                imgWrap.removeAttribute('aria-hidden');
            } else {
                imgWrap.classList.add('fact-image--hidden');
                imgWrap.setAttribute('aria-hidden', 'true');
                imgEl.src = '';
                imgEl.alt = '';
            }
        }

        // Show fact container, hide welcome note
        if (factContainer) factContainer.classList.remove('fact-container--hidden');
        if (welcomeNote)   welcomeNote.hidden = true;

        // Track lucky mode state and build download URL accordingly
        isLuckyMode = updateCard._isLucky || false;
        updateCard._isLucky = false;

        // Download link — include lucky_tone so generate.php uses the right background
        if (dlBtn) {
            let dlUrl = BASE + 'generate.php?id=' + fact.id;
            if (isLuckyMode) dlUrl += '&lucky_tone=' + encodeURIComponent(fact.tone);
            dlBtn.href     = dlUrl;
            dlBtn.download = 'energy-fact-' + fact.id + '.png';
        }

        // Update active tone button styles
        setActiveToneButton(fact.tone, isLuckyMode);

        // Apply background image — same source as the downloadable PNG
        applyCardBackground(fact.background_url);

        // Sync browser URL
        const newUrl = window.location.pathname + '?id=' + fact.id;
        history.pushState({ factId: fact.id, tone: fact.tone }, '', newUrl);

        // Permalink
        if (permalink) {
            permalink.href        = window.location.href;
            permalink.textContent = window.location.href;
        }

        // OG image meta — also include lucky_tone if applicable
        const ogImg = document.querySelector('meta[property="og:image"]');
        if (ogImg) {
            let ogUrl = BASE + 'generate.php?id=' + fact.id;
            if (isLuckyMode) ogUrl += '&lucky_tone=' + encodeURIComponent(fact.tone);
            ogImg.setAttribute('content', ogUrl);
        }
    }

    // ── Highlight the active tone button ──────────────────────────
    function setActiveToneButton(tone, isLucky) {
        const lucky = isLucky || false;
        if (boostBtn) {
            const on = !lucky && tone === 'boost';
            boostBtn.classList.toggle('active', on);
            boostBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
        }
        if (motivateBtn) {
            const on = !lucky && tone === 'motivate';
            motivateBtn.classList.toggle('active', on);
            motivateBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
        }
        if (luckyBtn) {
            luckyBtn.classList.toggle('active', lucky);
            luckyBtn.setAttribute('aria-pressed', lucky ? 'true' : 'false');
        }
    }

    // ── Apply background image to the card (mirrors generate.php) ─
    function applyCardBackground(bgUrl) {
        if (!memeCard) return;
        if (bgUrl) {
            const overlay = 'linear-gradient(rgba(0,0,0,0.52), rgba(0,0,0,0.52))';
            memeCard.style.backgroundImage    = overlay + ', url(' + BASE + bgUrl + ')';
            memeCard.style.backgroundSize     = 'cover';
            memeCard.style.backgroundPosition = 'center';
            memeCard.classList.add('meme-card--has-bg');
        } else {
            memeCard.style.backgroundImage    = '';
            memeCard.style.backgroundSize     = '';
            memeCard.style.backgroundPosition = '';
            memeCard.classList.remove('meme-card--has-bg');
        }
    }

    // ── Fetch a fact by tone ──────────────────────────────────────
    // luckyTone: pass the tone when in lucky mode so the API can
    // resolve lucky-specific backgrounds server-side.
    function fetchFact(tone, excludeId, luckyTone, callback) {
        let url = BASE + 'api/fact.php?tone=' + encodeURIComponent(tone);
        if (excludeId) url += '&exclude=' + excludeId;
        if (luckyTone) url += '&lucky_tone=' + encodeURIComponent(luckyTone);

        fetch(url)
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(callback)
            .catch(function (err) {
                console.error('Failed to load fact:', err);
            });
    }

    // ── Public: select a tone (called by named tone buttons) ─────
    window.selectTone = function (tone) {
        const exclude = currentTone === tone ? currentId : 0;
        fetchFact(tone, exclude, null, function (fact) {
            updateCard._isLucky = false;
            updateCard(fact);
        });
    };

    // ── Public: lucky button — alternates tone, uses lucky backgrounds
    window.selectLucky = function () {
        const tone = luckyNextTone;
        luckyNextTone = tone === 'boost' ? 'motivate' : 'boost';
        // Pass lucky_tone so the API resolves lucky-specific backgrounds
        fetchFact(tone, 0, tone, function (fact) {
            updateCard._isLucky = true;
            updateCard(fact);
        });
    };

    // ── Browser back/forward ──────────────────────────────────────
    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.factId) {
            fetch(BASE + 'api/fact.php?id=' + e.state.factId)
                .then(function (res) { return res.json(); })
                .then(updateCard)
                .catch(function () {});
        }
    });

    // ── Copy fact text + permalink ────────────────────────────────
    window.copyFact = function () {
        const factText   = textEl   ? textEl.textContent.trim()   : '';
        const sourceText = sourceEl ? sourceEl.textContent.trim() : '';
        const sourceLink = sourceEl ? (sourceEl.querySelector('a') || {}).href || '' : '';
        const link       = permalink ? permalink.href : window.location.href;

        const combined = factText
            + (sourceText ? '\n\nSource: ' + sourceText : '')
            + (sourceLink ? '\n' + sourceLink : '')
            + '\n\n' + link;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(combined)
                .then(function () { flashBtn(copyBtn, 'Copied!'); })
                .catch(function () { fallbackCopy(combined); });
        } else {
            fallbackCopy(combined);
        }
    };

    function fallbackCopy(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            flashBtn(copyBtn, 'Copied!');
        } catch (e) {
            flashBtn(copyBtn, 'Failed');
        }
        document.body.removeChild(ta);
    }

    function flashBtn(btn, msg) {
        if (!btn) return;
        const orig = btn.textContent;
        btn.textContent = msg;
        setTimeout(function () { btn.textContent = orig; }, 2200);
    }

    // ── Share on X ────────────────────────────────────────────────
    window.shareX = function () {
        const fact    = textEl ? textEl.textContent.trim() : '';
        const url     = window.location.href;
        const snippet = fact.length > 220 ? fact.substring(0, 217) + '\u2026' : fact;
        window.open(
            'https://twitter.com/intent/tweet?text=' + encodeURIComponent(snippet + '\n\n' + url),
            '_blank', 'noopener,noreferrer,width=600,height=420'
        );
    };

    // ── Share on LinkedIn ─────────────────────────────────────────
    window.shareLinkedIn = function () {
        window.open(
            'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(window.location.href),
            '_blank', 'noopener,noreferrer,width=700,height=520'
        );
    };

    // ── Share on Facebook ─────────────────────────────────────────
    window.shareFacebook = function () {
        window.open(
            'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(window.location.href),
            '_blank', 'noopener,noreferrer,width=640,height=480'
        );
    };

    // ── Share on Instagram ────────────────────────────────────────
    // Instagram has no web share URL; use the native Web Share API on
    // mobile (which surfaces Instagram), and fall back to clipboard on desktop.
    window.shareInstagram = function () {
        const fact = textEl ? textEl.textContent.trim() : '';
        const url  = window.location.href;
        const igBtn = document.getElementById('btn-share-ig');

        if (navigator.share) {
            navigator.share({ title: 'Energy Fact', text: fact, url: url })
                .catch(function () {}); // user cancelled — ignore
        } else {
            // Desktop fallback: copy link and prompt
            const text = fact + '\n\n' + url;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function () {
                    flashBtn(igBtn, 'Link copied!');
                }).catch(function () { fallbackCopy(text); flashBtn(igBtn, 'Link copied!'); });
            } else {
                fallbackCopy(text);
                flashBtn(igBtn, 'Link copied!');
            }
        }
    };

})();
