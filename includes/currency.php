<?php
/**
 * Jambo Masai Tours — display-only multi-currency support.
 *
 * All prices are stored and calculated in USD everywhere (DB, discount
 * tiers, PDF quotes, admin). This file only fetches/caches exchange rates
 * so the frontend can convert USD -> chosen currency for DISPLAY. Nothing
 * server-side ever computes a non-USD price; conversion happens client-side
 * in currency.js reading data-price-usd attributes, using the rates this
 * emits as a JSON object.
 */

define('SUPPORTED_CURRENCIES', ['USD', 'EUR', 'GBP', 'JPY', 'CNY', 'AUD']);

const CURRENCY_SYMBOLS = [
    'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'JPY' => '¥', 'CNY' => '¥', 'AUD' => 'A$',
];

/**
 * Return USD -> currency rates as ['EUR' => 0.86, ...], refreshing from the
 * free Frankfurter API (European Central Bank data, no key required) at
 * most once a day. Falls back to the last cached rates (or 1:1 if none
 * exist yet) if the API is unreachable, so a network hiccup never breaks
 * the site — it just shows slightly stale or USD-only rates.
 */
function getCurrencyRates(): array {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS currency_rates (
        code VARCHAR(3) PRIMARY KEY,
        rate_to_usd DECIMAL(12,6) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $row = $db->query("SELECT MAX(updated_at) FROM currency_rates")->fetchColumn();
    $isStale = !$row || (time() - strtotime($row)) > 86400;

    if ($isStale) {
        refreshCurrencyRates($db);
    }

    $rates = ['USD' => 1.0];
    $stmt = $db->query("SELECT code, rate_to_usd FROM currency_rates");
    foreach ($stmt->fetchAll() as $r) {
        $rates[$r['code']] = (float)$r['rate_to_usd'];
    }

    // Guarantee every supported currency has a rate even on first run with
    // no network access — better a 1:1 placeholder than a missing entry
    // that breaks the JS lookup.
    foreach (SUPPORTED_CURRENCIES as $code) {
        if (!isset($rates[$code])) $rates[$code] = 1.0;
    }
    return $rates;
}

function refreshCurrencyRates(PDO $db): void {
    $symbols = implode(',', array_diff(SUPPORTED_CURRENCIES, ['USD']));
    $url = "https://api.frankfurter.dev/v1/latest?base=USD&symbols={$symbols}";

    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $resp = @file_get_contents($url, false, $ctx);
    if (!$resp) return;

    $data = json_decode($resp, true);
    if (empty($data['rates']) || !is_array($data['rates'])) return;

    $stmt = $db->prepare("INSERT INTO currency_rates (code, rate_to_usd, updated_at) VALUES (?, ?, NOW())
                           ON DUPLICATE KEY UPDATE rate_to_usd = VALUES(rate_to_usd), updated_at = NOW()");
    foreach ($data['rates'] as $code => $rate) {
        if (in_array($code, SUPPORTED_CURRENCIES, true)) {
            $stmt->execute([$code, $rate]);
        }
    }
}

/**
 * Wrap a USD amount for display: renders the USD-formatted price (so the
 * page always works even with JS disabled / before JS runs) plus a
 * data-price-usd attribute that currency.js reads to live-convert it into
 * whichever currency the visitor has selected.
 */
function priceSpan(float $usdAmount, string $extraClass = ''): string {
    $formatted = formatPrice($usdAmount);
    $class = trim('js-price ' . $extraClass);
    return '<span class="' . e($class) . '" data-price-usd="' . round($usdAmount, 2) . '">' . $formatted . '</span>';
}
