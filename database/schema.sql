-- Jambo Masai Tours Database Schema
-- Run: SOURCE /path/to/schema.sql  OR import via phpMyAdmin

CREATE DATABASE IF NOT EXISTS jambo_masai_tours
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE jambo_masai_tours;

-- ─────────────────────────────────────────────
-- ADMINS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admins (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username     VARCHAR(60)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  email        VARCHAR(150) NOT NULL UNIQUE,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- TOURS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS tours (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(200)  NOT NULL,
  slug          VARCHAR(200)  NOT NULL UNIQUE,
  destination   VARCHAR(100)  NOT NULL,
  tour_type     VARCHAR(100)  NOT NULL,
  description   TEXT          NOT NULL,
  highlights    TEXT,
  itinerary     TEXT,
  price         DECIMAL(10,2) NOT NULL,
  duration      VARCHAR(50)   NOT NULL,
  max_travelers TINYINT       NOT NULL DEFAULT 12,
  rating        DECIMAL(2,1)  NOT NULL DEFAULT 4.8,
  review_count  SMALLINT      NOT NULL DEFAULT 0,
  image         VARCHAR(500)  NOT NULL,
  gallery       TEXT,
  featured      TINYINT(1)    NOT NULL DEFAULT 0,
  created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- BOOKINGS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS bookings (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tour_id          INT UNSIGNED NOT NULL,
  first_name       VARCHAR(80)  NOT NULL,
  last_name        VARCHAR(80)  NOT NULL,
  email            VARCHAR(150) NOT NULL,
  phone            VARCHAR(30)  NOT NULL,
  country          VARCHAR(80)  NOT NULL,
  travel_date      DATE         NOT NULL,
  travelers        TINYINT      NOT NULL DEFAULT 1,
  special_requests TEXT,
  status           ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- CONTACTS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS contacts (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(150) NOT NULL,
  email      VARCHAR(150) NOT NULL,
  phone      VARCHAR(30),
  subject    VARCHAR(200) NOT NULL,
  message    TEXT         NOT NULL,
  status     ENUM('unread','read','replied') NOT NULL DEFAULT 'unread',
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- TESTIMONIALS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS testimonials (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_name VARCHAR(120) NOT NULL,
  country       VARCHAR(80)  NOT NULL,
  photo         VARCHAR(500),
  rating        TINYINT      NOT NULL DEFAULT 5,
  review        TEXT         NOT NULL,
  tour_name     VARCHAR(200),
  approved      TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- GALLERY
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS gallery (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title      VARCHAR(200) NOT NULL,
  image      VARCHAR(500) NOT NULL,
  category   ENUM('wildlife','landscapes','culture','safari-life','zanzibar','birds') NOT NULL DEFAULT 'wildlife',
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- BLOG POSTS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS blog_posts (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title      VARCHAR(250) NOT NULL,
  slug       VARCHAR(250) NOT NULL UNIQUE,
  excerpt    VARCHAR(400) NOT NULL,
  content    LONGTEXT     NOT NULL,
  image      VARCHAR(500) NOT NULL,
  author     VARCHAR(100) NOT NULL DEFAULT 'Jambo Masai Team',
  published  TINYINT(1)   NOT NULL DEFAULT 1,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ═════════════════════════════════════════════
-- SEED DATA
-- ═════════════════════════════════════════════

-- Admin (password: Admin@Jambo2024)
INSERT INTO admins (username, password_hash, email) VALUES
('admin', '$2y$10$CoO5JWPsux9NeqtpI599T.nqIzQPmdFSFTEB5IZNRq9AGB2Rn2FaC', 'admin@jambomasaitours.com');

-- Tours
INSERT INTO tours (name, slug, destination, tour_type, description, highlights, price, duration, max_travelers, rating, review_count, image, featured) VALUES
(
  'Serengeti Great Migration Safari',
  'serengeti-great-migration-safari',
  'Serengeti',
  'Wildlife Safari',
  'Witness one of nature''s greatest spectacles — over 1.5 million wildebeest thundering across the Serengeti plains. Our expert guides will position you at the best crossing points for heart-stopping action and unforgettable photographs.',
  'Great Wildebeest Migration|River Crossings|Big Five sightings|Luxury tented camps|Expert naturalist guides|Sunrise game drives',
  4200.00, '7 Days / 6 Nights', 8, 5.0, 347,
  'https://images.unsplash.com/photo-1547471080-7cc2caa01a7e?w=800&q=80&auto=format&fit=crop',
  1
),
(
  'Ngorongoro Crater Expedition',
  'ngorongoro-crater-expedition',
  'Ngorongoro',
  'Wildlife Safari',
  'Descend into the world''s largest intact volcanic caldera, a UNESCO World Heritage Site teeming with an extraordinary density of wildlife. Encounter lions, elephants, rhinos, flamingos and countless other species in this natural paradise.',
  'Black Rhino sightings|Flamingo-lined lakes|Dense Big Five population|UNESCO World Heritage Site|Maasai village visit|Olduvai Gorge stop',
  3100.00, '4 Days / 3 Nights', 10, 4.9, 289,
  'https://images.unsplash.com/photo-1523805009345-7448845a9e53?w=800&q=80&auto=format&fit=crop',
  1
),
(
  'Kilimanjaro Summit Trek',
  'kilimanjaro-summit-trek',
  'Kilimanjaro',
  'Trekking',
  'Stand on the Roof of Africa at 5,895 metres. Our Machame Route itinerary offers the best acclimatisation profile and summit success rates. All logistics — permits, porters, guides, meals and equipment — are meticulously arranged.',
  'Uhuru Peak summit|Machame "Whiskey" Route|Certified mountain guides|All meals & equipment|High altitude acclimatisation|Certificate of achievement',
  2850.00, '8 Days / 7 Nights', 12, 4.8, 412,
  'https://images.unsplash.com/photo-1521150932951-303a95503ed3?w=800&q=80&auto=format&fit=crop',
  1
),
(
  'Zanzibar Beach & Spice Holiday',
  'zanzibar-beach-spice-holiday',
  'Zanzibar',
  'Beach Holiday',
  'Unwind on the spice island''s powder-white beaches and turquoise lagoons. Combine relaxation with cultural exploration — tour historic Stone Town, join a spice farm tour, snorkel pristine coral reefs and sample the finest Swahili seafood.',
  'Stone Town UNESCO tour|Spice farm experience|Snorkeling & dolphin watching|Nungwi & Kendwa beaches|Swahili cooking class|Sunset dhow cruise',
  1900.00, '5 Days / 4 Nights', 15, 4.9, 221,
  'https://images.unsplash.com/photo-1568452618825-e3521b0cdf57?w=800&q=80&auto=format&fit=crop',
  1
),
(
  'Maasai Cultural Immersion Tour',
  'maasai-cultural-immersion-tour',
  'Ngorongoro',
  'Cultural Tour',
  'Live as a Maasai for three days. Stay in a traditional boma, learn to make fire with sticks, join the warriors on their cattle walk, and participate in the famous adumu jumping ceremony. An authentic, deeply moving experience.',
  'Authentic Maasai boma stay|Warrior training & adumu jump|Traditional food & medicine|Beadwork with Maasai women|Sunrise cattle walk|Community contribution included',
  1650.00, '3 Days / 2 Nights', 6, 5.0, 178,
  'https://images.unsplash.com/photo-1551632436-cbf8dd35adfa?w=800&q=80&auto=format&fit=crop',
  1
),
(
  'Tarangire Elephant Safari',
  'tarangire-elephant-safari',
  'Tarangire',
  'Wildlife Safari',
  'Home to Africa''s highest concentration of elephants, Tarangire''s ancient baobab trees and sweeping savannah create a dramatic backdrop for extraordinary wildlife encounters. Less crowded than the Northern Circuit''s flagship parks.',
  'Giant elephant herds|Ancient baobab trees|Tree-climbing lions|Excellent birdwatching|Fewer tourist vehicles|Night game drives available',
  2400.00, '4 Days / 3 Nights', 10, 4.8, 156,
  'https://images.unsplash.com/photo-1564760055775-d63b17a55c44?w=800&q=80&auto=format&fit=crop',
  1
);

-- Testimonials
INSERT INTO testimonials (customer_name, country, photo, rating, review, tour_name) VALUES
('Sarah Mitchell', 'United Kingdom', 'https://randomuser.me/api/portraits/women/44.jpg', 5, 'Jambo Masai Tours gave us the most extraordinary two weeks of our lives. Our guide Moses had an uncanny ability to find predators — we witnessed two lion kills and a cheetah hunt in seven days. The camp locations were breathtaking. Worth every penny.', 'Serengeti Great Migration Safari'),
('James & Linda Kowalski', 'United States', 'https://randomuser.me/api/portraits/men/32.jpg', 5, 'From the airport pickup to the final farewell, every detail was flawless. The Ngorongoro crater at dawn with only our Land Cruiser in sight is a memory I''ll carry forever. The team genuinely cares about you having a perfect experience.', 'Ngorongoro Crater Expedition'),
('Henrik Larsson', 'Sweden', 'https://randomuser.me/api/portraits/men/68.jpg', 5, 'Summit morning on Kilimanjaro was brutal and beautiful. Our lead guide Jonas kept my spirits high when altitude hit hard. When I finally stood at Uhuru Peak, tears just came. Jambo Masai made it happen. Forever grateful.', 'Kilimanjaro Summit Trek'),
('Akiko Tanaka', 'Japan', 'https://randomuser.me/api/portraits/women/65.jpg', 5, 'The Maasai cultural tour was life-changing. I expected a tourist show — instead I was welcomed as family. Dancing with the warriors, sleeping under the stars in the boma, learning their ancient knowledge. Real, raw, wonderful.', 'Maasai Cultural Immersion Tour'),
('Marco & Giulia Ferrari', 'Italy', 'https://randomuser.me/api/portraits/men/11.jpg', 5, 'We booked as a honeymoon and Jambo Masai surpassed every expectation. Zanzibar''s beaches are paradise, and the safari was pure adrenaline. The private sunrise game drive arranged as a surprise was magical. Grazie mille!', 'Zanzibar Beach & Spice Holiday'),
('David Okonkwo', 'Canada', 'https://randomuser.me/api/portraits/men/75.jpg', 5, 'Third safari with Jambo Masai, and they keep raising the bar. The new Tarangire itinerary is exceptional. Fifty elephants crossing the river as the sun set — I have 2,000 photos and still cannot choose a favourite. Already planning trip four.', 'Tarangire Elephant Safari'),
('Emma Schulz', 'Germany', 'https://randomuser.me/api/portraits/women/28.jpg', 5, 'Exceptional attention to detail. Our dietary requirements were handled perfectly at every camp. The guides are wildlife biologists, not just drivers — the depth of knowledge they share transforms the experience from sightseeing to true learning.', 'Serengeti Great Migration Safari'),
('Sophie & Luc Dupont', 'France', 'https://randomuser.me/api/portraits/women/56.jpg', 5, 'We came with a long list of dream sightings and Jambo Masai delivered every single one. The hospitality was warm, genuine and deeply African. This is not just a tour company — it is a family. We will return.', 'Ngorongoro Crater Expedition');

-- Gallery
INSERT INTO gallery (title, image, category) VALUES
('Lion Family at Sunset', 'https://images.unsplash.com/photo-1546182990-dffeafbe841d?w=600&q=80&auto=format&fit=crop', 'wildlife'),
('Elephant Herd Crossing', 'https://images.unsplash.com/photo-1564760055775-d63b17a55c44?w=600&q=80&auto=format&fit=crop', 'wildlife'),
('Serengeti Golden Plains', 'https://images.unsplash.com/photo-1547471080-7cc2caa01a7e?w=600&q=80&auto=format&fit=crop', 'landscapes'),
('Ngorongoro Crater Dawn', 'https://images.unsplash.com/photo-1523805009345-7448845a9e53?w=600&q=80&auto=format&fit=crop', 'landscapes'),
('Maasai Warriors Dancing', 'https://images.unsplash.com/photo-1551632436-cbf8dd35adfa?w=600&q=80&auto=format&fit=crop', 'culture'),
('Safari Land Cruiser', 'https://images.unsplash.com/photo-1504432842672-1a79f78e4084?w=600&q=80&auto=format&fit=crop', 'safari-life'),
('Zanzibar Turquoise Waters', 'https://images.unsplash.com/photo-1568452618825-e3521b0cdf57?w=600&q=80&auto=format&fit=crop', 'zanzibar'),
('African Fish Eagle', 'https://images.unsplash.com/photo-1550159930-40066082a4fc?w=600&q=80&auto=format&fit=crop', 'birds'),
('Kilimanjaro Summit', 'https://images.unsplash.com/photo-1521150932951-303a95503ed3?w=600&q=80&auto=format&fit=crop', 'landscapes'),
('Luxury Safari Camp', 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600&q=80&auto=format&fit=crop', 'safari-life'),
('Maasai Beadwork', 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=600&q=80&auto=format&fit=crop', 'culture'),
('Leopard in Acacia', 'https://images.unsplash.com/photo-1474511320723-9a56873867b5?w=600&q=80&auto=format&fit=crop', 'wildlife'),
('Zanzibar Stone Town', 'https://images.unsplash.com/photo-1582610285985-a42d9193f2fd?w=600&q=80&auto=format&fit=crop', 'zanzibar'),
('Hot Air Balloon Safari', 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=600&q=80&auto=format&fit=crop', 'safari-life'),
('Flamingos at Lake Manyara', 'https://images.unsplash.com/photo-1495542779398-9fec7dc7986c?w=600&q=80&auto=format&fit=crop', 'birds'),
('Maasai Village Life', 'https://images.unsplash.com/photo-1489392191049-fc10c97e64b6?w=600&q=80&auto=format&fit=crop', 'culture'),
('Baobab Trees at Dusk', 'https://images.unsplash.com/photo-1516026672322-bc52d61a55d5?w=600&q=80&auto=format&fit=crop', 'landscapes'),
('Cheetah on Watch', 'https://images.unsplash.com/photo-1509021436665-8f07dbf5bf1d?w=600&q=80&auto=format&fit=crop', 'wildlife');

-- Blog Posts
INSERT INTO blog_posts (title, slug, excerpt, content, image, author) VALUES
(
  'Best Time to Visit Tanzania: A Month-by-Month Guide',
  'best-time-to-visit-tanzania',
  'From the Great Migration to the long rains, timing your Tanzania safari perfectly makes all the difference. Our experts break down every month so you know exactly when to book.',
  '<p>Tanzania is a year-round destination, but knowing <strong>when</strong> to visit can transform a great safari into an absolutely extraordinary one.</p><h3>January – February: Short Dry Season</h3><p>Excellent game viewing with fewer tourists. Calving season in the southern Serengeti — thousands of wildebeest calves born daily, attracting large predator numbers.</p><h3>June – October: Peak Season</h3><p>The long dry season is arguably the best time for wildlife. The Great Migration river crossings peak in July–August. Clear skies, excellent photography light, and the thirsty wildlife congregate around waterholes.</p><h3>November – December: Short Rains</h3><p>Brief afternoon showers keep the landscape lush and green. Birdwatching peaks as migrants arrive. Fewer crowds and lower rates — a hidden gem period for budget-conscious travellers.</p><p>Our recommendation: <strong>July through October</strong> for the migration, <strong>January through February</strong> for calving season and value.</p>',
  'https://images.unsplash.com/photo-1547471080-7cc2caa01a7e?w=800&q=80&auto=format&fit=crop',
  'Moses Oloserian'
),
(
  'Safari Packing List: What to Bring and What to Leave Home',
  'safari-packing-list',
  'Overpacking is the number one mistake first-time safari-goers make. Our senior guides share the definitive list of what you actually need — and what will just weigh you down.',
  '<p>After guiding over 500 safaris, we''ve seen every packing mistake imaginable. Here is what actually matters.</p><h3>Clothing</h3><p>Neutral colours only — khaki, olive, beige and tan. Avoid bright colours and white. Layers are essential: mornings can be cold, afternoons hot. A good fleece and lightweight windproof jacket are non-negotiable.</p><h3>Optics</h3><p>Binoculars (8x42 or 10x42) are arguably more important than your camera. Spend well here. A 100-400mm lens transforms your wildlife photography.</p><h3>Health Essentials</h3><p>Malaria prophylaxis, insect repellent (DEET 50%+), high-SPF sunscreen, hand sanitiser, and any personal medications. Consult your doctor 6 weeks before departure.</p><h3>Leave at Home</h3><p>Formal wear, heavy books, excessive shoes, and — please — anything camouflage patterned (restricted in many parks).</p>',
  'https://images.unsplash.com/photo-1504432842672-1a79f78e4084?w=800&q=80&auto=format&fit=crop',
  'Sarah Kimani'
),
(
  'Zanzibar Travel Guide: Beyond the Beach',
  'zanzibar-travel-guide',
  'Zanzibar is famous for its beaches, but the island has layers of history, spice, and Swahili culture that most visitors barely scratch. Here is how to experience the real Zanzibar.',
  '<p>Most visitors spend their entire Zanzibar holiday horizontal on a beach. That is wonderful — but there is so much more to this Indian Ocean jewel.</p><h3>Stone Town: A Living UNESCO Site</h3><p>Lose yourself in the labyrinthine alleys of Stone Town, a UNESCO World Heritage Site. The Arab Fort, the House of Wonders, and the famous carved doors are highlights — but the real magic is simply wandering and letting the city reveal itself.</p><h3>Spice Farms</h3><p>Zanzibar earned the nickname "Spice Island" honestly. A morning at a working spice farm, where you taste cloves, vanilla, nutmeg, cardamom and cinnamon fresh from the source, is a sensory revelation.</p><h3>Mnemba Atoll</h3><p>For world-class snorkelling and diving, Mnemba Atoll is unmatched in the region. Sea turtles, spinner dolphins and an extraordinary diversity of reef fish await.</p><h3>When to Visit</h3><p>June–October and January–February are the best months. Avoid April–May (long rains).</p>',
  'https://images.unsplash.com/photo-1568452618825-e3521b0cdf57?w=800&q=80&auto=format&fit=crop',
  'Amina Hassan'
);

-- Sample bookings
INSERT INTO bookings (tour_id, first_name, last_name, email, phone, country, travel_date, travelers, special_requests, status) VALUES
(1, 'James', 'Anderson', 'james.anderson@email.com', '+1 555 123 4567', 'United States', '2026-07-15', 2, 'Celebrating 25th anniversary. Prefer the best river crossing viewpoints.', 'confirmed'),
(3, 'Erik', 'Bergström', 'erik.b@email.se', '+46 70 123 4567', 'Sweden', '2026-08-20', 1, 'Solo trek. Intermediate fitness level. Please advise on training.', 'pending'),
(4, 'Yuki', 'Nakamura', 'yuki.n@email.jp', '+81 90 1234 5678', 'Japan', '2026-09-10', 2, 'Vegetarian meals required for both guests.', 'confirmed'),
(2, 'Pierre', 'Dubois', 'pierre.d@email.fr', '+33 6 12 34 56 78', 'France', '2026-10-05', 4, 'Family with two teenage children. First safari.', 'pending'),
(6, 'Michael', 'Thompson', 'michael.t@email.au', '+61 400 123 456', 'Australia', '2026-11-01', 6, 'Photography group. We need extended morning drives.', 'confirmed');
