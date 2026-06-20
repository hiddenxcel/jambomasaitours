<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/db.php';

$pageTitle    = 'Safari FAQ | Tanzania Travel Questions Answered — Jambo Masai Tours';
$pageDescription = 'Everything you need to know before your Tanzania safari. Visa, vaccinations, best time to visit, packing list, costs, safety, and more — answered by our safari experts.';
$currentPage  = 'about';
$canonicalUrl = SITE_URL . '/faq.php';
$ogImage      = IMG_SERENGETI;

$faqs = [
  'Planning Your Safari' => [
    ['q'=>'When is the best time to visit Tanzania for a safari?','a'=>'Tanzania offers year-round safari experiences, but the best time depends on your priorities. June–October (dry season) is peak season: wildlife concentrates at water sources, vegetation is sparse for better sightings, and the famous Mara River crossings happen. January–March is the calving season in the southern Serengeti — thousands of wildebeest calves born daily — with fewer crowds and lower rates. April–May (long rains) and November (short rains) offer the lowest prices and lush green landscapes.'],
    ['q'=>'How far in advance should I book a Tanzania safari?','a'=>'For peak season travel (June–October), we recommend booking 6–12 months in advance, especially for top-rated lodges and camps. For travel in January–March or November–May, 3–6 months is usually sufficient. Last-minute bookings are sometimes possible but limit your accommodation choices significantly. Kilimanjaro climbs should always be booked at least 3 months in advance.'],
    ['q'=>'How long should my Tanzania safari be?','a'=>'A minimum of 5–7 days is recommended to do Tanzania justice, allowing you to visit Serengeti and Ngorongoro Crater. For a more complete experience including Tarangire and a Zanzibar beach extension, 10–14 days is ideal. Kilimanjaro climbs require an additional 7–10 days. We tailor every itinerary to your exact budget and interests.'],
    ['q'=>'Is Tanzania safe for tourists?','a'=>'Yes — Tanzania is one of Africa\'s most stable and welcoming countries, consistently rated among the safest safari destinations. The main tourist areas (Arusha, Serengeti, Ngorongoro, Zanzibar) have a strong tourism infrastructure and very low crime rates directed at visitors. Standard travel precautions apply: secure your valuables, follow guide instructions, and purchase comprehensive travel insurance.'],
    ['q'=>'Can children go on a Tanzania safari?','a'=>'Absolutely — Tanzania is wonderful for family safaris. Most national parks welcome children of all ages on game drives. We recommend children aged 6+ for bush walks and 12+ for Kilimanjaro. Many luxury lodges have dedicated family accommodations and child-friendly safari programmes. We design bespoke family itineraries that keep children engaged and inspired.'],
  ],
  'Visas & Entry' => [
    ['q'=>'Do I need a visa to visit Tanzania?','a'=>'Most nationalities require a visa to enter Tanzania. Citizens of the USA, UK, EU countries, Canada, and Australia can obtain a single-entry Tourist Visa on arrival at Kilimanjaro International Airport or Julius Nyerere Airport (Dar es Salaam) for USD 50. We strongly recommend applying for an e-Visa before travel at eservices.immigration.go.tz to avoid queues on arrival. Zanzibar uses the same Tanzania visa.'],
    ['q'=>'Do I need a separate visa for Zanzibar?','a'=>'No — Zanzibar is part of Tanzania, so your single Tanzania visa covers the entire country including Zanzibar. You do not need any additional visa or permit to fly from the mainland to Zanzibar.'],
    ['q'=>'What vaccinations do I need for Tanzania?','a'=>'Yellow fever vaccination is required if you are arriving from a yellow fever endemic country. We strongly recommend being up to date with: Hepatitis A & B, Typhoid, Tetanus, and Meningitis vaccines. Malaria prophylaxis is essential for all Tanzania travel (consult your doctor about Malarone, Doxycycline or Lariam). Some visitors also take anti-diarrheal precautions. Consult a travel health clinic 6–8 weeks before departure.'],
  ],
  'Safari Experience' => [
    ['q'=>'What is the Great Migration?','a'=>'The Great Migration is the largest overland animal movement on Earth — approximately 1.5 million wildebeest, 250,000 zebra, and 500,000 other antelope move in a continuous circular route between Tanzania\'s Serengeti and Kenya\'s Maasai Mara, following rainfall and fresh grass. The famous Mara River crossings (July–October) — where wildebeest plunge into crocodile-filled waters — are the most dramatic single wildlife event on the continent. The migration is year-round, but each season offers different spectacles.'],
    ['q'=>'What is included in your safari packages?','a'=>'Our safari packages typically include: all accommodation (tented camps or lodges), full board meals, airport transfers, national park entry fees, game drives in 4WD safari vehicles with pop-up roofs, professional English-speaking guide, and park ranger fees. International flights, travel insurance, optional activities (balloon safari, walking safari, cultural visits), and personal expenses are not included. Each package is clearly itemised when you receive your quote.'],
    ['q'=>'What kind of vehicle will we use on safari?','a'=>'We use purpose-built 4WD Land Cruiser safari vehicles with pop-up roofs for 360° game viewing, comfortable seating for 2–6 guests, and charging points for devices. Each vehicle has a trained professional guide who is also a skilled off-road driver. Private vehicles mean you set your own pace — no shared vehicles with strangers on our private safaris.'],
    ['q'=>'Will we definitely see the Big Five?','a'=>'Tanzania offers excellent Big Five (lion, leopard, elephant, buffalo, black rhino) viewing. Lion and elephant are virtually guaranteed in Serengeti and Tarangire. Leopard requires patience — Seronera and Ngorongoro Crater are best. Buffalo are common throughout. Black rhino is best seen in Ngorongoro Crater (Tanzania\'s most reliable rhino population) and Moru Kopjes in Serengeti. While wildlife cannot be 100% guaranteed, our guides\' expertise maximises every sighting opportunity.'],
  ],
  'Kilimanjaro Trekking' => [
    ['q'=>'What is the easiest Kilimanjaro route?','a'=>'The Marangu Route (5–6 days) is often called the "Coca-Cola Route" for its relative accessibility and hut accommodation instead of tents. However, it has a lower summit success rate due to its fast ascent profile. For first-timers seeking a higher success rate, we recommend the Lemosho Route (7–8 days) which has excellent acclimatisation and scenic variety.'],
    ['q'=>'What is the summit success rate on Kilimanjaro?','a'=>'The overall Kilimanjaro summit success rate across all routes is approximately 65%. On longer routes with better acclimatisation (Lemosho 8 days, Northern Circuit 10 days), our success rate exceeds 85%. Acclimatisation is the single most important factor — we never rush ascents and always carry emergency oxygen.'],
    ['q'=>'Is Kilimanjaro technically difficult?','a'=>'No — Kilimanjaro does not require any technical climbing skills, ropes, or ice axes. It is a high-altitude trek with some steep sections. The primary challenge is altitude (5,895m) and the mental determination to push through summit night, which is cold, dark, and arduous. Good physical fitness, the right mental attitude, and choosing a long enough route are the keys to success.'],
  ],
  'Practical Information' => [
    ['q'=>'What currency is used in Tanzania?','a'=>'The Tanzanian Shilling (TZS) is the official currency. US Dollars are widely accepted in tourist areas, lodges, and for park fees. ATMs are available in Arusha, Dar es Salaam, and Zanzibar. We recommend bringing USD cash in small denominations (ideally printed after 2006). Credit cards are accepted at most lodges but connectivity can be unreliable in remote areas.'],
    ['q'=>'How do I get from Kilimanjaro Airport to Arusha?','a'=>'Kilimanjaro International Airport (JRO) is approximately 45 minutes from Arusha city centre. We provide all airport transfers for our clients — your guide will meet you at arrivals with a name board. Alternatively, shared shuttle buses (Riverside Shuttle) and taxis are available. Most international safari clients arrive at JRO rather than Dar es Salaam.'],
    ['q'=>'What should I pack for a Tanzania safari?','a'=>'Essential items: lightweight neutral-coloured clothing (khaki, olive, beige), warm layer for early morning drives, wide-brim hat, high-SPF sunscreen, quality sunglasses, binoculars, camera with zoom lens, insect repellent (DEET-based), personal medications, and a power bank. Avoid bright colours and white clothing on safari. Full packing lists are provided after booking.'],
    ['q'=>'What is the internet and phone signal like in the parks?','a'=>'Mobile signal (Vodacom, Airtel) is available in Arusha and most lodge areas, but is limited or absent inside national parks and on remote circuits. Most lodges offer Wi-Fi, though speeds vary. We recommend disconnecting and enjoying the wilderness — your phone will still be there when you return, and the Serengeti will not.'],
  ],
];

$schemaFaqs = [];
foreach ($faqs as $cat => $items) {
    foreach ($items as $f) {
        $schemaFaqs[] = ['@type'=>'Question','name'=>$f['q'],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f['a']]];
    }
}
$headExtra = '<script type="application/ld+json">
[
  {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": ' . json_encode($schemaFaqs, JSON_UNESCAPED_UNICODE) . '
  },
  {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
      { "@type": "ListItem", "position": 1, "name": "Home", "item": "' . SITE_URL . '" },
      { "@type": "ListItem", "position": 2, "name": "FAQ" }
    ]
  }
]
</script>';
require_once 'includes/dark_header.php';
?>

<!-- ═══ HERO ═══ -->
<div class="page-hero pt-[68px]" style="min-height:320px">
  <img src="<?= IMG_SERENGETI ?>" alt="Tanzania Safari FAQ" fetchpriority="high"
       class="absolute inset-0 w-full h-full object-cover ken-burns" style="opacity:.35" width="1920" height="600">
  <div class="absolute inset-0" style="background:linear-gradient(to top,#23362f 0%,rgba(10,10,10,.6) 50%,rgba(10,10,10,.2) 100%)"></div>
  <div class="relative z-10 max-w-4xl mx-auto px-4 lg:px-8 pb-14 pt-32">
    <nav class="flex items-center gap-2 font-nav text-[.62rem] text-white/35 mb-4">
      <a href="<?= url() ?>" class="hover:text-white transition-colors">Home</a><span>›</span>
      <span class="text-white/55">Safari FAQ</span>
    </nav>
    <div class="section-tag reveal"><i class="fas fa-question-circle mr-1"></i>Tanzania Safari FAQ</div>
    <h1 class="font-heading text-4xl md:text-5xl font-bold text-white mt-2 mb-3 reveal">
      Your Safari Questions, Answered
    </h1>
    <p class="text-white/50 text-lg max-w-2xl reveal" style="font-family:'Inter',sans-serif">
      Everything you need to know before booking your Tanzania adventure — from visa requirements and vaccinations to packing and wildlife.
    </p>
  </div>
</div>

<!-- ═══ FAQ CONTENT ═══ -->
<div class="max-w-4xl mx-auto px-4 lg:px-8 py-16">

  <!-- Category nav -->
  <div class="flex flex-wrap gap-2 mb-10 reveal">
    <?php $ci = 0; foreach ($faqs as $cat => $items): ?>
    <a href="#cat-<?= $ci ?>" style="font-family:'Montserrat',sans-serif;font-size:.65rem;font-weight:600;padding:.4rem .9rem;border-radius:999px;background:rgba(160,94,34,.08);border:1px solid rgba(160,94,34,.2);color:#a05e22;text-decoration:none;transition:all .2s" class="cat-link">
      <?= e($cat) ?>
    </a>
    <?php $ci++; endforeach; ?>
  </div>

  <?php $ci = 0; foreach ($faqs as $cat => $items): $fi = 0; ?>
  <section class="mb-12 reveal" id="cat-<?= $ci ?>">
    <h2 class="font-heading text-2xl font-bold text-white mb-6 flex items-center gap-3">
      <span style="width:6px;height:24px;background:linear-gradient(135deg,#a05e22,#7d4817);border-radius:3px;display:inline-block;flex-shrink:0"></span>
      <?= e($cat) ?>
    </h2>
    <div style="display:flex;flex-direction:column;gap:.55rem">
      <?php foreach ($items as $faq): $fid = 'f'.$ci.'_'.$fi; ?>
      <div style="background:#2c463d;border:1px solid rgba(255,255,255,.07);border-radius:14px;overflow:hidden">
        <button onclick="toggleQ('<?= $fid ?>')"
                style="width:100%;display:flex;align-items:center;justify-content:space-between;padding:1.1rem 1.25rem;background:none;border:none;cursor:pointer;text-align:left;gap:1rem">
          <span style="font-family:'Montserrat',sans-serif;font-size:.85rem;font-weight:600;color:#fff;line-height:1.4">
            <?= e($faq['q']) ?>
          </span>
          <i id="qi-<?= $fid ?>" class="fas fa-chevron-down" style="color:#a05e22;font-size:.65rem;flex-shrink:0;transition:transform .3s"></i>
        </button>
        <div id="qa-<?= $fid ?>" style="display:none;padding:0 1.25rem 1.1rem">
          <p style="font-size:.85rem;color:rgba(255,255,255,.5);line-height:1.8"><?= e($faq['a']) ?></p>
        </div>
      </div>
      <?php $fi++; endforeach; ?>
    </div>
  </section>
  <?php $ci++; endforeach; ?>

  <!-- Still have questions CTA -->
  <div class="glass-card p-8 text-center reveal mt-8">
    <div class="section-tag mx-auto mb-3"><i class="fas fa-envelope mr-1"></i>Still Have Questions?</div>
    <h2 class="font-heading text-2xl font-bold text-white mb-3">Talk to a Safari Expert</h2>
    <p class="text-white/45 mb-6" style="font-size:.88rem;max-width:460px;margin-left:auto;margin-right:auto">Our team is based in Arusha and answers every enquiry personally — no bots, no call centres.</p>
    <div class="flex flex-wrap gap-3 justify-center">
      <a href="<?= url('contact.php') ?>" class="btn-em btn-em-primary">
        <i class="fas fa-envelope" style="font-size:.7rem"></i> Send a Message
      </a>
      <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>" class="btn-em btn-em-wa" target="_blank" rel="noopener">
        <i class="fab fa-whatsapp" style="font-size:.8rem"></i> WhatsApp Now
      </a>
    </div>
  </div>
</div>

<script>
function toggleQ(id) {
  var body = document.getElementById('qa-' + id);
  var icon = document.getElementById('qi-' + id);
  var open = body.style.display !== 'none' && body.style.display !== '';
  body.style.display = open ? 'none' : 'block';
  icon.style.transform = open ? '' : 'rotate(180deg)';
}
document.querySelectorAll('.cat-link').forEach(function(a){
  a.addEventListener('mouseover', function(){ this.style.background='rgba(160,94,34,.15)'; });
  a.addEventListener('mouseout',  function(){ this.style.background='rgba(160,94,34,.08)'; });
});
</script>

<?php require_once 'includes/dark_footer.php'; ?>
