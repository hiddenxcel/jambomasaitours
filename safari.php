<?php
/* Kurasa za Phase 5: aina ya msafiri, idadi ya siku, mbuga, na miongozo ya "pesa".
   URL:  /safari/{slug}  /parks/{slug}  /tanzania-safari-packages  /tanzania-vs-kenya-safari  ...
   Tours zinatoka database kwa vichujio vya jina/aina (hakuna bei za kubuni). */
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$key = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['page'] ?? ''));
$y   = seoYears();
$cta = fn(string $t) => 'Hi! ' . $t;

$typeLinks = [
    ['Honeymoon safaris', '/safari/honeymoon'], ['Family safaris', '/safari/family'], ['Budget safaris', '/safari/budget'],
    ['Camping safaris', '/safari/camping'], ['Luxury safaris', '/safari/luxury'], ['Fly-in safaris', '/safari/fly-in'],
    ['Balloon safari', '/safari/balloon'], ['Safari + Zanzibar', '/safari/zanzibar-combo'], ['Kenya + Tanzania', '/safari/kenya-tanzania-combo'],
    ['Group safaris', '/safari/group'],
];
$durLinks = [];
foreach ([2, 3, 4, 5, 6, 7, 8] as $d) $durLinks[] = [$d . '-day safaris', '/safari/' . $d . '-day'];
$base = function (string $self) use ($typeLinks, $durLinks) {
    $all = array_merge([['All safari packages', '/tanzania-safari-packages']], $typeLinks, $durLinks);
    return array_values(array_filter($all, fn($l) => $l[1] !== $self));
};

$pages = [];

/* ── Helper ya ukurasa wa aina ya msafiri ── */
$type = function (string $slug, string $name, string $sql, string $lead, array $sections, array $faqs, string $badge = 'Tanzania safari') use ($y, $cta, $base, &$pages) {
    $pages[$slug] = [
      'title' => $name . ' ' . $y . ' | Jambo Masai Tours',
      'desc' => truncate($name . ' ' . $y . ': ' . $lead, 158),
      'keywords' => strtolower($name) . ', ' . strtolower($name) . ' ' . date('Y') . ', ' . strtolower($name) . ' packages, tanzania safari',
      'canonical' => '/safari/' . $slug,
      'crumbs' => [['Safari Packages', '/tanzania-safari-packages'], [$name, '/safari/' . $slug]],
      'badge' => $badge,
      'h1' => $name . ' ' . $y,
      'lead' => $lead,
      'cta_msg' => $cta('I am interested in a ' . strtolower($name) . ' in Tanzania (' . $y . '). Please share options and prices.'),
      'sections' => $sections,
      'tour_sql' => $sql,
      'tour_heading' => $name . ' packages',
      'faqs' => $faqs,
      'related' => array_slice($base('/safari/' . $slug), 0, 9),
      'current' => 'tours',
    ];
};

$type('honeymoon', 'Tanzania Honeymoon Safari', "tour_type = 'Honeymoon Safari' OR LOWER(name) REGEXP 'honeymoon|love'",
  'A Tanzania honeymoon safari combines private game drives, romantic camps and lodges, and the option of a sunrise balloon flight over the Serengeti, with Zanzibar beaches to finish.',
  [
    ['h2' => 'What makes a great honeymoon safari', 'list' => ['A private vehicle and guide, so you set the pace.', 'Carefully chosen lodges or tented camps with views, privacy and good food.', 'Optional balloon safari at sunrise, followed by a bush breakfast.', 'Bush dinners and special touches for celebrations.', 'A beach finish in Zanzibar after the safari.']],
    ['h2' => 'Best time for a honeymoon safari', 'p' => ['June to October is the classic dry season with the migration in the north. January and February offer calving season with fewer vehicles, and December to March is warm and good for Zanzibar beaches. Tell us your wedding date and we will suggest the best window.']],
    ['h2' => 'How long should it be?', 'p' => ['Most honeymoon safaris run 4 to 7 days, often followed by 3 to 5 days in Zanzibar. A fly-in option saves driving time if you want more time at the lodge.']],
  ],
  [['Can you arrange a surprise or special dinner?', 'Yes. Let us know your occasion and we will ask the lodge or camp to arrange a celebration, subject to availability.'], ['Is a balloon safari included?', 'Some honeymoon itineraries include it and others offer it as an option. It is booked ahead because spaces are limited.'], ['Can we add Zanzibar?', 'Yes. We combine honeymoon safaris with Zanzibar, including fly-in options between the Serengeti and the island.']], 'Romantic');

$type('family', 'Tanzania Family Safari', "tour_type = 'Family Safari' OR LOWER(name) REGEXP 'family'",
  'A family safari in Tanzania works best with the right pace: shorter drives, child-friendly lodges, flexible meals and wildlife that keeps young travellers interested.',
  [
    ['h2' => 'Tips for safari with children', 'list' => ['Choose lodges with space, pools and family rooms.', 'Keep drives shorter and include time to rest. Breaking up long journeys with flights can help.', 'Ngorongoro Crater, Tarangire and Lake Manyara offer lots of wildlife in a compact area.', 'Some activities and lodges have minimum ages, so tell us your children\'s ages when you enquire.', 'Add a Maasai village visit or Materuni waterfall walk to vary the days.']],
    ['h2' => 'Best time for families', 'p' => ['School holidays often fall in July and August and around Christmas and New Year. The dry season is easiest for game viewing, while December to March offers warm weather and calving season in the south.']],
  ],
  [['What age can children go on safari?', 'Many lodges and parks welcome children of all ages, though some activities and camps set minimum ages. Tell us your children\'s ages and we will recommend suitable options.'], ['Are the vehicles safe for kids?', 'We use 4WD safari vehicles with pop-up roofs and seatbelts. Your guide briefs everyone on safety rules before each drive.']], 'Families');

$type('budget', 'Tanzania Budget Safari', "tour_type = 'Budget Safari' OR LOWER(name) REGEXP 'budget|affordable'",
  'A budget safari in Tanzania uses simple lodges or camping, shared costs and shorter itineraries to bring the wildlife experience within reach, without cutting the guide or the game drives.',
  [
    ['h2' => 'How budget safaris keep costs down', 'list' => ['Group departures that share vehicle and guide costs.', 'Basic lodges or camping instead of luxury camps.', 'Shorter itineraries of 2 to 5 days in the northern parks.', 'Travelling in the green season (March to May and November) when rates are lower.']],
    ['h2' => 'What to expect', 'p' => ['Accommodation is comfortable but simple, and some parks are visited as a day trip rather than staying inside. You still get licensed guides, park entry and game drives. Ask us exactly what is included before you book.']],
  ],
  [['Is a budget safari worth it?', 'Yes, if you want the core wildlife experience at a lower price. You trade some comfort and flexibility for savings.'], ['Which parks are covered on budget safaris?', 'Most budget safaris visit Tarangire, Lake Manyara, Ngorongoro Crater and sometimes the Serengeti.']], 'Value');

$type('camping', 'Tanzania Camping Safari', "tour_type = 'Camping Safari' OR LOWER(name) LIKE '%camping%'",
  'A camping safari puts you close to the wildlife and the night sounds of the bush, with tents, camp staff and a guide, at a lower cost than lodges.',
  [
    ['h2' => 'What camping safari is like', 'list' => ['Sleep in comfortable dome tents with mattresses.', 'A cook prepares meals and camp staff set up and take down.', 'Basic shared washing and toilet facilities at campsites.', 'Wake up inside the park and hear the animals at night.']],
    ['h2' => 'Camping, lodges or both', 'p' => ['Several of our itineraries mix lodges and camping, for example lodges in Arusha and Ngorongoro and camping in the Serengeti, so you save money without giving up all comforts.']],
  ],
  [['Is camping safe with wildlife around?', 'Yes. Campsites are managed, and your guide briefs you on safety rules, including not walking around alone at night.'], ['Do I need to bring a sleeping bag?', 'We confirm what is provided when you book. Many campers bring their own or ask us to arrange one.']], 'Adventure');

$type('luxury', 'Tanzania Luxury Safari', "tour_type = 'Luxury Safari' OR LOWER(name) LIKE '%luxury%'",
  'A luxury safari in Tanzania means exclusive camps and lodges, private vehicles, expert guides, fly-in transfers and thoughtful service from arrival to departure.',
  [
    ['h2' => 'What luxury includes', 'list' => ['Premium tented camps and lodges in prime wildlife areas.', 'Private vehicle and guide, with flexible schedules.', 'Light aircraft transfers between airstrips to save driving time.', 'Special experiences such as balloon flights and bush dinners.', 'Seamless service, with transfers and extensions arranged for you.']],
    ['h2' => 'Combine with Zanzibar', 'p' => ['Finish with a Zanzibar beach stay. Fly-in itineraries from Zanzibar to the Serengeti let you see the plains without long road transfers.']],
  ],
  [['Is a luxury safari worth the extra cost?', 'It gives you more privacy, comfort and time in the best wildlife areas. We can build a mix of luxury and mid-range stays to match your budget.']], 'Premium');

$type('fly-in', 'Tanzania Fly-in Safari', "LOWER(name) REGEXP 'fly|flight|flying'",
  'A fly-in safari uses short flights in light aircraft between airstrips and the Serengeti, giving you more time on game drives and less time on the road.',
  [
    ['h2' => 'Why fly in', 'list' => ['Save hours of driving between parks and airports.', 'Aerial views of the plains and Rift Valley.', 'Easy links from Zanzibar, Arusha and Kilimanjaro to the Serengeti.', 'Ideal for short trips, honeymoons and travellers with limited time.']],
    ['h2' => 'Good to know', 'p' => ['Light aircraft have strict luggage limits and usually require soft bags. We confirm the allowance when you book.']],
  ],
  [['Which airports connect to the Serengeti?', 'Flights serve airstrips in the Serengeti and Ndutu region from Arusha, Kilimanjaro and Zanzibar, subject to schedules.'], ['Is flying more expensive?', 'Yes, flights add cost but save driving time. We can compare a fly-in and a drive-in version for you.']], 'Save time');

$type('balloon', 'Serengeti Balloon Safari', "LOWER(name) LIKE '%balloon%'",
  'A hot-air balloon safari over the Serengeti plains at sunrise is one of Tanzania\'s most memorable experiences, ending with a champagne bush breakfast.',
  [
    ['h2' => 'What to expect', 'list' => ['An early start to be at the launch site before sunrise.', 'About an hour drifting over the plains, with views of herds and predators.', 'A bush breakfast after landing.', 'Booked in advance, because spaces are limited and depend on weather.']],
    ['h2' => 'Add it to your safari', 'p' => ['Balloon flights are usually an optional extra on a Serengeti safari. Tell us when you enquire so we can book your date early.']],
  ],
  [['Is the balloon safari safe?', 'Flights are operated by licensed pilots and depend on weather conditions. Minimum age limits usually apply.'], ['When is the best time to fly?', 'Flights run year-round, with the best wildlife viewing when the herds are in the central and southern Serengeti.']], 'Serengeti');

$type('zanzibar-combo', 'Safari + Zanzibar Holiday', "LOWER(name) LIKE '%zanzibar%'",
  'Combine a Tanzania safari with a Zanzibar beach holiday: wildlife in the Serengeti and Ngorongoro, followed by white sand beaches and Stone Town.',
  [
    ['h2' => 'Popular ways to combine', 'list' => ['Safari first, then fly to Zanzibar to relax.', 'Fly-in itineraries between Zanzibar and the Serengeti or Ndutu.', 'Short safari plus a longer beach stay if you have limited time.']],
    ['h2' => 'Best time', 'p' => ['June to October and December to February are the best months for Zanzibar beaches, and align well with the migration and calving seasons.']],
  ],
  [['How many days do I need for safari and Zanzibar?', 'About 8 to 10 days is comfortable, for example 4 to 5 days on safari and 3 to 5 days in Zanzibar.'], ['Can I fly directly between the Serengeti and Zanzibar?', 'Flights are available on some schedules and are included in several of our itineraries. We confirm the best option when you book.']], 'Bush + beach');

$type('kenya-tanzania-combo', 'Kenya + Tanzania Safari', "LOWER(name) REGEXP 'kenya|mara|nairobi|amboseli'",
  'Combine the Masai Mara in Kenya with the Serengeti and Ngorongoro in Tanzania to follow the Great Migration across both sides of the border.',
  [
    ['h2' => 'Why combine the two countries', 'list' => ['See the same migration from both the Mara and the Serengeti.', 'Experience different landscapes, including Amboseli with Kilimanjaro views.', 'A single organised itinerary with transfers across the border.']],
    ['h2' => 'Good to know', 'p' => ['Border crossings and entry requirements can change, so check current rules for your nationality. We help with the logistics and can arrange flights if you prefer.']],
  ],
  [['How do we travel between Kenya and Tanzania?', 'Overland across the border or by flight, depending on the itinerary. We arrange the transfer in advance.'], ['How long should a combined safari be?', 'At least 6 to 7 days, longer if you want to see both Amboseli and the Mara.']], 'Two countries');

$type('group', 'Tanzania Group Safari', "tour_type = 'Group Safari' OR LOWER(name) LIKE '%group%'",
  'A group safari shares the vehicle and guide with other travellers, lowering the cost per person while keeping the experience of a guided Tanzania safari.',
  [
    ['h2' => 'Why join a group', 'list' => ['Lower cost per person than a private safari.', 'Meet other travellers and share the experience.', 'Fixed itineraries that are simple to plan.']],
    ['h2' => 'Private or group?', 'p' => ['If you want to choose the pace, camps and timings, a private safari is better. Group safaris suit solo travellers and budget-conscious guests.']],
  ],
  [['How big are the groups?', 'Group size depends on the departure. Ask us for the exact size and vehicle arrangement for the date you choose.'], ['Can I join as a solo traveller?', 'Yes. Group safaris are a good option for solo travellers.']], 'Shared');

/* ── Kurasa za idadi ya siku ── */
$durText = [
  2 => ['A 2-day safari is a short, focused trip, usually to Ngorongoro, Tarangire or Lake Manyara. It suits travellers with limited time or a layover.', ['Tarangire or Lake Manyara in one day, Ngorongoro Crater on another.', 'Fly-in options from Zanzibar for a quick taste of the Serengeti.']],
  3 => ['A 3-day safari typically covers Tarangire or Lake Manyara and Ngorongoro Crater, and sometimes a short Serengeti visit, giving you big wildlife for a short trip.', ['Tarangire National Park for elephants and baobabs.', 'Ngorongoro Crater for the Big Five opportunities.', 'Group and budget options are common at this length.']],
  4 => ['A 4-day safari lets you add the Serengeti to Tarangire or Manyara and Ngorongoro, so you experience the classic northern circuit in a short time.', ['Serengeti game drives and the Ngorongoro Crater.', 'Good balance of time and cost for first-time visitors.']],
  5 => ['A 5-day safari gives time for Tarangire, Serengeti and Ngorongoro, plus Lake Manyara or extra Serengeti days, at a comfortable pace.', ['More time in the Serengeti for predators and migration.', 'A comfortable length for first-timers.']],
  6 => ['A 6-day safari lets you cover the northern circuit with extra nights in the Serengeti, which improves your chances of seeing the migration or a river crossing in season.', ['More than one night in the Serengeti.', 'Time for cultural stops, such as a Maasai boma.']],
  7 => ['A 7-day safari allows a relaxed northern circuit with several nights in the Serengeti, and possible extras such as Lake Eyasi or a balloon flight.', ['Time for culture, a balloon safari or Lake Eyasi.', 'A good length for honeymoons.']],
  8 => ['An 8-day safari can include five parks and a cultural day, or a safari with a Kilimanjaro day hike and coffee tour, for a fuller Tanzania experience.', ['Tarangire, Serengeti, Ngorongoro, Lake Manyara and Lake Eyasi.', 'Optional Materuni waterfalls and coffee tour at the end.']],
];
foreach ($durText as $d => [$lead, $list]) {
    $slug = $d . '-day';
    $pages[$slug] = [
      'title' => $d . '-Day Tanzania Safari ' . $y . ' | Packages',
      'desc' => $d . '-day Tanzania safari packages for ' . $y . ': itineraries, what you can see in ' . $d . ' days and options by budget, with local Maasai guides.',
      'keywords' => $d . ' day tanzania safari, ' . $d . ' days safari tanzania, ' . $d . '-day safari packages ' . date('Y'),
      'canonical' => '/safari/' . $slug,
      'crumbs' => [['Safari Packages', '/tanzania-safari-packages'], [$d . '-Day Safaris', '/safari/' . $slug]],
      'badge' => $d . ' days',
      'h1' => $d . '-Day Tanzania Safari ' . $y,
      'lead' => $lead,
      'cta_msg' => $cta('I am interested in a ' . $d . '-day Tanzania safari (' . $y . '). Please share options and prices.'),
      'sections' => [
        ['h2' => 'What you can do in ' . $d . ' days', 'list' => $list],
        ['h2' => 'Is ' . $d . ' days enough?', 'p' => ['It depends on your goals and time. Shorter trips focus on a few parks, and longer trips add more days in the Serengeti and cultural or beach extensions. Tell us your dates and interests and we will recommend the best length.']],
      ],
      'tour_sql' => "LOWER(name) REGEXP '^" . $d . "[- ]?day'",
      'tour_heading' => $d . '-day safari packages',
      'faqs' => [['What is the best length for a Tanzania safari?', 'Most travellers choose 5 to 7 days to see the Serengeti and Ngorongoro comfortably. Shorter trips work if you have limited time.'], ['Can I extend my safari?', 'Yes. We can add days, a cultural visit, a Kilimanjaro climb or a Zanzibar beach stay.']],
      'related' => array_slice($base('/safari/' . $slug), 0, 9),
      'current' => 'tours',
    ];
}

/* ── Mbuga ── */
$park = function (string $slug, string $name, string $sql, string $lead, array $sections, array $faqs, string $badge) use ($y, $cta) {
    return [
      'title' => $name . ' Safari ' . $y . ' | Guide',
      'desc' => $name . ' safari in ' . $y . ': wildlife, best time to visit, how to get there and how to combine it with other Tanzania parks.',
      'keywords' => strtolower($name) . ' safari, ' . strtolower($name) . ' tanzania, ' . strtolower($name) . ' best time, ' . strtolower($name) . ' wildlife',
      'canonical' => '/parks/' . $slug,
      'crumbs' => [['Safari Parks', '/destinations'], [$name, '/parks/' . $slug]],
      'badge' => $badge,
      'h1' => $name . ' Safari ' . $y,
      'lead' => $lead,
      'cta_msg' => $cta('I am interested in a safari to ' . $name . ' (' . $y . '). Please share options and prices.'),
      'sections' => $sections,
      'tour_sql' => $sql,
      'tour_heading' => 'Safaris that include ' . $name,
      'faqs' => $faqs,
      'related' => [['All destinations', '/destinations'], ['Best time to visit Tanzania', '/best-time-to-visit-tanzania'], ['All safari packages', '/tanzania-safari-packages'], ['Great Migration', '/migration']],
      'current' => 'destinations',
    ];
};

$pages['park-manyara'] = $park('manyara', 'Lake Manyara', "LOWER(name) LIKE '%manyara%'",
  'Lake Manyara National Park lies below the Rift Valley escarpment and packs forest, lake and plains into a compact park. It is known for its tree-climbing lions, big troops of baboons, elephants and birdlife.',
  [['h2' => 'Highlights', 'list' => ['Groundwater forest with monkeys and baboons.', 'Tree-climbing lions in some areas.', 'Elephants, giraffe, hippos and buffalo.', 'Birdlife on the alkaline lake, including flamingos in some seasons.']],
   ['h2' => 'Best time', 'p' => ['Manyara can be visited all year. The dry season from June to October gives the best wildlife viewing, and the green season is excellent for birdlife.']],
   ['h2' => 'How to combine', 'p' => ['Manyara is an easy first or last stop on the northern circuit, often paired with Tarangire and Ngorongoro, and close to Lake Eyasi for a cultural visit.']]],
  [['How long do I need at Lake Manyara?', 'A half-day or full-day game drive is typical, and many guests stay overnight in the area.'], ['Is Lake Manyara worth it?', 'Yes, especially on shorter itineraries, because it adds forest scenery and different wildlife.']], 'Rift Valley');

$pages['park-arusha-national-park'] = $park('arusha-national-park', 'Arusha National Park', "LOWER(name) LIKE '%arusha national%'",
  'Arusha National Park is small, scenic and close to Arusha town. It combines Mount Meru, Ngurdoto Crater and the Momella Lakes, with giraffe, buffalo and colobus monkeys, and options for walking.',
  [['h2' => 'Highlights', 'list' => ['Views of Mount Meru and, on clear days, Kilimanjaro.', 'Giraffe, buffalo, zebra and black-and-white colobus monkeys.', 'Momella Lakes with flamingos and other birds in some seasons.', 'Walking safaris with a ranger, and the starting point for Mount Meru climbs.']],
   ['h2' => 'Best for', 'p' => ['A short day trip from Arusha, a first or last-day activity, or a base for climbing Mount Meru.']]],
  [['How far is Arusha National Park from Arusha?', 'It is close to Arusha town, which makes it a convenient half-day or full-day trip.'], ['Can I combine it with a Meru climb?', 'Yes. The Mount Meru trek starts in Arusha National Park.']], 'Near Arusha');

$pages['park-northern-serengeti'] = $park('northern-serengeti', 'Northern Serengeti', "LOWER(name) REGEXP 'northern serengeti|mara river|crossing'",
  'The northern Serengeti (Kogatende and Lamai) is the home of the Mara River crossings from July to October, with remote scenery and excellent big cat sightings.',
  [['h2' => 'Highlights', 'list' => ['Mara River crossings in season.', 'Big cats, elephants and giraffe.', 'Quieter areas away from the central circuits.']],
   ['h2' => 'Best time', 'p' => ['July to October for the crossings. Allow at least three nights here for the best chance of seeing one.']]],
  [['How do I get to the northern Serengeti?', 'By long road transfer from the central Serengeti or by flying into northern airstrips.'], ['Can you guarantee a crossing?', 'No. The herds decide, but we position you to improve your chances.']], 'Mara River');

$pages['park-western-serengeti'] = $park('western-serengeti', 'Western Serengeti and Grumeti', "LOWER(name) REGEXP 'grumeti|western'",
  'The Western Corridor and the Grumeti River host the early migration crossings from late May to July, with crocodile-filled waters and riverine forest.',
  [['h2' => 'Highlights', 'list' => ['Grumeti River crossings in season.', 'Riverine forest with colobus monkeys and many birds.', 'Quieter than the northern Serengeti in some months.']],
   ['h2' => 'Best time', 'p' => ['Late May to July for the Grumeti crossings, depending on the rains and herd movements.']]],
  [['When do the Grumeti crossings happen?', 'Typically from late May to July, although timing varies each year.'], ['Is the Western Corridor easy to reach?', 'It can be reached by road from the central Serengeti or by flying into nearby airstrips.']], 'Grumeti River');

$pages['park-ruaha'] = $park('ruaha', 'Ruaha National Park', "LOWER(name) LIKE '%ruaha%'",
  'Ruaha is a large, remote park in southern Tanzania with baobab-studded landscapes, the Great Ruaha River and strong predator populations. It is quieter than the northern parks.',
  [['h2' => 'Highlights', 'list' => ['Large lion and elephant populations.', 'Dry riverbeds and baobab trees.', 'Fewer vehicles than the northern parks.', 'Walking and night activities at some camps.']],
   ['h2' => 'Plan a custom trip', 'p' => ['Southern parks such as Ruaha are usually reached by flight. We arrange these as tailor-made itineraries, so ask us for a custom quote.']]],
  [['How do I get to Ruaha?', 'Usually by light aircraft from Dar es Salaam or Arusha. We arrange this as part of a custom itinerary.'], ['When is the best time to visit Ruaha?', 'The dry season from June to October, when animals concentrate along the river.']], 'Southern Tanzania');

$pages['park-nyerere'] = $park('nyerere', 'Nyerere (Selous) National Park', "LOWER(name) REGEXP 'nyerere|selous'",
  'Nyerere National Park, formerly part of the Selous Game Reserve, is known for the Rufiji River and its boat safaris, as well as walking safaris and wild dogs.',
  [['h2' => 'Highlights', 'list' => ['Boat safaris on the Rufiji River and its lakes.', 'Hippos, crocodiles, elephants and many birds.', 'Walking and fishing activities at some camps.']],
   ['h2' => 'Plan a custom trip', 'p' => ['Nyerere is usually reached by a short flight from Dar es Salaam. We can combine it with Ruaha or Zanzibar in a tailor-made itinerary.']]],
  [['How do I get to Nyerere?', 'By a short flight from Dar es Salaam or by road. We arrange this as part of a custom itinerary.'], ['Can I combine Nyerere with Zanzibar?', 'Yes. It is a popular bush and beach combination.']], 'Southern Tanzania');

/* ── Miongozo ya "pesa" ── */
$pages['tanzania-safari-packages'] = [
  'title' => 'Tanzania Safari Packages ' . $y . ' | All Itineraries',
  'desc' => 'Browse all Tanzania safari packages for ' . $y . ' by type and length: honeymoon, family, budget, camping, luxury, fly-in, migration, calving, Kilimanjaro and cultural tours.',
  'keywords' => 'tanzania safari packages, tanzania safari tours, tanzania safari ' . date('Y') . ', serengeti safari packages, tanzania safari itineraries',
  'canonical' => '/tanzania-safari-packages',
  'crumbs' => [['Safari Packages', '/tanzania-safari-packages']],
  'badge' => 'All packages',
  'h1' => 'Tanzania Safari Packages ' . $y,
  'lead' => 'Choose your Tanzania safari by who you are travelling with, how long you have, or what you want to see. Every package can be tailored, and we are a Maasai-founded company based in Arusha.',
  'cta_msg' => $cta('Please help me choose a Tanzania safari package for ' . $y . '.'),
  'sections' => [
    ['h2' => 'Browse by traveller', 'p' => ['Honeymoon, family, budget, camping, luxury, fly-in, balloon, group and combined safaris.'], 'cards' => array_map(fn($l) => ['Safari type', $l[0], 'See ' . strtolower($l[0]) . ' and prices.'], array_slice($typeLinks, 0, 6))],
    ['h2' => 'Browse by length', 'p' => ['From a quick 2-day trip to a full 8-day journey: ' . implode(', ', array_map(fn($l) => $l[0], $durLinks)) . '.']],
    ['h2' => 'Browse by experience', 'list' => ['Great Migration and river crossings (July to October).', 'Ndutu calving season (December to March).', 'Kilimanjaro and Mount Meru climbs.', 'Cultural tours with Maasai, Hadzabe and Chagga communities.', 'Safari and Zanzibar combinations.']],
  ],
  'tour_sql' => '1=1', 'tour_limit' => 60,
  'tour_heading' => 'All safari packages',
  'tour_intro' => 'Starting prices per person. Every itinerary can be tailored to your dates and budget.',
  'faqs' => [['How do I choose a safari package?', 'Start with your time, budget and what you want to see. Tell us your dates and we will recommend the best option.'], ['Can I customise a package?', 'Yes. We tailor itineraries, accommodation levels and extensions such as Zanzibar or Kilimanjaro.']],
  'related' => array_merge([['Best time to visit Tanzania', '/best-time-to-visit-tanzania'], ['Tanzania vs Kenya', '/tanzania-vs-kenya-safari'], ['First-timer guide', '/tanzania-safari-for-first-timers'], ['How to choose a safari company', '/how-to-choose-a-tanzania-safari-company']], array_slice($typeLinks, 0, 4)),
  'current' => 'tours',
];

$pages['tanzania-vs-kenya-safari'] = [
  'title' => 'Tanzania vs Kenya Safari ' . $y . ' | Which Is Better?',
  'desc' => 'Tanzania or Kenya for your ' . $y . ' safari? Compare Serengeti and Masai Mara, crowds, costs, migration timing, parks and logistics, plus how to combine both countries.',
  'keywords' => 'tanzania vs kenya safari, serengeti vs masai mara, tanzania or kenya safari, kenya vs tanzania safari ' . date('Y'),
  'canonical' => '/tanzania-vs-kenya-safari',
  'crumbs' => [['Tanzania vs Kenya', '/tanzania-vs-kenya-safari']],
  'badge' => 'Comparison',
  'h1' => 'Tanzania vs Kenya Safari ' . $y,
  'lead' => 'Both countries offer world-class wildlife and the Great Migration. The better choice depends on your dates, the type of experience you want and how you like to travel. This guide compares the two and shows how to see both.',
  'cta_msg' => $cta('Should I choose Tanzania or Kenya for my safari in ' . $y . '? Please advise.'),
  'sections' => [
    ['h2' => 'Quick comparison', 'table' => ['head' => ['', 'Tanzania', 'Kenya'], 'rows' => [
      ['Famous park', 'Serengeti and Ngorongoro Crater', 'Masai Mara and Amboseli'],
      ['Size and feel', 'Very large parks with space and variety', 'More compact reserves, often busy at peak times'],
      ['Migration', 'Calving Dec–Mar, crossings Jul–Oct', 'Crossings roughly Aug–Oct'],
      ['Variety', 'Crater, plains, lakes, Kilimanjaro, Zanzibar', 'Savannah, Amboseli elephants, conservancies'],
      ['Gateway', 'Arusha and Kilimanjaro airport', 'Nairobi'],
    ]]],
    ['h2' => 'Choose Tanzania if', 'list' => ['You want large, varied parks and the Ngorongoro Crater.', 'You want calving season or river crossings in the Serengeti.', 'You want to combine safari with Kilimanjaro or Zanzibar.']],
    ['h2' => 'Choose Kenya if', 'list' => ['You prefer a shorter trip near Nairobi.', 'You want to visit Amboseli with its elephants and Kilimanjaro views.', 'You are interested in private conservancies.']],
    ['h2' => 'Or combine both', 'p' => ['A 6 to 8 day Kenya and Tanzania itinerary lets you follow the migration across the border and enjoy both landscapes. We arrange the cross-border logistics.']],
  ],
  'tour_sql' => "LOWER(name) REGEXP 'kenya|mara|nairobi|amboseli'",
  'tour_heading' => 'Kenya and Tanzania combined safaris',
  'faqs' => [['Is Tanzania or Kenya better for the Great Migration?', 'Both. The herds are in Tanzania for most of the year and cross into Kenya\'s Masai Mara in the second half of the year. Calving season takes place in Tanzania.'], ['Which is cheaper?', 'Costs depend on season, accommodation level and itinerary in both countries. Ask us for quotes for each option.'], ['Can I visit both on one trip?', 'Yes. We offer combined Kenya and Tanzania itineraries.']],
  'related' => [['Great Migration', '/migration'], ['Calving season', '/calving-season'], ['Kenya + Tanzania combo', '/safari/kenya-tanzania-combo'], ['Best time to visit Tanzania', '/best-time-to-visit-tanzania']],
  'current' => 'tours',
];

$pages['tanzania-safari-for-first-timers'] = [
  'title' => 'Tanzania Safari for First-Timers ' . $y . ' | Guide',
  'desc' => 'First time on safari in Tanzania? How many days, when to go, which parks, what to pack and how to choose your first safari in ' . $y . '. Practical advice from local guides.',
  'keywords' => 'tanzania safari for first timers, first time safari tanzania, best tanzania safari for beginners, how to plan a tanzania safari',
  'canonical' => '/tanzania-safari-for-first-timers',
  'crumbs' => [['First-Timer Guide', '/tanzania-safari-for-first-timers']],
  'badge' => 'First safari',
  'h1' => 'Tanzania Safari for First-Timers ' . $y,
  'lead' => 'Planning your first safari can feel overwhelming. Here is a simple guide to how long to go for, when to travel, which parks to choose and what to expect, from local Maasai guides.',
  'cta_msg' => $cta('This is my first safari. Please help me plan a Tanzania trip for ' . $y . '.'),
  'sections' => [
    ['h2' => 'The classic first safari', 'p' => ['A 5 to 7 day itinerary covering Tarangire, the Serengeti and Ngorongoro Crater, sometimes with Lake Manyara, gives first-timers the best mix of wildlife and landscapes.']],
    ['h2' => 'Planning checklist', 'list' => ['Pick your dates: June to October for classic dry-season safari, or December to March for calving.', 'Decide your comfort level: camping, mid-range lodges or luxury camps.', 'Choose private or group departure.', 'Decide whether to add Zanzibar or a cultural day.', 'Check passport, visa and vaccination requirements well before travel.', 'Book early, since many travellers now plan 9 to 14 months ahead.']],
    ['h2' => 'What to pack', 'list' => ['Neutral-coloured, lightweight clothing, with a warm layer for early mornings.', 'Hat, sunglasses and sunscreen.', 'Binoculars and a camera with spare memory.', 'Comfortable closed shoes and insect repellent.']],
    ['h2' => 'What a day looks like', 'p' => ['You usually start early for the best wildlife light, return for a midday rest, and head out again in the late afternoon. Your guide explains behaviour and spots animals you would miss.']],
  ],
  'tour_sql' => "LOWER(name) REGEXP '^(4|5|6)[- ]?day'",
  'tour_heading' => 'Popular first-safari itineraries',
  'faqs' => [['How many days do I need for a first safari?', 'Five to seven days is ideal. Four days covers the essentials if time is short.'], ['Is safari safe for beginners?', 'Yes. Your guide briefs you on safety, and you stay in vehicles or camps with experienced staff.'], ['Do I need to be fit?', 'No. Game drives are done from the vehicle, and walking is optional.']],
  'related' => [['Best time to visit Tanzania', '/best-time-to-visit-tanzania'], ['All safari packages', '/tanzania-safari-packages'], ['Tanzania vs Kenya', '/tanzania-vs-kenya-safari'], ['Safari FAQ', '/faq']],
  'current' => 'tours',
];

$pages['how-to-choose-a-tanzania-safari-company'] = [
  'title' => 'How to Choose a Tanzania Safari Company ' . $y,
  'desc' => 'How to choose a Tanzania safari company in ' . $y . ': licensing, reviews, itineraries, guide quality, crew treatment, safety, payment terms and questions to ask before you book.',
  'keywords' => 'best tanzania safari companies, how to choose a safari company, tanzania safari operator, tato licensed safari company, safari operator checklist',
  'canonical' => '/how-to-choose-a-tanzania-safari-company',
  'crumbs' => [['Choosing a Safari Company', '/how-to-choose-a-tanzania-safari-company']],
  'badge' => 'Buyer\'s guide',
  'h1' => 'How to Choose a Tanzania Safari Company ' . $y,
  'lead' => 'There are hundreds of safari operators in Tanzania. A good one is licensed, transparent and well reviewed, and treats its guides and crew well. Use this checklist before you book.',
  'cta_msg' => $cta('I am comparing Tanzania safari companies for ' . $y . '. Can you tell me about your safaris?'),
  'sections' => [
    ['h2' => 'Checklist', 'list' => ['Licensing: is the company licensed by the Tanzania authorities and a member of TATO?', 'Reviews: check independent sites such as TripAdvisor and SafariBookings, and read recent detailed reviews.', 'Transparency: are inclusions, exclusions and park fees clearly listed?', 'Guides: are guides experienced, licensed and well trained?', 'Vehicles: private 4WD with pop-up roof and a window seat for every guest?', 'Crew welfare: how are guides, drivers and porters treated and paid?', 'Safety: first aid, insurance and emergency procedures.', 'Communication: quick, clear replies and a real person to speak to.']],
    ['h2' => 'Questions to ask', 'list' => ['What exactly is included in the price?', 'How many guests share each vehicle?', 'Which lodges or camps will we use, and can I see alternatives?', 'What is the payment schedule and cancellation policy?', 'Who will be my guide, and what is his or her experience?']],
    ['h2' => 'Red flags', 'list' => ['Prices far below everyone else, with unclear inclusions.', 'Pressure to pay in full immediately or by unusual methods.', 'No physical office or licence details.', 'Reviews that all look similar or very recent only.']],
    ['h2' => 'About Jambo Masai Tours', 'p' => ['We are a Maasai-founded, TATO-licensed safari operator in Arusha with local guides and a growing base of independent reviews. You are welcome to compare us with any other operator using this checklist.']],
  ],
  'tour_sql' => '1=1', 'tour_limit' => 6,
  'tour_heading' => 'Popular Tanzania safari packages',
  'faqs' => [['Is it better to book directly with a local operator?', 'Booking with a local operator often gives you direct access to the guides and more flexibility, and avoids middleman fees. Always check licensing and reviews.'], ['What is TATO?', 'The Tanzania Association of Tour Operators, the industry body for licensed operators. Ask any company for its licence details.'], ['Can I visit your office in Arusha?', 'Yes. Contact us to arrange a meeting when you arrive in Arusha.']],
  'related' => [['About us', '/about'], ['Guest reviews', '/reviews'], ['All safari packages', '/tanzania-safari-packages'], ['Contact', '/contact']],
  'current' => 'about',
];

/* ── Dispatch ── */
if ($key === '') { $key = 'tanzania-safari-packages'; }
if (!isset($pages[$key])) { render404(); }
$cfg = $pages[$key];
$cfg['desc'] = truncate($cfg['desc'], 158);
$cfg['og'] = $cfg['og'] ?? IMG_SERENGETI;
require __DIR__ . '/includes/seo_page.php';
