<?php
/**
 * Site footer and document close. Optional:
 *   $energytools_footer_html (string) — inner HTML for .site-footer__copy (default disclaimer)
 *   $energytools_footer_append (string) — extra HTML after main copy (e.g. version line)
 */
$energytools_footer_html = $energytools_footer_html ?? '<p>These tools use representative patterns and assumptions. They are not a substitute for utility or engineering data where precision is required.</p>';
$energytools_footer_append = $energytools_footer_append ?? '';
?>
<footer class="site-footer">
    <div class="site-footer__copy">
        <?= $energytools_footer_html ?>
        <?= $energytools_footer_append ?>
    </div>
</footer>
