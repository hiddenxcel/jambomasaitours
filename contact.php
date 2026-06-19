<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';
require_once 'includes/mailer.php';

$errors = []; $success = false; $formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) { http_response_code(403); die('Invalid security token.'); }
    $formData = [
        'name'    => sanitizeInput($_POST['name']    ?? ''),
        'email'   => sanitizeEmail($_POST['email']   ?? ''),
        'phone'   => sanitizeInput($_POST['phone']   ?? ''),
        'subject' => sanitizeInput($_POST['subject'] ?? ''),
        'message' => sanitizeInput($_POST['message'] ?? ''),
    ];
    if (empty($formData['name']))    $errors[] = 'Name is required.';
    if (!$formData['email'])         $errors[] = 'A valid email address is required.';
    if (empty($formData['subject'])) $errors[] = 'Subject is required.';
    if (empty($formData['message'])) $errors[] = 'Message is required.';
    if (strlen($formData['message']) < 20) $errors[] = 'Message must be at least 20 characters.';
    if (empty($errors)) {
        getDB()->prepare("INSERT INTO contacts (name,email,phone,subject,message,status,created_at) VALUES (:name,:email,:phone,:subject,:message,'unread',NOW())")->execute($formData);
        unset($_SESSION[CSRF_TOKEN_NAME]);

        /* Send admin notification email */
        if (getSetting('notify_on_contact', '1') === '1') {
            emailContactAdmin($formData);
        }

        $success = true; $formData = [];
    }
}

$pageTitle       = 'Contact Jambo Masai Tours | Safari Experts in Arusha, Tanzania';
$pageDescription = 'Contact Jambo Masai Tours for safari inquiries. WhatsApp, email, phone — our team is always ready to help plan your perfect African adventure.';
$currentPage     = 'contact';
$csrfToken       = generateCsrfToken();
$canonicalUrl    = SITE_URL . '/contact.php';
$headExtra = '<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ContactPage",
  "name": "Contact Jambo Masai Tours",
  "url": "' . $canonicalUrl . '",
  "description": ' . json_encode($pageDescription) . ',
  "mainEntity": {
    "@type": "TravelAgency",
    "name": "Jambo Masai Tours",
    "url": "' . SITE_URL . '",
    "telephone": "+255659667271",
    "contactPoint": {
      "@type": "ContactPoint",
      "telephone": "+255659667271",
      "contactType": "customer service",
      "availableLanguage": ["English", "Swahili"],
      "areaServed": ["TZ", "KE"],
      "hoursAvailable": {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
        "opens": "07:00",
        "closes": "20:00"
      }
    },
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Arusha City Centre",
      "addressLocality": "Arusha",
      "addressCountry": "TZ"
    },
    "geo": {
      "@type": "GeoCoordinates",
      "latitude": -3.3988608,
      "longitude": 36.6715112
    }
  }
}
</script>';
require_once 'includes/dark_header.php';
?>

<!-- PAGE HERO -->
<div class="page-hero pt-[68px]" style="min-height:320px">
  <img src="<?= IMG_SERENGETI ?>" alt="Contact Jambo Masai Tours" fetchpriority="high"
       class="absolute inset-0 w-full h-full object-cover ken-burns" style="opacity:.3">
  <div class="absolute inset-0" style="background:linear-gradient(to bottom,rgba(10,10,10,.75),rgba(10,10,10,1))"></div>
  <div class="relative z-10 max-w-7xl mx-auto px-4 lg:px-6 pb-10 w-full">
    <nav class="flex items-center gap-2 font-nav text-[.65rem] text-white/35 mb-4">
      <a href="<?= url() ?>" class="hover:text-white transition-colors">Home</a><span>›</span>
      <span class="text-white/60">Contact</span>
    </nav>
    <div class="section-tag">Get in Touch</div>
    <h1 class="font-heading text-white font-bold" style="font-size:clamp(2rem,5vw,3rem)">
      Plan Your <span class="hero-grad">Safari</span>
    </h1>
    <p class="text-white/50 mt-2 max-w-lg text-[.92rem]">Our safari specialists are ready to craft your perfect Tanzania journey.</p>
  </div>
</div>

<!-- QUICK CONTACT CARDS -->
<section class="px-4 lg:px-0 -mt-6 relative z-10">
  <div class="max-w-7xl mx-auto">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 reveal">
      <?php foreach ([
        ['fab fa-whatsapp',      '#25D366','rgba(37,211,102,.12)', 'Chat on WhatsApp',  'Instant reply',        'https://wa.me/'.WHATSAPP_NUMBER.'?text='.urlencode('Hello! I\'d like to plan a safari.'), true],
        ['fas fa-phone',         '#34d399','rgba(16,185,129,.12)', 'Call Us',           e(SITE_PHONE),          'tel:'.e(SITE_PHONE), false],
        ['fas fa-envelope',      '#60a5fa','rgba(96,165,250,.12)', 'Email Us',          e(SITE_EMAIL),          'mailto:'.e(SITE_EMAIL), false],
        ['fas fa-map-marker-alt','#f59e0b','rgba(245,158,11,.12)', 'Our Office',        'Arusha, Tanzania',     'https://maps.app.goo.gl/s6Qvyg9nqs3ucMmj6', true],
      ] as $card): ?>
      <a href="<?= $card[5] ?>" <?= $card[6]?'target="_blank" rel="noopener"':'' ?>
         class="glass-card flex items-center gap-3 p-4 transition-all hover:scale-[1.03] group" style="text-decoration:none">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 transition-transform group-hover:scale-110" style="background:<?= $card[2] ?>">
          <i class="<?= $card[0] ?>" style="color:<?= $card[1] ?>;font-size:1rem"></i>
        </div>
        <div class="min-width:0">
          <div class="font-nav font-bold text-[.72rem] text-white/80 group-hover:text-white transition-colors"><?= $card[3] ?></div>
          <div class="text-white/35 text-[.65rem] truncate"><?= $card[4] ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CONTENT -->
<section class="py-16 px-4 lg:px-0">
  <div class="max-w-7xl mx-auto">
    <div class="grid lg:grid-cols-[1fr_1.4fr] gap-12">

      <!-- Contact info -->
      <div class="reveal">
        <h2 class="font-heading text-white text-2xl font-bold mb-6">We'd Love to Hear From You</h2>
        <p class="text-white/50 leading-relaxed mb-8 text-[.94rem]">
          Whether you have a question, want to request a custom itinerary, or simply want to chat
          about your African dream trip — we're here for you.
        </p>

        <div class="space-y-4 mb-8">
          <?php foreach ([
            ['fa-map-marker-alt','#34d399', 'Our Office',     'Arusha, Tanzania, 12105<br><a href="https://maps.app.goo.gl/cqcERfdGpABg9xo49" target="_blank" rel="noopener" style="color:#10b981;font-size:.78rem">View on Google Maps →</a>'],
            ['fa-phone',         '#fbbf24', 'Phone',          '<a href="tel:'.e(SITE_PHONE).'" class="hover:text-emerald-400 transition-colors">'.e(SITE_PHONE).'</a>'],
            ['fa-envelope',      '#60a5fa', 'Email',          '<a href="mailto:'.e(SITE_EMAIL).'" class="hover:text-emerald-400 transition-colors">'.e(SITE_EMAIL).'</a>'],
            ['fa-clock',         '#f97316', 'Response Time',  'We reply within 24 hours (usually much faster)'],
            ['fa-calendar-check','#a78bfa', 'Office Hours',   'Mon–Fri: 8am–6pm · Sat–Sun: 9am–4pm EAT'],
          ] as $c): ?>
          <div class="flex items-start gap-4 glass-card p-4">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:<?= $c[1] ?>15">
              <i class="fas <?= $c[0] ?> text-sm" style="color:<?= $c[1] ?>"></i>
            </div>
            <div>
              <div class="font-nav font-bold text-[.65rem] uppercase tracking-wider text-white/35 mb-0.5"><?= $c[2] ?></div>
              <div class="text-white/70 text-[.9rem]"><?= $c[3] ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>?text=<?= urlencode('Hello Jambo Masai Tours! I\'d like to plan a safari.') ?>"
           class="btn-em btn-em-wa w-full justify-center" target="_blank" rel="noopener">
          <i class="fab fa-whatsapp text-lg"></i> Chat Directly on WhatsApp
        </a>
      </div>

      <!-- Contact form -->
      <div class="glass-card p-7 reveal" style="transition-delay:100ms">
        <h3 class="font-heading text-white text-xl font-bold mb-6">Send Us a Message</h3>

        <?php if ($success): ?>
        <div class="rounded-xl p-5 mb-6 flex items-start gap-3" style="background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.2)">
          <i class="fas fa-check-circle text-emerald-400 text-lg flex-shrink-0"></i>
          <div>
            <p class="font-semibold text-emerald-400 text-sm">Message sent successfully!</p>
            <p class="text-white/50 text-[.83rem] mt-0.5">Thank you for reaching out. We'll reply within 24 hours.</p>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="rounded-xl p-4 mb-5 flex items-start gap-3" style="background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.2)">
          <i class="fas fa-exclamation-circle text-red-400 mt-0.5"></i>
          <ul class="space-y-1">
            <?php foreach ($errors as $err): ?>
            <li class="text-red-300/80 text-[.83rem]">· <?= e($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>

        <form method="POST" action="contact.php" novalidate>
          <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div><label class="f-label" for="c-name">Full Name <span>*</span></label>
              <input type="text" class="f-input" id="c-name" name="name" required placeholder="John Smith" value="<?= e($formData['name'] ?? '') ?>"></div>
            <div><label class="f-label" for="c-email">Email <span>*</span></label>
              <input type="email" class="f-input" id="c-email" name="email" required placeholder="john@example.com" value="<?= e($formData['email'] ?? '') ?>"></div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div><label class="f-label" for="c-phone">Phone / WhatsApp</label>
              <input type="tel" class="f-input" id="c-phone" name="phone" placeholder="+1 555 000 0000" value="<?= e($formData['phone'] ?? '') ?>"></div>
            <div><label class="f-label" for="c-subject">Subject <span>*</span></label>
              <select class="f-select" id="c-subject" name="subject" required>
                <option value="">— Select subject —</option>
                <?php foreach (['Safari Booking Inquiry','Custom Itinerary Request','Tour Information','Pricing Question','Kilimanjaro Trek','Zanzibar Holiday','General Question'] as $s): ?>
                <option value="<?= e($s) ?>" <?= ($formData['subject'] ?? '') === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="mb-5">
            <label class="f-label" for="c-message">Message <span>*</span></label>
            <textarea class="f-textarea" id="c-message" name="message" required
                      placeholder="Tell us about your dream safari — when, how many people, any special requirements?"><?= e($formData['message'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="btn-em btn-em-primary w-full justify-center">
            <i class="fas fa-paper-plane text-xs"></i> Send Message
          </button>
        </form>
      </div>

    </div>

    <!-- Google Maps -->
    <div class="mt-12 rounded-2xl overflow-hidden reveal" style="height:320px">
      <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3986.2!2d36.6715112!3d-3.3988608!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x183703d6b67422f3%3A0x66839739065310be!2sJAMBO+MASAI+TOURS!5e0!3m2!1sen!2stz!4v1748000000000"
              class="w-full h-full" style="filter:invert(.9) hue-rotate(180deg) saturate(.6) brightness(.8)"
              allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"
              title="Jambo Masai Tours — Arusha, Tanzania"></iframe>
    </div>
  </div>
</section>

<!-- WHY BOOK WITH US + FAQ -->
<section class="py-16 px-4 lg:px-0">
  <div class="max-w-7xl mx-auto">
    <div class="grid lg:grid-cols-2 gap-10">

      <!-- Why Book With Us -->
      <div class="reveal">
        <div class="section-tag">Why Choose Us</div>
        <h2 class="font-heading text-white text-2xl font-bold mb-8 mt-1">Why Book With <span class="hero-grad">Jambo Masai?</span></h2>
        <div class="space-y-4">
          <?php foreach ([
            ['fa-user-shield', '#10b981', 'Local Maasai Experts',      'Born and raised in Tanzania — unmatched wildlife instincts and cultural depth.'],
            ['fa-medal',       '#f59e0b', 'Best Price Guarantee',       'Direct booking, no middlemen. Transparent pricing with no hidden fees.'],
            ['fa-headset',     '#60a5fa', '24/7 Safari Support',        'We\'re with you from the moment you land to your safe departure home.'],
            ['fa-leaf',        '#34d399', 'Eco & Community First',       'Every booking supports Maasai education and wildlife conservation.'],
          ] as $w): ?>
          <div class="flex items-start gap-4 glass-card p-4 reveal">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:<?= $w[1] ?>15">
              <i class="fas <?= $w[0] ?>" style="color:<?= $w[1] ?>;font-size:.88rem"></i>
            </div>
            <div>
              <div class="font-nav font-bold text-[.78rem] text-white/85 mb-0.5"><?= $w[2] ?></div>
              <div class="text-white/45 text-[.8rem] leading-relaxed"><?= $w[3] ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- FAQ -->
      <div class="reveal" style="transition-delay:100ms">
        <div class="section-tag">FAQ</div>
        <h2 class="font-heading text-white text-2xl font-bold mb-8 mt-1">Common <span class="hero-grad">Questions</span></h2>
        <div class="space-y-3" id="faq-wrap">
          <?php foreach ([
            ['How far in advance should I book?',          'For peak season (June–October), book 3–6 months ahead. Other periods, 1–2 months is fine. Last-minute is possible but accommodation choices may be limited.'],
            ['Is a deposit required?',                     'Yes — 30% secures your booking. Balance is due 30 days before your safari. We accept bank transfer, PayPal, Visa/Mastercard, and M-Pesa.'],
            ['What is your cancellation policy?',          'Free cancellation up to 48 hours after booking. 60+ days before = 90% refund · 30–59 days = 70% · 14–29 days = 50% · Less than 14 days = no refund. Travel insurance is strongly recommended.'],
            ['Can you arrange airport pickup in Arusha?', 'Yes — complimentary pickup/drop-off at Kilimanjaro International Airport (JRO) for all safaris 3+ days. Arusha airport pickups are also available.'],
            ['Do I need a visa to visit Tanzania?',        'Most nationalities obtain a visa on arrival or via e-Visa (evisa.immigration.go.tz) for $50. We guide you through the process after booking.'],
          ] as $i => [$q, $a]): ?>
          <div class="glass-card overflow-hidden faq-item">
            <button class="w-full flex items-center justify-between gap-4 p-4 text-left" onclick="toggleFaqC(this)">
              <span class="font-nav font-semibold text-[.82rem] text-white/80"><?= $q ?></span>
              <span class="faq-icon-c w-7 h-7 rounded-full flex items-center justify-center text-emerald-400 font-bold flex-shrink-0 text-lg transition-all" style="background:rgba(16,185,129,.12)">+</span>
            </button>
            <div class="faq-ans-c" style="max-height:0;overflow:hidden;transition:max-height .4s cubic-bezier(.16,1,.3,1)">
              <p class="px-4 pb-4 text-white/50 text-[.83rem] leading-relaxed"><?= $a ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- WHATSAPP CTA BANNER -->
<section class="py-8 px-4 lg:px-0">
  <div class="max-w-7xl mx-auto">
    <div class="relative rounded-2xl overflow-hidden reveal" style="min-height:180px">
      <img src="<?= IMG_MAASAI ?>" alt="" class="absolute inset-0 w-full h-full object-cover" style="opacity:.25">
      <div class="absolute inset-0" style="background:linear-gradient(to right,rgba(10,10,10,.95),rgba(10,10,10,.7))"></div>
      <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-6 p-8">
        <div>
          <h3 class="font-heading text-white font-bold text-xl mb-1">Prefer a Quick Chat?</h3>
          <p class="text-white/50 text-[.88rem]">Our safari experts are online. Average response: under 2 minutes on WhatsApp.</p>
        </div>
        <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>?text=<?= urlencode('Jambo! I\'d like to plan a safari. Please help me!') ?>"
           target="_blank" rel="noopener"
           class="flex items-center gap-2 font-nav font-bold text-sm px-7 py-3.5 rounded-xl transition-all hover:scale-105 flex-shrink-0"
           style="background:#25D366;color:#fff;box-shadow:0 4px 20px rgba(37,211,102,.35)">
          <i class="fab fa-whatsapp text-xl"></i> Start WhatsApp Chat
        </a>
      </div>
    </div>
  </div>
</section>

<style>
.faq-item:hover{border-color:rgba(16,185,129,.2)}
</style>
<script>
function toggleFaqC(btn) {
  var item = btn.closest('.faq-item');
  var ans  = item.querySelector('.faq-ans-c');
  var icon = item.querySelector('.faq-icon-c');
  var open = ans.style.maxHeight && ans.style.maxHeight !== '0px';
  document.querySelectorAll('.faq-ans-c').forEach(function(a){a.style.maxHeight='0px';});
  document.querySelectorAll('.faq-icon-c').forEach(function(i){i.textContent='+';i.style.transform='';});
  if (!open) { ans.style.maxHeight = ans.scrollHeight + 'px'; icon.textContent = '−'; icon.style.transform = 'rotate(45deg)'; }
}
</script>

<?php require_once 'includes/dark_footer.php'; ?>
