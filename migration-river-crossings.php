<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$y = seoYears();
$cfg = [
  'title' => 'Mara River Crossing Safari ' . $y . ' | When & Where',
  'desc' => 'Mara River crossing safari ' . $y . ': when the wildebeest cross (July to October), where to stay in the northern Serengeti, how to improve your chances and what to expect.',
  'keywords' => 'mara river crossing, wildebeest river crossing tanzania, mara river crossing safari ' . date('Y') . ', great migration river crossing, northern serengeti safari, kogatende, grumeti river crossing',
  'canonical' => '/migration/river-crossings',
  'crumbs' => [['The Great Migration', '/migration'], ['River Crossings', '/migration/river-crossings']],
  'badge' => 'Jul – Oct',
  'h1' => 'Mara River Crossing Safari ' . $y,
  'lead' => 'Thousands of wildebeest and zebra plunge into the crocodile-filled Mara River in the northern Serengeti between July and October. This guide explains when and where the crossings happen, and how to give yourself the best chance of seeing one.',
  'cta_msg' => 'Hi! I would like a Mara River crossing safari (' . $y . '). Please share dates and prices.',
  'sections' => [
    ['h2' => 'When do the river crossings happen?', 'p' => [
      'The main Mara River crossings take place from July to October, with the busiest period usually from August to September. Smaller crossings of the Grumeti River in the Western Corridor can happen from late May to July. The herds follow the rains and the grass, so exact timing changes from year to year and no operator can guarantee a crossing on a given day.',
    ]],
    ['h2' => 'Where to go', 'list' => [
      'Northern Serengeti (Kogatende, Lamai): the main area for Mara River crossings.',
      'Western Corridor (Grumeti): earlier crossings, often quieter, from late May to July.',
      'Stay inside or very close to the crossing area so you can be at the river early and stay late.',
    ]],
    ['h2' => 'How to improve your chances', 'list' => [
      'Spend at least three nights in the northern Serengeti, and four or more if you can.',
      'Travel between late July and September, when the herds are most often at the river.',
      'Pack a picnic lunch and be ready to wait: herds can gather at the bank for hours before crossing.',
      'Choose an experienced guide who tracks the herds daily and positions you at the right crossing point.',
      'Keep a flexible plan, because the herd, not the schedule, decides.',
    ]],
    ['h2' => 'What to expect', 'p' => [
      'A crossing is sudden and intense. Herds gather, hesitate, and then surge into the water, while crocodiles wait and predators watch from the banks. Several vehicles may gather at popular points in peak months, so a good guide and good timing matter. If the herds do not cross during your stay, the northern Serengeti still offers superb big cat and elephant sightings.',
    ]],
    ['h2' => 'Mara River crossings vs calving season', 'table' => ['head' => ['', 'River crossings', 'Calving season'], 'rows' => [
      ['Months', 'July to October', 'December to March'],
      ['Area', 'Northern Serengeti', 'Ndutu and southern Serengeti'],
      ['Crowds', 'Higher in peak months', 'Lower'],
      ['Certainty', 'Not guaranteed on a given day', 'Very reliable births and predators'],
    ]]],
  ],
  'tour_sql' => "tour_type = 'Great Migration Safari' OR LOWER(name) LIKE '%crossing%' OR LOWER(name) LIKE '%northern serengeti%'",
  'tour_heading' => 'River crossing safari packages',
  'faqs' => [
    ['Can you guarantee we will see a river crossing?', 'No. Crossings depend on the herds and the weather. What we offer is expert positioning with Maasai guides who track the herds daily, and itineraries with enough nights in the north to improve your odds.'],
    ['Which month is best for the Mara River crossings?', 'August and September are usually the most reliable, although crossings can occur from July to October.'],
    ['How many days do I need?', 'A minimum of three nights in the northern Serengeti, ideally four or five, within a 6 to 8 day safari.'],
    ['Do the crossings happen in Tanzania or Kenya?', 'Both. The herds cross the Mara River between the Serengeti in Tanzania and the Masai Mara in Kenya. Our safaris focus on the Tanzanian side and can be combined with Kenya.'],
  ],
  'related' => [['Great Migration ' . $y, '/migration'], ['Where is the migration now?', '/migration/where-is-the-migration-now'], ['Calving season', '/calving-season'], ['Best time to visit Tanzania', '/best-time-to-visit-tanzania']],
];
require __DIR__ . '/includes/seo_page.php';
