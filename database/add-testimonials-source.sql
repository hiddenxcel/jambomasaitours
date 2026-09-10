-- Adds review-platform tagging to testimonials, used by the homepage
-- reviews section and the new /reviews page to show Google/TripAdvisor
-- source chips and filters.
ALTER TABLE testimonials
  ADD COLUMN IF NOT EXISTS source VARCHAR(20) NOT NULL DEFAULT 'site' COMMENT 'site, google, tripadvisor',
  ADD COLUMN IF NOT EXISTS source_url VARCHAR(500) NULL COMMENT 'link to the original review';
