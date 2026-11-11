<?php
require_once __DIR__ . '/includes/layout.php';
$u = require_login();
if (is_post()) {
    csrf_check();
    $n = post('name'); $em = post('email'); $sb = post('subject'); $m = post('message');
    if ($n && filter_var($em, FILTER_VALIDATE_EMAIL) && $m) { q('INSERT INTO messages (name,email,subject,message) VALUES (?,?,?,?)', [$n, $em, $sb, $m]); flash('success', 'Thank you! Your message has been sent. We will reply soon.'); }
    else flash('error', 'Please fill name, a valid email and your message.');
    redirect('contact.php');
}
site_header('Contact Us', 'contact');
?>
<section class="page-hero" style="--bg:url(<?= site_img('contact') ?>)"><div class="container"><h1>Contact Us</h1><p>We'd love to hear from you.</p><div class="crumbs">HOME / CONTACT</div></div></section>
<section class="section"><div class="container contact-grid">
  <div class="rv rv-l"><span class="eyebrow">Get In Touch</span><h2 style="font-size:2.2rem;margin-bottom:10px">Visit or write to us</h2>
    <div class="cinfo"><div class="fi"><?= icon('pin') ?></div><div><h4>Address</h4><p class="muted"><?= SITE_ADDRESS ?></p></div></div>
    <div class="cinfo"><div class="fi"><?= icon('phone') ?></div><div><h4>Phone</h4><p class="muted"><?= SITE_PHONE ?></p></div></div>
    <div class="cinfo"><div class="fi"><?= icon('mail') ?></div><div><h4>Email</h4><p class="muted"><?= SITE_EMAIL ?></p></div></div>
    <div class="cinfo"><div class="fi"><?= icon('clock') ?></div><div><h4>Opening Hours</h4><p class="muted">Mon - Sat: 10 AM - 9 PM<br>Sunday: 11 AM - 8 PM</p></div></div></div>
  <div class="card rv rv-r"><h3 style="font-size:1.6rem;margin-bottom:18px">Send us a message</h3>
    <form method="post" data-validate novalidate><?= csrf_field() ?>
      <div class="row2"><div class="fg"><label>Your Name <span class="req">*</span></label><input type="text" name="name" required value="<?= e($u['name'] ?? '') ?>"></div><div class="fg"><label>Email <span class="req">*</span></label><input type="email" name="email" required value="<?= e($u['email'] ?? '') ?>"></div></div>
      <div class="fg"><label>Subject</label><input type="text" name="subject"></div>
      <div class="fg"><label>Message <span class="req">*</span></label><textarea name="message" required></textarea></div>
      <button class="btn btn-gold btn-block"><?= icon('mail', 18) ?> Send Message</button></form></div>
</div></section>
<?php site_footer();
