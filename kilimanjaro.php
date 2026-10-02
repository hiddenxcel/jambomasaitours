<?php
/* Kurasa zote za mlima: /kilimanjaro, /kilimanjaro/{slug}, /mount-meru.
   Kila ukurasa ni mkusanyiko wa data unaotumiwa na includes/seo_page.php.
   Hakuna bei za kubuni: bei zote zinatoka kwenye tours za database. */
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$key = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['page'] ?? ''));
$y   = seoYears();
$kiliSql = "LOWER(name) LIKE '%kilimanjaro%'";

$cta = fn(string $t) => 'Hi! ' . $t;

/* ── Data ya routes (ukweli wa jumla, usio na bei) ── */
$routes = [
  'lemosho-route' => [
    'name' => 'Lemosho', 'days' => '7 to 8 days', 'type' => 'Camping', 'side' => 'West', 'level' => 'Moderate to hard',
    'lead' => 'The Lemosho route approaches Kilimanjaro from the quiet western side, crossing the Shira Plateau before joining the Machame trail. A long, gradual profile gives excellent acclimatisation, which is why it is one of the most successful and most scenic routes.',
    'why' => ['Gradual ascent that suits acclimatisation, especially on the 8-day itinerary.', 'Starts in rainforest where you may see monkeys and birdlife, then crosses open moorland.', 'Quieter than Machame until the routes merge near Lava Tower.', 'Rewarding views over the Shira Plateau and the Western Breach.'],
    'itin' => [['Day 1', 'Londorossi Gate to Lemosho trailhead and Mti Mkubwa camp', 'Rainforest walk'], ['Day 2', 'Mti Mkubwa to Shira 1', 'Moorland begins'], ['Day 3', 'Shira 1 to Shira 2', 'Plateau and views'], ['Day 4', 'Shira 2 via Lava Tower to Barranco', 'Climb high, sleep low'], ['Day 5', 'Barranco Wall to Karanga', 'Scramble and views'], ['Day 6', 'Karanga to Barafu', 'Summit preparation'], ['Day 7', 'Summit Uhuru Peak, descend to Mweka', 'Summit night'], ['Day 8', 'Mweka to the gate', 'Certificates and transfer']],
    'faq' => [['How many days is the Lemosho route?', 'Seven or eight days. We recommend eight, because the extra day improves acclimatisation and your chance of reaching the summit.'], ['Is Lemosho harder than Machame?', 'It is a little longer but the gradient is gentler, so many climbers find it easier on the body. The summit day is the same.'], ['Is Lemosho good for beginners?', 'Yes, if you are fit and choose the 8-day option. No technical climbing is needed.']],
  ],
  'machame-route' => [
    'name' => 'Machame', 'days' => '6 to 7 days', 'type' => 'Camping', 'side' => 'South-west', 'level' => 'Moderate to hard',
    'lead' => 'Machame, often called the Whiskey route, is Kilimanjaro\'s most popular trail. It climbs through rainforest and moorland, passes the Lava Tower and the Barranco Wall, and offers varied scenery and a good acclimatisation profile on the 7-day schedule.',
    'why' => ['Varied scenery: rainforest, moorland, alpine desert and glaciers.', 'Climb high, sleep low profile through Lava Tower helps acclimatisation.', 'A well-supported route with good camps.', 'More climbers than Lemosho, so busier in peak months.'],
    'itin' => [['Day 1', 'Machame Gate to Machame Camp', 'Rainforest'], ['Day 2', 'Machame Camp to Shira Camp', 'Moorland'], ['Day 3', 'Shira to Lava Tower to Barranco', 'Acclimatisation day'], ['Day 4', 'Barranco Wall to Karanga', 'Scrambling'], ['Day 5', 'Karanga to Barafu', 'Rest before summit'], ['Day 6', 'Summit Uhuru Peak, descend to Mweka', 'Summit night'], ['Day 7', 'Mweka to the gate', 'Certificates']],
    'faq' => [['Is Machame a hard route?', 'It is a steady, demanding trek with a steep Barranco Wall, but no technical climbing. The 7-day schedule is more comfortable than 6 days.'], ['Machame or Lemosho?', 'Lemosho is longer, quieter and slightly better for acclimatisation. Machame is shorter and slightly more affordable in time and cost.'], ['Why is it called the Whiskey route?', 'It is a tougher trail than Marangu (the Coca-Cola route), hence the nickname.']],
  ],
  'marangu-route' => [
    'name' => 'Marangu', 'days' => '5 to 6 days', 'type' => 'Huts', 'side' => 'South-east', 'level' => 'Moderate',
    'lead' => 'Marangu is the only Kilimanjaro route with sleeping huts rather than tents. It is the oldest and most direct trail, and climbs and descends along the same path. We recommend the 6-day version with an extra acclimatisation day.',
    'why' => ['Hut accommodation with beds instead of tents.', 'Shorter and more direct than other routes.', 'The 5-day schedule gives less time to acclimatise, so a 6-day itinerary is better.', 'Ascent and descent share the same path, so scenery is less varied.'],
    'itin' => [['Day 1', 'Marangu Gate to Mandara Hut', 'Rainforest'], ['Day 2', 'Mandara to Horombo Hut', 'Moorland'], ['Day 3', 'Acclimatisation day at Horombo (6-day plan)', 'Rest and short hike'], ['Day 4', 'Horombo to Kibo Hut', 'Alpine desert'], ['Day 5', 'Summit Uhuru Peak, return to Horombo', 'Summit night'], ['Day 6', 'Horombo to Marangu Gate', 'Certificates']],
    'faq' => [['Is Marangu the easiest route?', 'It has the gentlest gradient and huts, but a short schedule leaves less time to acclimatise, so success rates can be lower than on longer routes.'], ['Do I need a tent on Marangu?', 'No. Marangu is the only route with huts, which are shared dormitory-style rooms.'], ['Is the 5-day or 6-day plan better?', 'The 6-day plan, because the extra acclimatisation day improves your summit chances.']],
  ],
  'rongai-route' => [
    'name' => 'Rongai', 'days' => '6 to 7 days', 'type' => 'Camping', 'side' => 'North', 'level' => 'Moderate',
    'lead' => 'Rongai approaches Kilimanjaro from the north near the Kenyan border. It is a quieter route with a gentler gradient and a drier climate, then joins the Marangu trail near Kibo for the summit and descent.',
    'why' => ['Quieter than Machame and Marangu, with a wilder feel.', 'Gentle gradient and less rainfall, which suits the wetter months.', 'Different views, including the northern plains and wildlife-rich foothills.', 'Fewer camps and less variety in the middle section than Machame.'],
    'itin' => [['Day 1', 'Nale Moru Gate to Simba Camp', 'Forest edge'], ['Day 2', 'Simba to Second Cave', 'Moorland'], ['Day 3', 'Second Cave to Kikelewa', 'Open moorland'], ['Day 4', 'Kikelewa to Mawenzi Tarn', 'Acclimatisation'], ['Day 5', 'Mawenzi Tarn to Kibo Hut', 'Alpine desert'], ['Day 6', 'Summit Uhuru Peak, descend to Horombo', 'Summit night'], ['Day 7', 'Horombo to Marangu Gate', 'Certificates']],
    'faq' => [['Is Rongai good in the rainy season?', 'Rongai is on the drier northern side, so it is often chosen when other routes are wet, although April and May are still rainy.'], ['Is Rongai easier than Machame?', 'The gradient is gentler, but the summit day is the same. Choose the 7-day schedule for better acclimatisation.'], ['How busy is Rongai?', 'It is generally quieter than Machame and Marangu.']],
  ],
  'northern-circuit' => [
    'name' => 'Northern Circuit', 'days' => '9 to 10 days', 'type' => 'Camping', 'side' => 'West and north', 'level' => 'Moderate to hard',
    'lead' => 'The Northern Circuit is the longest Kilimanjaro route, circling the mountain from the west to the north before the summit. Its length gives the best acclimatisation and the quietest trails, with the highest success rates when climbers have the time.',
    'why' => ['Best acclimatisation because of the length.', 'The quietest route, with the most scenic views around the mountain.', 'Takes more time and costs more because of the extra days.', 'Starts like Lemosho and finishes via the northern slopes.'],
    'itin' => [['Days 1-3', 'Lemosho start through rainforest to the Shira Plateau', 'Gradual ascent'], ['Days 4-5', 'Lava Tower and northern traverse', 'Climb high, sleep low'], ['Days 6-7', 'North-eastern slopes toward Kibo', 'Quiet wilderness'], ['Day 8', 'Summit Uhuru Peak, descend', 'Summit night'], ['Days 9-10', 'Descend to Mweka and the gate', 'Certificates']],
    'faq' => [['Who is the Northern Circuit for?', 'Climbers who have 9 to 10 days and want the best chance of the summit and the quietest experience.'], ['Is it much harder than other routes?', 'It is longer, not steeper. The gradual pace helps acclimatisation.'], ['Is it worth the extra days?', 'For many climbers, yes: more time at altitude before summit night improves success and comfort.']],
  ],
  'umbwe-route' => [
    'name' => 'Umbwe', 'days' => '5 to 6 days', 'type' => 'Camping', 'side' => 'South', 'level' => 'Hard',
    'lead' => 'Umbwe is the steepest and most direct Kilimanjaro route. It is quiet and dramatic but gains altitude quickly, so it is best for experienced, very fit climbers who already acclimatise well.',
    'why' => ['Very steep ascent with limited acclimatisation time.', 'Very quiet trail with dramatic scenery.', 'Only recommended for experienced trekkers.', 'Many climbers choose Lemosho or Machame instead.'],
    'itin' => [['Day 1', 'Umbwe Gate to Umbwe Cave Camp', 'Steep rainforest'], ['Day 2', 'Umbwe Cave to Barranco', 'Ridge walking'], ['Day 3', 'Barranco to Karanga', 'Barranco Wall'], ['Day 4', 'Karanga to Barafu', 'Summit preparation'], ['Day 5', 'Summit Uhuru Peak, descend to Mweka', 'Summit night'], ['Day 6', 'Mweka to the gate', 'Certificates']],
    'faq' => [['Is Umbwe suitable for beginners?', 'No. It is steep with limited acclimatisation, so we normally recommend a longer, gentler route for first-time climbers.'], ['Is Umbwe dangerous?', 'It is not technical, but the quick gain in altitude raises the risk of altitude sickness. Good fitness and prior altitude experience help.']],
  ],
];

$pages = [];

/* ── Route pages ── */
foreach ($routes as $slug => $r) {
    $rows = [];
    foreach ($r['itin'] as $d) $rows[] = [$d[0], $d[1], $d[2]];
    $pages[$slug] = [
      'title' => $r['name'] . ' Route Kilimanjaro ' . $y . ' | Guide',
      'desc' => $r['name'] . ' route Kilimanjaro ' . $y . ': ' . $r['days'] . ', ' . strtolower($r['type']) . ', ' . strtolower($r['side']) . ' approach. Day-by-day itinerary, difficulty and tips with KINAPA-certified guides.',
      'keywords' => strtolower($r['name']) . ' route kilimanjaro, ' . strtolower($r['name']) . ' route itinerary, kilimanjaro routes, climb kilimanjaro ' . date('Y'),
      'canonical' => '/kilimanjaro/' . $slug,
      'crumbs' => [['Kilimanjaro', '/kilimanjaro'], [$r['name'] . ' Route', '/kilimanjaro/' . $slug]],
      'badge' => $r['days'] . ' · ' . $r['type'],
      'h1' => $r['name'] . ' Route Kilimanjaro ' . $y,
      'lead' => $r['lead'],
      'cta_msg' => $cta('I would like to climb Kilimanjaro on the ' . $r['name'] . ' route (' . $y . '). Please share dates and prices.'),
      'sections' => [
        ['h2' => $r['name'] . ' route at a glance', 'table' => ['head' => ['Detail', 'Info'], 'rows' => [['Duration', $r['days']], ['Accommodation', $r['type']], ['Approach', $r['side']], ['Difficulty', $r['level']], ['Summit', 'Uhuru Peak, 5,895 m']]]],
        ['h2' => 'Why choose the ' . $r['name'] . ' route', 'list' => $r['why']],
        ['h2' => $r['name'] . ' route itinerary', 'table' => ['head' => ['Day', 'Route', 'Focus'], 'rows' => $rows]],
        ['h2' => 'Included with a Jambo Masai Tours climb', 'list' => ['KINAPA-certified guides and a full crew of porters and cooks', 'Park fees, camping or hut fees and rescue fees', 'Airport transfers and hotel nights before and after the climb, as listed on your tour', 'Safety equipment: first aid kit and emergency oxygen on every trek', 'Final inclusions are shown on each tour page']],
      ],
      'tour_sql' => $kiliSql,
      'tour_heading' => 'Kilimanjaro climbs ' . $y,
      'faqs' => $r['faq'],
      'related' => [['All Kilimanjaro routes', '/kilimanjaro'], ['Best time to climb', '/kilimanjaro/best-time-to-climb'], ['Packing list', '/kilimanjaro/packing-list'], ['Success rate & acclimatisation', '/kilimanjaro/success-rate-and-acclimatization'], ['Mount Meru', '/mount-meru']],
      'current' => 'trekking', 'og' => IMG_SERENGETI,
    ];
}

/* ── Hub ── */
$cmp = [];
foreach ($routes as $slug => $r) $cmp[] = [$r['name'], $r['days'], $r['type'], $r['side'], $r['level']];
$pages['hub'] = [
  'title' => 'Climb Kilimanjaro ' . $y . ' | Routes, Dates & Guide',
  'desc' => 'Climb Kilimanjaro in ' . $y . ' with KINAPA-certified guides. Compare the Lemosho, Machame, Marangu, Rongai, Northern Circuit and Umbwe routes, best months, packing and success tips.',
  'keywords' => 'climb kilimanjaro, kilimanjaro routes, kilimanjaro climb ' . date('Y') . ', kilimanjaro lemosho machame, mount kilimanjaro tour, kilimanjaro trekking tanzania, uhuru peak',
  'canonical' => '/kilimanjaro',
  'crumbs' => [['Kilimanjaro', '/kilimanjaro']],
  'badge' => 'Uhuru Peak · 5,895 m',
  'h1' => 'Climb Mount Kilimanjaro ' . $y,
  'lead' => 'Kilimanjaro is Africa\'s highest mountain and the world\'s tallest free-standing peak. You need no technical climbing skills, but you do need the right route, enough days and good guides. This guide compares every route and helps you plan the best climb for ' . $y . '.',
  'cta_msg' => $cta('I would like to climb Kilimanjaro in ' . $y . '. Please help me choose a route.'),
  'sections' => [
    ['h2' => 'Compare the Kilimanjaro routes', 'table' => ['head' => ['Route', 'Days', 'Sleeping', 'Side', 'Difficulty'], 'rows' => $cmp]],
    ['h2' => 'How to choose your route', 'list' => [
      'Best chance of reaching the summit: Lemosho (8 days) or the Northern Circuit (9 to 10 days).',
      'Popular, varied scenery: Machame (7 days).',
      'Prefer huts to tents: Marangu (6 days).',
      'Quieter and drier side: Rongai (7 days).',
      'Experienced and very fit climbers only: Umbwe.',
    ]],
    ['h2' => 'Plan your climb', 'cards' => [
      ['Best time', 'Jan–Mar and Jun–Oct', 'The two dry windows. See our month-by-month guide.'],
      ['Preparation', 'Fitness and kit', 'Train for several weeks and pack for freezing summit night. See the packing list and training plan.'],
      ['Acclimatisation', 'Go slowly', 'Longer itineraries and a steady pace give better summit odds. See our success guide.'],
    ]],
    ['h2' => 'Add a safari or Zanzibar', 'p' => ['Many guests climb first and then relax on a short safari or a Zanzibar beach extension. Ask us for a combined itinerary and we will arrange transfers and accommodation.']],
  ],
  'tour_sql' => $kiliSql,
  'tour_heading' => 'Kilimanjaro climbs and combinations',
  'faqs' => [
    ['How many days do I need to climb Kilimanjaro?', 'Between 5 and 10 days depending on the route. We recommend at least 7 days, and 8 on Lemosho, for better acclimatisation.'],
    ['Do I need climbing experience?', 'No technical skills are needed. Kilimanjaro is a high-altitude trek, so fitness, a slow pace and good preparation matter most.'],
    ['Do I need a guide?', 'Yes. Park rules require every climber to go with a licensed guide, and we provide a full crew.'],
    ['When is the best time to climb?', 'January to March and June to October are the driest periods.'],
    ['Can I climb Mount Meru first?', 'Yes. Mount Meru is a popular 3 to 4 day acclimatisation trek before Kilimanjaro.'],
  ],
  'related' => [['Lemosho route', '/kilimanjaro/lemosho-route'], ['Machame route', '/kilimanjaro/machame-route'], ['Best time to climb', '/kilimanjaro/best-time-to-climb'], ['Packing list', '/kilimanjaro/packing-list'], ['Mount Meru', '/mount-meru'], ['Kilimanjaro & Meru overview', '/mountain-trekking']],
  'current' => 'trekking',
];

/* ── Best time ── */
$pages['best-time-to-climb'] = [
  'title' => 'Best Time to Climb Kilimanjaro ' . $y . ' | Months',
  'desc' => 'Best time to climb Kilimanjaro in ' . $y . ': the dry windows (Jan–Mar, Jun–Oct), month-by-month conditions, full-moon summits and which routes suit the wetter months.',
  'keywords' => 'best time to climb kilimanjaro, kilimanjaro weather by month, kilimanjaro dry season, kilimanjaro full moon climb, climb kilimanjaro ' . date('Y'),
  'canonical' => '/kilimanjaro/best-time-to-climb',
  'crumbs' => [['Kilimanjaro', '/kilimanjaro'], ['Best Time to Climb', '/kilimanjaro/best-time-to-climb']],
  'badge' => 'Plan ' . $y,
  'h1' => 'Best Time to Climb Kilimanjaro ' . $y,
  'lead' => 'You can climb Kilimanjaro all year, but the driest and clearest conditions are from January to March and June to October. Here is what to expect in each month so you can choose the right dates.',
  'cta_msg' => $cta('Which month is best for me to climb Kilimanjaro in ' . $y . '?'),
  'sections' => [
    ['h2' => 'Month by month', 'table' => ['head' => ['Months', 'Conditions on the mountain', 'Notes'], 'rows' => [
      ['January – February', 'Dry and clear, cold at the summit', 'Popular dry window with good visibility'],
      ['March', 'Rains starting', 'Early March can still be good, then wetter'],
      ['April – May', 'Long rains', 'Wet trails and cloud, quietest on the mountain'],
      ['June – August', 'Dry and cool', 'Peak season with very clear skies'],
      ['September – October', 'Dry, warming', 'Excellent conditions, slightly fewer climbers'],
      ['November', 'Short rains', 'Some rain, fewer climbers'],
      ['December', 'Mostly dry', 'Popular around Christmas and New Year, book early'],
    ]]],
    ['h2' => 'Full-moon summits', 'p' => ['Many climbers time summit night for a full moon, which lights the trail and makes the pre-dawn climb easier. Ask us which dates in ' . $y . ' fit a full moon summit.']],
    ['h2' => 'Which routes suit the wet months?', 'list' => ['Rongai is on the drier northern side and is often chosen when other routes are wet.', 'Longer routes such as Lemosho and the Northern Circuit give you more flexibility to wait out weather.', 'Pack waterproofs in any month, because weather on the mountain changes quickly.']],
  ],
  'tour_sql' => $kiliSql,
  'tour_heading' => 'Kilimanjaro climbs ' . $y,
  'faqs' => [
    ['What is the best month to climb Kilimanjaro?', 'January, February, August and September are popular for clear, stable conditions, although any month in the two dry windows works well.'],
    ['Can I climb Kilimanjaro in the rainy season?', 'Yes, but trails are wetter and visibility lower. April and May are the wettest and quietest months.'],
    ['Is it very cold at the summit?', 'Yes, summit night is below freezing in every month, so warm layers are essential. See our packing list.'],
  ],
  'related' => [['All Kilimanjaro routes', '/kilimanjaro'], ['Packing list', '/kilimanjaro/packing-list'], ['Best time to visit Tanzania', '/best-time-to-visit-tanzania']],
  'current' => 'trekking',
];

/* ── Packing list ── */
$pages['packing-list'] = [
  'title' => 'Kilimanjaro Packing List ' . $y . ' | Complete Gear Guide',
  'desc' => 'Kilimanjaro packing list for ' . $y . ': layers, boots, sleeping bag, poles, headlamp, hydration and what to leave at home. Practical gear guide from KINAPA-certified guides.',
  'keywords' => 'kilimanjaro packing list, what to pack for kilimanjaro, kilimanjaro gear list, kilimanjaro clothing, kilimanjaro sleeping bag',
  'canonical' => '/kilimanjaro/packing-list',
  'crumbs' => [['Kilimanjaro', '/kilimanjaro'], ['Packing List', '/kilimanjaro/packing-list']],
  'badge' => 'Gear guide',
  'h1' => 'Kilimanjaro Packing List ' . $y,
  'lead' => 'Kilimanjaro takes you from tropical forest to freezing summit in a few days, so layering is the key. Use this checklist, and bring broken-in boots that you have already walked in.',
  'cta_msg' => $cta('Can you send me a Kilimanjaro packing checklist for ' . $y . '?'),
  'sections' => [
    ['h2' => 'Clothing (layers)', 'list' => ['Moisture-wicking base layers (top and bottom)', 'Fleece or light insulated mid-layer', 'Warm insulated jacket (down or synthetic) for summit night', 'Waterproof jacket and trousers', 'Hiking trousers and a couple of shirts', 'Warm hat, sun hat or cap, neck gaiter or buff', 'Waterproof gloves with warm liners', 'Thermal socks and spare pairs']],
    ['h2' => 'Footwear', 'list' => ['Broken-in waterproof hiking boots with ankle support', 'Camp shoes or sandals', 'Gaiters (useful on dusty or wet trails)']],
    ['h2' => 'Equipment', 'list' => ['Warm sleeping bag rated for low temperatures (can be rented)', 'Daypack of about 25 to 35 litres, and a duffel for porters', 'Trekking poles', 'Headlamp with spare batteries', 'Water bottles or a hydration bladder, and purification tablets', 'Sunglasses and high-SPF sunscreen and lip balm']],
    ['h2' => 'Personal items', 'list' => ['Any prescribed medicines, and altitude medication after talking to your doctor', 'Toiletries, wet wipes and hand sanitiser', 'Snacks and electrolyte sachets', 'Camera, power bank and copies of documents', 'Travel insurance that covers high-altitude trekking']],
    ['h2' => 'Rent or buy?', 'p' => ['Some items, such as sleeping bags, jackets and poles, can be rented in Arusha or Moshi. Ask us what we can arrange before you buy expensive gear.']],
  ],
  'tour_sql' => $kiliSql,
  'tour_heading' => 'Kilimanjaro climbs ' . $y,
  'faqs' => [
    ['How cold does Kilimanjaro get?', 'Summit night is typically well below freezing, with wind chill making it feel colder, so a warm insulated jacket, hat and gloves are essential.'],
    ['How much luggage can porters carry?', 'Porters carry a limited weight per climber, so pack your main bag efficiently and carry essentials in your daypack. We confirm the limit when you book.'],
    ['Can I rent gear?', 'Yes, many items can be rented locally. Ask us when you book.'],
  ],
  'related' => [['All Kilimanjaro routes', '/kilimanjaro'], ['Training plan', '/kilimanjaro/training-plan'], ['Success rate & acclimatisation', '/kilimanjaro/success-rate-and-acclimatization']],
  'current' => 'trekking',
];

/* ── Success rate + acclimatisation ── */
$pages['success-rate-and-acclimatization'] = [
  'title' => 'Kilimanjaro Success Rate & Altitude Sickness ' . $y,
  'desc' => 'Kilimanjaro summit success rate and altitude sickness: how route length, pace and acclimatisation change your odds, with practical tips from KINAPA-certified guides.',
  'keywords' => 'kilimanjaro success rate, kilimanjaro altitude sickness, kilimanjaro acclimatization, how to prepare for kilimanjaro, pole pole kilimanjaro',
  'canonical' => '/kilimanjaro/success-rate-and-acclimatization',
  'crumbs' => [['Kilimanjaro', '/kilimanjaro'], ['Success Rate & Acclimatisation', '/kilimanjaro/success-rate-and-acclimatization']],
  'badge' => 'Altitude guide',
  'h1' => 'Kilimanjaro Success Rate & Altitude Sickness',
  'lead' => 'Most climbers who fail to reach the summit are stopped by altitude, not by fitness or technical difficulty. The biggest factors you control are the length of your itinerary, your pace and how well you look after yourself.',
  'cta_msg' => $cta('How can I improve my chances of reaching the Kilimanjaro summit in ' . $y . '?'),
  'sections' => [
    ['h2' => 'What affects your summit chances', 'list' => ['Route length: longer routes such as Lemosho (8 days) and the Northern Circuit give your body more time to adapt.', 'Pace: walk "pole pole" (slowly) from the first day.', 'Hydration and food: drink plenty and eat even when your appetite is low.', 'Sleep and rest: arrive rested and take rest seriously.', 'Fitness: good cardio fitness makes the long days easier.', 'Guide quality and group size: attentive guides spot early symptoms.']],
    ['h2' => 'Climb high, sleep low', 'p' => ['Routes such as Machame and Lemosho visit Lava Tower at about 4,600 m before descending to Barranco to sleep. This helps your body adapt and is one reason these routes work well.']],
    ['h2' => 'Altitude sickness basics', 'list' => ['Mild symptoms include headache, nausea and trouble sleeping.', 'Tell your guide early. Do not hide symptoms.', 'Serious symptoms (confusion, severe breathlessness, loss of coordination) mean descending immediately.', 'Talk to a doctor before your trip about altitude medication.', 'Our guides check your oxygen saturation and symptoms every day.']],
  ],
  'tour_sql' => $kiliSql,
  'tour_heading' => 'Kilimanjaro climbs ' . $y,
  'faqs' => [
    ['What is the Kilimanjaro success rate?', 'It varies by route and itinerary. Longer routes with more acclimatisation days have noticeably higher success than short 5-day itineraries. We guide with extra safety checks on every climb.'],
    ['Does fitness guarantee summiting?', 'No. Fit climbers can still struggle with altitude, which is why pace and acclimatisation matter as much as fitness.'],
    ['Is there oxygen on the mountain?', 'We carry emergency oxygen and a first aid kit on every climb.'],
  ],
  'related' => [['Training plan', '/kilimanjaro/training-plan'], ['Lemosho route', '/kilimanjaro/lemosho-route'], ['Northern Circuit', '/kilimanjaro/northern-circuit'], ['Mount Meru warm-up', '/mount-meru']],
  'current' => 'trekking',
];

/* ── Training plan ── */
$pages['training-plan'] = [
  'title' => 'Kilimanjaro Training Plan ' . $y . ' | 12-Week Guide',
  'desc' => 'Kilimanjaro training plan: a practical 12-week programme of cardio, hiking with a pack, stair work and strength, plus tips on boots, hydration and mental preparation.',
  'keywords' => 'kilimanjaro training plan, how to train for kilimanjaro, kilimanjaro fitness, kilimanjaro hiking preparation',
  'canonical' => '/kilimanjaro/training-plan',
  'crumbs' => [['Kilimanjaro', '/kilimanjaro'], ['Training Plan', '/kilimanjaro/training-plan']],
  'badge' => '12 weeks',
  'h1' => 'Kilimanjaro Training Plan ' . $y,
  'lead' => 'You do not need to be an athlete to climb Kilimanjaro, but you do need to be comfortable walking for 6 to 8 hours a day for several days in a row. Start about 12 weeks before your trip.',
  'cta_msg' => $cta('Can you advise how to train for Kilimanjaro in ' . $y . '?'),
  'sections' => [
    ['h2' => '12-week outline', 'table' => ['head' => ['Weeks', 'Focus', 'Example'], 'rows' => [
      ['1 – 4', 'Build a base', '3 to 4 cardio sessions a week (walking, cycling, swimming) and one longer walk'],
      ['5 – 8', 'Add hills and weight', 'Weekend hikes of 3 to 5 hours with a daypack, stair or incline work, leg strength'],
      ['9 – 11', 'Peak', 'Back-to-back long hikes on weekends, wearing your boots and pack'],
      ['12', 'Taper', 'Lighter activity, rest, check gear, sleep well'],
    ]]],
    ['h2' => 'Key tips', 'list' => ['Wear your hiking boots on training walks so they are fully broken in.', 'Practise walking slowly and steadily, which is how you climb.', 'Strengthen your legs and core with squats, lunges and step-ups.', 'Practise long days and eating and drinking while walking.', 'See your doctor for a check-up if you have any health concerns.']],
  ],
  'tour_sql' => $kiliSql,
  'tour_heading' => 'Kilimanjaro climbs ' . $y,
  'faqs' => [
    ['How fit do I need to be for Kilimanjaro?', 'A good level of general fitness. You should be able to walk for several hours a day with a small pack on consecutive days.'],
    ['Can I climb Kilimanjaro if I am over 50 or a beginner?', 'Many people do. Choose a longer route, train well and let us know your experience so we can advise.'],
  ],
  'related' => [['Packing list', '/kilimanjaro/packing-list'], ['Success rate & acclimatisation', '/kilimanjaro/success-rate-and-acclimatization'], ['All routes', '/kilimanjaro']],
  'current' => 'trekking',
];

/* ── Cost: sababu za gharama bila kubuni namba — bei halisi ziko kwenye tours ── */
$pages['cost-and-whats-included'] = [
  'title' => 'Kilimanjaro Climb Cost ' . $y . ' | What Is Included',
  'desc' => 'What affects the cost of climbing Kilimanjaro in ' . $y . ': route length, park fees, crew, group size and accommodation, and what a good operator should include.',
  'keywords' => 'kilimanjaro climb cost, how much does it cost to climb kilimanjaro, kilimanjaro price, kilimanjaro park fees, kilimanjaro package inclusions',
  'canonical' => '/kilimanjaro/cost-and-whats-included',
  'crumbs' => [['Kilimanjaro', '/kilimanjaro'], ['Cost & What Is Included', '/kilimanjaro/cost-and-whats-included']],
  'badge' => 'Pricing guide',
  'h1' => 'Kilimanjaro Climb Cost ' . $y . ': What You Pay For',
  'lead' => 'The price of a Kilimanjaro climb depends mainly on route length, park fees, the quality of the crew and the support you receive. Here is what is behind the price, and what to check before you book. Current starting prices for each tour are shown on the tour pages below.',
  'cta_msg' => $cta('Please share the current price for a Kilimanjaro climb in ' . $y . '.'),
  'sections' => [
    ['h2' => 'What affects the cost', 'list' => ['Number of days: each extra day adds park fees, camping fees and crew costs.', 'Route: some routes have higher fees or need more support.', 'Group size: small private groups usually cost more per person than larger groups.', 'Crew: a full crew of guides, assistant guides, porters and a cook is required.', 'Accommodation and transfers before and after the climb.', 'Gear rental, extra nights and optional extras such as a safari or Zanzibar.']],
    ['h2' => 'What should be included', 'list' => ['Park entry, camping or hut and rescue fees', 'Licensed guides, assistant guides, porters and cook', 'All meals and drinking water on the mountain', 'Tents, sleeping mats and a mess tent (or huts on Marangu)', 'Airport and gate transfers, and hotel nights as listed', 'Safety equipment, including a first aid kit and emergency oxygen']],
    ['h2' => 'What is usually extra', 'list' => ['International flights, visas and travel insurance', 'Tips for the crew', 'Personal gear and sleeping bag rental', 'Drinks and personal items']],
    ['h2' => 'Cheap does not always mean better', 'p' => ['Very low prices often mean underpaid porters, fewer safety checks or hidden extras. Ask any operator what is included, how porters are treated and what emergency equipment is carried.']],
  ],
  'tour_sql' => $kiliSql,
  'tour_heading' => 'Kilimanjaro tours and starting prices',
  'tour_intro' => 'These are our current Kilimanjaro tours. The price shown is the starting price per person.',
  'faqs' => [
    ['How much does it cost to climb Kilimanjaro?', 'It depends on the route and number of days. See the tours below for our current starting prices, and ask us for a quote for your dates and group size.'],
    ['Are tips included?', 'Tips for the crew are usually paid separately. We explain the recommended amounts when you book.'],
    ['Do you offer discounts for groups?', 'Group pricing can apply. Contact us with your group size and dates.'],
  ],
  'related' => [['All Kilimanjaro routes', '/kilimanjaro'], ['Packing list', '/kilimanjaro/packing-list'], ['Best time to climb', '/kilimanjaro/best-time-to-climb']],
  'current' => 'trekking',
];

/* ── Mount Meru ── */
$pages['meru'] = [
  'title' => 'Mount Meru Climb ' . $y . ' | 3-4 Day Trek Guide',
  'desc' => 'Climb Mount Meru (4,562 m) in Arusha National Park: a 3 to 4 day trek with wildlife, a dramatic crater rim and an ideal warm-up for Kilimanjaro. Itinerary and tips for ' . $y . '.',
  'keywords' => 'mount meru climb, mount meru trek, mount meru itinerary, meru acclimatization kilimanjaro, arusha national park trekking, socialist peak',
  'canonical' => '/mount-meru',
  'crumbs' => [['Mount Meru', '/mount-meru']],
  'badge' => '4,562 m · 3-4 days',
  'h1' => 'Mount Meru Climb ' . $y,
  'lead' => 'Mount Meru is Tanzania\'s second-highest mountain and sits inside Arusha National Park. The trek climbs through forest with buffalo and giraffe, then follows a sharp crater rim to Socialist Peak, with sunrise views of Kilimanjaro.',
  'cta_msg' => $cta('I would like to climb Mount Meru in ' . $y . '. Please share dates and prices.'),
  'sections' => [
    ['h2' => 'Mount Meru at a glance', 'table' => ['head' => ['Detail', 'Info'], 'rows' => [['Summit', 'Socialist Peak, 4,562 m'], ['Duration', '3 to 4 days'], ['Starting point', 'Momella Gate, Arusha National Park'], ['Accommodation', 'Mountain huts'], ['Safety', 'An armed park ranger accompanies every group'], ['Difficulty', 'Moderate to hard (steep, with a narrow summit ridge)']]]],
    ['h2' => 'Itinerary', 'table' => ['head' => ['Day', 'Route', 'Focus'], 'rows' => [
      ['Day 1', 'Momella Gate to Miriakamba Hut', 'Forest and wildlife walk'],
      ['Day 2', 'Miriakamba to Saddle Hut, optional Little Meru', 'Acclimatisation'],
      ['Day 3', 'Summit Socialist Peak at dawn, return to Miriakamba', 'Summit climb'],
      ['Day 4', 'Descend to Momella Gate', 'Return and transfer'],
    ]]],
    ['h2' => 'Why climb Meru', 'list' => ['A good acclimatisation trek before Kilimanjaro.', 'Wildlife on the trail, which is unusual for a mountain climb.', 'A dramatic crater rim and views of Kilimanjaro at sunrise.', 'Shorter and often quieter than Kilimanjaro.']],
    ['h2' => 'Best time to climb Meru', 'p' => ['The drier months, June to October and December to February, give the clearest views and safest footing. The long rains in April and May are wet and slippery.']],
  ],
  'tour_sql' => "LOWER(name) LIKE '%meru%'",
  'tour_heading' => 'Mount Meru tours',
  'faqs' => [
    ['How hard is Mount Meru?', 'It is steep with a narrow ridge near the top, but needs no technical skills. Good fitness and a steady pace are enough.'],
    ['Is Meru good preparation for Kilimanjaro?', 'Yes. Spending 3 to 4 days at altitude on Meru improves your acclimatisation, and many climbers do Meru first and Kilimanjaro afterwards.'],
    ['Do I need a guide and a ranger on Meru?', 'Yes. Park rules require a guide, and an armed ranger accompanies each group because of wildlife on the trail.'],
    ['Can I combine Meru and Kilimanjaro?', 'Yes. Ask us for a Meru-and-Kilimanjaro itinerary, with an optional safari afterwards.'],
  ],
  'related' => [['Climb Kilimanjaro', '/kilimanjaro'], ['Kilimanjaro routes', '/kilimanjaro/lemosho-route'], ['Best time to climb', '/kilimanjaro/best-time-to-climb'], ['Kilimanjaro & Meru overview', '/mountain-trekking']],
  'current' => 'trekking',
];

if ($key === '' || $key === 'hub') $key = 'hub';
if (!isset($pages[$key])) { render404(); }

$cfg = $pages[$key];
$cfg['og'] = $cfg['og'] ?? IMG_SERENGETI;
require __DIR__ . '/includes/seo_page.php';
