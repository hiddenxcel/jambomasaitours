<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$s1 = calvingSeason(); $s2 = calvingSeason(1);
$cfg = [
  'title' => 'Green Season Safari Tanzania ' . $s1 . ' & ' . $s2,
  'desc' => 'Tanzania green season safari ' . $s1 . ' and ' . $s2 . ': lower rates, calving season, birdlife and quiet parks. What to expect from November to May and who it suits.',
  'keywords' => 'green season safari tanzania, tanzania rainy season safari, low season safari tanzania, cheap safari tanzania, tanzania safari november, tanzania safari april may',
  'canonical' => '/green-season-safari',
  'crumbs' => [['Green Season Safari', '/green-season-safari']],
  'badge' => 'Nov – May',
  'h1' => 'Green Season Safari in Tanzania ' . $s1 . ' & ' . $s2,
  'lead' => 'From November to May the plains turn green, newborn animals arrive and the parks are quiet. The green season is the best-value time to visit Tanzania, and in December to March it includes the calving season.',
  'cta_msg' => 'Hi! I am interested in a green season safari in Tanzania (' . $s1 . '). Please share options.',
  'sections' => [
    ['h2' => 'What is the green season?', 'p' => [
      'Tanzania has a short rainy period in November and December and longer rains from March to May. Between them, January and February are usually hot and dry, which is why they are so good for the calving season. The landscape is lush, the skies are dramatic and there are far fewer vehicles than in the July to October peak.',
    ]],
    ['h2' => 'Why travel in the green season', 'list' => [
      'Lower rates: many lodges and camps reduce prices, especially in April, May and November.',
      'Calving season: December to March in the southern Serengeti and Ndutu, with predator action and few vehicles.',
      'Birdlife: migrant birds arrive and resident species are in breeding plumage.',
      'Photography: green backgrounds, storm light and clear air after rain.',
      'Quiet parks: a more private experience at sightings.',
    ]],
    ['h2' => 'What to expect', 'p' => [
      'Rain usually comes as afternoon showers or short storms rather than all-day downpours, except at the peak of the long rains in April and May. Some roads can be slower and a few remote camps close in late April and May. Pack a light waterproof jacket and enjoy cooler mornings. Our guides adjust game drives around the weather.',
    ]],
    ['h2' => 'Best green season months', 'cards' => [
      ['Dec–Feb', 'Calving season', 'Warm, green and busy with wildlife. Best green season option for first-timers.'],
      ['Mar', 'Early rains', 'Good value. Southern Serengeti is still lively early in the month.'],
      ['Apr–May', 'Long rains', 'Lowest prices and fewest visitors. Best for travellers who do not mind rain.'],
      ['Nov', 'Short rains', 'Fresh grass, migrant birds, herds moving south toward Ndutu.'],
    ]],
  ],
  'tour_sql' => "LOWER(name) LIKE '%calving%' OR LOWER(name) LIKE '%ndutu%' OR LOWER(name) LIKE '%budget%' OR LOWER(name) LIKE '%mid%range%'",
  'tour_heading' => 'Safaris that suit the green season',
  'faqs' => [
    ['Is it worth going on safari in the rainy season?', 'Yes, especially between December and March, when calving season brings exceptional wildlife with fewer vehicles. April and May are wetter but offer the best prices and quietest parks.'],
    ['Will it rain all day?', 'Usually not. Rain commonly falls in the afternoon or as short storms, and mornings are often clear. The heaviest rain is in April and May.'],
    ['Is the green season good for families?', 'Yes. Lower prices, warm weather in December to February and active wildlife make it a good time for families, as long as you pack for some rain.'],
    ['Are lodges open in the green season?', 'Most lodges and camps stay open, although a few remote camps close in late April and May. We choose properties that operate during your dates.'],
  ],
  'related' => [['Calving season', '/calving-season'], ['Best time to visit Tanzania', '/best-time-to-visit-tanzania'], ['Christmas & New Year safari', '/christmas-new-year-safari'], ['All safari packages', '/tours']],
];
require __DIR__ . '/includes/seo_page.php';
