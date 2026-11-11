<?php
require_once __DIR__ . '/includes/layout.php';
if ($u = user()) redirect(home_for($u));
$errors = [];
if (is_post()) {
    csrf_check();
    $name = post('name'); $username = strtolower(post('username')); $email = strtolower(post('email')); $phone = post('phone'); $pw = $_POST['password'] ?? ''; $cf = $_POST['confirm'] ?? '';
    if (strlen($name) < 3) $errors['name'] = 'Please enter your full name.';
    if (!preg_match('/^[a-z0-9_.]{3,30}$/', $username)) $errors['username'] = 'Username: 3-30 letters, numbers, dot or underscore.';
    elseif (val('SELECT 1 FROM users WHERE username=?', [$username])) $errors['username'] = 'This username is already taken.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    elseif (val('SELECT 1 FROM users WHERE email=?', [$email])) $errors['email'] = 'This email is already registered.';
    if (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) $errors['phone'] = 'Enter a valid phone number.';
    if (strlen($pw) < 6) $errors['password'] = 'Password must be at least 6 characters.';
    if ($pw !== $cf) $errors['confirm'] = 'Passwords do not match.';
    if (!$errors) {
        q('INSERT INTO users (name,username,email,phone,password,role) VALUES (?,?,?,?,?,?)', [$name, $username, $email, $phone, password_hash($pw, PASSWORD_DEFAULT), 'client']);
        $id = insert_id(); notify($id, 'Welcome to Elegance Salon', 'Your account is ready. Book your first appointment today!', 'info');
        login_user(row('SELECT * FROM users WHERE id=?', [$id])); flash('success', 'Account created successfully. Welcome!'); redirect('user/dashboard.php');
    }
}
$old = fn($k) => e(post($k)); $er = fn($k) => isset($errors[$k]) ? '<span class="err">' . e($errors[$k]) . '</span>' : '';
head_html('Create Account', 'authpage'); render_flashes();
?>
<div class="auth">
  <div class="auth-art" style="--bg:url(<?= site_img('register') ?>)">
    <a href="<?= url('login.php') ?>" class="brand"><span class="brand-mark"><?= icon('scissors', 22) ?></span><span>Elegance<small>Salon &amp; Spa</small></span></a><div class="orb"></div>
    <h2>Join the Elegance family</h2><p>Create your free account to unlock the website, explore our services, meet our stylists and book appointments online.</p>
    <ul><li><?= icon('check') ?> Access services &amp; stylists</li><li><?= icon('check') ?> Instant booking confirmation</li><li><?= icon('check') ?> Reschedule or cancel anytime</li></ul>
  </div>
  <div class="auth-form"><div class="auth-box">
    <h1>Create Account</h1><p>It only takes a minute.</p>
    <form method="post" data-validate novalidate><?= csrf_field() ?>
      <div class="fg"><label>Full Name <span class="req">*</span></label><div class="inp-ic"><?= icon('user', 18) ?><input type="text" name="name" required value="<?= $old('name') ?>"></div><?= $er('name') ?></div>
      <div class="row2"><div class="fg"><label>Username <span class="req">*</span></label><input type="text" name="username" required value="<?= $old('username') ?>"><?= $er('username') ?></div>
      <div class="fg"><label>Phone <span class="req">*</span></label><input type="tel" name="phone" required value="<?= $old('phone') ?>" placeholder="03xx-xxxxxxx"><?= $er('phone') ?></div></div>
      <div class="fg"><label>Email <span class="req">*</span></label><div class="inp-ic"><?= icon('mail', 18) ?><input type="email" name="email" required value="<?= $old('email') ?>"></div><?= $er('email') ?></div>
      <div class="row2"><div class="fg"><label>Password <span class="req">*</span></label><input type="password" name="password" required data-strength="#st"><div class="strength" id="st"><i></i></div><?= $er('password') ?></div>
      <div class="fg"><label>Confirm Password <span class="req">*</span></label><input type="password" name="confirm" required><?= $er('confirm') ?></div></div>
      <button class="btn btn-gold btn-block"><?= icon('check', 18) ?> Create Account</button>
    </form>
    <div class="auth-alt">Already a member? <a href="<?= url('login.php') ?>">Login</a></div>
  </div></div>
</div>
<script src="<?= asset('assets/js/app.js') ?>"></script>
<script src="<?= asset('assets/js/animations.js') ?>"></script></body></html>
