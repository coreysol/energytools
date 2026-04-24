<?php
/**
 * Sticky site header. Requires:
 *   $energytools_brand_line (string) — subtitle next to "Energy Tools"
 *   $energytools_home_href (string) — link to hub (e.g. '../index.php' or 'index.php')
 */
if (!isset($energytools_brand_line, $energytools_home_href)) {
    throw new InvalidArgumentException('design-header.php: set $energytools_brand_line and $energytools_home_href');
}
?>
<body class="tool-page">
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
    <div class="site-header__inner">
        <a class="site-brand" href="<?= htmlspecialchars($energytools_home_href, ENT_QUOTES, 'UTF-8') ?>">
            <span class="site-brand__name">Energy Tools</span>
            <span class="site-brand__sep" aria-hidden="true">|</span>
            <span class="site-brand__line"><?= htmlspecialchars((string) $energytools_brand_line, ENT_QUOTES, 'UTF-8') ?></span>
        </a>
        <nav class="site-nav" aria-label="Site">
            <ul class="site-nav__list">
                <li><a class="site-nav__link" href="<?= htmlspecialchars($energytools_home_href, ENT_QUOTES, 'UTF-8') ?>">All tools</a></li>
            </ul>
        </nav>
    </div>
</header>
