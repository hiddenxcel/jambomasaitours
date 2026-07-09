<?php
/**
 * Shared Google tags (gtag.js) — include once inside <head> on every page.
 * Loads a single gtag.js and configures every Google product we use:
 *   - Google Ads (conversion tracking)   — AW-18302464700
 *   - Google Analytics 4 (if a GA4 ID is set in admin settings)
 *
 * Google recommends ONE gtag.js library + one dataLayer, then a config()
 * line per product. Keep the Ads ID here (single source of truth).
 */

$GOOGLE_ADS_ID = 'AW-18302464700';
$_ga4 = function_exists('getSetting') ? getSetting('ga4_measurement_id') : '';

/* The library only needs to be loaded once — use the Ads ID as the src id. */
?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($GOOGLE_ADS_ID, ENT_QUOTES) ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?= htmlspecialchars($GOOGLE_ADS_ID, ENT_QUOTES) ?>');
<?php if (!empty($_ga4)): ?>
  gtag('config', '<?= htmlspecialchars($_ga4, ENT_QUOTES) ?>');
<?php endif; ?>
</script>
