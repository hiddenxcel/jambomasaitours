<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$y = seoYears();
$mNow = (int)date('n');
$monthName = date('F');

/* [mwezi, eneo, maelezo] — makadirio ya jumla; makundi yanafuata mvua na nyasi */
$cal = [
  1  => ['January',   'Ndutu & southern Serengeti', 'Herds are on the short-grass plains and calving is under way.'],
  2  => ['February',  'Ndutu & southern Serengeti', 'Peak calving, with up to thousands of newborns a day and predators close by.'],
  3  => ['March',     'Southern to central Serengeti', 'Herds begin to drift north-west as the plains dry out.'],
  4  => ['April',     'Central Serengeti', 'Long rains. Herds move through the Seronera and Moru areas.'],
  5  => ['May',       'Central to Western Corridor', 'Rutting season. Herds head toward the Grumeti River.'],
  6  => ['June',      'Western Corridor', 'Grumeti River crossings are possible. Herds start moving north.'],
  7  => ['July',      'Western Corridor to northern Serengeti', 'Herds arrive in the north and the first Mara River crossings begin.'],
  8  => ['August',    'Northern Serengeti', 'Mara River crossings are at their busiest.'],
  9  => ['September', 'Northern Serengeti', 'Mara River crossings continue, with fewer vehicles than August.'],
  10 => ['October',   'Northern Serengeti', 'Herds begin to turn south. Late crossings are still possible.'],
  11 => ['November',  'Northern to eastern Serengeti', 'Short rains start and herds move south through the east.'],
  12 => ['December',  'Southern Serengeti & Ndutu', 'Herds gather on the southern plains as calving approaches.'],
];
$cards = [];
foreach ($cal as $n => $c) $cards[] = [$c[0], $c[1], $c[2], $n === $mNow];

$now = $cal[$mNow];
$cfg = [
  'title' => 'Where Is the Great Migration Now? ' . $monthName . ' ' . date('Y'),
  'desc' => 'Where is the Great Migration in ' . $monthName . ' ' . date('Y') . '? Right now the herds are in ' . $now[1] . '. Month-by-month map of the wildebeest migration for ' . $y . '.',
  'keywords' => 'where is the great migration now, great migration location ' . strtolower($monthName) . ', wildebeest migration ' . date('Y') . ', great migration by month, serengeti migration map',
  'canonical' => '/migration/where-is-the-migration-now',
  'crumbs' => [['The Great Migration', '/migration'], ['Where Is the Migration Now?', '/migration/where-is-the-migration-now']],
  'badge' => 'Updated ' . $monthName . ' ' . date('Y'),
  'h1' => 'Where Is the Great Migration Now?',
  'lead' => 'In ' . $monthName . ' the herds are usually in the ' . $now[1] . '. ' . $now[2] . ' Herd positions change with the rains, so treat this as a guide and ask us for the latest sightings before you travel.',
  'cta_msg' => 'Hi! Where is the Great Migration at the moment, and what do you recommend for ' . $y . '?',
  'sections' => [
    ['h2' => 'The migration, month by month', 'p' => [
      'The Great Migration is a continuous clockwise movement of around 1.5 million wildebeest and hundreds of thousands of zebra and gazelle through the Serengeti ecosystem. The cards below show where the herds usually are in each month. This month is highlighted.',
    ], 'cards' => $cards],
    ['h2' => 'How to use this guide', 'list' => [
      'Match your dates to the area: southern Serengeti and Ndutu from December to March, central and western areas from April to June, and the north from July to October.',
      'Remember that the herds are spread over a very large area, and positions shift week to week with the rains.',
      'Book a camp near the herds for your dates. We choose the camp based on the latest guide reports.',
    ]],
  ],
  'tour_sql' => "tour_type = 'Great Migration Safari' OR LOWER(name) LIKE '%migration%' OR LOWER(name) LIKE '%calving%'",
  'tour_heading' => 'Great Migration safari packages',
  'faqs' => [
    ['Where is the Great Migration right now?', 'In ' . $monthName . ' the herds are usually in the ' . $now[1] . '. ' . $now[2] . ' Exact positions vary from year to year.'],
    ['Is the Great Migration in Tanzania or Kenya?', 'Both. The herds spend most of the year in the Tanzanian Serengeti and cross into Kenya\'s Masai Mara in the second half of the year.'],
    ['When is the best time to see the migration?', 'It depends on what you want to see: calving from December to March, and river crossings from July to October.'],
    ['Does the migration happen all year?', 'Yes. The herds are always on the move, so there is something to see in every month.'],
  ],
  'related' => [['Great Migration ' . $y, '/migration'], ['River crossings', '/migration/river-crossings'], ['Calving season', '/calving-season'], ['Best time to visit Tanzania', '/best-time-to-visit-tanzania']],
];
require __DIR__ . '/includes/seo_page.php';
