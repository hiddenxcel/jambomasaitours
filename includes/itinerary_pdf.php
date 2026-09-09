<?php
/**
 * Jambo Masai Tours — Itinerary/Proposal PDF generator (Dompdf, no Composer).
 */

require_once __DIR__ . '/../vendor/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

define('ITINERARY_PDF_DIR', __DIR__ . '/../uploads/itineraries/');
define('ITINERARY_PDF_URL', SITE_URL . '/uploads/itineraries/');

/**
 * Split a day's hotel image + supporting photos into up to $max images for
 * that day's page. AI-matched library photos (by caption) take priority when
 * available; otherwise falls back to cycling through the tour's own gallery
 * so consecutive days don't all show the exact same shots.
 */
function pickDayImages(array $day, array $galleryPhotos, int $dayIndex, int $max = 2, array $aiImageUrls = []): array {
    $images = [];
    if (!empty($day['hotel_image'])) {
        $images[] = $day['hotel_image'];
    }
    foreach ($aiImageUrls as $url) {
        if (count($images) >= $max) break;
        if (!in_array($url, $images, true)) $images[] = $url;
    }
    if (count($images) < $max && !empty($galleryPhotos)) {
        $count = count($galleryPhotos);
        for ($i = 0; $i < $max && count($images) < $max; $i++) {
            $img = $galleryPhotos[($dayIndex + $i) % $count]['image'];
            if (!in_array($img, $images, true)) {
                $images[] = $img;
            }
        }
    }
    return array_slice($images, 0, $max);
}

/**
 * Build one day's block within a two-days-per-page day spread. Uses the
 * tour's own real accommodation (single field, as stored) — no invented
 * Mid-range/Luxury/Exclusive tiers unless the data actually has them.
 */
function buildDayBlock(array $day, array $galleryPhotos, int $dayIndex, array $aiImageUrls = [], array $activities = []): string {
    $meals = array_filter(array_map('trim', explode(',', $day['meals'] ?? '')));
    $highlights = array_values(array_filter(explode('|', $day['highlights'] ?? '')));

    $route = '';
    if (!empty($day['departure_location']) || !empty($day['arrival_location'])) {
        $route = e($day['departure_location'])
            . (($day['departure_location'] && $day['arrival_location']) ? ' &rarr; ' : '')
            . e($day['arrival_location']);
    }
    $routeHtml = $route ? '<div class="day-route">' . $route . '</div>' : '';

    $mealsHtml = $meals ? '<div class="day-meta-line"><strong>Meals:</strong> ' . e(implode(', ', $meals)) . '</div>' : '';

    /* AI-planned Morning/Afternoon/Evening schedule, when available — falls
       back to the plain highlights list otherwise (e.g. Groq not configured). */
    $activitiesHtml = '';
    if (!empty($activities)) {
        foreach ($activities as $block => $bullets) {
            $items = '';
            foreach ($bullets as $b) { $items .= '<li>' . e($b) . '</li>'; }
            $activitiesHtml .= '<div class="activity-block"><span class="activity-block-label">' . e($block) . '</span><ul>' . $items . '</ul></div>';
        }
        $activitiesHtml = '<div class="day-activities"><strong>Activities</strong>' . $activitiesHtml . '</div>';
    }

    $specialHtml = '';
    if (!$activitiesHtml && !empty($highlights)) {
        $items = '';
        foreach (array_slice($highlights, 0, 4) as $h) {
            $items .= '<li>' . e(trim($h)) . '</li>';
        }
        $specialHtml = '<div class="day-special"><strong>What Makes This Day Special?</strong><ul>' . $items . '</ul></div>';
    }

    $notesHtml = !empty($day['notes'])
        ? '<div class="day-notes"><strong>Note:</strong> ' . e($day['notes']) . '</div>'
        : '';

    $stayRow = !empty($day['accommodation'])
        ? '<div class="day-stay-row"><span class="day-stay-label">Suggested Stay</span><span class="day-stay-name">' . e($day['accommodation']) . '</span></div>'
        : '';

    $imgs = pickDayImages($day, $galleryPhotos, $dayIndex, 3, $aiImageUrls);
    $photosHtml = '';
    foreach ($imgs as $img) {
        $photosHtml .= '<div class="day-thumb"><img src="' . e($img) . '"></div>';
    }

    return <<<HTML
    <div class="day-block">
      <div class="day-photos">{$photosHtml}</div>
      <div class="day-content">
        <div class="day-tag">Day {$day['day_number']}</div>
        <h3 class="day-title">{$day['title_e']}</h3>
        {$routeHtml}
        <p class="day-desc">{$day['desc_e']}</p>
        {$activitiesHtml}
        {$specialHtml}
        {$notesHtml}
        {$mealsHtml}
        {$stayRow}
      </div>
    </div>
    HTML;
}

/**
 * Build a vertical route-map timeline from each day's departure/arrival
 * locations — a single chained sequence of stops across the whole trip
 * (Arusha → Tarangire → Karatu → Serengeti → ...), not a repeated per-day
 * departure/arrival pair. Consecutive days that don't change location (e.g.
 * a full day exploring the same area) collapse into one stop spanning both
 * day numbers, so the map reads as places visited, not a list of no-op hops.
 *
 * $cleanedLocations (optional): day_number => ['departure'=>..., 'arrival'=>...]
 * from cleanRouteLocations() — resolves vague text ("Same area") to real
 * place names using the day sequence. Falls back to the raw stored text
 * when unavailable (e.g. Groq not configured or the call failed).
 */
function buildRouteStops(array $days, array $cleanedLocations = []): array {
    $stops = [];
    foreach ($days as $d) {
        $clean = $cleanedLocations[$d['day_number']] ?? null;
        $dep = trim($clean['departure'] ?? '') ?: trim($d['departure_location'] ?? '');
        $arr = trim($clean['arrival'] ?? '') ?: trim($d['arrival_location'] ?? '');
        if ($dep === '' && $arr === '') continue;

        if (empty($stops) && $dep !== '') {
            $stops[] = ['place' => $dep, 'day_from' => $d['day_number'], 'day_to' => $d['day_number'], 'leg' => null];
        }

        $destination = $arr !== '' ? $arr : $dep;
        $last = end($stops);
        $samePlace = $last && (
            stripos($destination, $last['place']) !== false ||
            stripos($last['place'], $destination) !== false
        );
        if ($samePlace) {
            $stops[count($stops) - 1]['day_to'] = $d['day_number'];
        } else {
            $stops[] = [
                'place' => $destination,
                'day_from' => $d['day_number'],
                'day_to' => $d['day_number'],
                'leg' => trim(($d['distance'] ?? '') . ' ' . ($d['travel_time'] ?? '')),
            ];
        }
    }
    return $stops;
}

/** Render the vertical route-map timeline HTML from stops built by buildRouteStops(). */
function buildRouteMapHtml(array $stops): string {
    if (count($stops) < 2) return '';

    $rows = '';
    $totalStops = count($stops);
    foreach ($stops as $i => $s) {
        $isLast = ($i === $totalStops - 1);
        $dayLabel = $s['day_from'] === $s['day_to'] ? 'Day ' . $s['day_from'] : 'Days ' . $s['day_from'] . '–' . $s['day_to'];
        $legHtml = (!$isLast && !empty($s['leg']))
            ? '<div class="route-leg">' . e($s['leg']) . '</div>'
            : '';
        $connector = $isLast ? '' : '<div class="route-connector"></div>';
        $markerClass = $i === 0 ? 'route-marker route-marker-start' : ($isLast ? 'route-marker route-marker-end' : 'route-marker');

        $rows .= <<<HTML
        <div class="route-stop">
          <div class="route-marker-col">
            <div class="{$markerClass}"></div>
            {$connector}
          </div>
          <div class="route-stop-body">
            <div class="route-day">{$dayLabel}</div>
            <div class="route-place">{$s['place']}</div>
            {$legHtml}
          </div>
        </div>
        HTML;
    }

    return $rows;
}

function buildItineraryHtml(array $tour, array $days, array $galleryPhotos, array $lead = [], ?array $quote = null, array $aiMatchesByDay = [], array $discountTiers = [], array $addons = [], array $cleanedLocations = [], array $plannedActivities = []): string {
    $siteName = SITE_NAME;
    $logoUrl = e(getSetting('logo_url', SITE_URL . '/uploads/logo-husika.png'));
    $tourName = e($tour['name']);
    $duration = e(formatDuration($tour['duration'] ?? ''));
    $destination = e($tour['destination'] ?? '');
    $travelStyle = e($tour['tour_type'] ?? '');
    $leadName = e($lead['name'] ?? 'Traveller');
    $travelDateFmt = !empty($lead['travel_date']) ? e(date('d F Y', strtotime($lead['travel_date']))) : 'Flexible';
    $peopleCount = $quote['people_count'] ?? 0;
    $basePrice = (float)($tour['price'] ?? 0);
    $guidePriceFmt = !empty($quote['discounted_price']) ? formatPrice($quote['discounted_price']) : formatPrice($basePrice);
    $generated = date('d M Y');
    $coverImg = !empty($galleryPhotos[0]['image']) ? $galleryPhotos[0]['image'] : ($tour['image'] ?? '');
    $total = count($days);
    $travelDateBlock = !empty($lead['travel_date'])
        ? '<div><div class="cover-lead-label">Preferred Travel Date</div><div class="cover-lead-value">' . $travelDateFmt . '</div></div>'
        : '';
    $firstName = e(explode(' ', trim($lead['name'] ?? 'Traveller'))[0] ?: 'Traveller');
    $refNumber = !empty($lead['ref_number']) ? e($lead['ref_number']) : ('#' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)));
    $travelDateSentence = !empty($lead['travel_date'])
        ? 'on ' . $travelDateFmt
        : 'on your preferred dates';
    $partySentence = $peopleCount > 0 ? ' for ' . $peopleCount . ' traveller' . ($peopleCount > 1 ? 's' : '') : '';

    /* --- At a Glance description (falls back to tour description) --- */
    $glanceDesc = !empty($tour['description']) ? nl2br(e($tour['description'])) : '';
    $glanceImg = !empty($galleryPhotos[1]['image']) ? $galleryPhotos[1]['image'] : $coverImg;

    /* --- Summary table rows --- */
    $summaryRows = '';
    foreach ($days as $d) {
        $summaryRows .= '<tr>'
            . '<td class="sum-day">Day ' . $d['day_number'] . '</td>'
            . '<td class="sum-title">' . $d['title_e'] . '</td>'
            . '<td class="sum-stay">' . e($d['accommodation'] ?: '—') . '</td>'
            . '</tr>';
    }

    /* --- Route map: vertical timeline chaining departure/arrival locations
       across the whole trip. Only rendered if there's real route data.
       ($cleanedLocations comes in pre-fetched from generateItineraryPdf()'s
       parallel Groq batch — resolves vague text like "Same area" using the
       day sequence, falling back to the raw stored text when unavailable.) --- */
    $routeStops = buildRouteStops($days, $cleanedLocations);
    $routeStopsHtml = buildRouteMapHtml($routeStops);
    $routeMapSection = '';
    if ($routeStopsHtml) {
        $routeMapSection = <<<HTML
        <div class="page-break"></div>
        <div class="section-header">
          <div class="section-eyebrow">Route Map</div>
          <h2 class="section-title">Your route, start to finish.</h2>
          <div class="section-sub">The path your journey follows across Tanzania, stop by stop.</div>
        </div>
        <div class="route-map">{$routeStopsHtml}</div>
        HTML;
    }

    /* --- Highlights quick-list: the intermediate stops (excluding the start
       point, which is just the pickup city) — a fast visual scan of the
       trip's key destinations, shown on the Summary page. --- */
    $highlightPlaces = array_slice(array_column($routeStops, 'place'), 1);
    $highlightsHtml = '';
    foreach ($highlightPlaces as $place) {
        $highlightsHtml .= '<div class="highlight-chip"><span class="highlight-dot"></span>' . e($place) . '</div>';
    }
    $highlightsSection = $highlightsHtml
        ? '<div class="highlights-wrap"><div class="highlights-label">Highlights</div><div class="highlights-grid">' . $highlightsHtml . '</div></div>'
        : '';
    /* Same chips, reused on the At a Glance page (no extra margin there —
       it sits inside the narrower main column, not the full-width summary page). */
    $glanceHighlightsHtml = $highlightsHtml
        ? '<div class="glance-highlights"><div class="highlights-label">Key Destinations</div><div class="highlights-grid">' . $highlightsHtml . '</div></div>'
        : '';

    /* --- Day pages: one day per page. (A two-days-per-page layout was tried
       first, but content length varies per day and Dompdf's natural page
       flow doesn't reliably keep both halves of a pair together — it was
       silently pushing the second day onto its own page anyway, breaking
       the "Days X–Y" header's promise. One day per page is what the block
       height actually supports predictably.) --- */
    $dayPages = '';
    foreach ($days as $d) {
        $idx = $d['day_number'] - 1;
        $aiUrls = $aiMatchesByDay[$d['day_number']] ?? [];
        $dayActivities = $plannedActivities[$d['day_number']] ?? [];
        $dayPages .= '<div class="page-break"></div><div class="spread-header">Day ' . $d['day_number'] . ' of ' . $total . '</div>'
            . buildDayBlock($d, $galleryPhotos, $idx, $aiUrls, $dayActivities);
    }

    /* --- Included / excluded --- */
    $included = array_filter(explode('|', $tour['included'] ?? ''));
    $notIncluded = array_filter(explode('|', $tour['not_included'] ?? ''));
    $incHtml = '';
    foreach ($included as $i) { $incHtml .= '<li>' . e(trim($i)) . '</li>'; }
    $excHtml = '';
    foreach ($notIncluded as $i) { $excHtml .= '<li>' . e(trim($i)) . '</li>'; }

    /* --- Optional add-ons: extras not included in the base price
       (e.g. Balloon Safari) — only rendered if the tour has any. --- */
    $addonsRowsHtml = '';
    foreach ($addons as $addon) {
        $unitLabel = $addon['price_unit'] === 'per_person' ? 'per person' : 'flat rate';
        $addonsRowsHtml .= '<div class="addon-row">'
            . '<div class="addon-info"><span class="addon-name">' . e($addon['name']) . '</span>'
            . (!empty($addon['description']) ? '<span class="addon-desc">' . e($addon['description']) . '</span>' : '')
            . '</div>'
            . '<div class="addon-price">' . formatPrice((float)$addon['price']) . ' <span class="addon-unit">' . $unitLabel . '</span></div>'
            . '</div>';
    }
    $addonsSection = $addonsRowsHtml
        ? '<div class="addons-box"><div class="addons-label">Optional Add-ons <span class="addons-sub">Not included — book with us before your trip</span></div>' . $addonsRowsHtml . '</div>'
        : '';

    /* --- Pricing table: per-person price for group sizes 1–10, using the
       real discount tiers (no invented Mid-range/Luxury/Exclusive columns
       unless the business actually models tiered pricing). --- */
    $pricingHtml = '';
    if ($quote) {
        $paymentTerms = e(getSetting('itinerary_payment_terms', '30% deposit to confirm your booking, with the remaining 70% due on arrival.'));
        $maxGuestCols = 10;
        $priceCols = '';
        for ($n = 1; $n <= $maxGuestCols; $n++) {
            $tierMatch = null;
            foreach ($discountTiers as $t) {
                if ($n >= (int)$t['min_people'] && ($t['max_people'] === null || $n <= (int)$t['max_people'])) {
                    $tierMatch = $t;
                }
            }
            $pct = $tierMatch ? (float)$tierMatch['discount_percent'] : 0.0;
            $perPerson = round($basePrice * (1 - $pct / 100), 2);
            $priceCols .= '<td>' . formatPrice($perPerson) . '</td>';
        }
        $headerCols = '';
        for ($n = 1; $n <= $maxGuestCols; $n++) { $headerCols .= '<th>' . $n . ' Guest' . ($n > 1 ? 's' : '') . '</th>'; }

        $hasDiscount = ($quote['discount_percent'] ?? 0) > 0;
        $discountNote = $hasDiscount
            ? '<div class="discount-badge">' . rtrim(rtrim(number_format($quote['discount_percent'], 2), '0'), '.') . '% group discount applied for your party of ' . $peopleCount . '</div>'
            : '';

        $pricingHtml = <<<HTML
        <div class="page-break"></div>
        <div class="section-header">
          <div class="section-eyebrow">Scope and Pricing</div>
          <h2 class="section-title">What your journey includes.</h2>
          <div class="section-sub">Final pricing follows confirmed dates, party size, and availability.</div>
        </div>

        <div class="guide-price-box">
          <span>Guide price per person sharing</span>
          <strong>From {$guidePriceFmt}</strong>
        </div>
        {$discountNote}

        <div class="price-table-wrap">
          <table class="price-table">
            <thead><tr><th class="pt-label">Party Size</th>{$headerCols}</tr></thead>
            <tbody><tr><td class="pt-label">Price / person</td>{$priceCols}</tr></tbody>
          </table>
        </div>

        <div class="two-col">
          <div class="two-col-item">
            <h3 class="pricing-h3">Included</h3>
            <ul class="side-list">{$incHtml}</ul>
          </div>
          <div class="two-col-item">
            <h3 class="pricing-h3">Not Included</h3>
            <ul class="side-list">{$excHtml}</ul>
          </div>
        </div>

        {$addonsSection}

        <div class="payment-terms-box">
          <div class="payment-terms-label">Payment Terms</div>
          <div class="payment-terms-text">{$paymentTerms}</div>
        </div>
        HTML;
    }

    /* --- Important Details / next steps page --- */
    $waLink = 'https://wa.me/' . WHATSAPP_NUMBER;
    $detailsHtml = <<<HTML
    <div class="page-break"></div>
    <div class="section-header">
      <div class="section-eyebrow">Good To Know</div>
      <h2 class="section-title">The fine detail, made simple.</h2>
      <div class="section-sub">This document is a starting point, not a booking confirmation.</div>
    </div>

    <div class="details-grid">
      <div class="details-cell">
        <div class="details-num">01</div>
        <h4>Availability</h4>
        <p>This proposal remains subject to availability until the required deposit is received and arrangements are confirmed in writing.</p>
      </div>
      <div class="details-cell">
        <div class="details-num">02</div>
        <h4>Pricing</h4>
        <p>Final pricing can change with travel dates, group size, accommodation category and park fee revisions.</p>
      </div>
      <div class="details-cell">
        <div class="details-num">03</div>
        <h4>Travel Protection</h4>
        <p>Comprehensive travel insurance, including medical treatment, cancellation and emergency evacuation, is strongly recommended.</p>
      </div>
      <div class="details-cell">
        <div class="details-num">04</div>
        <h4>Travel Documents</h4>
        <p>Passport, visa, vaccination and entry requirements remain each traveller's responsibility. Our team can provide planning guidance.</p>
      </div>
    </div>

    <div class="steps-row">
      <div class="step-cell"><span class="step-label">Review</span>Read through the route, daily pacing and stay options.</div>
      <div class="step-cell"><span class="step-label">Refine</span>Tell us what you'd like to change, add or remove.</div>
      <div class="step-cell"><span class="step-label">Confirm</span>Receive the final quotation and secure your journey.</div>
    </div>

    <div class="contact-strip">
      <div>
        <div class="contact-label">Start The Conversation</div>
        <div class="contact-line">{$siteName} · Arusha, Tanzania</div>
        <div class="contact-line">info@jambomasaitours.com · +255 659 667 271</div>
        <div class="contact-line">jambomasaitours.com</div>
      </div>
      <div class="contact-photo"><img src="{$coverImg}"></div>
    </div>
    HTML;

    /* --- About Us page: company story, contact details, membership badges,
       social links and review ratings — reuses site_settings already used
       elsewhere on the website (social links, contact info). Review ratings
       are only shown if an admin has actually set them (no invented numbers). --- */
    $aboutText = e(getSetting('itinerary_about_text',
        SITE_NAME . ' is a premier safari provider based in the heart of Arusha, Tanzania. We specialise in crafting '
        . 'tailor-made experiences — from Great Migration safaris to Kilimanjaro treks and Zanzibar beach escapes — '
        . 'led by an expert team of certified guides who know this land as home.'));
    $aboutPhoto = !empty($galleryPhotos[2]['image']) ? $galleryPhotos[2]['image'] : $coverImg;

    $socialLinks = [
        'Facebook' => getSetting('social_facebook'),
        'Instagram' => getSetting('social_instagram'),
        'YouTube' => getSetting('social_youtube'),
        'TikTok' => getSetting('social_tiktok'),
    ];
    $socialHtml = '';
    foreach ($socialLinks as $label => $url) {
        if (!empty($url)) $socialHtml .= '<div class="about-social-item">' . e($label) . '</div>';
    }

    $memberships = array_filter([getSetting('tato_member') ? 'Tanzania Association of Tour Operators' : '', getSetting('tourist_board_member') ? 'Tanzania Tourist Board' : '']);
    $membershipHtml = '';
    foreach ($memberships as $m) { $membershipHtml .= '<li>' . e($m) . '</li>'; }
    $membershipSection = $membershipHtml
        ? '<div class="about-block-label">Member Of</div><ul class="side-list">' . $membershipHtml . '</ul>'
        : '';

    $googleRating = getSetting('google_rating');
    $sbRating = getSetting('safaribookings_rating');
    $reviewsHtml = '';
    if ($googleRating) {
        $reviewsHtml .= '<div class="review-row"><span class="review-source">Google</span><span class="review-score">' . e($googleRating) . ' / 5</span></div>';
    }
    if ($sbRating) {
        $reviewsHtml .= '<div class="review-row"><span class="review-source">SafariBookings</span><span class="review-score">' . e($sbRating) . ' / 5</span></div>';
    }
    $reviewsSection = $reviewsHtml ? '<div class="about-block-label">Reviewed On</div>' . $reviewsHtml : '';

    $aboutHtml = <<<HTML
    <div class="page-break"></div>
    <div class="section-header">
      <div class="section-eyebrow">About Us</div>
      <h2 class="section-title">{$siteName}</h2>
    </div>

    <div class="about-wrap">
      <div class="about-col-main">
        <p class="about-text">{$aboutText}</p>
        {$reviewsSection}
      </div>
      <div class="about-col-side">
        <div class="about-photo-wrap"><img src="{$aboutPhoto}" class="about-photo"></div>
        <div class="about-block-label">Contact Us</div>
        <div class="about-contact-line"><strong>Address</strong> Arusha, Tanzania</div>
        <div class="about-contact-line"><strong>Phone</strong> +255 659 667 271</div>
        <div class="about-contact-line"><strong>Email</strong> info@jambomasaitours.com</div>
        <div class="about-contact-line"><strong>Website</strong> jambomasaitours.com</div>
        {$membershipSection}
        {$socialHtml}
      </div>
    </div>
    HTML;

    return <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset="UTF-8">
    <style>
      /* No @page top margin: Dompdf applies it inconsistently on the very
         first page, which let this page's content collide with the fixed
         header. Each page's opening block carries its own 90px padding-top
         instead, so every page (including the first) reserves the same
         header clearance regardless of that quirk. */
      @page { margin: 0; }
      body { font-family: 'DejaVu Sans', sans-serif; color: #222; font-size: 12px; line-height: 1.6; margin:0; }
      .page-break { page-break-before: always; }
      h1,h2,h3,h4 { font-family: 'DejaVu Sans', sans-serif; }

      /* Running header — repeats on every page via Dompdf's fixed-position support.
         Dompdf positions "fixed" elements relative to the full page box (y=0 at
         the very top), not the margin box, so top must be a small positive
         offset here — not a negative value pulling it above the page. */
      .doc-header { position: fixed; top: 24px; left: 0; right: 0; height: 44px; padding: 0 50px; display: table; width: 100%; box-sizing: border-box; }
      .doc-header-logo-cell { display: table-cell; vertical-align: middle; width: 50%; }
      .doc-header-doctype-cell { display: table-cell; vertical-align: middle; width: 50%; text-align: right; }
      .doc-header-logo { height: 36px; width: auto; }
      .doc-header-doctype { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: #a05e22; font-weight: bold; }
      .doc-header-rule { position: fixed; top: 78px; left: 50px; right: 50px; height: 1px; background: #eee; }

      /* Cover Letter — a short personal greeting page before the cover */
      .letter-wrap { padding: 90px 50px 0; }
      .letter-ref { font-size: 10px; color: #999; margin-bottom: 24px; }
      .letter-greeting { font-size: 15px; font-weight: bold; color: #1a1a1a; margin-bottom: 18px; }
      .letter-body p { font-size: 12px; color: #444; line-height: 1.9; margin: 0 0 14px; max-width: 560px; }
      .letter-signoff { margin-top: 30px; font-size: 12px; color: #444; }
      .letter-signoff .sign-name { font-weight: bold; color: #1a1a1a; margin-top: 4px; }
      .letter-contact { margin-top: 40px; display: table; }
      .letter-contact-cell { display: table-cell; vertical-align: middle; }
      .letter-contact-logo-wrap { display: inline-block; width: 46px; height: 46px; border-radius: 8px; overflow: hidden; margin-right: 14px; vertical-align: middle; }
      .letter-contact-logo { width: 46px; height: 46px; object-fit: cover; display: block; }
      .letter-contact-name { font-size: 12px; font-weight: bold; color: #1a1a1a; }
      .letter-contact-line { font-size: 10.5px; color: #888; margin-top: 2px; }

      /* Cover — clean document-style title page */
      .cover-wrap { padding: 90px 50px 0; }
      .cover-eyebrow { font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: #a05e22; font-weight: bold; margin-bottom: 8px; }
      .cover-title { font-size: 26px; font-weight: bold; color: #1a1a1a; margin: 0 0 10px; line-height: 1.3; }
      .cover-desc { font-size: 12px; color: #666; line-height: 1.7; margin: 0 0 24px; max-width: 640px; }
      .cover-lead-grid { display: flex; gap: 60px; margin-bottom: 24px; }
      .cover-lead-label { font-size: 9.5px; letter-spacing: 1px; text-transform: uppercase; color: #999; margin-bottom: 4px; }
      .cover-lead-value { font-size: 13px; font-weight: bold; color: #a05e22; }
      .cover-photo-wrap { border-radius: 6px; overflow: hidden; }
      .cover-photo { width: 100%; height: 300px; object-fit: cover; display: block; }
      .cover-meta-grid { display: table; width: 100%; table-layout: fixed; border: 1px solid #eee; border-top: none; }
      .cover-meta-cell { display: table-cell; padding: 14px 18px; border-right: 1px solid #eee; }
      .cover-meta-cell:last-child { border-right: none; }
      .cover-meta-label { font-size: 9px; letter-spacing: 1px; text-transform: uppercase; color: #999; margin-bottom: 4px; }
      .cover-meta-value { font-size: 13px; font-weight: bold; color: #222; }

      /* Section header (used before every content page) */
      .section-header { padding: 108px 50px 20px; }
      .section-eyebrow { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: #a05e22; margin-bottom: 6px; font-weight: bold; }
      .section-title { font-size: 22px; color: #1a1a1a; margin: 0 0 6px; font-weight: bold; }
      .section-sub { font-size: 11px; color: #888; max-width: 320px; float: right; text-align: right; margin-top: -34px; }

      /* At a Glance page */
      .glance-wrap { padding: 0 50px; }
      .glance-cols { display: table; width: 100%; table-layout: fixed; }
      .glance-col-main { display: table-cell; width: 62%; vertical-align: top; padding-right: 30px; }
      .glance-col-side { display: table-cell; width: 38%; vertical-align: top; }
      .glance-desc { font-size: 11.5px; color: #444; line-height: 1.8; }
      .glance-highlights { margin-top: 24px; }
      .glance-meta-row { padding: 12px 0; border-bottom: 1px solid #eee; }
      .glance-meta-label { font-size: 9.5px; letter-spacing: 1px; text-transform: uppercase; color: #a05e22; font-weight: bold; margin-bottom: 3px; }
      .glance-meta-value { font-size: 13px; font-weight: bold; color: #222; }
      .glance-photo-wrap { border-radius: 6px; overflow: hidden; margin-top: 30px; }
      .glance-photo { width: 100%; height: 220px; object-fit: cover; display: block; }

      /* Summary table */
      .summary-table { width: calc(100% - 100px); margin: 0 50px 40px; border-collapse: collapse; }
      .summary-table td { padding: 10px 8px; border-bottom: 1px solid #eee; font-size: 11.5px; vertical-align: top; }
      .sum-day { color: #a05e22; font-weight: bold; width: 60px; }
      .sum-title { color: #222; }
      .sum-stay { color: #888; text-align: right; width: 220px; }

      /* Highlights quick-list (key stops, shown under the Summary table) */
      .highlights-wrap { margin: 0 50px 30px; }
      .highlights-label { font-size: 9.5px; letter-spacing: 1.5px; text-transform: uppercase; color: #999; font-weight: bold; margin-bottom: 10px; }
      .highlights-grid { display: flex; flex-wrap: wrap; gap: 8px; }
      .highlight-chip { display: inline-block; font-size: 10.5px; color: #444; background: #faf6f0; border: 1px solid #eee; border-radius: 999px; padding: 6px 12px; }
      .highlight-dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #a05e22; margin-right: 7px; }

      /* Route map: vertical timeline of stops chained across the whole trip */
      .route-map { margin: 0 50px 40px; }
      .route-stop { display: table; width: 100%; table-layout: fixed; }
      .route-marker-col { display: table-cell; width: 26px; vertical-align: top; text-align: center; }
      .route-marker { width: 12px; height: 12px; border-radius: 50%; background: #a05e22; margin: 4px auto 0; }
      .route-marker-start, .route-marker-end { width: 16px; height: 16px; margin-top: 2px; }
      .route-connector { width: 2px; background: #e8d9c8; margin: 2px auto 0; height: 46px; }
      .route-stop-body { display: table-cell; vertical-align: top; padding: 0 0 20px 14px; }
      .route-day { font-size: 9px; letter-spacing: 1px; text-transform: uppercase; color: #999; margin-bottom: 2px; }
      .route-place { font-size: 14px; font-weight: bold; color: #1a1a1a; }
      .route-leg { font-size: 10px; color: #a05e22; margin-top: 3px; }

      /* Day spread (two days per page) */
      .spread-header { padding: 108px 50px 0; font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: #a05e22; font-weight: bold; }
      .day-block { padding: 14px 50px; display: table; width: calc(100% - 100px); margin: 0 auto; table-layout: fixed; }
      .day-photos { display: table-cell; width: 34%; vertical-align: top; padding-right: 16px; }
      /* border-radius directly on an <img> with object-fit:cover renders
         glitchy edges in Dompdf (the corner-cut artifact seen on larger
         renders); rounding the overflow-hidden wrapper instead is reliable. */
      .day-thumb { margin-bottom: 6px; border-radius: 4px; overflow: hidden; }
      .day-thumb img { width: 100%; height: 76px; object-fit: cover; display: block; }
      .day-content { display: table-cell; width: 66%; vertical-align: top; }
      .day-tag { font-size: 9.5px; letter-spacing: 1.5px; text-transform: uppercase; color: #a05e22; font-weight: bold; margin-bottom: 4px; }
      .day-title { font-size: 15px; font-weight: bold; color: #1a1a1a; margin: 0 0 6px; }
      .day-route { font-size: 10.5px; color: #888; margin-bottom: 6px; }
      .day-desc { font-size: 10.5px; color: #444; margin: 0 0 8px; }
      .day-activities { font-size: 10.5px; color: #444; margin-bottom: 10px; }
      .day-activities > strong { display: block; color: #a05e22; font-size: 11.5px; margin-bottom: 6px; }
      .activity-block { margin-bottom: 6px; }
      .activity-block-label { display: block; font-weight: bold; color: #1a1a1a; font-size: 10px; margin-bottom: 2px; }
      .activity-block ul { margin: 0; padding-left: 16px; }
      .activity-block li { margin-bottom: 2px; }
      .day-special { font-size: 10.5px; color: #444; margin-bottom: 8px; }
      .day-special strong { color: #a05e22; }
      .day-special ul { margin: 4px 0 0; padding-left: 16px; }
      .day-special li { margin-bottom: 2px; }
      .day-notes { margin: 8px 0; padding: 8px 10px; background: #fff8ec; border: 1px solid #f0d9b5; font-size: 10px; color: #7d4817; }
      .day-meta-line { font-size: 10.5px; color: #555; margin-top: 4px; }
      .day-stay-row { display: flex; justify-content: space-between; margin-top: 8px; padding-top: 8px; border-top: 1px solid #eee; font-size: 10.5px; }
      .day-stay-label { color: #999; text-transform: uppercase; font-size: 9px; letter-spacing: 1px; }
      .day-stay-name { color: #a05e22; font-weight: bold; }

      /* Pricing */
      .guide-price-box { margin: 0 50px 16px; border: 1.5px solid #a05e22; border-radius: 6px; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #444; }
      .guide-price-box strong { font-size: 20px; color: #a05e22; }
      .discount-badge { margin: 0 50px 16px; background: rgba(16,185,129,.1); color: #0d9668; border: 1px solid rgba(16,185,129,.25); border-radius: 4px; padding: 8px 14px; font-size: 10.5px; }
      .price-table-wrap { margin: 0 50px 26px; overflow: hidden; border-radius: 4px; }
      .price-table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
      .price-table th { background: #1a1a1a; color: #fff; padding: 8px 6px; text-align: center; font-size: 8.5px; letter-spacing: .5px; text-transform: uppercase; }
      .price-table td { padding: 9px 6px; text-align: center; border-bottom: 1px solid #eee; color: #333; font-weight: bold; }
      .price-table .pt-label { text-align: left; color: #a05e22; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; }
      .two-col { margin: 0 50px; display: table; width: calc(100% - 100px); table-layout: fixed; }
      .two-col-item { display: table-cell; width: 48%; vertical-align: top; padding-right: 4%; }
      .pricing-h3 { font-size: 13px; color: #222; margin: 0 0 10px; }
      .side-list { margin: 0; padding-left: 16px; font-size: 11px; color: #444; }
      .side-list li { margin-bottom: 4px; }
      .addons-box { margin: 24px 50px 0; border: 1px solid #eee; border-radius: 6px; padding: 16px 20px; }
      .addons-label { font-size: 13px; font-weight: bold; color: #1a1a1a; margin-bottom: 4px; }
      .addons-sub { display: block; font-size: 9.5px; font-weight: normal; color: #999; margin-top: 2px; }
      .addon-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-top: 1px solid #f2f2f2; }
      .addon-name { font-size: 11.5px; font-weight: bold; color: #444; }
      .addon-desc { display: block; font-size: 10px; color: #888; margin-top: 2px; }
      .addon-price { font-size: 12px; font-weight: bold; color: #a05e22; white-space: nowrap; }
      .addon-unit { font-size: 9px; font-weight: normal; color: #999; text-transform: uppercase; }
      .payment-terms-box { margin: 24px 50px 0; padding: 14px 20px; background: #faf6f0; border-left: 3px solid #a05e22; border-radius: 0 4px 4px 0; }
      .payment-terms-label { font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #a05e22; font-weight: bold; margin-bottom: 4px; }
      .payment-terms-text { font-size: 11px; color: #444; }

      /* Important details page */
      .details-grid { margin: 0 50px 24px; display: table; width: calc(100% - 100px); table-layout: fixed; border-collapse: separate; border-spacing: 12px; }
      .details-cell { display: table-cell; width: 50%; border: 1px solid #eee; border-radius: 6px; padding: 16px 18px; vertical-align: top; }
      .details-num { font-size: 10px; color: #a05e22; font-weight: bold; margin-bottom: 6px; }
      .details-cell h4 { font-size: 12.5px; color: #1a1a1a; margin: 0 0 6px; }
      .details-cell p { font-size: 10.5px; color: #666; margin: 0; line-height: 1.6; }
      .steps-row { margin: 0 50px 24px; display: table; width: calc(100% - 100px); table-layout: fixed; border-top: 1px solid #eee; border-bottom: 1px solid #eee; }
      .step-cell { display: table-cell; width: 33.33%; padding: 14px 16px; font-size: 10px; color: #666; border-right: 1px solid #eee; }
      .step-cell:last-child { border-right: none; }
      .step-label { display: block; font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #a05e22; font-weight: bold; margin-bottom: 5px; }
      .contact-strip { margin: 24px 50px 0; background: #1a1a1a; border-radius: 6px; padding: 20px 24px; display: table; width: calc(100% - 100px); table-layout: fixed; }
      .contact-strip > div { display: table-cell; vertical-align: middle; width: 62%; }
      .contact-photo { display: table-cell; width: 38%; height: 90px; border-radius: 4px; overflow: hidden; }
      .contact-photo img { width: 100%; height: 90px; object-fit: cover; display: block; }
      .contact-label { font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #d99a53; font-weight: bold; margin-bottom: 8px; }
      .contact-line { font-size: 11px; color: #fff; margin-bottom: 3px; }

      /* About Us page */
      .about-wrap { padding: 0 50px; display: table; width: 100%; table-layout: fixed; }
      .about-col-main { display: table-cell; width: 60%; vertical-align: top; padding-right: 30px; }
      .about-col-side { display: table-cell; width: 40%; vertical-align: top; }
      .about-text { font-size: 11.5px; color: #444; line-height: 1.8; margin: 0 0 20px; }
      .about-photo-wrap { border-radius: 6px; overflow: hidden; margin-bottom: 20px; }
      .about-photo { width: 100%; height: 150px; object-fit: cover; display: block; }
      .about-block-label { font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #a05e22; font-weight: bold; margin: 18px 0 8px; }
      .about-contact-line { font-size: 11px; color: #444; margin-bottom: 5px; }
      .about-contact-line strong { display: inline-block; width: 62px; color: #999; font-weight: normal; text-transform: uppercase; font-size: 9px; letter-spacing: .5px; }
      .about-social-item { display: inline-block; font-size: 10px; color: #a05e22; background: #faf6f0; border: 1px solid #eee; border-radius: 999px; padding: 4px 12px; margin: 0 6px 6px 0; }
      .review-row { display: flex; justify-content: space-between; max-width: 260px; padding: 8px 0; border-bottom: 1px solid #eee; font-size: 11.5px; }
      .review-source { color: #444; }
      .review-score { color: #a05e22; font-weight: bold; }

      .footer-note { padding: 20px 50px 40px; font-size: 10px; color: #999; text-align: center; border-top: 1px solid #eee; margin-top: 30px; }
    </style>
    </head>
    <body>

      <!-- Running header: repeats on every page -->
      <div class="doc-header">
        <div class="doc-header-logo-cell"><img class="doc-header-logo" src="{$logoUrl}"></div>
        <div class="doc-header-doctype-cell"><div class="doc-header-doctype">Private Itinerary</div></div>
      </div>
      <div class="doc-header-rule"></div>

      <!-- Cover Letter -->
      <div class="letter-wrap">
        <div class="letter-ref">Ref. Number: {$refNumber} &middot; {$leadName}</div>
        <div class="letter-greeting">Dear {$leadName},</div>
        <div class="letter-body">
          <p>Thank you for considering our <strong>{$tourName}</strong>. I am delighted to offer you a customised itinerary{$partySentence}, {$travelDateSentence}.</p>
          <p>I am certain this safari experience will leave a lasting impression on you, and our team is here to answer any questions or concerns you may have.</p>
          <p>I look forward to your response!</p>
        </div>
        <div class="letter-signoff">
          Best regards,
          <div class="sign-name">{$siteName}</div>
        </div>
        <div class="letter-contact">
          <div class="letter-contact-cell"><span class="letter-contact-logo-wrap"><img class="letter-contact-logo" src="{$logoUrl}"></span></div>
          <div class="letter-contact-cell">
            <div class="letter-contact-name">{$siteName}</div>
            <div class="letter-contact-line">+255 659 667 271 &middot; info@jambomasaitours.com</div>
          </div>
        </div>
      </div>

      <!-- Cover -->
      <div class="page-break"></div>
      <div class="cover-wrap">
        <div class="cover-eyebrow">Made for {$leadName}</div>
        <h1 class="cover-title">{$tourName}</h1>
        <p class="cover-desc">Experience {$destination} on this {$duration} journey — a tailor-made Tanzania safari crafted around your dates, group size and pace.</p>

        <div class="cover-lead-grid">
          <div>
            <div class="cover-lead-label">Prepared For</div>
            <div class="cover-lead-value">{$leadName}</div>
          </div>
          {$travelDateBlock}
        </div>

        <div class="cover-photo-wrap"><img class="cover-photo" src="{$coverImg}"></div>

        <div class="cover-meta-grid">
          <div class="cover-meta-cell">
            <div class="cover-meta-label">Duration</div>
            <div class="cover-meta-value">{$duration}</div>
          </div>
          <div class="cover-meta-cell">
            <div class="cover-meta-label">Route</div>
            <div class="cover-meta-value">{$destination}</div>
          </div>
          <div class="cover-meta-cell">
            <div class="cover-meta-label">Travel Style</div>
            <div class="cover-meta-value">{$travelStyle}</div>
          </div>
          <div class="cover-meta-cell">
            <div class="cover-meta-label">Guide Price</div>
            <div class="cover-meta-value">From {$guidePriceFmt}</div>
          </div>
        </div>
      </div>

      <!-- At a Glance -->
      <div class="page-break"></div>
      <div class="section-header">
        <div class="section-eyebrow">Your Safari At A Glance</div>
        <h2 class="section-title">A journey considered around your time.</h2>
      </div>
      <div class="glance-wrap">
        <div class="glance-cols">
          <div class="glance-col-main">
            <p class="glance-desc">{$glanceDesc}</p>
            {$glanceHighlightsHtml}
          </div>
          <div class="glance-col-side">
            <div class="glance-meta-row"><div class="glance-meta-label">Duration</div><div class="glance-meta-value">{$duration}</div></div>
            <div class="glance-meta-row"><div class="glance-meta-label">Route</div><div class="glance-meta-value">{$destination}</div></div>
            <div class="glance-meta-row"><div class="glance-meta-label">Experience</div><div class="glance-meta-value">{$travelStyle}</div></div>
            <div class="glance-meta-row"><div class="glance-meta-label">Travel Dates</div><div class="glance-meta-value">{$travelDateFmt}</div></div>
            <div class="glance-meta-row" style="border-bottom:none"><div class="glance-meta-label">Season</div><div class="glance-meta-value">Flexible</div></div>
          </div>
        </div>
        <div class="glance-photo-wrap"><img class="glance-photo" src="{$glanceImg}"></div>
      </div>

      <!-- Summary -->
      <div class="page-break"></div>
      <div class="section-header">
        <div class="section-eyebrow">Day By Day</div>
        <h2 class="section-title">Your journey, clearly mapped.</h2>
        <div class="section-sub">A private route with the freedom to refine pacing, stays and special interests before confirmation.</div>
      </div>
      <table class="summary-table">{$summaryRows}</table>

      <!-- Highlights -->
      {$highlightsSection}

      <!-- Route Map -->
      {$routeMapSection}

      <!-- Day-by-day spreads -->
      {$dayPages}

      <!-- Pricing -->
      {$pricingHtml}

      <!-- Important Details -->
      {$detailsHtml}

      <!-- About Us -->
      {$aboutHtml}

      <div class="footer-note">
        {$siteName} &middot; Arusha, Tanzania &middot; +255 659 667 271 &middot; info@jambomasaitours.com &middot; Prepared {$generated}
      </div>
    </body>
    </html>
    HTML;
}

/**
 * Render itinerary/proposal PDF to disk. Returns the filename on success.
 * $imageLibrary (optional): admin-curated photo pool with captions, used to
 * AI-match extra images per day on top of the tour's own hotel/gallery photos.
 * $discountTiers (optional): the tour's group-discount tiers, used to build
 * the per-guest-count pricing table.
 * $addons (optional): the tour's optional extras (e.g. Balloon Safari), shown
 * on the pricing page.
 */
function generateItineraryPdf(array $tour, array $days, array $galleryPhotos, array $lead = [], ?array $quote = null, array $imageLibrary = [], array $discountTiers = [], array $addons = []): ?string {
    if (!is_dir(ITINERARY_PDF_DIR)) {
        mkdir(ITINERARY_PDF_DIR, 0755, true);
    }

    foreach ($days as &$d) {
        $d['title_e'] = e($d['title'] ?? '');
        $d['desc_e']  = nl2br(e($d['description'] ?? ''));
    }
    unset($d);

    /* Three independent Groq calls (image matching, route-location cleanup,
       activity planning) used to run one after another — on a 7-day tour
       that summed to 60-90s and blew past browser fetch timeouts, surfacing
       as "Network error" even though generation was still in progress
       server-side. Firing all three at once via curl_multi keeps total wait
       close to the slowest single call instead of their sum. */
    require_once __DIR__ . '/ai_groq_batch.php';
    require_once __DIR__ . '/ai_image_matcher.php';
    require_once __DIR__ . '/ai_activity_planner.php';

    $usableLibrary = array_values(array_filter($imageLibrary, fn($p) => !empty($p['caption'])));
    $aiRequests = [];
    if (!empty($usableLibrary)) {
        $prompt = buildImageMatchPrompt($days, $usableLibrary, 3);
        if ($prompt) $aiRequests['images'] = ['prompt' => $prompt, 'max_tokens' => 500, 'temperature' => 0.3];
    }
    $routePrompt = buildRouteCleanupPrompt($days);
    if ($routePrompt) $aiRequests['route'] = ['prompt' => $routePrompt, 'max_tokens' => 600, 'temperature' => 0.2];
    $activityPrompt = buildActivityPlanPrompt($days);
    if ($activityPrompt) $aiRequests['activities'] = ['prompt' => $activityPrompt, 'max_tokens' => 1500, 'temperature' => 0.3];

    $aiMatchesByDay = [];
    $cleanedLocations = [];
    $plannedActivities = [];
    if (!empty($aiRequests) && !empty(getGroqApiKey())) {
        $raw = runGroqBatch($aiRequests);

        if (isset($raw['images'])) {
            $matches = parseImageMatchResult(groqBatchDecode($raw['images']), $usableLibrary, 3);
            $libraryById = array_column($usableLibrary, null, 'id');
            foreach ($matches as $dayNum => $ids) {
                $aiMatchesByDay[$dayNum] = array_values(array_filter(array_map(
                    fn($id) => $libraryById[$id]['image'] ?? null, $ids
                )));
            }
        }
        if (isset($raw['route'])) {
            $cleanedLocations = parseRouteCleanupResult(groqBatchDecode($raw['route']));
        }
        if (isset($raw['activities'])) {
            $plannedActivities = parseActivityPlanResult(groqBatchDecode($raw['activities']));
        }
    }

    $html = buildItineraryHtml($tour, $days, $galleryPhotos, $lead, $quote, $aiMatchesByDay, $discountTiers, $addons, $cleanedLocations, $plannedActivities);

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('isHtml5ParserEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filename = bin2hex(random_bytes(16)) . '.pdf';
    file_put_contents(ITINERARY_PDF_DIR . $filename, $dompdf->output());

    return $filename;
}
