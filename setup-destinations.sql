-- ═══════════════════════════════════════════════════
--  JAMBO MASAI TOURS — Destinations Table + Seed Data
--  Import via phpMyAdmin into: jambo_masai_tours
-- ═══════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `destinations` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title`       VARCHAR(120)  NOT NULL,
  `slug`        VARCHAR(120)  NOT NULL UNIQUE,
  `country`     VARCHAR(80)   NOT NULL DEFAULT 'Tanzania',
  `region`      VARCHAR(80)   NOT NULL DEFAULT '',
  `description` TEXT,
  `best_season` VARCHAR(120)  DEFAULT '',
  `climate`     VARCHAR(300)  DEFAULT '',
  `visa_info`   VARCHAR(400)  DEFAULT '',
  `highlights`  TEXT          COMMENT 'pipe-separated highlights',
  `image`       VARCHAR(500)  DEFAULT '',
  `sort_order`  TINYINT UNSIGNED DEFAULT 0,
  `active`      TINYINT(1)    DEFAULT 1,
  `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: 6 core Tanzania destinations
INSERT INTO `destinations`
  (`title`, `slug`, `country`, `region`, `description`, `best_season`, `climate`, `visa_info`, `highlights`, `image`, `sort_order`, `active`)
VALUES
(
  'Serengeti National Park',
  'serengeti',
  'Tanzania',
  'Mara & Simiyu Regions',
  'The world''s most famous wildlife arena. Over 1.5 million wildebeest thunder across the endless plains every year in one of nature''s greatest spectacles — the Great Migration. Lions, leopards and cheetahs follow in pursuit, making the Serengeti the pinnacle of African safari.',
  'June – October',
  'Warm and dry June–Oct (peak safari). Short rains November. Long rains March–May. Average 25°C.',
  'Tourist e-visa available online. Cost $50 USD. Most nationalities visa-on-arrival also accepted.',
  'Great Migration|Big Five Safari|Hot Air Balloon Sunrise|Endless Savannah Plains|Predator Sightings|Luxury Tented Camps',
  'https://images.unsplash.com/photo-1547471080-7cc2caa01a7e?w=1400&q=85&auto=format&fit=crop',
  1, 1
),
(
  'Ngorongoro Crater',
  'ngorongoro',
  'Tanzania',
  'Arusha Region',
  'The world''s largest intact volcanic caldera and a UNESCO World Heritage Site. This extraordinary natural amphitheatre shelters the highest concentration of wildlife in Africa, including the endangered black rhino. The crater floor spans 19 km — a world within a world.',
  'June – September',
  'Cool and misty mornings, warm sunny afternoons. 12–24°C year-round. Rain possible any month.',
  'Standard Tanzania tourist e-visa ($50). Conservation area fees apply on top of park entry.',
  'Rare Black Rhino|UNESCO World Heritage|Flamingo Soda Lakes|Dense Wildlife Year-Round|Maasai Cultural Lands|Crater Rim Viewpoints',
  'https://images.unsplash.com/photo-1523805009345-7448845a9e53?w=1400&q=85&auto=format&fit=crop',
  2, 1
),
(
  'Mount Kilimanjaro',
  'kilimanjaro',
  'Tanzania',
  'Kilimanjaro Region',
  'Africa''s highest peak at 5,895 m above sea level. Five breathtaking climate zones sweep you from tropical rainforest through moorland, alpine desert and glaciated arctic summit. The ultimate non-technical high-altitude trekking challenge — no ropes, no prior climbing experience needed.',
  'January – March & June – October',
  'Base: 25–35°C tropical. Summit: −10°C to −20°C. Weather varies dramatically by altitude zone.',
  'Standard Tanzania tourist e-visa covers the climb. Park entry and climbing permits required.',
  'Uhuru Peak at 5,895m|Five Distinct Climate Zones|Lemosho & Machame Routes|80% Summit Success Rate|Glacial Ice Fields|Spectacular Sunrise Views',
  'https://images.unsplash.com/photo-1521150932951-303a95503ed3?w=1400&q=85&auto=format&fit=crop',
  3, 1
),
(
  'Zanzibar Island',
  'zanzibar',
  'Tanzania',
  'Zanzibar Archipelago',
  'The legendary Spice Island. Powder-white beaches, crystal-clear azure lagoons, UNESCO-listed Stone Town and extraordinary marine biodiversity await. The perfect tropical extension to any mainland Tanzania safari — swim with dolphins, snorkel vibrant coral reefs and savour Swahili cuisine.',
  'June – October & December – February',
  'Tropical. Average 27°C. Indian Ocean water 24–29°C. Short rains November, long rains March–May.',
  'Tanzania tourist e-visa ($50 USD). All foreigners need passports even for inter-island travel.',
  'UNESCO Stone Town|Pristine White-Sand Beaches|Spice Farm Tours|Dolphin Swimming|World-Class Snorkelling & Diving|Authentic Swahili Cuisine',
  'https://images.unsplash.com/photo-1568452618825-e3521b0cdf57?w=1400&q=85&auto=format&fit=crop',
  4, 1
),
(
  'Tarangire National Park',
  'tarangire',
  'Tanzania',
  'Manyara Region',
  'Africa''s highest elephant density outside Amboseli. Ancient giant baobab trees frame a stunning landscape carved by the seasonal Tarangire River. Tree-climbing lions, massive elephant herds and over 550 bird species make Tarangire an unmissable hidden gem on Tanzania''s northern circuit.',
  'June – October',
  'Dry and hot June–Oct for best game viewing. Green and lush November–May with excellent birding.',
  'Standard Tanzania tourist e-visa covers all national parks on the northern circuit.',
  'Africa''s Highest Elephant Density|Ancient Giant Baobab Trees|Tree-Climbing Lions|550+ Bird Species|Tarangire River Wildlife Corridor|Uncrowded Safari Experience',
  'https://images.unsplash.com/photo-1564760055775-d63b17a55c44?w=1400&q=85&auto=format&fit=crop',
  5, 1
),
(
  'Maasai Cultural Heartland',
  'maasai-heartland',
  'Tanzania',
  'Arusha & Manyara Regions',
  'Live alongside Africa''s most iconic warrior tribe. Participate in ancient ceremonies, guided cultural walks, cattle-herding and traditions unchanged for centuries. Stay in authentic Maasai bomas under star-filled skies and gain a profound understanding of life on the African savannah.',
  'Year-round',
  'Moderate highland climate. Average 20–28°C. Short rains October–December, long rains March–May.',
  'Standard Tanzania tourist e-visa ($50 USD). Community entry fees support local development.',
  'Authentic Boma Homestays|Warrior Jumping Ceremonies|Traditional Beadwork & Crafts|Cattle Herding Experiences|Community-Based Conservation|Sunset Bush Walks',
  'https://images.unsplash.com/photo-1551632436-cbf8dd35adfa?w=1400&q=85&auto=format&fit=crop',
  6, 1
);
