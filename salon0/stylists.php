<?php
require_once __DIR__ . '/includes/layout.php';
require_login();
$stylists = rows('SELECT u.id,u.name,p.* FROM users u JOIN stylist_profiles p ON p.user_id=u.id WHERE u.role="stylist" AND u.status=1 ORDER BY u.id');
site_header('Our Stylists', 'stylists');
?>
<section class="page-hero" style="--bg:url(<?= site_img('stylists') ?>)"><div class="container"><h1>Our Stylists</h1><p>Meet the artists behind your perfect look, with their working schedule and treatments.</p><div class="crumbs">HOME / STYLISTS</div></div></section>
<section class="section"><div class="container"><div class="grid g4">
  <?php foreach ($stylists as $s) stylist_card($s); ?>
</div></div></section>
<?php site_footer();
