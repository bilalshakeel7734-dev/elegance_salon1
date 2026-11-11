<?php
require_once __DIR__ . '/includes/layout.php';
if ($u = user()) redirect(home_for($u));
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '');
if (is_post()) {
    csrf_check();
    $_SESSION['tries'] = $_SESSION['tries'] ?? 0;
    if ($_SESSION['tries'] >= 8 && time() < ($_SESSION['lock'] ?? 0)) flash('error', 'Too many attempts. Please wait a minute and try again.');
    else {
        $l = post('login'); $u = row('SELECT * FROM users WHERE (email=? OR username=?) AND status=1', [$l, $l]);
        if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
            $_SESSION['tries'] = 0; login_user($u); flash('success', 'Welcome back, ' . explode(' ', $u['name'])[0] . '!');
            redirect($next && !is_staff($u) ? $next : home_for($u));
        }
        $_SESSION['tries']++; if ($_SESSION['tries'] >= 8) $_SESSION['lock'] = time() + 60;
        flash('error', 'Invalid username/email or password.');
    }
}
head_html('Login', 'authpage'); render_flashes();
?>
<div class="auth">
  <div class="auth-art" style="--bg:url(<?= site_img('login') ?>)">
    <a href="<?= url('login.php') ?>" class="brand"><span class="brand-mark"><?= icon('scissors', 22) ?></span><span>Elegance<small>Salon &amp; Spa</small></span></a><div class="orb"></div>
    <h2>Welcome back to Elegance</h2><p>Login to book appointments, manage your schedule and enjoy a personalised salon experience.</p>
    <ul><li><?= icon('check') ?> Book in under a minute</li><li><?= icon('check') ?> Choose your favourite stylist</li><li><?= icon('check') ?> Track history &amp; invoices</li></ul>
  </div>
  <div class="auth-form"><div class="auth-box">
    <h1>Secure Login</h1><p>Enter your username or email and password.</p>
    <form method="post" data-validate novalidate><?= csrf_field() ?><input type="hidden" name="next" value="<?= e($next) ?>">
      <div class="fg"><label>Username or Email</label><div class="inp-ic"><?= icon('user', 18) ?><input type="text" name="login" required autofocus placeholder="you@example.com" value="<?= e(post('login')) ?>"></div></div>
      <div class="fg"><label>Password</label><div class="inp-ic"><?= icon('lock', 18) ?><input type="password" name="password" required placeholder="Your password"><button type="button" class="eye" tabindex="-1"><?= icon('eye', 18) ?></button></div></div>
      <button class="btn btn-gold btn-block"><?= icon('arrow', 18) ?> Login</button>
    </form>
    <div class="auth-alt">New to Elegance? <a href="<?= url('register.php') ?>">Create an account</a></div>
    <div class="demo"><b>Demo accounts (click to fill)</b>
      <button type="button" data-fill="client|client123">Client</button><button type="button" data-fill="admin|admin123">Admin</button><button type="button" data-fill="reception|reception123">Receptionist</button><button type="button" data-fill="sophia|stylist123">Stylist</button></div>
  </div></div>
</div>
<script src="<?= asset('assets/js/app.js') ?>"></script>
<script src="<?= asset('assets/js/animations.js') ?>"></script></body></html>
