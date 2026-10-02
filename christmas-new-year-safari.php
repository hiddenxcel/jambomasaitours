<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

/* Msimu wa sikukuu: "2026/27" hadi Des 20, kisha "2027/28" */
$y0 = (int)date('Y');
if ((int)date('n') === 12 && (int)date('j') > 20) { $y0++; }
$xmas = $y0 . '/' . substr((string)($y0 + 1), -2);
$next = ($y0 + 1) . '/' . substr((string)($y0 + 2), -2);
$cfg = [
  'title' => 'Christmas & New Year Safari Tanzania ' . $xmas . ' & ' . $next,
  'desc' => 'Christmas and New Year safari in Tanzania ' . $xmas . ' and ' . $next . ': Serengeti calving season, Ngorongoro, Zanzibar beach extensions and private departures. Book early.',
  'keywords' => 'christmas safari tanzania, new year safari tanzania, december safari tanzania, tanzania holiday safari, ndutu december, serengeti christmas, family christmas safari africa',
  'canonical' => '/christmas-new-year-safari',
  'crumbs' => [['Christmas & New Year Safari', '/christmas-new-year-safari']],
  'badge' => 'Holiday ' . $xmas,
  'h1' => 'Christmas & New Year Safari in Tanzania ' . $xmas . ' & ' . $next,
  'lead' => 'Spend the holidays in the Serengeti. In December the herds move onto the southern plains, calving season begins and the weather is warm, which makes it a memorable family trip with a Zanzibar beach finish if you like.',
  'cta_msg' => 'Hi! I would like a Christmas / New Year safari in Tanzania (' . $xmas . '). Please share availability and prices.',
  'sections' => [
    ['h2' => 'Why Tanzania at Christmas and New Year', 'list' => [
      'The herds gather around Ndutu and the southern Serengeti as calving season begins.',
      'Warm weather with short rains that usually fall in the afternoon, plus green scenery and good light.',
      'A natural fit for families, with school holidays and a mix of wildlife and beach time.',
      'Zanzibar is hot and sunny, so it combines well with a safari.',
    ]],
    ['h2' => 'Plan early', 'p' => [
      'Christmas and New Year are among the busiest weeks in Tanzania. Small camps around Ndutu and good lodges in Arusha, Ngorongoro and Zanzibar fill early, so we recommend booking several months ahead. Prices at this time are higher than the rest of the green season, so tell us your dates and budget and we will build the best option.',
    ]],
    ['h2' => 'Sample holiday itineraries', 'cards' => [
      ['4–5 days', 'Ndutu & Ngorongoro', 'A short calving season safari for the holidays.'],
      ['6–7 days', 'Serengeti, Ngorongoro & Tarangire', 'The classic Northern Circuit at a relaxed pace.'],
      ['8–10 days', 'Safari + Zanzibar', 'Wildlife first, then beach and Stone Town to finish.'],
    ]],
  ],
  'tour_sql' => "LOWER(name) LIKE '%calving%' OR LOWER(name) LIKE '%ndutu%' OR LOWER(name) LIKE '%family%' OR LOWER(name) LIKE '%zanzibar%'",
  'tour_heading' => 'Holiday safari packages',
  'faqs' => [
    ['Is Christmas a good time for a Tanzania safari?', 'Yes. The herds are in the southern Serengeti and Ndutu, calving season begins, and the weather is warm. It is busy, so book early.'],
    ['Will it rain at Christmas?', 'There can be short afternoon showers during the short rains, but days are usually warm and wildlife viewing is good.'],
    ['Can we celebrate Christmas in the bush?', 'Yes. Many camps and lodges host special Christmas and New Year dinners, and we can arrange a private celebration on request.'],
    ['Can I add Zanzibar?', 'Yes. We combine holiday safaris with Zanzibar beach extensions, including fly-in options.'],
  ],
  'related' => [['Calving season', '/calving-season'], ['Green season safari', '/green-season-safari'], ['Best time to visit Tanzania', '/best-time-to-visit-tanzania'], ['All safari packages', '/tours']],
];
require __DIR__ . '/includes/seo_page.php';
