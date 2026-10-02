<?php
/* Kurasa za utamaduni: /cultural-tours (hub) na /cultural-tours/{slug}.
   Maudhui ni ukweli wa jumla + tours halisi za database (bei zinatoka DB, hakuna bei za kubuni). */
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$key = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['page'] ?? ''));
if ($key === '') $key = 'hub';
$y = seoYears();
$cta = fn(string $t) => 'Hi! ' . $t;

$cultureSql = "LOWER(name) REGEXP 'maasai|boma|hadzabe|datoga|culture|cultural|materuni|coffee|kikuletwa|hot spring'";

$hubLinks = [
    ['Maasai village visit', '/cultural-tours/maasai-village-visit'],
    ['Hadzabe & Datoga (Lake Eyasi)', '/cultural-tours/hadzabe-datoga-lake-eyasi'],
    ['Materuni Waterfalls & coffee tour', '/cultural-tours/materuni-waterfalls-coffee-tour'],
    ['Kikuletwa hot springs', '/cultural-tours/kikuletwa-hot-springs'],
    ['Ethical village visits', '/cultural-tours/ethical-maasai-village-visit'],
    ['Day trips from Arusha', '/cultural-tours/day-trips-from-arusha'],
];
$others = fn(string $self) => array_values(array_filter($hubLinks, fn($l) => $l[1] !== '/cultural-tours/' . $self));

$pages = [];

$pages['hub'] = [
  'title' => 'Cultural Tours Tanzania ' . $y . ' | Maasai & Hadzabe',
  'desc' => 'Cultural tours in Tanzania ' . $y . ' with a Maasai-founded company: Maasai village visits, Hadzabe and Datoga at Lake Eyasi, Materuni Waterfalls, coffee tours and Kikuletwa hot springs.',
  'keywords' => 'cultural tours tanzania, maasai village tour, maasai cultural tour arusha, hadzabe tribe tour, lake eyasi cultural tour, materuni waterfalls coffee tour, tanzania cultural safari',
  'canonical' => '/cultural-tours',
  'crumbs' => [['Cultural Tours', '/cultural-tours']],
  'badge' => 'Maasai-founded',
  'h1' => 'Cultural Tours in Tanzania ' . $y,
  'lead' => 'Meet the people behind the landscapes. As a Maasai-founded company in Arusha, we arrange cultural experiences with the communities we know personally: Maasai villages, the Hadzabe and Datoga of Lake Eyasi, and the Chagga farms and waterfalls of Materuni. Join a day tour or add culture to any safari.',
  'cta_msg' => $cta('I am interested in a cultural tour in Tanzania (' . $y . '). Please share options.'),
  'sections' => [
    ['h2' => 'Our cultural experiences', 'cards' => [
      ['Maasai', 'Maasai village visit', 'Learn about daily life, dance, beadwork and cattle in a traditional boma, hosted by people we know.'],
      ['Hadzabe & Datoga', 'Lake Eyasi', 'Meet one of the last hunter-gatherer peoples, and Datoga blacksmiths and potters, near Lake Eyasi.'],
      ['Chagga', 'Materuni & coffee', 'Walk through farms to a waterfall on the slopes of Kilimanjaro, then roast and taste local coffee.'],
      ['Nature + culture', 'Kikuletwa hot springs', 'Swim in clear, warm natural pools, combined with a Maasai village visit on a day trip from Arusha.'],
    ]],
    ['h2' => 'Add culture to your safari', 'p' => ['Most of our safaris can include a cultural day. Popular combinations are a Maasai boma visit with a Serengeti and Ngorongoro safari, a Lake Eyasi day on the way to the Serengeti, and Materuni Waterfalls and a coffee tour at the end of your trip.']],
    ['h2' => 'Why travel with a Maasai-founded company', 'list' => ['We visit communities we have long relationships with, not staged stops.', 'Maasai guides explain the culture first-hand.', 'Our community tourism model is designed so that cultural fees reach the villages we work with.', 'We brief you on respectful behaviour, dress and photography before every visit.']],
  ],
  'tour_sql' => $cultureSql,
  'tour_heading' => 'Cultural tours and safaris with culture',
  'tour_intro' => 'These tours include Maasai, Hadzabe, Chagga or other cultural experiences. Prices shown are the current starting prices.',
  'faqs' => [
    ['Are the cultural visits authentic?', 'We work with communities we know, and visits are real homesteads and daily life rather than staged shows. We explain how fees are shared and how to behave respectfully.'],
    ['Can I add a cultural visit to a safari?', 'Yes. Most itineraries can include a Maasai boma, Lake Eyasi or a Materuni and coffee day. Ask us and we will tailor it.'],
    ['Is there a cultural day trip from Arusha?', 'Yes. Our 1-day Maasai Village and Kikuletwa Hot Springs tour runs from Arusha or Moshi.'],
    ['Is photography allowed?', 'Usually yes, after asking permission. Your guide will tell you what is appropriate at each place.'],
  ],
  'related' => $hubLinks,
  'current' => 'destinations',
  'og' => IMG_MAASAI,
];

$pages['maasai-village-visit'] = [
  'title' => 'Maasai Village Visit Tanzania ' . $y . ' | Boma Tour',
  'desc' => 'Visit a Maasai boma near Arusha with Maasai guides: traditional dance, beadwork, house visits and daily life. Day tours and safari add-ons for ' . $y . '. Respectful and community-led.',
  'keywords' => 'maasai village visit, maasai boma tour tanzania, maasai cultural tour arusha, visit maasai village, maasai culture experience',
  'canonical' => '/cultural-tours/maasai-village-visit',
  'crumbs' => [['Cultural Tours', '/cultural-tours'], ['Maasai Village Visit', '/cultural-tours/maasai-village-visit']],
  'badge' => 'Maasai culture',
  'h1' => 'Maasai Village Visit in Tanzania ' . $y,
  'lead' => 'A Maasai boma is a traditional homestead where families and their cattle live together. On a visit you learn about daily life, traditions and beliefs from Maasai hosts, and take part in songs, dances and crafts.',
  'cta_msg' => $cta('I would like to visit a Maasai village in Tanzania (' . $y . '). Please share dates and prices.'),
  'sections' => [
    ['h2' => 'What you can expect', 'list' => ['A welcome by the community and an introduction to Maasai life.', 'Traditional songs and dances, including the famous jumping dance.', 'Visits to homes and cattle enclosures, with explanations from your guide.', 'Beadwork and craft demonstrations, and a chance to buy directly from the makers.', 'Time for questions about traditions, children, cattle and modern life.']],
    ['h2' => 'Ways to visit', 'cards' => [
      ['Day trip', '1 day from Arusha', 'Combine a boma visit with Kikuletwa hot springs in our 1-day tour.'],
      ['Safari add-on', 'Serengeti & Ngorongoro', 'Several of our safaris include a Maasai boma on the way to or from the parks.'],
      ['Longer stays', 'Ask us', 'For a deeper experience we can arrange a longer cultural programme on request.'],
    ]],
    ['h2' => 'Visit respectfully', 'list' => ['Dress modestly, with covered shoulders and knees.', 'Ask before taking photos of people.', 'Follow your guide\'s advice and be curious rather than intrusive.', 'Bring small gifts only as advised by your guide, and avoid giving sweets or money directly to children.']],
  ],
  'tour_sql' => "LOWER(name) REGEXP 'maasai|boma'",
  'tour_heading' => 'Tours with a Maasai village visit',
  'faqs' => [
    ['Is a Maasai village visit worth it?', 'It is a good way to understand the people who have lived alongside Tanzania\'s wildlife for centuries, especially when it is led by Maasai guides and community-based.'],
    ['How long does a visit take?', 'A visit usually takes a few hours. It can be combined with hot springs as a full-day tour from Arusha.'],
    ['Is it ethical?', 'It can be, when the community chooses to host visitors and benefits from the fees. See our guide to ethical village visits.'],
  ],
  'related' => $others('maasai-village-visit'),
  'current' => 'destinations', 'og' => IMG_MAASAI,
];

$pages['hadzabe-datoga-lake-eyasi'] = [
  'title' => 'Hadzabe & Datoga Tour Lake Eyasi ' . $y . ' | Culture',
  'desc' => 'Meet the Hadzabe hunter-gatherers and Datoga blacksmiths at Lake Eyasi, Tanzania. Dawn hunt, bush skills and craft visits, combined with a Serengeti safari. Plan for ' . $y . '.',
  'keywords' => 'hadzabe tribe tour, lake eyasi cultural tour, datoga tribe, hadzabe bushmen tanzania, lake eyasi safari, hunter gatherers tanzania',
  'canonical' => '/cultural-tours/hadzabe-datoga-lake-eyasi',
  'crumbs' => [['Cultural Tours', '/cultural-tours'], ['Hadzabe & Datoga', '/cultural-tours/hadzabe-datoga-lake-eyasi']],
  'badge' => 'Lake Eyasi',
  'h1' => 'Hadzabe & Datoga Cultural Tour at Lake Eyasi ' . $y,
  'lead' => 'Near Lake Eyasi, the Hadzabe still live largely as hunter-gatherers, and their Datoga neighbours are known as blacksmiths and herders. A visit here is one of the most memorable cultural experiences in northern Tanzania.',
  'cta_msg' => $cta('I would like to visit the Hadzabe and Datoga at Lake Eyasi (' . $y . '). Please share options.'),
  'sections' => [
    ['h2' => 'The people of Lake Eyasi', 'cards' => [
      ['Hadzabe', 'Hunter-gatherers', 'A small community who hunt with bows and arrows and gather honey, roots and fruit. Their language includes distinctive click sounds.'],
      ['Datoga', 'Blacksmiths and herders', 'Known for metalwork, jewellery and cattle herding, with strong traditions and distinctive dress.'],
    ]],
    ['h2' => 'What the day looks like', 'list' => ['An early start, since the best time to meet the Hadzabe is at dawn.', 'Walking with Hadzabe hunters in the bush to see how they track and hunt.', 'Learning about fire-making, foraging and daily life.', 'Visiting a Datoga homestead to see blacksmithing and crafts.', 'Time on the lake shore, with birdlife depending on the season.']],
    ['h2' => 'Respectful visiting', 'list' => ['Ask permission before taking photos, and expect to pay a fee set with the community.', 'Dress simply and avoid bright, flashy clothes.', 'Remember that these are living communities, not performances.']],
    ['h2' => 'Combine it with a safari', 'p' => ['Lake Eyasi is a convenient overnight stop between Tarangire, Lake Manyara and the Serengeti or Ngorongoro. Our 8-day 5-parks safari includes a Lake Eyasi cultural day with the Hadzabe.']],
  ],
  'tour_sql' => "LOWER(name) REGEXP 'hadzabe|datoga|eyasi'",
  'tour_heading' => 'Tours that include Lake Eyasi culture',
  'faqs' => [
    ['When is the best time to visit the Hadzabe?', 'Early morning, when the hunters leave camp. The experience works in most months, although the dry season is easier on the trails.'],
    ['How much walking is involved?', 'You walk with the hunters at their pace, usually over uneven ground. Wear closed shoes and bring water.'],
    ['Is the visit ethical?', 'We work with local guides and agreed fees so that the communities benefit. We also brief you on photography and behaviour.'],
  ],
  'related' => $others('hadzabe-datoga-lake-eyasi'),
  'current' => 'destinations', 'og' => IMG_MAASAI,
];

$pages['materuni-waterfalls-coffee-tour'] = [
  'title' => 'Materuni Waterfalls & Coffee Tour ' . $y . ' | Moshi',
  'desc' => 'Materuni Waterfalls and Chagga coffee tour near Moshi: farm walk, waterfall swim, coffee roasting and local lunch on the slopes of Kilimanjaro. Add-on or day trip for ' . $y . '.',
  'keywords' => 'materuni waterfalls, materuni coffee tour, moshi day trip, chagga culture tour, kilimanjaro coffee tour, materuni waterfall tour',
  'canonical' => '/cultural-tours/materuni-waterfalls-coffee-tour',
  'crumbs' => [['Cultural Tours', '/cultural-tours'], ['Materuni Waterfalls & Coffee Tour', '/cultural-tours/materuni-waterfalls-coffee-tour']],
  'badge' => 'Moshi · Kilimanjaro slopes',
  'h1' => 'Materuni Waterfalls & Coffee Tour ' . $y,
  'lead' => 'Materuni is a Chagga village on the lower slopes of Kilimanjaro, near Moshi. A guided walk takes you through banana and coffee farms to a tall waterfall, followed by a hands-on coffee experience and a traditional meal.',
  'cta_msg' => $cta('I would like to add a Materuni Waterfalls and coffee tour (' . $y . '). Please share options.'),
  'sections' => [
    ['h2' => 'What you will do', 'list' => ['Walk through Chagga farms with banana trees, coffee plants and vegetables.', 'Reach the Materuni Waterfall for views and a refreshing swim when conditions allow.', 'Take part in the coffee process: pounding, roasting and tasting.', 'Enjoy a traditional Chagga lunch prepared by the community.', 'Learn about Chagga culture and farming life.']],
    ['h2' => 'Good to know', 'list' => ['Wear sturdy shoes, because the trail can be muddy and slippery.', 'Bring swimwear, a towel and a light rain jacket.', 'The tour is short and works well at the end of a safari or Kilimanjaro climb.']],
    ['h2' => 'Included in our tours', 'p' => ['Materuni and coffee tours are part of our 3-day Tarangire, Ngorongoro and Materuni safari and our 8-day 5-parks safari with Hadzabe culture.']],
  ],
  'tour_sql' => "LOWER(name) REGEXP 'materuni|coffee'",
  'tour_heading' => 'Tours that include Materuni and coffee',
  'faqs' => [
    ['Can I swim at Materuni Waterfall?', 'Yes, when water levels and conditions allow, and your guide will advise you. The water is cool.'],
    ['How long is the walk to the waterfall?', 'It is a moderate walk through the farms, around 30 to 45 minutes each way depending on pace and conditions.'],
    ['Is it a good end-of-trip activity?', 'Yes. It is relaxing and gives a taste of life on Kilimanjaro\'s slopes, which works well after a safari or climb.'],
  ],
  'related' => $others('materuni-waterfalls-coffee-tour'),
  'current' => 'destinations', 'og' => IMG_MAASAI,
];

$pages['kikuletwa-hot-springs'] = [
  'title' => 'Kikuletwa Hot Springs Tour ' . $y . ' | Chemka Pools',
  'desc' => 'Kikuletwa (Chemka) hot springs near Arusha and Moshi: clear, warm natural pools for swimming, plus a Maasai village visit. Day trip from Arusha or Moshi for ' . $y . '.',
  'keywords' => 'kikuletwa hot springs, chemka hot springs tour, hot springs near arusha, moshi day trip, maasai village and hot springs',
  'canonical' => '/cultural-tours/kikuletwa-hot-springs',
  'crumbs' => [['Cultural Tours', '/cultural-tours'], ['Kikuletwa Hot Springs', '/cultural-tours/kikuletwa-hot-springs']],
  'badge' => 'Day trip',
  'h1' => 'Kikuletwa (Chemka) Hot Springs Tour ' . $y,
  'lead' => 'Kikuletwa, also known as Chemka, is a spring-fed oasis of crystal-clear, warm water surrounded by trees. It is a favourite day trip near Moshi and Arusha, and pairs well with a Maasai village visit.',
  'cta_msg' => $cta('I would like the Maasai Village and Kikuletwa Hot Springs day tour (' . $y . '). Please share dates.'),
  'sections' => [
    ['h2' => 'Why go', 'list' => ['Warm, clear pools where you can swim and relax.', 'A rope swing and shady trees around the springs.', 'A good rest day after a climb or between safaris.', 'Easy to combine with a Maasai boma visit in one day.']],
    ['h2' => 'Our day tour', 'p' => ['Our 1-day Maasai Village and Kikuletwa Hot Springs tour starts from Arusha or Moshi. You visit a Maasai village to learn about local culture and daily life, then relax and swim at the springs before returning.']],
    ['h2' => 'What to bring', 'list' => ['Swimwear and a towel', 'Sun protection and a hat', 'Water shoes or sandals', 'A change of clothes']],
  ],
  'tour_sql' => "LOWER(name) REGEXP 'kikuletwa|hot spring'",
  'tour_heading' => 'Hot springs tours',
  'faqs' => [
    ['Are Kikuletwa hot springs really hot?', 'The water is pleasantly warm rather than hot, which makes it ideal for swimming.'],
    ['Is it suitable for children?', 'Yes, with adult supervision. The pools are clear and shallow in places.'],
    ['Can I do it from Moshi?', 'Yes. The tour can start in Arusha or Moshi.'],
  ],
  'related' => $others('kikuletwa-hot-springs'),
  'current' => 'destinations', 'og' => IMG_MAASAI,
];

$pages['ethical-maasai-village-visit'] = [
  'title' => 'Ethical Maasai Village Visits | How to Choose ' . $y,
  'desc' => 'How to choose an ethical Maasai village visit in Tanzania: questions to ask, red flags, how fees reach the community and respectful behaviour. Advice from a Maasai-founded company.',
  'keywords' => 'ethical maasai village visit, responsible tourism tanzania, authentic maasai experience, is maasai village visit ethical, community tourism tanzania',
  'canonical' => '/cultural-tours/ethical-maasai-village-visit',
  'crumbs' => [['Cultural Tours', '/cultural-tours'], ['Ethical Village Visits', '/cultural-tours/ethical-maasai-village-visit']],
  'badge' => 'Responsible travel',
  'h1' => 'Ethical Maasai Village Visits: How to Choose',
  'lead' => 'A good village visit benefits the community and respects its people. A poor one feels like a staged show. Here is how to tell the difference, written from the point of view of a Maasai-founded company.',
  'cta_msg' => $cta('I would like an ethical Maasai cultural experience (' . $y . '). Please tell me how your visits work.'),
  'sections' => [
    ['h2' => 'Questions to ask your operator', 'list' => ['Does the community choose to host visitors, and who decides how fees are used?', 'Where do the fees go, and is it transparent?', 'Is the visit led by a Maasai guide or someone from the community?', 'Will it be a real homestead, or a purpose-built tourist site?', 'Are group sizes small and visits scheduled to avoid overwhelming the village?']],
    ['h2' => 'Red flags', 'list' => ['Large tour buses arriving every hour.', 'Pressure to buy souvenirs or donate money.', 'Children being photographed or used for money.', 'No clear explanation of how the community benefits.']],
    ['h2' => 'How we approach it', 'p' => ['We work with villages we know, keep groups small and explain how fees are shared. Our community tourism model is designed so that cultural fees go to the villages we work with, supporting projects such as education and clean water.']],
    ['h2' => 'Be a respectful visitor', 'list' => ['Dress modestly and ask before taking photos.', 'Listen as much as you talk, and be curious about daily life, not only the costume.', 'Buy crafts directly from the makers at fair prices.', 'Avoid handing out sweets or money, and follow your guide\'s advice.']],
  ],
  'tour_sql' => "LOWER(name) REGEXP 'maasai|boma'",
  'tour_heading' => 'Tours with a Maasai village visit',
  'faqs' => [
    ['Are Maasai village visits exploitative?', 'They can be, if the community does not benefit or visits are staged. Choose an operator that works directly with villages, explains how fees are used and keeps groups small.'],
    ['How do fees reach the community?', 'Ask your operator. We explain our arrangements openly and work with the villages we visit.'],
    ['Can I stay overnight?', 'Longer stays can be arranged on request for travellers who want a deeper experience.'],
  ],
  'related' => $others('ethical-maasai-village-visit'),
  'current' => 'destinations', 'og' => IMG_MAASAI,
];

$pages['day-trips-from-arusha'] = [
  'title' => 'Day Trips from Arusha ' . $y . ' | Culture & Nature',
  'desc' => 'Day trips from Arusha in ' . $y . ': Maasai village and Kikuletwa hot springs, Materuni Waterfalls and coffee, and short safaris. One-day cultural and nature tours with local guides.',
  'keywords' => 'day trips from arusha, things to do in arusha, arusha day tours, arusha cultural tour, one day tour arusha, moshi day trip',
  'canonical' => '/cultural-tours/day-trips-from-arusha',
  'crumbs' => [['Cultural Tours', '/cultural-tours'], ['Day Trips from Arusha', '/cultural-tours/day-trips-from-arusha']],
  'badge' => '1-day tours',
  'h1' => 'Day Trips from Arusha ' . $y,
  'lead' => 'Short on time or arriving before your safari? Arusha has plenty to do in a day. Here are our favourite one-day tours, from cultural visits to hot springs and waterfalls.',
  'cta_msg' => $cta('What day trips do you offer from Arusha (' . $y . ')? Please share options and prices.'),
  'sections' => [
    ['h2' => 'Our one-day experiences', 'cards' => [
      ['Culture + nature', 'Maasai village & hot springs', 'Learn about Maasai life, then swim at Kikuletwa, in one day.'],
      ['Chagga culture', 'Materuni & coffee', 'Waterfall walk, coffee tasting and a local lunch near Moshi.'],
      ['Wildlife', 'Short safaris', 'Ask about short wildlife trips from Arusha if you have limited time.'],
    ]],
    ['h2' => 'Good to know', 'list' => ['Day trips can start from Arusha or Moshi.', 'They work well on your arrival or departure day, or as a rest day between activities.', 'Private tours are available, and we can adapt timing for your flights.']],
  ],
  'tour_sql' => "duration LIKE '1 Day%' OR LOWER(tour_type) LIKE '%day trip%' OR LOWER(name) LIKE '1-day%'",
  'tour_heading' => 'One-day tours',
  'faqs' => [
    ['What can I do in Arusha in one day?', 'You can visit a Maasai village and hot springs, take a Materuni waterfall and coffee tour, or join a short guided trip. Ask us for current options.'],
    ['Can you collect me from the airport or hotel?', 'Yes. We arrange pickups from your hotel in Arusha or Moshi, and transfers from the airport on request.'],
  ],
  'related' => $others('day-trips-from-arusha'),
  'current' => 'destinations', 'og' => IMG_MAASAI,
];

if (!isset($pages[$key])) { render404(); }
$cfg = $pages[$key];
require __DIR__ . '/includes/seo_page.php';
