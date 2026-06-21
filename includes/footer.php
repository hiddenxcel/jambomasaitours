<?php
// Newsletter form handling
$newsletterMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newsletter_email'])) {
  $email = sanitizeEmail($_POST['newsletter_email'] ?? '');
  if ($email) {
    $newsletterMsg = 'success';
  } else {
    $newsletterMsg = 'error';
  }
}
?>

<!-- ─── FOOTER ─────────────────────────────────────── -->
<footer class="footer" id="footer">
  <div class="container">
    <div class="footer-top">

      <!-- Brand col -->
      <div class="footer-brand">
        <div class="footer-brand__name">Jambo Masai Tours</div>
        <div class="footer-brand__tagline">Tanzania Safari Experts</div>
        <p class="footer-brand__desc">
          Award-winning luxury safari operator based in Tanzania. We craft bespoke African adventures
          that combine world-class wildlife encounters, authentic cultural immersion, and unmatched
          hospitality. Est. 2010.
        </p>
        <div class="footer-social">
          <a href="<?= e(getSetting('social_facebook','#')) ?>" class="footer-social__link" aria-label="Facebook" target="_blank" rel="noopener">&#xf09a;</a>
          <a href="<?= e(getSetting('social_instagram','#')) ?>" class="footer-social__link" aria-label="Instagram" target="_blank" rel="noopener">&#x1F4F7;</a>
          <a href="<?= e(getSetting('social_twitter','#')) ?>" class="footer-social__link" aria-label="Twitter" target="_blank" rel="noopener">&#x1D54F;</a>
          <a href="<?= e(getSetting('social_youtube','#')) ?>" class="footer-social__link" aria-label="YouTube" target="_blank" rel="noopener">&#x25B6;</a>
          <a href="<?= e(getSetting('social_tripadvisor','#')) ?>" class="footer-social__link" aria-label="TripAdvisor" target="_blank" rel="noopener">&#9733;</a>
        </div>
      </div>

      <!-- Quick links -->
      <div>
        <div class="footer-col__title">Quick Links</div>
        <ul class="footer-links">
          <li><a href="<?= url() ?>"               class="footer-link">Home</a></li>
          <li><a href="<?= url('about') ?>"    class="footer-link">About Us</a></li>
          <li><a href="<?= url('tours') ?>"    class="footer-link">Safari Tours</a></li>
          <li><a href="<?= url('destinations') ?>" class="footer-link">Destinations</a></li>
          <li><a href="<?= url('gallery') ?>"  class="footer-link">Gallery</a></li>
          <li><a href="<?= url('blog') ?>"     class="footer-link">Travel Blog</a></li>
          <li><a href="<?= url('contact') ?>"  class="footer-link">Contact Us</a></li>
          <li><a href="<?= url('booking') ?>"  class="footer-link">Book Safari</a></li>
        </ul>
      </div>

      <!-- Contact col -->
      <div>
        <div class="footer-col__title">Contact</div>
        <div class="footer-contact-item">
          <span class="footer-contact-item__icon">📍</span>
          <span>Arusha, Tanzania<br>Near Mount Meru, Northern Circuit</span>
        </div>
        <div class="footer-contact-item">
          <span class="footer-contact-item__icon">📞</span>
          <a href="tel:<?= e(SITE_PHONE) ?>"><?= e(SITE_PHONE) ?></a>
        </div>
        <div class="footer-contact-item">
          <span class="footer-contact-item__icon">✉️</span>
          <a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a>
        </div>
        <div class="footer-contact-item">
          <span class="footer-contact-item__icon">💬</span>
          <a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" target="_blank" rel="noopener">
            Chat on WhatsApp
          </a>
        </div>
      </div>

      <!-- Newsletter col -->
      <div>
        <div class="footer-col__title">Newsletter</div>
        <p style="font-size:.875rem;color:rgba(255,255,255,.55);margin-bottom:var(--space-4);line-height:1.7;">
          Get safari tips, best deals and wildlife stories delivered to your inbox.
        </p>
        <?php if ($newsletterMsg === 'success'): ?>
        <p style="color:var(--color-gold);font-size:.875rem;">&#10003; Subscribed! Thank you.</p>
        <?php else: ?>
        <form class="newsletter-form" method="POST" action="#footer">
          <input type="email" name="newsletter_email" class="newsletter-input"
                 placeholder="Your email address" required
                 aria-label="Email address for newsletter">
          <button type="submit" class="newsletter-btn" aria-label="Subscribe">&#x27A4;</button>
        </form>
        <?php if ($newsletterMsg === 'error'): ?>
        <p style="color:#f87171;font-size:.78rem;margin-top:.5rem;">Please enter a valid email.</p>
        <?php endif; ?>
        <?php endif; ?>
      </div>

    </div>

    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved. &middot; Built by <span style="color:var(--color-gold)">hiddenxcel</span></span>
      <span>
        <a href="#">Privacy Policy</a> &nbsp;·&nbsp;
        <a href="#">Terms of Service</a> &nbsp;·&nbsp;
        <a href="<?= url('admin/login.php') ?>">Admin</a>
      </span>
      <span>Crafted with ❤ in Tanzania</span>
    </div>
  </div>
</footer>

<!-- WhatsApp Float -->
<div id="whatsapp-float" class="whatsapp-float" aria-label="Chat on WhatsApp">
  <a class="whatsapp-float__btn"
     href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>?text=Hello%20Jambo%20Masai%20Tours%2C%20I%27m%20interested%20in%20booking%20a%20safari."
     target="_blank" rel="noopener"
     aria-label="Open WhatsApp chat">
    <span aria-hidden="true">💬</span>
    <span class="whatsapp-float__tooltip">Chat with us!</span>
  </a>
</div>

<!-- Back to Top -->
<button id="back-to-top" class="back-to-top" aria-label="Back to top">&#8679;</button>

<!-- Lightbox -->
<div id="lightbox" class="lightbox" role="dialog" aria-modal="true" aria-label="Image viewer">
  <button class="lightbox__close" aria-label="Close">&#10005;</button>
  <img class="lightbox__image" src="" alt="">
</div>

<!-- Scripts -->
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js" defer></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"       defer></script>
<script src="<?= SITE_URL ?>/assets/js/animations.js" defer></script>
<?php if (!empty($loadBookingJS)): ?>
<script src="<?= SITE_URL ?>/assets/js/booking.js" defer></script>
<?php endif; ?>

</body>
</html>
