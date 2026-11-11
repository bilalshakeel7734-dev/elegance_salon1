<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_client();
$all = rows(appt_query('a.client_id=?'), [$u['id']]);
$upcoming = array_values(array_filter(array_reverse($all), fn($a) => $a['status'] === 'confirmed' && appt_is_future($a)));
$done = array_filter($all, fn($a) => $a['status'] === 'completed');
$spent = (int)val('SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN appointments a ON a.id=p.appointment_id WHERE a.client_id=?', [$u['id']]);
$pending_fb = array_filter($all, fn($a) => $a['status'] !== 'cancelled' && !$a['fb_id']);
$next = $upcoming[0] ?? null;
panel_header('Dashboard', $u);
?>
<div class="next-card rv">
  <?php if ($next): ?><span class="eyebrow">Your next appointment</span><h2><?= e($next['service']) ?> with <?= e($next['stylist']) ?></h2>
    <div class="next-meta"><span><?= icon('calendar', 17) ?> <?= fdate($next['appt_date']) ?></span><span><?= icon('clock', 17) ?> <?= ftime($next['appt_time']) ?></span><span><?= icon('money', 17) ?> <?= money($next['price']) ?></span></div>
    <div class="acts mt"><a class="btn btn-gold btn-sm" href="<?= url('user/appointments.php?f=upcoming') ?>">Manage</a></div>
  <?php else: ?><span class="eyebrow">Hello, <?= e(explode(' ', $u['name'])[0]) ?></span><h2>Ready for a little pampering?</h2><p style="color:#d8d2c5;margin-bottom:16px">You have no upcoming appointments. Book your next treatment now.</p><a class="btn btn-gold btn-sm" href="<?= url('user/book.php') ?>"><?= icon('plus', 16) ?> Book Appointment</a><?php endif; ?>
</div>
<div class="stats">
  <div class="stat rv"><div class="si"><?= icon('calendar', 26) ?></div><div><b data-count="<?= count($upcoming) ?>">0</b><span>Upcoming</span></div></div>
  <div class="stat green rv"><div class="si"><?= icon('check', 26) ?></div><div><b data-count="<?= count($done) ?>">0</b><span>Completed</span></div></div>
  <div class="stat blue rv"><div class="si"><?= icon('money', 26) ?></div><div><b><?= money($spent) ?></b><span>Total Spent</span></div></div>
  <div class="stat red rv"><div class="si"><?= icon('star', 26) ?></div><div><b data-count="<?= count($pending_fb) ?>">0</b><span>Feedback Pending</span></div></div>
</div>
<div class="panel-grid">
  <div class="box rv"><div class="box-h"><h3>Recent Appointments</h3><a href="<?= url('user/appointments.php') ?>" class="btn btn-ghost btn-xs">View all</a></div>
    <?php if (!$all): ?><div class="empty"><?= icon('calendar', 40) ?><p>No appointments yet.</p></div><?php else: ?>
    <div class="tbl-wrap"><table><thead><tr><th>Service</th><th>Stylist</th><th>Date</th><th>Status</th></tr></thead><tbody>
    <?php foreach (array_slice($all, 0, 6) as $a): ?><tr><td><?= e($a['service']) ?></td><td><?= e($a['stylist']) ?></td><td><?= fdate($a['appt_date'], 'd M Y') ?>, <?= ftime($a['appt_time']) ?></td><td><?= badge($a['status']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>
  <div><div class="box rv"><div class="box-h"><h3>Quick Actions</h3></div><div style="display:grid;gap:10px">
    <a class="btn btn-gold" href="<?= url('user/book.php') ?>"><?= icon('plus', 17) ?> Book Appointment</a><a class="btn btn-ghost" href="<?= url('user/calendar.php') ?>"><?= icon('calendar', 17) ?> View Calendar</a><a class="btn btn-ghost" href="<?= url('services.php') ?>"><?= icon('scissors', 17) ?> Browse Services</a><a class="btn btn-ghost" href="<?= url('user/profile.php') ?>"><?= icon('settings', 17) ?> Profile Settings</a></div></div>
  <?php if ($upcoming): ?><div class="box rv"><div class="box-h"><h3>Upcoming</h3></div><?php foreach (array_slice($upcoming, 0, 4) as $a): ?>
    <div class="mini"><div class="dt"><b><?= date('d', strtotime($a['appt_date'])) ?></b><small><?= date('M', strtotime($a['appt_date'])) ?></small></div><div><h4><?= e($a['service']) ?></h4><small class="muted"><?= ftime($a['appt_time']) ?> &middot; <?= e($a['stylist']) ?></small></div></div><?php endforeach; ?></div><?php endif; ?></div>
</div>
<?php panel_footer();
