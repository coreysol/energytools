<?php
/**
 * Opens HTML document and <head>. Requires:
 *   $energytools_page_title (string)
 *   $energytools_design_css_prefix (string) — e.g. 'assets' or '../assets', no trailing slash
 * Optional:
 *   $energytools_base_href (string|null) — full base URL for relative links
 *   $energytools_extra_head_html (string) — additional head markup (trusted, built in PHP only)
 */
if (!isset($energytools_page_title, $energytools_design_css_prefix)) {
    throw new InvalidArgumentException('design-head.php: set $energytools_page_title and $energytools_design_css_prefix');
}
$energytools_base_href = $energytools_base_href ?? null;
$energytools_extra_head_html = $energytools_extra_head_html ?? '';
$energytools_head_prefix_html = $energytools_head_prefix_html ?? '';
$p = $energytools_design_css_prefix;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if ($energytools_head_prefix_html !== ''): ?>
<?= $energytools_head_prefix_html ?>
<?php endif; ?>
<?php if ($energytools_base_href !== null && $energytools_base_href !== ''): ?>
    <base href="<?= htmlspecialchars($energytools_base_href, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
    <title><?= htmlspecialchars((string) $energytools_page_title, ENT_QUOTES, 'UTF-8') ?></title>
<?php if ($energytools_extra_head_html !== ''): ?>
<?= $energytools_extra_head_html ?>
<?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300..800&family=Nunito:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?>/css/site.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?>/css/tools.css">
<?php
$energytools_extra_stylesheets = $energytools_extra_stylesheets ?? [];
foreach ($energytools_extra_stylesheets as $energytools_extra_href) {
    echo '    <link rel="stylesheet" href="' . htmlspecialchars((string) $energytools_extra_href, ENT_QUOTES, 'UTF-8') . '">' . "\n";
}
?>
</head>
