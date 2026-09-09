/**
 * Jambo Masai Tours — client-side USD display conversion.
 *
 * All prices render server-side in USD (see includes/currency.php's
 * priceSpan()). This script finds every `[data-price-usd]` node, keeps its
 * original USD text as a fallback, and swaps the displayed text for the
 * visitor's chosen currency using rates injected via window.JMT_CURRENCY_RATES.
 * Nothing here touches actual booking/payment amounts — those stay USD
 * server-side (PDF quotes, admin, booking form submission value).
 */
(function () {
  var STORAGE_KEY = 'jmt_currency';
  var SYMBOLS = { USD: '$', EUR: '€', GBP: '£', JPY: '¥', CNY: '¥', AUD: 'A$' };
  var NO_DECIMALS = { JPY: true }; // yen conventionally shown without cents/decimals

  function getRates() {
    return (window.JMT_CURRENCY_RATES && typeof window.JMT_CURRENCY_RATES === 'object')
      ? window.JMT_CURRENCY_RATES
      : { USD: 1 };
  }

  function getCurrentCurrency() {
    try {
      var stored = localStorage.getItem(STORAGE_KEY);
      if (stored && getRates()[stored]) return stored;
    } catch (e) {}
    return 'USD';
  }

  function setCurrentCurrency(code) {
    try { localStorage.setItem(STORAGE_KEY, code); } catch (e) {}
    document.cookie = STORAGE_KEY + '=' + code + ';path=/;max-age=31536000';
  }

  function formatAmount(amount, code) {
    var symbol = SYMBOLS[code] || code + ' ';
    var decimals = NO_DECIMALS[code] ? 0 : 0; // whole-number display everywhere, matches formatPrice()
    var rounded = Math.round(amount);
    return symbol + rounded.toLocaleString('en-US');
  }

  function applyCurrency(code) {
    var rates = getRates();
    var rate = rates[code] || 1;
    var nodes = document.querySelectorAll('[data-price-usd]');
    for (var i = 0; i < nodes.length; i++) {
      var el = nodes[i];
      var usd = parseFloat(el.getAttribute('data-price-usd'));
      if (isNaN(usd)) continue;
      el.textContent = formatAmount(usd * rate, code);
    }
    var badges = document.querySelectorAll('.js-currency-label');
    for (var j = 0; j < badges.length; j++) badges[j].textContent = code;
    var items = document.querySelectorAll('.jmt-cur-item');
    for (var k = 0; k < items.length; k++) {
      items[k].classList.toggle('active', items[k].getAttribute('data-currency') === code);
    }
  }

  window.jmtSetCurrency = function (code) {
    if (!getRates()[code]) return;
    setCurrentCurrency(code);
    applyCurrency(code);
  };

  window.jmtConvertPrice = function (usdAmount) {
    var code = getCurrentCurrency();
    var rates = getRates();
    return formatAmount(usdAmount * (rates[code] || 1), code);
  };

  document.addEventListener('DOMContentLoaded', function () {
    applyCurrency(getCurrentCurrency());
  });
})();
