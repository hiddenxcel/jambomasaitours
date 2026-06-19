USE jambo_masai_tours;

CREATE TABLE IF NOT EXISTS hero_slides (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  headline   VARCHAR(250) NOT NULL,
  subtitle   VARCHAR(400),
  btn1_text  VARCHAR(80),
  btn1_link  VARCHAR(300),
  btn2_text  VARCHAR(80),
  btn2_link  VARCHAR(300),
  sort_order TINYINT NOT NULL DEFAULT 0,
  active     TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS hero_video (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  video_url      VARCHAR(500),
  fallback_image VARCHAR(500),
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS page_intros (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_slug  VARCHAR(80) NOT NULL UNIQUE,
  title      VARCHAR(250) NOT NULL,
  subtitle   VARCHAR(400),
  bg_image   VARCHAR(500),
  active     TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- Seed: default hero video (fallback image)
INSERT IGNORE INTO hero_video (id, video_url, fallback_image) VALUES
(1, '', 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=1920&q=85&auto=format&fit=crop');

-- Seed: 2 hero slides
INSERT IGNORE INTO hero_slides (id, headline, subtitle, btn1_text, btn1_link, btn2_text, btn2_link, sort_order) VALUES
(1, 'Experience Tanzania Like Never Before', 'Luxury Safaris, Maasai Culture & Unforgettable Adventures', 'Explore Safaris', 'tours.php', 'Book Safari', 'booking.php', 1),
(2, 'Your Dream Holiday', 'Customized Tours & Safaris', 'Start Planning', 'booking.php', 'Get a Quote', 'https://kilimanjarotophikers.webyetu.online/contact', 2);

-- Seed: page intros for every page
INSERT IGNORE INTO page_intros (page_slug, title, subtitle, bg_image) VALUES
('tours',        'Our Safari Tours',      'Handcrafted journeys across Tanzania\'s most extraordinary wilderness areas', 'https://images.unsplash.com/photo-1547471080-7cc2caa01a7e?w=1920&q=80&auto=format&fit=crop'),
('destinations', 'Tanzania Destinations', 'From the Serengeti plains to Zanzibar\'s turquoise shores', 'https://images.unsplash.com/photo-1523805009345-7448845a9e53?w=1920&q=80&auto=format&fit=crop'),
('about',        'Our Story',             'A Maasai-founded safari company built on passion, culture and authenticity', 'https://images.unsplash.com/photo-1504432842672-1a79f78e4084?w=1920&q=80&auto=format&fit=crop'),
('gallery',      'Wildlife & Wonders',    'Moments captured across Tanzania\'s most breathtaking landscapes', 'https://images.unsplash.com/photo-1546182990-dffeafbe841d?w=1920&q=80&auto=format&fit=crop'),
('blog',         'Safari Travel Blog',    'Expert tips, guides and stories from our experienced safari team', 'https://images.unsplash.com/photo-1564760055775-d63b17a55c44?w=1920&q=80&auto=format&fit=crop'),
('booking',      'Book Your Safari',      'Secure your dream African adventure in three simple steps', 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=1920&q=80&auto=format&fit=crop'),
('contact',      'Get in Touch',          'Our safari specialists are ready to plan your perfect Tanzania journey', 'https://images.unsplash.com/photo-1551632436-cbf8dd35adfa?w=1920&q=80&auto=format&fit=crop');
