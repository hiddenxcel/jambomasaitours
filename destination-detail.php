<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$slug = sanitizeInput($_GET['slug'] ?? '');
/* Normalise slug aliases */
$slugAliases = ['maasai'=>'maasai-heartland','masai'=>'maasai-heartland','kili'=>'kilimanjaro','serengeti-national-park'=>'serengeti','ngorongoro-crater'=>'ngorongoro','zanzibar-island'=>'zanzibar','tarangire-national-park'=>'tarangire'];
if (isset($slugAliases[$slug])) $slug = $slugAliases[$slug];

/* -- Try DB first (admin-managed destinations) -- */
$dbDest = null;
try {
    $dbDb = getDB();
    $dbSt = $dbDb->prepare("SELECT * FROM destination_pages_full WHERE slug=? AND active=1 LIMIT 1");
    $dbSt->execute([$slug]);
    $dbDest = $dbSt->fetch();
} catch (\Throwable $e) {}

if ($dbDest) {
    /* Build $dest from DB row */
    $dest = [
        'title'       => $dbDest['title'],
        'subtitle'    => $dbDest['subtitle'],
        'region'      => $dbDest['region'],
        'area'        => $dbDest['area'],
        'best_time'   => $dbDest['best_time'],
        'altitude'    => $dbDest['altitude'],
        'image'       => $dbDest['image_url'],
        'og_title'    => $dbDest['og_title'] ?: ($dbDest['title'].' | Jambo Masai Tours'),
        'meta_desc'   => $dbDest['meta_desc'],
        'intro'       => $dbDest['intro'],
        'description' => $dbDest['description'],
        'highlights'  => json_decode($dbDest['highlights'] ?? '[]', true) ?: [],
        'wildlife'    => (function($w){ $a=json_decode($w??'[]',true); return is_array($a)?$a:array_filter(array_map('trim',explode(',',$w??'')));})($dbDest['wildlife']),
        'faqs'        => json_decode($dbDest['faqs'] ?? '[]', true) ?: [],
        'months'      => ['Jan'=>'good','Feb'=>'good','Mar'=>'fair','Apr'=>'low','May'=>'low','Jun'=>'peak','Jul'=>'peak','Aug'=>'peak','Sep'=>'good','Oct'=>'good','Nov'=>'fair','Dec'=>'good'],
        'peak_note'   => $dbDest['best_time'],
        'search'      => $dbDest['search_term'] ?: $slug,
        'geo'         => [$dbDest['geo_lat'], $dbDest['geo_lon']],
    ];
    /* Set $db and build $destinations from DB for sidebar */
    $db = $dbDb;
    try {
        $allDbDests = $db->query("SELECT slug,title,region,image_url FROM destination_pages_full WHERE active=1 ORDER BY sort_order ASC")->fetchAll();
        $destinations = [];
        foreach ($allDbDests as $dd) {
            $destinations[$dd['slug']] = ['title'=>$dd['title'],'region'=>$dd['region'],'image'=>$dd['image_url']];
        }
    } catch (\Throwable $e) { $destinations = []; }
    /* Skip hardcoded array */
    goto dest_ready;
}

/* -------------------------------------------------------
   DESTINATION DATA — SEO-optimised content (hardcoded fallback)
------------------------------------------------------- */
$destinations = [

  'serengeti' => [
    'title'      => 'Serengeti National Park',
    'subtitle'   => 'Witness the Greatest Wildlife Show on Earth',
    'region'     => 'Northern Tanzania',
    'area'       => '14,763 km²',
    'best_time'  => 'June – October',
    'altitude'   => '920 – 1,850 m',
    'image'      => IMG_SERENGETI,
    'og_title'   => 'Serengeti Safari Tanzania | Best Time, Wildlife & Tours — Jambo Masai',
    'meta_desc'  => 'Plan your Serengeti safari with Jambo Masai Tours. Witness the Great Migration, Big Five, and endless plains. Expert guides, luxury camps, and bespoke packages.',
    'intro'      => 'The Serengeti is Tanzania\'s crown jewel — a vast, ancient ecosystem stretching across 14,763 square kilometres of open savanna, acacia woodland and granite kopjes. Home to the world\'s greatest wildlife spectacle, the annual Great Migration sees 1.5 million wildebeest and 250,000 zebra thunder across the plains in an eternal cycle of life.',
    'description'=> 'Few places on earth can match the raw, primal beauty of the Serengeti. The name itself comes from the Maasai word <em>siringet</em> — "the place where the land moves on forever" — and standing on its open plains at dawn, you understand exactly why. The Serengeti hosts the densest concentration of large predators in Africa, including over 3,000 lions, 1,000 leopards, and vast packs of hyena and wild dog. Year-round wildlife viewing is exceptional, but the magic peaks between June and October when the dry season concentrates animals around permanent water sources and the famous river crossings at the Mara River attract visitors from around the world.',
    'highlights' => [
      ['icon'=>'fa-paw',           'title'=>'Big Five',           'desc'=>'Lion, leopard, elephant, buffalo and black rhino all call the Serengeti home.'],
      ['icon'=>'fa-horse',         'title'=>'Great Migration',    'desc'=>'1.5 million wildebeest follow an 800 km circular route — a spectacle unlike any other.'],
      ['icon'=>'fa-hot-air-balloon','title'=>'Balloon Safaris',   'desc'=>'Drift silently above the plains at sunrise for a truly unforgettable perspective.'],
      ['icon'=>'fa-camera',        'title'=>'Photography',        'desc'=>'Serengeti\'s light, wildlife density and dramatic skies make it a photographer\'s paradise.'],
      ['icon'=>'fa-star',          'title'=>'Predator Action',    'desc'=>'Cheetah hunts, lion prides on kopjes, leopards in acacia trees — drama is constant.'],
      ['icon'=>'fa-moon',          'title'=>'Night Game Drives',  'desc'=>'Discover the nocturnal world with specialist guides in private conservancy areas.'],
    ],
    'wildlife'   => ['Lion','Leopard','Cheetah','Elephant','Buffalo','Wildebeest','Zebra','Hippo','Crocodile','Wild Dog','Hyena','Giraffe','Topi','Grant\'s Gazelle'],
    'months'     => ['Jan'=>'good','Feb'=>'good','Mar'=>'fair','Apr'=>'low','May'=>'low','Jun'=>'peak','Jul'=>'peak','Aug'=>'peak','Sep'=>'peak','Oct'=>'good','Nov'=>'fair','Dec'=>'good'],
    'peak_note'  => 'Peak season (Jun–Oct): Dry weather, wildlife concentrated at water. River crossings at their most dramatic. Jan–Mar: Calving season — thousands of wildebeest born daily.',
    'search'     => 'serengeti',
    'geo'        => ['-2.3333','34.8333'],
    'faqs'       => [
      ['q'=>'When is the best time to see the Great Migration in Serengeti?','a'=>'The Great Migration moves year-round. The famous Mara River crossings happen July–October when herds push north into Kenya. December–March brings calving season in the southern Serengeti — one of nature\'s most dramatic events with thousands of wildebeest born daily.'],
      ['q'=>'How many days should I spend in the Serengeti?','a'=>'We recommend a minimum of 3 nights to experience the Serengeti properly — enough for 6 game drives across different habitats. For the full migration experience, 5–7 nights allows you to follow the herds and witness multiple wildlife events.'],
      ['q'=>'Is the Serengeti accessible year-round?','a'=>'Yes, the Serengeti is open year-round. April–May (long rains) are quieter with fewer visitors and lower rates, while the landscape turns lush green. Roads in the north can be challenging during heavy rains, but our guides know all conditions well.'],
      ['q'=>'What animals will I see in the Serengeti?','a'=>'The Serengeti hosts the Big Five (lion, leopard, elephant, buffalo, and black rhino in Moru Kopjes), plus cheetah, wild dog, hippo, crocodile, giraffe, zebra, and over 500 bird species. Lion sightings are virtually guaranteed.'],
      ['q'=>'Are balloon safaris available in the Serengeti?','a'=>'Yes — Serengeti balloon safaris depart at dawn from Seronera and the northern Serengeti. The 1-hour flight concludes with a champagne bush breakfast. We can include this in any itinerary as a special add-on.'],
    ],
  ],

  'ngorongoro' => [
    'title'      => 'Ngorongoro Crater',
    'subtitle'   => 'Africa\'s Eden — A UNESCO World Heritage Caldera',
    'region'     => 'Crater Highlands, Tanzania',
    'area'       => '264 km² (crater floor)',
    'best_time'  => 'Year-round',
    'altitude'   => '2,286 m (crater rim)',
    'image'      => IMG_NGORONGORO,
    'og_title'   => 'Ngorongoro Crater Safari Tanzania | Big Five & Black Rhino Tours',
    'meta_desc'  => 'Safari inside Ngorongoro Crater — Africa\'s most perfect wildlife arena. See the Big Five including rare black rhino. Book with Jambo Masai Tours, Tanzania\'s local experts.',
    'intro'      => 'Ngorongoro Crater is the world\'s largest intact volcanic caldera — a 264 km² natural amphitheatre that shelters one of the densest concentrations of wildlife in Africa. Declared a UNESCO World Heritage Site in 1979, the crater\'s permanent water and grassland supports over 25,000 large animals year-round.',
    'description'=> 'Formed when a massive volcano collapsed inward approximately 2-3 million years ago, Ngorongoro Crater is often described as Africa\'s Garden of Eden. The crater walls — rising 600 metres above the floor — create a microclimate that keeps the ecosystem perpetually green and productive. Unlike the Serengeti, wildlife here does not migrate; the resident population remains trapped within this natural paradise year-round. The crater hosts Tanzania\'s last stable population of black rhino, one of Africa\'s most critically endangered species, making Ngorongoro one of the best places on the continent for a rhino sighting.',
    'highlights' => [
      ['icon'=>'fa-circle',        'title'=>'Black Rhino',        'desc'=>'Ngorongoro has Tanzania\'s best chance of seeing the critically endangered black rhino.'],
      ['icon'=>'fa-water',         'title'=>'Lake Magadi',        'desc'=>'A soda lake that turns pink with flamingos — a stunning sight within the crater.'],
      ['icon'=>'fa-globe-africa',  'title'=>'UNESCO Heritage',    'desc'=>'One of Africa\'s most significant protected areas and Eighth Wonder of the World.'],
      ['icon'=>'fa-paw',           'title'=>'Big Five Guaranteed','desc'=>'The crater\'s resident population ensures Big Five sightings year-round.'],
      ['icon'=>'fa-mountain',      'title'=>'Crater Rim Lodges',  'desc'=>'Stay on the rim for breathtaking sunrise views over the caldera — truly unforgettable.'],
      ['icon'=>'fa-users',         'title'=>'Maasai Culture',     'desc'=>'The crater highlands are home to Maasai who coexist peacefully with wildlife.'],
    ],
    'wildlife'   => ['Lion','Leopard','Black Rhino','Elephant','Buffalo','Hippo','Flamingo','Wildebeest','Zebra','Hyena','Jackal','Eland','Serval'],
    'months'     => ['Jan'=>'peak','Feb'=>'peak','Mar'=>'good','Apr'=>'fair','May'=>'fair','Jun'=>'peak','Jul'=>'peak','Aug'=>'peak','Sep'=>'peak','Oct'=>'good','Nov'=>'good','Dec'=>'peak'],
    'peak_note'  => 'Year-round destination. Dry season (Jun–Oct) offers best game viewing. Jan–Mar is excellent with green landscapes and newborn animals. Crater floor closed at night — day visits only.',
    'search'     => 'ngorongoro',
    'geo'        => ['-3.1756','35.5873'],
    'faqs'       => [
      ['q'=>'Can I see a black rhino in Ngorongoro Crater?','a'=>'Yes — Ngorongoro Crater has Tanzania\'s best black rhino population, with approximately 20-25 individuals. Our guides know their preferred locations and the best times of day for sightings. Spotting a black rhino is a highlight that not every visitor experiences, but our success rate is high.'],
      ['q'=>'Can I stay overnight inside the crater?','a'=>'No — overnight stays on the crater floor are not permitted. All lodges are located on the crater rim. Vehicles must exit the crater by 6pm. This protects nocturnal wildlife and the crater ecosystem.'],
      ['q'=>'How long does a crater game drive take?','a'=>'A typical Ngorongoro Crater game drive lasts 6–8 hours including a picnic lunch at the hippo pool. We enter at opening time (6am) to catch the best predator activity and spend the full day exploring the different crater habitats.'],
      ['q'=>'Is Ngorongoro worth combining with Serengeti?','a'=>'Absolutely — the Ngorongoro–Serengeti combination is our most popular itinerary. The two parks complement each other perfectly: Ngorongoro\'s enclosed ecosystem versus Serengeti\'s vast open plains. Most of our 5–10 day itineraries include both.'],
      ['q'=>'What makes Ngorongoro different from other safari parks?','a'=>'Unlike other parks where wildlife ranges over thousands of kilometres, Ngorongoro\'s crater walls act as a natural enclosure. This means wildlife is always present on the crater floor, making game viewing more concentrated and predictable. The dramatic scenery — crater walls, soda lake, swamps and grassland — also makes it uniquely photogenic.'],
    ],
  ],

  'kilimanjaro' => [
    'title'      => 'Mount Kilimanjaro',
    'subtitle'   => 'Conquer the Roof of Africa — 5,895 Metres Above the World',
    'region'     => 'Kilimanjaro Region, Tanzania',
    'area'       => '756 km² (national park)',
    'best_time'  => 'January – March & June – October',
    'altitude'   => '5,895 m (Uhuru Peak)',
    'image'      => IMG_KILIMANJARO,
    'og_title'   => 'Kilimanjaro Climb Tanzania | Routes, Costs & Expert Guides',
    'meta_desc'  => 'Climb Kilimanjaro with KINAPA-certified guides from Jambo Masai Tours. All routes — Marangu, Machame, Lemosho, Rongai. 85%+ summit success rate. Book your climb today.',
    'intro'      => 'Mount Kilimanjaro is Africa\'s highest peak and the world\'s tallest free-standing volcano, rising majestically to 5,895 metres above the Tanzanian plains. A Kilimanjaro climb is one of the world\'s most rewarding adventure experiences — accessible to fit, determined trekkers without technical climbing skills.',
    'description'=> 'Kilimanjaro\'s iconic snow-capped summit, Uhuru Peak, draws over 50,000 climbers annually from every corner of the globe. What makes Kilimanjaro unique among the world\'s great summits is its accessibility — no ropes, no ice axes, no technical climbing experience required. The mountain passes through five distinct ecological zones: cultivated farmland, rainforest, heath and moorland, alpine desert, and finally the arctic summit zone with its ancient glaciers. Our KINAPA-certified guides have led hundreds of successful ascents across all major routes, with an above-average summit success rate achieved through careful acclimatisation scheduling.',
    'highlights' => [
      ['icon'=>'fa-mountain',      'title'=>'Uhuru Peak',         'desc'=>'Africa\'s highest point at 5,895m — an achievement that transforms every climber.'],
      ['icon'=>'fa-route',         'title'=>'Multiple Routes',    'desc'=>'7 official routes: Marangu, Machame, Lemosho, Rongai, Northern Circuit and more.'],
      ['icon'=>'fa-snowflake',     'title'=>'Ancient Glaciers',   'desc'=>'The summit\'s retreating glaciers and volcanic rock create an otherworldly landscape.'],
      ['icon'=>'fa-leaf',          'title'=>'5 Eco-Zones',        'desc'=>'Trek through rainforest, moorland, alpine desert and arctic zone in one climb.'],
      ['icon'=>'fa-certificate',   'title'=>'KINAPA Certified',   'desc'=>'All our guides are KINAPA-certified with wilderness first aid training.'],
      ['icon'=>'fa-campground',    'title'=>'Quality Equipment',  'desc'=>'We provide high-quality tents, sleeping bags and mattresses on all routes.'],
    ],
    'wildlife'   => ['Colobus Monkey','Blue Monkey','Elephant','Buffalo','Leopard','Eland','Duiker','Sunbird','White-necked Raven','Alpine Chat'],
    'months'     => ['Jan'=>'peak','Feb'=>'peak','Mar'=>'good','Apr'=>'low','May'=>'low','Jun'=>'good','Jul'=>'peak','Aug'=>'peak','Sep'=>'peak','Oct'=>'good','Nov'=>'fair','Dec'=>'good'],
    'peak_note'  => 'Best climbing: Jan–Mar (clear skies, dry trails) and Jun–Oct (dry season). Avoid Apr–May (long rains) and Nov (short rains). Dec is busy but possible. Acclimatisation is key — never rush.',
    'search'     => 'kilimanjaro',
    'geo'        => ['-3.0674','37.3556'],
    'faqs'       => [
      ['q'=>'Do I need climbing experience to summit Kilimanjaro?','a'=>'No technical climbing experience or equipment is required. Kilimanjaro is a high-altitude trek, not a technical climb. You need good physical fitness, strong determination, and the right mental attitude. We recommend training with regular hiking and cardiovascular exercise for 3–6 months before your climb.'],
      ['q'=>'Which Kilimanjaro route has the highest success rate?','a'=>'The Lemosho Route (8–9 days) has our highest success rate due to its excellent acclimatisation profile and scenic variety. The Northern Circuit (10 days) offers the ultimate experience with a near 90% summit success rate. We do not recommend the Marangu Route (5–6 days) for first-time high-altitude trekkers.'],
      ['q'=>'How long does it take to climb Kilimanjaro?','a'=>'Most routes take 6–9 days depending on the chosen path. Longer routes (Lemosho, Northern Circuit) have better acclimatisation and higher success rates. We strongly advise against 5-day options as insufficient acclimatisation significantly reduces your summit chances.'],
      ['q'=>'What should I pack for a Kilimanjaro climb?','a'=>'Key items include: layered clothing system (base, mid, outer), waterproof jacket and trousers, warm gloves and hat, trekking boots (broken in), trekking poles, and headlamp. We provide a detailed packing list on booking and can arrange rental gear in Arusha for items you prefer not to bring from home.'],
      ['q'=>'What is included in your Kilimanjaro packages?','a'=>'All our climbs include: KINAPA-certified lead guide, experienced assistant guides and porters, all park fees and camping/hut fees, tent/accommodation (depending on route), three meals and snacks daily, oxygen and pulse oximeter for monitoring, emergency evacuation protocol, and summit certificate. International flights and personal travel insurance are not included.'],
    ],
  ],

  'zanzibar' => [
    'title'      => 'Zanzibar Island',
    'subtitle'   => 'The Spice Island — Paradise Beaches & Ancient Stone Town',
    'region'     => 'Zanzibar Archipelago, Tanzania',
    'area'       => '1,651 km²',
    'best_time'  => 'June – October & December – February',
    'altitude'   => 'Sea level',
    'image'      => IMG_ZANZIBAR,
    'og_title'   => 'Zanzibar Tanzania Tours | Beaches, Stone Town & Spice Islands',
    'meta_desc'  => 'Discover Zanzibar\'s white sand beaches, UNESCO Stone Town, and vibrant coral reefs. Combine your Tanzania safari with a Zanzibar beach extension. Book with Jambo Masai Tours.',
    'intro'      => 'Zanzibar is Tanzania\'s legendary Spice Island — an archipelago of white coral sand, turquoise lagoons and ancient Swahili culture floating in the Indian Ocean. The island\'s rich history as a centre of the spice trade and its UNESCO-listed Stone Town create a destination unlike anywhere else in East Africa.',
    'description'=> 'Zanzibar\'s main island, Unguja, offers an intoxicating blend of natural beauty and historical depth. The northern and eastern coasts boast some of East Africa\'s finest beaches — Nungwi, Kendwa, Matemwe and Paje are particularly beloved — while the famous Stone Town preserves 1,000 years of Swahili, Arab, Persian and Indian cultural heritage within a labyrinth of narrow streets and elaborately carved doorways. Beneath the surface, Zanzibar\'s coral reefs are alive with colour — snorkelling and diving reveal an underwater world of sea turtles, dolphins, reef sharks and kaleidoscopic fish. Spice tours, sunset dhow cruises, and fresh seafood barbecues on the beach complete what many consider to be Africa\'s most perfect island escape.',
    'highlights' => [
      ['icon'=>'fa-umbrella-beach','title'=>'White Sand Beaches',  'desc'=>'Nungwi, Kendwa, Matemwe and Paje — some of East Africa\'s most beautiful beaches.'],
      ['icon'=>'fa-landmark',      'title'=>'Stone Town',         'desc'=>'UNESCO World Heritage city with 1,000 years of Swahili, Arab and Indian history.'],
      ['icon'=>'fa-fish',          'title'=>'Snorkelling & Diving','desc'=>'Pristine coral reefs, sea turtles, dolphins and manta rays in crystal waters.'],
      ['icon'=>'fa-seedling',      'title'=>'Spice Tours',        'desc'=>'Visit clove, vanilla, cinnamon and pepper plantations — taste the island\'s heritage.'],
      ['icon'=>'fa-ship',          'title'=>'Dhow Cruises',       'desc'=>'Sail on a traditional wooden dhow at sunset with the golden Indian Ocean horizon.'],
      ['icon'=>'fa-kiwi-bird',     'title'=>'Dolphin Watching',   'desc'=>'Swim with wild spinner dolphins at Kizimkazi on Zanzibar\'s southern coast.'],
    ],
    'wildlife'   => ['Spinner Dolphin','Green Sea Turtle','Hawksbill Turtle','Reef Shark','Manta Ray','Red Colobus Monkey','Flying Fox','Flamingo','Dugong'],
    'months'     => ['Jan'=>'peak','Feb'=>'peak','Mar'=>'good','Apr'=>'low','May'=>'low','Jun'=>'peak','Jul'=>'peak','Aug'=>'peak','Sep'=>'peak','Oct'=>'good','Nov'=>'fair','Dec'=>'peak'],
    'peak_note'  => 'Jun–Oct: Cool and dry, perfect beach weather. Dec–Feb: Warm and dry, ideal for diving. Apr–May: Long rains — some resorts close. Best diving: Oct–Feb (clearest waters). Best waves for surfing/kite: Jun–Sep.',
    'search'     => 'zanzibar',
    'geo'        => ['-6.1659','39.2026'],
    'faqs'       => [
      ['q'=>'Do I need a visa to visit Zanzibar?','a'=>'Zanzibar is part of Tanzania, so the same visa rules apply. Citizens of most Western countries (USA, UK, EU, Canada, Australia) can obtain a single-entry Tanzania Tourist Visa on arrival at Zanzibar Airport or Dar es Salaam for USD 50. We recommend applying for an e-Visa before travel at eservices.immigration.go.tz.'],
      ['q'=>'Can I combine a Tanzania safari with Zanzibar?','a'=>'Yes — this is our most popular itinerary style. We call it "Bush & Beach." A typical combination is 5–7 days on safari (Serengeti, Ngorongoro, Tarangire) followed by 3–5 days relaxing on Zanzibar. Domestic flights connect Arusha to Zanzibar in under 2 hours.'],
      ['q'=>'What is the best beach in Zanzibar?','a'=>'It depends on what you prefer. Nungwi and Kendwa (north) are best for swimming year-round with calm, clear water. Matemwe (northeast) is quieter and superb for snorkelling. Paje (east) is the kite-surfing capital. For seclusion, the small islands (Mnemba, Chumbe) are extraordinary but require day trips.'],
      ['q'=>'Is Stone Town worth visiting?','a'=>'Absolutely — Stone Town is one of East Africa\'s most fascinating destinations. The UNESCO-listed old city preserves centuries of Swahili, Arab, Indian and Portuguese influence in a living, breathing city. Key sites include the Old Fort, Palace Museum, Freddie Mercury birthplace, spice market, and the sunset front at Forodhani Gardens.'],
      ['q'=>'What water activities are available in Zanzibar?','a'=>'Zanzibar offers world-class snorkelling and scuba diving, dolphin swimming at Kizimkazi, deep-sea fishing, kitesurfing at Paje, traditional dhow sailing, glass-bottom boat tours, and sea kayaking. Mnemba Atoll is rated one of East Africa\'s top dive sites with visibility often exceeding 30 metres.'],
    ],
  ],

  'tarangire' => [
    'title'      => 'Tarangire National Park',
    'subtitle'   => 'Tanzania\'s Elephant Sanctuary Beneath Ancient Baobabs',
    'region'     => 'Manyara Region, Northern Tanzania',
    'area'       => '2,850 km²',
    'best_time'  => 'June – October',
    'altitude'   => '1,100 m',
    'image'      => IMG_TARANGIRE,
    'og_title'   => 'Tarangire National Park Tanzania | Elephants, Baobabs & Safari',
    'meta_desc'  => 'Safari in Tarangire National Park — Tanzania\'s elephant capital. Ancient baobabs, massive elephant herds, and exceptional birdlife. Book with Jambo Masai Tours.',
    'intro'      => 'Tarangire National Park is Tanzania\'s best-kept safari secret — a vast, ancient wilderness of giant baobab trees and the Tarangire River that draws the largest elephant herds in northern Tanzania during the dry season. Less visited than Serengeti but equally compelling, Tarangire offers an intimate, unhurried safari experience.',
    'description'=> 'The Tarangire River is the park\'s lifeblood — the only permanent water source in the region during the dry season, which transforms the park into one of Tanzania\'s greatest wildlife spectacles between July and October. Hundreds of elephants, zebra, wildebeest, buffalo and gazelle converge on the river\'s banks while lions, leopards and cheetahs patrol the surrounding savanna. The park is defined by its extraordinary landscape of ancient baobab trees — some over 1,000 years old — which rise from the golden grass like prehistoric giants, creating one of Africa\'s most photogenic environments. Tarangire also boasts Tanzania\'s greatest diversity of bird species, with over 550 recorded including the unusual ash-starling found only in this ecosystem.',
    'highlights' => [
      ['icon'=>'fa-paw',           'title'=>'Elephant Herds',     'desc'=>'Tarangire has the highest elephant density in northern Tanzania — herds of 200+ are common.'],
      ['icon'=>'fa-tree',          'title'=>'Ancient Baobabs',    'desc'=>'1,000-year-old baobab trees create an iconic, otherworldly landscape unique to Tarangire.'],
      ['icon'=>'fa-feather',       'title'=>'550+ Bird Species',  'desc'=>'Tanzania\'s richest birdlife including the rare ash-starling found only in this ecosystem.'],
      ['icon'=>'fa-water',         'title'=>'Tarangire River',    'desc'=>'The only permanent water in the region attracts extraordinary wildlife concentrations.'],
      ['icon'=>'fa-eye',           'title'=>'Fewer Visitors',     'desc'=>'Far less crowded than Serengeti — enjoy private game drives and uninterrupted wildlife moments.'],
      ['icon'=>'fa-tent',          'title'=>'Bush Camping',       'desc'=>'Exclusive tented camps and fly-camping deep in the wilderness offer a true safari atmosphere.'],
    ],
    'wildlife'   => ['Elephant','Lion','Leopard','Cheetah','Giraffe','Buffalo','Wildebeest','Zebra','Oryx','Eland','Hartebeest','Python','Monitor Lizard','Ash-starling'],
    'months'     => ['Jan'=>'good','Feb'=>'good','Mar'=>'fair','Apr'=>'low','May'=>'low','Jun'=>'peak','Jul'=>'peak','Aug'=>'peak','Sep'=>'peak','Oct'=>'peak','Nov'=>'good','Dec'=>'good'],
    'peak_note'  => 'Peak season (Jun–Oct): Dry season concentrates wildlife at the river — elephant herds of 200+ are common. Green season (Jan–Mar): Lush landscapes and excellent birdlife as migratory species arrive.',
    'search'     => 'tarangire',
    'geo'        => ['-3.8500','36.0167'],
    'faqs'       => [
      ['q'=>'How does Tarangire compare to Serengeti?','a'=>'Tarangire offers a more intimate, less crowded experience compared to Serengeti. It\'s famous for elephants and baobabs where Serengeti is famous for the migration and big cats. Most visitors combine Tarangire with Serengeti and Ngorongoro for a comprehensive northern Tanzania circuit. Tarangire works beautifully as the opening or closing park of a longer safari.'],
      ['q'=>'When is the best time to visit Tarangire?','a'=>'July–October is peak season when the dry Tarangire River draws massive elephant concentrations — herds of 200+ animals. However, January–March is excellent for birdwatching and green season photography. The park is much less visited than Serengeti, so even at peak season you\'ll have game drives largely to yourself.'],
      ['q'=>'Is Tarangire good for a first-time safari?','a'=>'Tarangire is excellent for first-time safari-goers, especially those fascinated by elephants. The park\'s manageable size, dramatic scenery, and abundant wildlife create a quintessential African safari experience without the large crowds sometimes found in more famous parks.'],
      ['q'=>'Can I do a walking safari in Tarangire?','a'=>'Yes — Tarangire is one of Tanzania\'s best parks for guided walking safaris. Several private concession areas adjacent to the national park offer multi-day fly-camping and walking itineraries. Walking alongside elephants and giraffe with an experienced armed ranger is an extraordinary experience.'],
      ['q'=>'What makes Tarangire unique among Tanzania\'s parks?','a'=>'Three things make Tarangire stand out: its extraordinary elephant population (largest herds in northern Tanzania), the iconic ancient baobab trees that create a unique and photogenic landscape, and its outstanding birdlife of over 550 species. Combined, these create a safari experience that feels entirely different to Serengeti or Ngorongoro.'],
    ],
  ],

  'maasai-heartland' => [
    'title'      => 'Maasai Cultural Heartland',
    'subtitle'   => 'Step Into an Ancient World — Authentic Maasai Culture & Traditions',
    'region'     => 'Arusha & Manyara Region, Tanzania',
    'area'       => 'Arusha surroundings',
    'best_time'  => 'Year-round',
    'altitude'   => '1,380 m (Arusha)',
    'image'      => IMG_MAASAI,
    'og_title'   => 'Maasai Cultural Safari Tanzania | Village Visits & Warrior Experience',
    'meta_desc'  => 'Experience authentic Maasai culture with Jambo Masai Tours — village visits, warrior training, traditional ceremonies, and community-led safaris in Tanzania.',
    'intro'      => 'The Maasai are one of Africa\'s most iconic peoples — proud, semi-nomadic warriors who have maintained their ancient traditions and pastoral way of life for centuries despite the pressures of the modern world. Jambo Masai Tours is itself Maasai-founded, giving us unique, genuine access to cultural experiences that most operators simply cannot provide.',
    'description'=> 'A Maasai cultural experience is not a performance for tourists — it is a genuine window into a living culture that has endured for over 500 years. Our community partnerships allow you to participate in daily village life, sit with elders, hear stories of migration and warrior tradition, learn the art of fire-making and beadwork, and share a meal prepared by Maasai women. The areas around Arusha and the Manyara escarpment are the heartland of Maasai territory in Tanzania, where traditional manyatta (homestead) settlements exist alongside modern society. For visitors seeking more than animal sightings, a Maasai cultural immersion is one of Africa\'s most profound travel experiences.',
    'highlights' => [
      ['icon'=>'fa-home',          'title'=>'Village Life',       'desc'=>'Enter a genuine Maasai manyatta, meet the family, and understand daily life from the inside.'],
      ['icon'=>'fa-fire',          'title'=>'Warrior Experience', 'desc'=>'Learn traditional skills — fire-making, spear-throwing, jumping dance and warrior songs.'],
      ['icon'=>'fa-gem',           'title'=>'Beadwork & Crafts',  'desc'=>'Maasai beadwork is Africa\'s finest. Learn the meaning of colours and create your own piece.'],
      ['icon'=>'fa-music',         'title'=>'Ceremonies',         'desc'=>'Witness traditional songs, dances and ceremonies performed for cultural sharing, not tourism.'],
      ['icon'=>'fa-users',         'title'=>'Community Tourism',  'desc'=>'All fees go directly to Maasai communities — supporting education, water and healthcare.'],
      ['icon'=>'fa-seedling',      'title'=>'Conservation',       'desc'=>'The Maasai are Africa\'s original conservationists — learn their relationship with wildlife.'],
    ],
    'wildlife'   => ['Zebra','Giraffe','Wildebeest','Impala','Baboon','Vervet Monkey','Flamingo','Eagle','Vulture','Secretary Bird'],
    'months'     => ['Jan'=>'peak','Feb'=>'peak','Mar'=>'good','Apr'=>'good','May'=>'good','Jun'=>'peak','Jul'=>'peak','Aug'=>'peak','Sep'=>'peak','Oct'=>'peak','Nov'=>'good','Dec'=>'peak'],
    'peak_note'  => 'Year-round destination. Weather in Arusha is mild and pleasant throughout the year. Cultural experiences are available any time — combine with a northern Tanzania safari circuit.',
    'search'     => 'maasai',
    'geo'        => ['-3.3986','36.6730'],
    'faqs'       => [
      ['q'=>'Are Maasai village visits authentic or staged for tourists?','a'=>'Our visits are genuine community experiences, not performances. As a Maasai-founded company, we have deep relationships with specific villages and families. You\'ll visit real homesteads where people actually live, meet the village elder, and participate in daily activities — not a rehearsed show. We are transparent about how fees are distributed to the community.'],
      ['q'=>'Is it respectful to visit a Maasai village as a tourist?','a'=>'When done thoughtfully, cultural tourism is highly beneficial. Our community tourism model ensures that 100% of cultural fees go directly to the villages — supporting education, clean water projects and healthcare. The Maasai communities we work with actively choose to host visitors and value the cultural exchange. We provide guidance on respectful behavior and dress before each visit.'],
      ['q'=>'Can I stay overnight in a Maasai village?','a'=>'Yes — for the most immersive experience, we offer 1–2 night stays in specially prepared Maasai manyatta guesthouses. This allows you to experience morning and evening rituals, share meals, and sleep under African stars within the community. This option is for adventurous travellers seeking deep cultural connection.'],
      ['q'=>'What is the best way to combine cultural and wildlife experiences?','a'=>'We recommend combining a Maasai cultural experience with Tarangire National Park or Lake Manyara, both of which are within easy reach of Maasai villages near Arusha. A 5–7 day itinerary can seamlessly blend 2 days of cultural immersion with 3–4 days of wildlife safari.'],
      ['q'=>'What should I bring to a Maasai village visit?','a'=>'Dress modestly (no shorts for women, covered shoulders). Bring a small gift for the community — sugar, tea or children\'s school supplies are appreciated. Photography is welcome after asking permission from individuals. Leave mobile phones in your pocket when invited to participate in ceremonies — be present in the moment.'],
    ],
  ],

];

if (!isset($destinations[$slug])) {
    redirect(url('destinations.php'));
}
$dest = $destinations[$slug];
$db = getDB();

dest_ready:

/* Related tours from DB */
$tours = [];
try {
    $st = $db->prepare("SELECT id,name,slug,price,duration,tour_type,image,rating,destination FROM tours WHERE LOWER(destination) LIKE :s OR LOWER(name) LIKE :s2 ORDER BY featured DESC, rating DESC LIMIT 6");
    $st->execute([':s'=>'%'.$dest['search'].'%',':s2'=>'%'.$dest['search'].'%']);
    $tours = $st->fetchAll();
} catch (\Throwable $e) {}

/* Related blog posts */
$blogPosts = [];
try {
    $bp = $db->prepare("SELECT id,title,slug,excerpt,image,created_at,category FROM blog_posts WHERE published=1 AND (LOWER(title) LIKE :s OR LOWER(content) LIKE :s2) ORDER BY created_at DESC LIMIT 3");
    $bp->execute([':s'=>'%'.$dest['search'].'%',':s2'=>'%'.$dest['search'].'%']);
    $blogPosts = $bp->fetchAll();
} catch (\Throwable $e) {}

$canonicalUrl = SITE_URL . '/destination/' . $slug;
$pageTitle    = $dest['og_title'];
$pageDescription = $dest['meta_desc'];
$ogImage      = $dest['image'];
$ogType       = 'website';
$currentPage  = 'destinations';

/* Override intro/description from DB if admin has set custom content */
try {
    $dbPage = $db->prepare("SELECT intro, description FROM destination_pages WHERE slug=? LIMIT 1");
    $dbPage->execute([$slug]);
    $dbRow = $dbPage->fetch();
    if ($dbRow && !empty($dbRow['intro']))       $dest['intro']       = $dbRow['intro'];
    if ($dbRow && !empty($dbRow['description']))  $dest['description'] = $dbRow['description'];
} catch (\Throwable $e) {}

/* FAQ schema */
$faqSchema = json_encode([
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => array_map(fn($f) => [
        '@type'          => 'Question',
        'name'           => $f['q'],
        'acceptedAnswer' => ['@type'=>'Answer','text'=>$f['a']],
    ], $dest['faqs']),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$logoUrl_seo = getSetting('logo_url') ?: (SITE_URL . '/uploads/logo-husika.png');

$headExtra = '
<script type="application/ld+json">
[
  {
    "@context": "https://schema.org",
    "@type": "TouristAttraction",
    "name": ' . json_encode($dest['title']) . ',
    "url": ' . json_encode($canonicalUrl) . ',
    "description": ' . json_encode(strip_tags($dest['intro'])) . ',
    "image": ' . json_encode($dest['image']) . ',
    "geo": {
      "@type": "GeoCoordinates",
      "latitude": ' . json_encode($dest['geo'][0]) . ',
      "longitude": ' . json_encode($dest['geo'][1]) . '
    },
    "address": {
      "@type": "PostalAddress",
      "addressRegion": ' . json_encode($dest['region']) . ',
      "addressCountry": "TZ"
    },
    "touristType": ["Safari", "Wildlife", "Adventure Travel"],
    "publicAccess": true,
    "availableLanguage": ["English", "Swahili"],
    "provider": {
      "@type": "TravelAgency",
      "name": "Jambo Masai Tours",
      "url": ' . json_encode(SITE_URL) . '
    }
  },
  ' . $faqSchema . ',
  {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
      { "@type": "ListItem", "position": 1, "name": "Home",         "item": ' . json_encode(SITE_URL) . ' },
      { "@type": "ListItem", "position": 2, "name": "Destinations", "item": ' . json_encode(SITE_URL . '/destinations.php') . ' },
      { "@type": "ListItem", "position": 3, "name": ' . json_encode($dest['title']) . ' }
    ]
  }
]
</script>
';
require_once 'includes/dark_header.php';
?>

<!-- navbar spacer -->
<div style="height:68px"></div>

<!-- --- HERO --- -->
<div class="relative overflow-hidden" style="min-height:320px">
  <img src="<?= e($dest['image']) ?>" alt="<?= e($dest['title']) ?> Safari Tanzania"
       class="absolute inset-0 w-full h-full object-cover ken-burns" fetchpriority="high"
       width="1920" height="500" style="opacity:.65">
  <div class="absolute inset-0" style="background:linear-gradient(to top,#23362f 0%,rgba(10,10,10,.45) 55%,rgba(10,10,10,.05) 100%)"></div>
  <div class="absolute inset-0" style="background:linear-gradient(to right,rgba(10,10,10,.5),transparent 65%)"></div>
  <div class="relative z-10 max-w-6xl mx-auto px-4 lg:px-8 flex flex-col justify-end pb-10 pt-8">
    <!-- breadcrumb -->
    <nav class="flex items-center gap-2 font-nav text-[.62rem] text-white/35 mb-4">
      <a href="<?= url() ?>" class="hover:text-white transition-colors">Home</a><span>›</span>
      <a href="<?= url('destinations.php') ?>" class="hover:text-white transition-colors">Destinations</a><span>›</span>
      <span class="text-white/55"><?= e($dest['title']) ?></span>
    </nav>
    <div class="section-tag reveal"><i class="fas fa-map-marker-alt mr-1"></i><?= e($dest['region']) ?></div>
    <h1 class="font-heading text-4xl md:text-5xl lg:text-6xl font-bold text-white leading-tight mt-2 reveal">
      <?= e($dest['title']) ?>
    </h1>
    <p class="text-lg text-white/60 mt-3 max-w-2xl reveal" style="font-family:'Inter',sans-serif">
      <?= e($dest['subtitle']) ?>
    </p>
    <!-- quick stats -->
    <div class="flex flex-wrap gap-5 mt-6 reveal">
      <?php foreach ([['fa-ruler-combined',$dest['area'],'Area'],['fa-clock',$dest['best_time'],'Best Time'],['fa-mountain',$dest['altitude'],'Altitude']] as $s): ?>
      <div class="flex items-center gap-2">
        <div style="width:34px;height:34px;border-radius:50%;background:rgba(160,94,34,.15);border:1px solid rgba(160,94,34,.3);display:flex;align-items:center;justify-content:center">
          <i class="fas <?= $s[0] ?>" style="color:#a05e22;font-size:.7rem"></i>
        </div>
        <div>
          <div style="font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:700;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.1em"><?= $s[2] ?></div>
          <div style="font-family:'Montserrat',sans-serif;font-size:.78rem;font-weight:600;color:#fff"><?= e($s[1]) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- --- MAIN CONTENT --- -->
<div class="max-w-6xl mx-auto px-4 lg:px-8 py-16">
  <div style="display:grid;grid-template-columns:1fr 300px;gap:2.5rem;align-items:start" class="dest-grid">

    <!-- Left: content -->
    <div>

      <!-- Overview -->
      <section class="reveal mb-12">
        <div class="section-tag"><i class="fas fa-binoculars mr-1"></i>Overview</div>
        <h2 class="font-heading text-3xl font-bold text-white mb-4">About <?= e($dest['title']) ?></h2>
        <p class="text-white/55 leading-relaxed mb-4" style="font-size:.95rem"><?= $dest['intro'] ?></p>
        <p class="text-white/45 leading-relaxed" style="font-size:.9rem"><?= $dest['description'] ?></p>
      </section>

      <!-- Highlights grid -->
      <section class="reveal mb-12">
        <div class="section-tag"><i class="fas fa-star mr-1"></i>Highlights</div>
        <h2 class="font-heading text-2xl font-bold text-white mb-6">Why Visit <?= e($dest['title']) ?></h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem">
          <?php foreach ($dest['highlights'] as $h): ?>
          <div class="glass-card p-5 reveal">
            <div style="width:42px;height:42px;border-radius:10px;background:rgba(160,94,34,.12);border:1px solid rgba(160,94,34,.2);display:flex;align-items:center;justify-content:center;margin-bottom:.85rem">
              <i class="fas <?= $h['icon'] ?>" style="color:#a05e22;font-size:.9rem"></i>
            </div>
            <h3 style="font-family:'Montserrat',sans-serif;font-size:.82rem;font-weight:700;color:#fff;margin-bottom:.35rem"><?= e($h['title']) ?></h3>
            <p style="font-size:.78rem;color:rgba(255,255,255,.4);line-height:1.6"><?= e($h['desc']) ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- Wildlife -->
      <section class="reveal mb-12">
        <div class="section-tag"><i class="fas fa-paw mr-1"></i>Wildlife</div>
        <h2 class="font-heading text-2xl font-bold text-white mb-5">Animals You May See</h2>
        <div class="flex flex-wrap gap-2">
          <?php foreach ($dest['wildlife'] as $w): ?>
          <span style="font-family:'Montserrat',sans-serif;font-size:.65rem;font-weight:600;padding:.3rem .85rem;border-radius:999px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);color:rgba(255,255,255,.5)">
            <?= e($w) ?>
          </span>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- Best time calendar -->
      <section class="reveal mb-12">
        <div class="section-tag"><i class="fas fa-calendar-alt mr-1"></i>Best Time</div>
        <h2 class="font-heading text-2xl font-bold text-white mb-5">When to Visit <?= e($dest['title']) ?></h2>
        <div style="display:grid;grid-template-columns:repeat(12,1fr);gap:4px;margin-bottom:.85rem">
          <?php
          $mColors = ['peak'=>['#a05e22','rgba(160,94,34,.15)'],'good'=>['#fbbf24','rgba(245,158,11,.12)'],'fair'=>['#60a5fa','rgba(59,130,246,.1)'],'low'=>['rgba(255,255,255,.2)','rgba(255,255,255,.04)']];
          foreach ($dest['months'] as $mon => $level):
            $c = $mColors[$level];
          ?>
          <div style="text-align:center">
            <div style="height:36px;border-radius:6px;background:<?= $c[1] ?>;border:1px solid <?= $c[0] ?>20;margin-bottom:4px"></div>
            <span style="font-family:'Montserrat',sans-serif;font-size:.55rem;font-weight:600;color:<?= $c[0] ?>"><?= $mon ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="flex flex-wrap gap-3 mb-4">
          <?php foreach (['peak'=>['#a05e22','Peak Season'],'good'=>['#fbbf24','Good'],'fair'=>['#60a5fa','Fair'],'low'=>['rgba(255,255,255,.3)','Low Season']] as $k=>$v): ?>
          <span style="display:flex;align-items:center;gap:.3rem;font-family:'Montserrat',sans-serif;font-size:.6rem;color:rgba(255,255,255,.45)">
            <span style="width:10px;height:10px;border-radius:2px;background:<?= $v[0] ?>;display:inline-block"></span><?= $v[1] ?>
          </span>
          <?php endforeach; ?>
        </div>
        <p style="font-size:.82rem;color:rgba(255,255,255,.4);line-height:1.6;font-style:italic"><?= e($dest['peak_note']) ?></p>
      </section>

      <!-- Related Tours -->
      <?php if (!empty($tours)): ?>
      <section class="reveal mb-12">
        <div class="section-tag"><i class="fas fa-compass mr-1"></i>Safari Packages</div>
        <h2 class="font-heading text-2xl font-bold text-white mb-6">Tours in <?= e($dest['title']) ?></h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem">
          <?php foreach ($tours as $t): ?>
          <a href="<?= url('tour/'.e($t['slug'])) ?>" style="background:#2c463d;border:1px solid rgba(255,255,255,.07);border-radius:16px;overflow:hidden;display:block;text-decoration:none;transition:all .3s" class="reveal tour-card">
            <div style="height:160px;overflow:hidden">
              <img src="<?= e($t['image']) ?>" alt="<?= e($t['name']) ?> Tanzania"
                   style="width:100%;height:100%;object-fit:cover;transition:transform .5s" class="tc-img"
                   loading="lazy" width="400" height="160">
            </div>
            <div style="padding:1rem">
              <div style="font-family:'Montserrat',sans-serif;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#a05e22;margin-bottom:.35rem"><?= e($t['tour_type']) ?></div>
              <h3 style="font-family:'Nanum Myeongjo',serif;font-size:.95rem;font-weight:700;color:#fff;margin-bottom:.35rem;line-height:1.3"><?= e($t['name']) ?></h3>
              <div style="display:flex;align-items:center;justify-content:space-between">
                <span style="font-family:'Montserrat',sans-serif;font-size:.7rem;color:rgba(255,255,255,.4)"><?= e($t['duration']) ?></span>
                <span style="font-family:'Montserrat',sans-serif;font-size:.78rem;font-weight:700;color:#a05e22">From $<?= number_format((float)$t['price']) ?></span>
              </div>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

      <!-- FAQ -->
      <section class="reveal mb-12" id="faq">
        <div class="section-tag"><i class="fas fa-question-circle mr-1"></i>FAQ</div>
        <h2 class="font-heading text-2xl font-bold text-white mb-6">Frequently Asked Questions</h2>
        <div style="display:flex;flex-direction:column;gap:.6rem">
          <?php foreach ($dest['faqs'] as $i => $faq): ?>
          <div style="background:#2c463d;border:1px solid rgba(255,255,255,.07);border-radius:14px;overflow:hidden" class="reveal">
            <button onclick="toggleFaq(<?= $i ?>)" style="width:100%;display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;background:none;border:none;cursor:pointer;text-align:left;gap:.75rem">
              <span style="font-family:'Montserrat',sans-serif;font-size:.82rem;font-weight:600;color:#fff;line-height:1.4"><?= e($faq['q']) ?></span>
              <i id="faq-icon-<?= $i ?>" class="fas fa-plus" style="color:#a05e22;font-size:.7rem;flex-shrink:0;transition:transform .3s"></i>
            </button>
            <div id="faq-body-<?= $i ?>" style="display:none;padding:0 1.25rem 1rem">
              <p style="font-size:.82rem;color:rgba(255,255,255,.5);line-height:1.75"><?= e($faq['a']) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </section>

    </div>

    <!-- Right: sidebar -->
    <div style="position:sticky;top:84px">

      <!-- Book CTA -->
      <div class="glass-card p-6 mb-4 reveal">
        <h3 style="font-family:'Nanum Myeongjo',serif;font-size:1.15rem;font-weight:700;color:#fff;margin-bottom:.35rem">Plan Your <?= e($dest['title']) ?> Trip</h3>
        <p style="font-size:.78rem;color:rgba(255,255,255,.4);line-height:1.5;margin-bottom:1.1rem">Our experts are ready to design your perfect itinerary.</p>
        <a href="<?= url('booking.php') ?>" class="btn-em btn-em-primary w-full" style="display:flex;justify-content:center;margin-bottom:.5rem">
          <i class="fas fa-calendar-check mr-2" style="font-size:.75rem"></i> Book Safari
        </a>
        <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>?text=<?= urlencode('Hi! I\'m interested in a ' . $dest['title'] . ' safari.') ?>" class="btn-em btn-em-wa w-full" style="display:flex;justify-content:center" target="_blank" rel="noopener">
          <i class="fab fa-whatsapp mr-2" style="font-size:.8rem"></i> WhatsApp Us
        </a>
      </div>

      <!-- Quick facts -->
      <div class="glass-card p-5 mb-4 reveal">
        <h4 style="font-family:'Montserrat',sans-serif;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.14em;color:rgba(255,255,255,.35);margin-bottom:.85rem">Quick Facts</h4>
        <?php foreach ([
          ['fa-map-marker-alt','Region',$dest['region']],
          ['fa-ruler-combined','Area',$dest['area']],
          ['fa-mountain','Altitude',$dest['altitude']],
          ['fa-sun','Best Time',$dest['best_time']],
        ] as $f): ?>
        <div style="display:flex;align-items:flex-start;gap:.65rem;margin-bottom:.7rem">
          <i class="fas <?= $f[0] ?>" style="color:#a05e22;font-size:.65rem;margin-top:.15rem;flex-shrink:0;width:14px"></i>
          <div>
            <div style="font-family:'Montserrat',sans-serif;font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.25)"><?= $f[1] ?></div>
            <div style="font-size:.78rem;color:rgba(255,255,255,.65)"><?= e($f[2]) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Other destinations -->
      <div class="glass-card p-5 reveal">
        <h4 style="font-family:'Montserrat',sans-serif;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.14em;color:rgba(255,255,255,.35);margin-bottom:.85rem">Other Destinations</h4>
        <?php foreach ($destinations as $ds => $dd):
          if ($ds === $slug) continue; ?>
        <a href="<?= url('destination/'.$ds) ?>" style="display:flex;align-items:center;gap:.6rem;padding:.5rem .4rem;border-radius:10px;text-decoration:none;transition:background .2s;margin-bottom:.15rem" class="other-dest">
          <img src="<?= e($dd['image']) ?>" alt="<?= e($dd['title']) ?>" style="width:44px;height:32px;object-fit:cover;border-radius:6px;flex-shrink:0" loading="lazy" width="44" height="32">
          <div>
            <div style="font-family:'Montserrat',sans-serif;font-size:.72rem;font-weight:600;color:rgba(255,255,255,.65);transition:color .2s"><?= e($dd['title']) ?></div>
            <div style="font-family:'Montserrat',sans-serif;font-size:.58rem;color:rgba(255,255,255,.25)"><?= e($dd['region']) ?></div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</div>

<!-- --- FOOTER CTA --- -->
<section style="background:linear-gradient(135deg,rgba(160,94,34,.06),rgba(5,150,105,.04));border-top:1px solid rgba(160,94,34,.1);padding:5rem 1rem">
  <div class="max-w-3xl mx-auto text-center reveal">
    <div class="section-tag mx-auto"><i class="fas fa-compass mr-1"></i>Ready to Explore</div>
    <h2 class="font-heading text-3xl md:text-4xl font-bold text-white mb-4 mt-2">
      Your <?= e($dest['title']) ?> Safari Awaits
    </h2>
    <p class="text-white/45 mb-8" style="font-size:.92rem">
      Let our expert guides craft your perfect <?= e($dest['title']) ?> itinerary. Every detail handled, every moment extraordinary.
    </p>
    <div class="flex flex-wrap gap-4 justify-center">
      <a href="<?= url('booking.php') ?>" class="btn-em btn-em-primary">
        <i class="fas fa-calendar-check" style="font-size:.75rem"></i> Plan My Safari
      </a>
      <a href="<?= url('tours.php') ?>" class="btn-em btn-em-outline">
        <i class="fas fa-compass" style="font-size:.75rem"></i> View All Tours
      </a>
    </div>
  </div>
</section>

<style>
.tour-card:hover{border-color:rgba(160,94,34,.25) !important;transform:translateY(-4px);box-shadow:0 16px 40px rgba(0,0,0,.5)}
.tour-card:hover .tc-img{transform:scale(1.05)}
.other-dest:hover{background:rgba(255,255,255,.05)}
.other-dest:hover div:first-child div:first-child{color:#a05e22}
@media(max-width:900px){.dest-grid{grid-template-columns:1fr !important}}
</style>
<script>
function toggleFaq(i){
  var body=document.getElementById('faq-body-'+i);
  var icon=document.getElementById('faq-icon-'+i);
  var open=body.style.display!=='none'&&body.style.display!=='';
  body.style.display=open?'none':'block';
  icon.style.transform=open?'':'rotate(45deg)';
}
</script>

<?php require_once 'includes/dark_footer.php'; ?>


