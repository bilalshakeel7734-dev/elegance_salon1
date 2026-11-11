<?php
require_once __DIR__ . '/includes/layout.php';
require_login();
$stylists = (int)val('SELECT COUNT(*) FROM users WHERE role="stylist"'); $clients = (int)val('SELECT COUNT(*) FROM users WHERE role="client"'); $done = (int)val('SELECT COUNT(*) FROM appointments WHERE status="completed"');
site_header('About Us', 'about');
?>
<section class="page-hero" style="--bg:url(<?= site_img('about_hero') ?>)"><div class="container"><h1>About Elegance Salon</h1><p>A story of passion, precision and personal care.</p><div class="crumbs">HOME / ABOUT</div></div></section>
<section class="section"><div class="container split">
  <div class="rv rv-l"><span class="eyebrow">Our Story</span><h2 style="font-size:2.5rem;margin-bottom:16px">Beauty is an art, and we treat it that way</h2>
    <p class="muted">Elegance Salon is a popular beauty establishment offering hair styling, manicures, pedicures, facials and spa therapies. We started with a simple idea: every client deserves a personal, unhurried and beautifully executed experience.</p>
    <p class="muted mt">Today our team of expert stylists and therapists serves thousands of clients, and our online system keeps appointments, schedules and client preferences perfectly organised, so you can focus on feeling wonderful.</p>
    <ul class="tick-list"><li><?= icon('check') ?> Personalised consultation with every service</li><li><?= icon('check') ?> Hygiene-first, sterilised tools</li><li><?= icon('check') ?> Online booking with instant confirmation</li></ul></div>
  <div class="pic rv rv-r"><img src="<?= site_img('about',1000) ?>" alt="Salon" onerror="this.classList.add('img-fail')"></div>
</div></section>
<section class="section alt"><div class="container">
  <div class="grid g3 feats">
    <?php foreach ([['sparkle','Our Mission','To deliver exceptional beauty services that make every client feel confident, cared for and truly special.'],['eye','Our Vision','To be the most trusted and loved salon destination, known for artistry, hygiene and warm hospitality.'],['star','Our Values','Craftsmanship, integrity, kindness and continuous learning guide everything we do.']] as [$i,$t,$d]): ?>
    <div class="feat rv"><div class="fi"><?= icon($i, 30) ?></div><h3 style="margin-bottom:8px"><?= $t ?></h3><p class="muted small"><?= $d ?></p></div><?php endforeach; ?>
  </div>
</div></section>
<section class="section dark"><div class="container"><div class="grid g4 center">
  <?php foreach ([[12,'+','Years of Experience'],[$clients+9500,'+','Happy Clients'],[$stylists,'','Expert Stylists'],[$done+4200,'+','Treatments Done']] as [$n,$s,$l]): ?>
  <div class="rv"><b style="font-family:var(--serif);font-size:3rem;color:var(--gold)" data-count="<?= $n ?>" data-suf="<?= $s ?>">0</b><div><?= $l ?></div></div><?php endforeach; ?>
</div></div></section>
<section class="cta"><div class="container rv"><h2>Experience Elegance</h2><p>Your appointment is only a few clicks away.</p><a href="<?= url('user/book.php') ?>" class="btn btn-gold">Book Appointment</a></div></section>
<?php site_footer();
