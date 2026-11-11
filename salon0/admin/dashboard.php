<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin', 'receptionist', 'stylist');
$r = $u['role']; $isSt = $r === 'stylist'; $today = date('Y-m-d');
$sw = $isSt ? ' AND a.stylist_id=' . (int)$u['id'] : '';
$todayList = rows(appt_query("a.appt_date=? AND a.status<>'cancelled'$sw", 'a.appt_time'), [$today]);
$upcoming = rows(appt_query("a.appt_date>? AND a.status='confirmed'$sw", 'a.appt_date, a.appt_time') . ' LIMIT 6', [$today]);
$recentBookings = $isSt ? [] : rows(appt_query('1=1', 'a.created_at DESC, a.id DESC') . ' LIMIT 5');
$cnt = fn($c) => (int)val("SELECT COUNT(*) FROM appointments a WHERE $c$sw");
$stats = [['calendar', $cnt("a.appt_date='$today' AND a.status<>'cancelled'"), 'Today\'s Appointments', ''], ['clock', $cnt("a.status='confirmed' AND a.appt_date>='$today'"), 'Upcoming', 'blue'], ['check', $cnt("a.status='completed'"), 'Completed', 'green']];
if (!$isSt) { $stats[] = ['users', (int)val("SELECT COUNT(*) FROM users WHERE role='client'"), 'Clients', '']; }
$rev = (int)val('SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN appointments a ON a.id=p.appointment_id WHERE 1=1' . $sw);
$myRate = $isSt ? (int)val('SELECT commission_rate FROM stylist_profiles WHERE user_id=?', [$u['id']]) : 0;
$low = $r === 'admin' ? rows('SELECT * FROM inventory WHERE quantity<=reorder_level ORDER BY quantity') : [];
$pendingMsg = $r !== 'stylist' ? (int)val('SELECT COUNT(*) FROM messages') : 0;
// last 7 days chart
$days = []; for ($i = 6; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i days")); $days[$d] = (int)val("SELECT COUNT(*) FROM appointments a WHERE a.appt_date=? AND a.status<>'cancelled'$sw", [$d]); }
$mx = max(1, max($days));
panel_header($isSt ? 'My Dashboard' : ucfirst($r) . ' Dashboard', $u);
?>
<div class="stats">
  <?php foreach ($stats as [$i, $n, $l, $c]): ?><div class="stat <?= $c ?> rv"><div class="si"><?= icon($i, 26) ?></div><div><b data-count="<?= $n ?>">0</b><span><?= $l ?></span></div></div><?php endforeach; ?>
  <?php if ($r !== 'receptionist'): ?><div class="stat green rv"><div class="si"><?= icon('money', 26) ?></div><div><b><?= money($isSt ? $rev * $myRate / 100 : $rev) ?></b><span><?= $isSt ? 'My Commission' : 'Total Revenue' ?></span></div></div><?php endif; ?>
  <?php if ($low): ?><div class="stat red rv"><div class="si"><?= icon('alert', 26) ?></div><div><b><?= count($low) ?></b><span>Low Stock Items</span></div></div><?php endif; ?>
</div>
<div class="panel-grid">
  <div><div class="box rv"><div class="box-h"><h3>Today's Schedule <small class="muted">(<?= fdate($today, 'd M Y') ?>)</small></h3><a href="<?= url('admin/appointments.php') ?>" class="btn btn-ghost btn-xs">All appointments</a></div>
    <?php if (!$todayList): ?><div class="empty"><?= icon('calendar', 40) ?><p>No appointments today.</p></div><?php else: foreach ($todayList as $a): ?>
      <div class="mini"><div class="dt"><b><?= date('h', strtotime($a['appt_time'])) ?>:<?= date('i', strtotime($a['appt_time'])) ?></b><small><?= date('A', strtotime($a['appt_time'])) ?></small></div><div><h4><?= e($a['client']) ?> - <?= e($a['service']) ?></h4><small class="muted">with <?= e($a['stylist']) ?> &middot; <?= money($a['price']) ?></small></div><?= badge($a['status']) ?></div><?php endforeach; endif; ?></div>
  <div class="box rv"><div class="box-h"><h3>Appointments - Last 7 Days</h3></div><div class="cols"><?php foreach ($days as $d => $n): ?><div class="col"><i data-h="<?= round($n / $mx * 85) ?>" data-v="<?= $n ?>"></i><?= date('D', strtotime($d)) ?></div><?php endforeach; ?></div></div></div>
  <div><div class="box rv"><div class="box-h"><h3>Upcoming</h3></div><?php if (!$upcoming): ?><p class="muted small">No upcoming appointments.</p><?php endif; foreach ($upcoming as $a): ?>
    <div class="mini"><div class="dt"><b><?= date('d', strtotime($a['appt_date'])) ?></b><small><?= date('M', strtotime($a['appt_date'])) ?></small></div><div><h4><?= e($a['client']) ?></h4><small class="muted"><?= e($a['service']) ?> &middot; <?= ftime($a['appt_time']) ?></small></div></div><?php endforeach; ?></div>
  <?php if (!$isSt): ?><div class="box rv"><div class="box-h"><h3>Recent Bookings</h3><a href="<?= url('admin/appointments.php') ?>" class="btn btn-ghost btn-xs">View all</a></div><?php if (!$recentBookings): ?><p class="muted small">No bookings yet.</p><?php endif; foreach ($recentBookings as $a): ?>
    <div class="mini"><div class="dt"><b><?= date('d', strtotime($a['appt_date'])) ?></b><small><?= date('M', strtotime($a['appt_date'])) ?></small></div><div><h4><?= e($a['client']) ?> - <?= e($a['service']) ?></h4><small class="muted">with <?= e($a['stylist']) ?> &middot; <?= fdate($a['appt_date'], 'd M Y') ?>, <?= ftime($a['appt_time']) ?> &middot; <?= money($a['price']) ?></small></div><?= badge($a['status']) ?></div><?php endforeach; ?></div><?php endif; ?>
  <?php if ($low): ?><div class="box rv"><div class="box-h"><h3 style="color:var(--bad)">Low Stock Alert</h3><a href="<?= url('admin/inventory.php') ?>" class="btn btn-ghost btn-xs">Manage</a></div><?php foreach ($low as $i): ?><div class="mini"><div><h4><?= e($i['name']) ?></h4><small class="muted">Reorder level <?= $i['reorder_level'] ?></small></div><span class="badge b-low"><?= $i['quantity'] ?> left</span></div><?php endforeach; ?></div><?php endif; ?>
  <?php if ($pendingMsg): ?><div class="box rv"><a href="<?= url('admin/messages.php') ?>" class="btn btn-ghost btn-block"><?= icon('message', 16) ?> <?= $pendingMsg ?> contact messages</a></div><?php endif; ?></div>
</div>
<?php panel_footer();
