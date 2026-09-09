-- ============================================================
-- Jambo Masai Tours — New tables added for:
--   - Itinerary PDF sharing/download system
--   - Group discount pricing tiers
--   - Optional tour add-ons (Balloon Safari, etc.)
--   - Admin-curated image library (AI photo matching)
--   - Multi-currency display (exchange rate cache)
--
-- SAFE TO RUN: every statement uses IF NOT EXISTS, so running this
-- on a database that already has some/all of these tables (e.g.
-- because the site already auto-created them on first page load)
-- will not error or lose data — it just skips what already exists.
--
-- NOTE: All of these tables also auto-create themselves the first
-- time their page is visited (CREATE TABLE IF NOT EXISTS is baked
-- into the PHP). Running this file by hand is optional — it just
-- lets you have everything ready immediately after deploy instead
-- of waiting for each admin page to be opened once.
-- ============================================================

-- Shared itinerary PDF download links
CREATE TABLE IF NOT EXISTS itinerary_shares (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    token           VARCHAR(64) NOT NULL UNIQUE,
    tour_id         INT NOT NULL,
    name            VARCHAR(150) DEFAULT '',
    email           VARCHAR(190) DEFAULT '',
    whatsapp        VARCHAR(40)  DEFAULT '',
    travelers       INT DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    price_per_person DECIMAL(10,2) DEFAULT 0,
    total_price     DECIMAL(10,2) DEFAULT 0,
    travel_date     DATE DEFAULT NULL,
    pdf_filename    VARCHAR(64)  DEFAULT '',
    view_count      INT NOT NULL DEFAULT 0,
    last_viewed_at  TIMESTAMP NULL,
    expires_at      TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tour (tour_id)
);

-- Rate-limit tracking for the itinerary request form (max 5/hour per IP)
CREATE TABLE IF NOT EXISTS itinerary_share_attempts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    ip         VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ip_time (ip, created_at)
);

-- Group-size discount tiers per tour (e.g. 4-6 people = 5% off)
CREATE TABLE IF NOT EXISTS tour_discount_tiers (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    tour_id          INT NOT NULL,
    min_people       INT NOT NULL,
    max_people       INT DEFAULT NULL,
    discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tour (tour_id)
);

-- Optional paid extras per tour (e.g. Balloon Safari $600pp)
CREATE TABLE IF NOT EXISTS tour_addons (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tour_id     INT NOT NULL,
    name        VARCHAR(150) NOT NULL,
    description VARCHAR(255) DEFAULT '',
    price       DECIMAL(10,2) NOT NULL DEFAULT 0,
    price_unit  VARCHAR(20) NOT NULL DEFAULT 'per_person',
    sort_order  INT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tour (tour_id)
);

-- Admin-curated photo library used by Groq AI to auto-pick images
-- matching each itinerary day's description (not tied to one tour).
CREATE TABLE IF NOT EXISTS tour_image_library (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    image       VARCHAR(500) NOT NULL,
    caption     VARCHAR(255) NOT NULL DEFAULT '',
    destination VARCHAR(200) DEFAULT '',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cached USD -> currency exchange rates (refreshed daily from a free API)
CREATE TABLE IF NOT EXISTS currency_rates (
    code        VARCHAR(3) PRIMARY KEY,
    rate_to_usd DECIMAL(12,6) NOT NULL,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
