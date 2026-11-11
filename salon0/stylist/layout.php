<?php
require_once __DIR__ . '/functions.php';

function head_html(string $title, string $bodyClass = ''): void {
    // Move page-level inline scripts after app.js so helpers (\$\$, Calendar, openModal...) exist when they run.
    ob_start(function ($buf) {
        if (!preg_match_all('#<script>(.*?)</script>#s', $buf, $m)) return $buf;
        $buf = preg_replace('#<script>(.*?)</script>#s', '', $buf);
        $pos = strripos($buf, '</body>');
        return $pos === false ? $buf . implode('', $m[0]) : substr($buf, 0, $pos) . implode("\n", $m[0]) . "\n" . substr($buf, $pos);
    });
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | <?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/animations.css') ?>">
</head>
<body class="<?= e($bodyClass) ?>" data-base="<?= e(BASE_URL) ?>">
<div id="preloader"><div class="pl-ring"></div><span>Elegance</span></div>
<?php
}

function render_flashes(): void {
    $f = flashes(); if (!$f) return;
    echo '<div class="toasts">';
    foreach ($f as [$t, $m]) echo '<div class="toast t-' . e($t) . '">' . ($t === 'success' ? icon('check', 18) : icon('alert', 18)) . '<span>' . e($m) . '</span><button class="toast-x" aria-label="close">&times;</button></div>';
    echo '</div>';
}

function site_header(string $title, string $active = '', string $extra = ''): void {
    head_html($title, trim('site ' . $extra));
    $u = user();
    $lock = false;
    $all = ['index.php' => ['Home', 'home', false], 'about.php' => ['About', 'about', true], 'services.php' => ['Services', 'services', true], 'stylists.php' => ['Stylists', 'stylists', true], 'contact.php' => ['Contact', 'contact', false]];
    $items = $u ? $all : []; // guests: no nav pages until login/register
    ?>
<header class="nav" id="nav">
  <div class="container nav-in">
    <a href="<?= url($u ? 'index.php' : 'login.php') ?>" class="brand"><span class="brand-mark"><?= icon('scissors', 22) ?></span><span>Elegance<small>Salon &amp; Spa</small></span></a>
    <nav class="menu" id="menu">
      <?php foreach ($items as $f => [$label, $key, $prot]): $href = ($prot && $lock) ? url('login.php?next=' . urlencode(url($f))) : url($f); ?>
        <a href="<?= $href ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= $label ?><?= ($prot && $lock) ? icon('lock', 13) : '' ?></a>
      <?php endforeach; ?>
      <div class="menu-actions">
      <?php if ($u): ?>
        <a href="<?= url('user/book.php') ?>" class="btn btn-gold btn-sm"><?= icon('calendar', 16) ?> Book Now</a>
        <div class="dd">
          <button class="avatar-btn" type="button"><span class="avatar"><?= e(initials($u['name'])) ?></span><span class="hide-sm"><?= e(explode(' ', $u['name'])[0]) ?></span></button>
          <div class="dd-menu">
            <a href="<?= url(home_for($u)) ?>"><?= icon('home', 16) ?> Dashboard</a>
            <?php if (!is_staff($u)): ?><a href="<?= url('user/appointments.php') ?>"><?= icon('calendar', 16) ?> My Appointments</a><?php endif; ?>
            <a href="<?= url(is_staff($u) ? 'admin/profile.php' : 'user/profile.php') ?>"><?= icon('settings', 16) ?> Profile Settings</a>
            <a href="<?= url('logout.php') ?>"><?= icon('logout', 16) ?> Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= url('login.php') ?>" class="btn btn-ghost btn-sm">Login</a>
        <a href="<?= url('register.php') ?>" class="btn btn-gold btn-sm">Sign Up</a>
      <?php endif; ?>
      </div>
    </nav>
    <button class="burger" id="burger" aria-label="Menu"><?= icon('menu', 26) ?></button>
  </div>
</header>
<?php render_flashes();
}

function stylist_card(array $s): void {
    $svcs = rows('SELECT sv.name FROM stylist_services ss JOIN services sv ON sv.id=ss.service_id WHERE ss.stylist_id=? AND sv.active=1 LIMIT 4', [$s['id']]);
    $rt = row('SELECT ROUND(AVG(f.rating),1) AS a, COUNT(*) AS n FROM feedback f JOIN appointments ap ON ap.id=f.appointment_id WHERE ap.stylist_id=?', [$s['id']]);
    $today = in_array(date('N'), explode(',', (string)$s['work_days']));
    $first = explode(' ', $s['name'])[0]; ?>
  <article class="stl stl2 rv"><div class="sp-in">
    <div class="sp-photo"><?= img_tag($s['photo'], $s['name']) ?></div>
    <span class="sp-exp"><?= icon('crown', 14) ?> <?= (int)$s['experience'] ?> yrs</span>
    <span class="sp-live <?= $today ? 'on' : 'off' ?>"><i></i><?= $today ? 'Available today' : 'Off today' ?></span>
    <div class="sp-bottom">
      <div class="sp-name"><h3><?= e($s['name']) ?></h3><div class="role"><?= e($s['title']) ?></div>
        <?php if ($rt && $rt['n']): ?><div class="sp-rate"><span>&#9733;</span> <b><?= e($rt['a']) ?></b> <small>(<?= (int)$rt['n'] ?> reviews)</small></div><?php endif; ?></div>
      <div class="sp-panel"><div>
        <p class="sp-bio"><?= e(excerpt((string)$s['bio'], 95)) ?></p>
        <div class="chips"><?php foreach ($svcs as $v): ?><span class="chip"><?= e($v['name']) ?></span><?php endforeach; ?></div>
        <div class="sp-meta"><span><?= icon('calendar', 14) ?> <?= days_label($s['work_days']) ?></span><span><?= icon('clock', 14) ?> <?= ftime($s['start_time']) ?> - <?= ftime($s['end_time']) ?></span></div>
        <a href="<?= url('user/book.php?stylist=' . $s['id']) ?>" class="btn btn-gold btn-sm btn-block"><?= icon('calendar', 15) ?> Book with <?= e($first) ?></a>
      </div></div>
    </div>
  </div></article>
<?php }

function site_footer(): void {
    $u = user(); ?>
<footer class="footer">
  <div class="container foot-grid">
    <div>
      <a href="<?= url('index.php') ?>" class="brand light"><span class="brand-mark"><?= icon('scissors', 22) ?></span><span>Elegance<small>Salon &amp; Spa</small></span></a>
      <p class="muted-l">Where beauty meets artistry. Premium hair, nail, skin and spa care in the heart of Karachi.</p>
    </div>
    <div><h4>Explore</h4>
      <a href="<?= url('index.php') ?>">Home</a><a href="<?= url('about.php') ?>">About</a><a href="<?= url('services.php') ?>">Services</a><a href="<?= url('contact.php') ?>">Contact</a>
    </div>
    <div><h4>Visit Us</h4>
      <p><?= icon('pin', 16) ?> <?= SITE_ADDRESS ?></p><p><?= icon('phone', 16) ?> <?= SITE_PHONE ?></p><p><?= icon('mail', 16) ?> <?= SITE_EMAIL ?></p>
    </div>
    <div><h4>Opening Hours</h4><p>Mon - Sat: 10:00 AM - 9:00 PM</p><p>Sunday: 11:00 AM - 8:00 PM</p><a href="<?= url('user/book.php') ?>" class="btn btn-gold btn-sm mt">Book Appointment</a></div>
  </div>
  <div class="foot-bar"><div class="container">&copy; <?= date('Y') ?> Elegance Salon. All rights reserved.</div></div>
</footer>
<button id="toTop" class="to-top" aria-label="Back to top"><?= icon('arrow', 18) ?></button>
<script src="<?= asset('assets/js/app.js') ?>"></script>
<script src="<?= asset('assets/js/animations.js') ?>"></script>
</body></html>
<?php }

// ---------- dashboard panels ----------
function panel_menu(array $u): array {
    if (!is_staff($u)) return [
        ['user/dashboard.php', 'Dashboard', 'home'], ['user/book.php', 'Book Appointment', 'plus'], ['user/appointments.php', 'My Appointments', 'calendar'],
        ['user/calendar.php', 'Calendar', 'clock'], ['user/payments.php', 'Payments & Invoices', 'card'], ['user/notifications.php', 'Notifications', 'bell'], ['user/profile.php', 'Profile Settings', 'settings']];
    $m = [['admin/dashboard.php', 'Dashboard', 'home']];
    $r = $u['role'];
    $m[] = ['admin/appointments.php', $r === 'stylist' ? 'My Appointments' : 'Appointments', 'calendar'];
    $m[] = ['admin/calendar.php', 'Calendar', 'clock'];
    if ($r === 'stylist') $m[] = ['admin/schedule.php', 'My Schedule & Earnings', 'money'];
    if (in_array($r, ['admin', 'receptionist'])) { $m[] = ['admin/clients.php', 'Clients', 'users']; $m[] = ['admin/payments.php', 'Payments & Invoices', 'card']; $m[] = ['admin/feedback.php', 'Feedback', 'star']; $m[] = ['admin/messages.php', 'Messages', 'message']; }
    if ($r === 'admin') { $m[] = ['admin/services.php', 'Services', 'scissors']; $m[] = ['admin/staff.php', 'Staff', 'user']; $m[] = ['admin/inventory.php', 'Inventory', 'box']; $m[] = ['admin/reports.php', 'Reports', 'chart']; }
    $m[] = ['admin/notifications.php', 'Notifications', 'bell'];
    $m[] = ['admin/profile.php', 'Profile Settings', 'settings'];
    return $m;
}

function panel_header(string $title, array $u): void {
    head_html($title, 'panel');
    $cur = basename($_SERVER['SCRIPT_NAME']); $dir = basename(dirname($_SERVER['SCRIPT_NAME']));
    $unread = (int)val('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0', [$u['id']]);
    $nlink = is_staff($u) ? 'admin/notifications.php' : 'user/notifications.php';
    ?>
<div class="shell">
  <aside class="side" id="side">
    <a href="<?= url('index.php') ?>" class="brand light"><span class="brand-mark"><?= icon('scissors', 22) ?></span><span>Elegance<small><?= is_staff($u) ? ucfirst($u['role']) . ' Panel' : 'My Account' ?></small></span></a>
    <nav>
      <?php foreach (panel_menu($u) as [$f, $l, $ic]): $on = ($dir . '/' . $cur) === $f; ?>
        <a href="<?= url($f) ?>" class="<?= $on ? 'on' : '' ?>"><?= icon($ic, 19) ?><span><?= $l ?></span><?= ($ic === 'bell' && $unread) ? '<em class="pill">' . $unread . '</em>' : '' ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="side-foot"><a href="<?= url('index.php') ?>"><?= icon('home', 18) ?><span>Visit Website</span></a><a href="<?= url('logout.php') ?>"><?= icon('logout', 18) ?><span>Logout</span></a></div>
  </aside>
  <div class="side-bd" id="sideBd"></div>
  <main class="main">
    <div class="topbar">
      <button class="burger show-md" id="sideBtn" aria-label="Menu"><?= icon('menu', 24) ?></button>
      <h1><?= e($title) ?></h1>
      <div class="top-r">
        <a href="<?= url($nlink) ?>" class="bell"><?= icon('bell', 21) ?><?= $unread ? '<i>' . $unread . '</i>' : '' ?></a>
        <a href="<?= url(is_staff($u) ? 'admin/profile.php' : 'user/profile.php') ?>" class="who"><span class="avatar"><?= e(initials($u['name'])) ?></span><span class="hide-sm"><b><?= e($u['name']) ?></b><small><?= e(ucfirst($u['role'])) ?></small></span></a>
      </div>
    </div>
    <div class="content">
<?php render_flashes();
}

function panel_footer(): void { ?>
    </div>
  </main>
</div>
<script src="<?= asset('assets/js/app.js') ?>"></script>
<script src="<?= asset('assets/js/animations.js') ?>"></script>
</body></html>
<?php }

function modal_open(string $id, string $title, string $size = ''): void {
    echo '<div class="modal" id="' . e($id) . '" aria-hidden="true"><div class="modal-box ' . e($size) . '"><button class="modal-x" data-close aria-label="Close">' . icon('x', 20) . '</button><h3 class="modal-t">' . e($title) . '</h3>';
}
function modal_close(): void { echo '</div></div>'; }
