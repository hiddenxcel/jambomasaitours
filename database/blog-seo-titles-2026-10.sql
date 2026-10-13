-- ============================================================================
-- Blog: fupisha SEO Titles (<= 60 herufi) na urekebishe makosa
-- Tarehe: 2026-10-03
--
-- Namna ya kuendesha (production):
--   cPanel -> phpMyAdmin -> chagua database ya tovuti -> tab "SQL" (au "Import")
--   -> bandika/chagua faili hii -> "Go".
--
-- Ni salama kuendesha mara nyingi (idempotent): kila UPDATE inatumia slug ya
-- makala, haifuti chochote, na inabadilisha SEO Title (na Meta Description ya
-- makala moja) tu. Kichwa cha makala (title) na maudhui havibadilishwi.
-- ============================================================================

-- 5: SEO Title ilikuwa ya makala ya Kilimanjaro (ilinakiliwa kimakosa)
UPDATE blog_posts SET seo_title = 'Experience Tanzania Like Never Before | Jambo Masai Tours'
 WHERE slug = 'experience-tanzania-like-never-before-with-jambo-masai-tours';

-- 6: Kilimanjaro trek
UPDATE blog_posts SET seo_title = 'Kilimanjaro Trek Tips: Altitude, Safety & Packing'
 WHERE slug = 'important-information-for-your-mount-kilimanjaro-trek';

-- 7: first-time visitors
UPDATE blog_posts SET seo_title = 'First-Time Tanzania Safari Guide 2026 & 2027'
 WHERE slug = 'best-tanzania-safari-for-first-time-visitors-a-complete-guide';

-- 14
UPDATE blog_posts SET seo_title = 'Tanzania Safaris: Serengeti & Kilimanjaro | Jambo Masai'
 WHERE slug = 'tanzania-safari-adventures-with-jambo-masai-tours';

-- 17: packing list (pia Meta Description ilikuwa herufi 168, sasa < 160)
UPDATE blog_posts
   SET seo_title = 'Tanzania Safari Packing List 2026 & 2027',
       meta_description = 'Tanzania safari packing list 2026 & 2027: what clothes, gear, documents and essentials to bring for a comfortable African safari.'
 WHERE slug = 'tanzania-safari-packing-list-2026-what-to-pack-for-the-perfect-african-safari';

-- 18
UPDATE blog_posts SET seo_title = 'Tanzania Wildlife, Culture & Wonders | Jambo Masai'
 WHERE slug = 'discover-tanzania-a-journey-through-wildlife-culture-and-natural-wonders';

-- 19
UPDATE blog_posts SET seo_title = 'Private & Luxury Tanzania Safaris | Jambo Masai'
 WHERE slug = 'experience-the-best-tanzania-safari-with-jambo-masai-tours-private-luxury-safaris';

-- 20
UPDATE blog_posts SET seo_title = 'Tanzania Vaccination Requirements for Tourists'
 WHERE slug = 'tanzania-vaccination-requirements-for-tourists-yellow-fever-malaria-travel-health-guide';

-- 21
UPDATE blog_posts SET seo_title = 'Tanzania SIM Cards & Internet for Tourists'
 WHERE slug = 'tanzania-sim-cards-internet-for-tourists-complete-guide-to-staying-connected';

-- 23
UPDATE blog_posts SET seo_title = 'Tanzania Safari Lodges & Camps: What to Expect'
 WHERE slug = 'accommodation-guide-for-your-tanzania-safari-what-to-expect-and-how-to-choose-the-right-stay';

-- 26
UPDATE blog_posts SET seo_title = 'Sustainable Safaris in Tanzania | Jambo Masai'
 WHERE slug = 'sustainable-safaris-how-wildlife-conservation-protects-tanzanias-ecosystems';

-- 27
UPDATE blog_posts SET seo_title = 'Tanzania Safari & Zanzibar Beach Holiday Guide'
 WHERE slug = 'bush-to-beach-combining-a-tanzania-safari-and-zanzibar-vacation';

-- Ukaguzi (hiari): lazima isionyeshe safu yoyote ya makala hizi yenye SEO Title > 60
-- SELECT id, CHAR_LENGTH(seo_title) AS len, seo_title FROM blog_posts WHERE CHAR_LENGTH(seo_title) > 60;
