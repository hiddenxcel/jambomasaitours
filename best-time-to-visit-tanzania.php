<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$y = seoYears();
$cfg = [
  'title' => 'Best Time to Visit Tanzania ' . $y . ' | Month by Month',
  'desc' => 'When to visit Tanzania in ' . $y . ': month-by-month guide to the Great Migration, calving season, Kilimanjaro climbs and Zanzibar beaches, with the best months for each.',
  'keywords' => 'best time to visit tanzania, best time for safari tanzania, tanzania weather by month, best month serengeti, best time kilimanjaro, tanzania dry season, tanzania green season',
  'canonical' => '/best-time-to-visit-tanzania',
  'crumbs' => [['Best Time to Visit Tanzania', '/best-time-to-visit-tanzania']],
  'badge' => 'Plan ' . $y,
  'h1' => 'Best Time to Visit Tanzania ' . $y,
  'lead' => 'Tanzania is a year-round destination, but the best month depends on what you want to see. This guide shows the best time for the Great Migration, calving season, Kilimanjaro and Zanzibar, so you can choose your dates with confidence.',
  'cta_msg' => 'Hi! Help me choose the best month for my Tanzania safari (' . $y . ').',
  'sections' => [
    ['h2' => 'The short answer', 'p' => [
      'The dry season from late June to October is the classic safari window: animals gather around water, the grass is short and roads are good. It is also the busiest and most expensive time. December to March is excellent for the calving season in the southern Serengeti, and the green season (March to May, and November) offers the lowest prices and the fewest visitors.',
    ]],
    ['h2' => 'Month by month', 'table' => ['head' => ['Month', 'Conditions', 'Best for'], 'rows' => [
      ['January', 'Hot, mostly dry', 'Calving in Ndutu, Kilimanjaro, Zanzibar'],
      ['February', 'Hot and dry, peak calving', 'Calving season, predator action, Kilimanjaro'],
      ['March', 'Rains start building', 'Green season value, Ndutu early in the month'],
      ['April', 'Long rains', 'Lowest prices, lush scenery, few visitors'],
      ['May', 'Long rains ending', 'Value safaris, Western Corridor from late May'],
      ['June', 'Dry begins, cooler', 'Grumeti crossings, Kilimanjaro, Zanzibar'],
      ['July', 'Dry and cool', 'Great Migration in the north, all parks'],
      ['August', 'Dry, peak season', 'Mara River crossings, Tarangire elephants'],
      ['September', 'Dry', 'Mara River crossings, quieter than August'],
      ['October', 'Dry, warming', 'Northern Serengeti, Kilimanjaro, Zanzibar'],
      ['November', 'Short rains begin', 'Value, birdlife, herds heading south'],
      ['December', 'Short rains, warm', 'Calving begins, Christmas safaris, Zanzibar'],
    ]]],
    ['h2' => 'Best time by experience', 'cards' => [
      ['Great Migration', 'July to October (north)', 'Mara River crossings in the northern Serengeti. Grumeti River in the Western Corridor from June to July.'],
      ['Calving season', 'December to March', 'Peak in February around Ndutu and the southern Serengeti. Fewer vehicles and constant predator action.'],
      ['Kilimanjaro', 'Jan–Mar and Jun–Oct', 'The two main dry windows. Clearer skies and safer trails. April, May and November are wet on the mountain.'],
      ['Tarangire', 'June to October', 'Dry season brings big elephant herds to the Tarangire River.'],
      ['Ngorongoro Crater', 'All year', 'Resident wildlife at any time. Fewer crowds in the green season.'],
      ['Zanzibar', 'Jun–Oct and Dec–Feb', 'Drier, sunnier months for beach time after your safari.'],
    ]],
    ['h2' => 'How to choose', 'list' => [
      'Want the river crossings? Travel between July and October, stay in the northern Serengeti, and allow at least three nights.',
      'Want fewer vehicles and baby animals? Choose a calving season safari in January or February.',
      'Travelling on a tighter budget? April, May and November have the lowest rates, with a trade-off of more rain.',
      'Combining a climb with a safari? Climb Kilimanjaro in a dry window, then relax on a short safari or in Zanzibar.',
      'Travelling at Christmas or New Year? Book as early as possible, because popular camps fill months ahead.',
    ]],
  ],
  'tour_sql' => '1=1',
  'tour_heading' => 'Popular Tanzania safari packages ' . $y,
  'faqs' => [
    ['What is the best month for a Tanzania safari?', 'For most travellers the dry season from late June to October is best for wildlife viewing, with the Great Migration river crossings from July to October. If you prefer fewer vehicles and want to see newborn animals, January and February are outstanding.'],
    ['What is the cheapest time to visit Tanzania?', 'April and May during the long rains, and November, have the lowest accommodation rates. Roads can be slower and some camps close, but the landscape is green and the parks are quiet.'],
    ['Can I see the Great Migration all year?', 'Yes. The herds are always somewhere in the Serengeti ecosystem, but their location changes through the year. See our month-by-month Great Migration guide for where they are in each month.'],
    ['When is the best time to climb Kilimanjaro?', 'January to March and June to October are the driest periods and the most popular. We help you choose the best route and dates for your fitness and schedule.'],
    ['How far ahead should I book?', 'Many travellers now book 9 to 14 months in advance, especially for July to October, February and Christmas. Last-minute availability can still exist, so contact us for current options.'],
  ],
  'related' => [['Great Migration ' . $y, '/migration'], ['Calving season', '/calving-season'], ['Green season safari', '/green-season-safari'], ['Where is the migration now?', '/migration/where-is-the-migration-now'], ['Kilimanjaro & Meru', '/mountain-trekking']],
];
require __DIR__ . '/includes/seo_page.php';
