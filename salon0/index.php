<?php
require_once __DIR__ . '/includes/layout.php';
if (!user()) redirect('login.php'); // guests cannot see landing/home
$services = rows('SELECT * FROM services WHERE active=1 ORDER BY id');
$svcPanels = [
  'women' => rows("SELECT * FROM services WHERE active=1 AND audience IN ('women','unisex') ORDER BY id LIMIT 6"),
  'men'   => rows("SELECT * FROM services WHERE active=1 AND audience IN ('men','unisex') ORDER BY CASE audience WHEN 'men' THEN 0 ELSE 1 END, id LIMIT 6"),
];
$stylists = rows('SELECT u.id,u.name,p.* FROM users u JOIN stylist_profiles p ON p.user_id=u.id WHERE u.role="stylist" AND u.status=1');
$reviews = rows('SELECT f.*,u.name FROM feedback f JOIN users u ON u.id=f.client_id WHERE f.rating>=4 AND f.comment<>"" ORDER BY f.id DESC LIMIT 3');
$u = user(); $go = fn($f) => $u ? url($f) : url('login.php?next=' . urlencode(url($f)));
site_header('Home', 'home', 'home-top');
?>
<section class="hero">
  <div class="hero-bg" style="background-image:url(<?= site_img('hero',2000) ?>)"></div>
  <div class="hero-orb"></div>
  <div class="container">
    <span class="eyebrow">Luxury Salon &amp; Spa</span>
    <h1><span class="w" style="--i:0">Where</span> <span class="w" style="--i:1">Beauty</span> <span class="w" style="--i:2">Meets</span> <em class="w" style="--i:3">Artistry</em></h1>
    <p>Hair styling, manicure, pedicure and signature facials by Karachi's finest stylists. Book your appointment online in under a minute.</p>
    <div class="hero-cta">
      <a href="<?= url('user/book.php') ?>" class="btn btn-gold"><?= icon('calendar', 18) ?> Book Appointment</a>
      <a href="<?= $go('services.php') ?>" class="btn btn-ghost" style="color:#fff;border-color:#fff">Explore Services</a>
    </div>
    <div class="hero-stats">
      <div><b data-count="12" data-suf="+">0</b><span>Years of Craft</span></div>
      <div><b data-count="9500" data-suf="+">0</b><span>Happy Clients</span></div>
      <div><b data-count="<?= count($stylists) ?>">0</b><span>Expert Stylists</span></div>
    </div>
  </div>
</section>

<div class="marquee" aria-hidden="true"><div class="marquee-in">
  <?php for ($k = 0; $k < 2; $k++) foreach ($services as $sv): ?><span><?= e($sv['name']) ?></span><?php endforeach; ?>
</div></div>

<section class="section about-sec">
  <div class="container split">
    <div class="pic rv rv-l">
      <div class="pic-img"><img src="<?= site_img('interior',1000) ?>" alt="Inside Elegance Salon" onerror="this.classList.add('img-fail')"></div>
      <span class="pic-dot d1"></span><span class="pic-dot d2"></span>
      <div class="badge-float"><b data-count="12" data-suf="+">12+</b>Years of Excellence</div>
    </div>
    <div class="rv rv-r">
      <span class="eyebrow">About Elegance</span>
      <h2 style="font-size:clamp(1.9rem,4vw,2.8rem);margin-bottom:16px">A sanctuary of style, crafted for you</h2>
      <p class="muted">Elegance Salon is one of Karachi's most loved beauty destinations. From precision hair styling to relaxing spa rituals, every visit is designed to leave you looking radiant and feeling renewed.</p>
      <ul class="tick-list"><li><?= icon('check') ?> Certified, experienced stylists</li><li><?= icon('check') ?> Premium, skin-safe products</li><li><?= icon('check') ?> Hygienic, relaxing environment</li><li><?= icon('check') ?> Easy online booking &amp; reminders</li></ul>
      <a href="<?= $go('about.php') ?>" class="btn btn-dark">Learn More <?= icon('arrow', 16) ?></a>
    </div>
  </div>
</section>

<section class="section alt svc-home">
  <div class="container">
    <div class="sec-head rv"><span class="eyebrow">Our Services</span><h2>Treatments You'll Love</h2><p>Hair, nails, skin, grooming and spa, tailored to your personal style, for women and men.</p></div>
    <div class="aud-wrap rv"><div class="aud-tabs"><span class="ind"></span><button type="button" class="on" data-aud="women">Women</button><button type="button" data-aud="men">Men</button></div></div>
    <?php foreach ($svcPanels as $k => $list): ?>
    <div class="grid g3 svc-panel" data-panel="<?= $k ?>"<?= $k === 'men' ? ' hidden' : '' ?>>
      <?php foreach ($list as $s): ?>
      <article class="svc rv" data-aud="<?= e($s['audience']) ?>">
        <div class="img"><?= img_tag($s['image'], $s['name']) ?><span class="tag"><?= e($s['category']) ?></span><?= aud_badge($s['audience']) ?></div>
        <div class="ico"><?= icon($s['icon'], 22) ?></div>
        <div class="bd"><h3><?= e($s['name']) ?></h3><p><?= e(excerpt($s['description'], 100)) ?></p>
          <div class="meta"><div><span class="price"><?= money($s['price']) ?></span><br><span class="dur"><?= icon('clock', 14) ?> <?= $s['duration'] ?> min</span></div><a href="<?= url('user/book.php?service=' . $s['id']) ?>" class="btn btn-gold btn-sm">Book</a></div></div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    <div class="center mt2"><a href="<?= $go('services.php?for=women') ?>" data-href="<?= $go('services.php') ?>" id="svcAll" class="btn btn-ghost">View All Services</a></div>
  </div>
</section>

<section class="section why">
  <span class="blob b1"></span><span class="blob b2"></span><span class="blob b3"></span>
  <div class="container">
    <div class="sec-head rv"><span class="eyebrow">Why Elegance</span><h2>The Elegance Difference</h2><p>Four reasons thousands of Karachi clients trust us with their look.</p></div>
    <div class="grid g4 feats">
      <?php foreach ([['sparkle','Premium Products','Only salon-grade, dermatologist-approved brands.'],['users','Expert Stylists','Trained professionals with years of experience.'],['calendar','Easy Booking','Pick your stylist, date and time in a few clicks.'],['bell','Smart Reminders','Instant confirmation and appointment updates.']] as [$i,$t,$d]): ?>
      <div class="feat rv"><div class="fi"><?= icon($i, 30) ?></div><h3 style="font-size:1.2rem;margin-bottom:8px"><?= $t ?></h3><p class="muted small"><?= $d ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section alt">
  <div class="container">
    <div class="sec-head rv"><span class="eyebrow">Meet The Team</span><h2>Our Master Stylists</h2></div>
    <div class="grid g4">
      <?php foreach ($stylists as $s) stylist_card($s); ?>
    </div>
  </div>
</section>

<?php if ($reviews): ?>
<section class="section dark testi-sec">
  <div class="container">
    <div class="sec-head rv"><span class="eyebrow">Testimonials</span><h2>What Our Clients Say</h2></div>
    <div class="grid g3">
      <?php foreach ($reviews as $r): ?>
      <div class="testi rv"><?= stars((int)$r['rating']) ?><p>"<?= e($r['comment']) ?>"</p><div class="by"><span class="avatar"><?= e(initials($r['name'])) ?></span><?= e($r['name']) ?></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="cta"><div class="container rv"><h2>Ready for your best look yet?</h2><p>Create a free account and reserve your appointment today.</p>
  <?php if ($u): ?><a href="<?= url('user/book.php') ?>" class="btn btn-gold">Book Appointment</a><?php else: ?><a href="<?= url('register.php') ?>" class="btn btn-gold">Create Free Account</a> <a href="<?= url('login.php') ?>" class="btn btn-light">Login</a><?php endif; ?></div></section>
<?php site_footer();
