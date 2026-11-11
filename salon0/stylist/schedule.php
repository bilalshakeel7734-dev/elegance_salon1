<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('stylist');
$p = row('SELECT * FROM stylist_profiles WHERE user_id=?', [$u['id']]);
$svcs = rows('SELECT s.name FROM stylist_services ss JOIN services s ON s.id=ss.service_id WHERE ss.stylist_id=?', [$u['id']]);
$done = rows(appt_query("a.stylist_id=? AND a.status='completed'", 'a.appt_date DESC'), [$u['id']]);
$rev = array_sum(array_column($done, 'price')); $comm = $rev * ($p['commission_rate'] ?? 0) / 100;
$upc = rows(appt_query("a.stylist_id=? AND a.status='confirmed' AND a.appt_date>=?", 'a.appt_date, a.appt_time'), [$u['id'], date('Y-m-d')]);
panel_header('My Schedule & Earnings', $u);
?>
<div class="stats"><div class="stat"><div class="si"><?= icon('check', 26) ?></div><div><b><?= count($done) ?></b><span>Services Done</span></div></div><div class="stat blue"><div class="si"><?= icon('money', 26) ?></div><div><b><?= money($rev) ?></b><span>Revenue Generated</span></div></div><div class="stat green"><div class="si"><?= icon('card', 26) ?></div><div><b><?= money($comm) ?></b><span>Commission (<?= (int)($p['commission_rate'] ?? 0) ?>%)</span></div></div></div>
<div class="grid g2"><div class="box"><div class="box-h"><h3>My Weekly Shift</h3></div>
  <div class="sched" style="border:0;margin:0;padding:0;font-size:.95rem"><div><?= icon('calendar', 17) ?> <b>Days:</b> <?= days_label($p['work_days'] ?? '') ?></div><div><?= icon('clock', 17) ?> <b>Hours:</b> <?= ftime($p['start_time'] ?? '10:00') ?> - <?= ftime($p['end_time'] ?? '19:00') ?></div><div><?= icon('user', 17) ?> <b>Shift:</b> <?= e($p['shift_note'] ?? '-') ?></div></div>
  <div class="lbl mt2">Assigned services</div><div class="chips" style="justify-content:flex-start"><?php foreach ($svcs as $s): ?><span class="chip"><?= e($s['name']) ?></span><?php endforeach; ?></div><p class="hint">Contact the admin to change your shift or services.</p></div>
 <div class="box" style="margin-top:0"><div class="box-h"><h3>Upcoming Appointments</h3></div><?php if (!$upc) echo '<p class="muted small">None scheduled.</p>'; foreach (array_slice($upc, 0, 6) as $a): ?><div class="mini"><div class="dt"><b><?= date('d', strtotime($a['appt_date'])) ?></b><small><?= date('M', strtotime($a['appt_date'])) ?></small></div><div><h4><?= e($a['client']) ?></h4><small class="muted"><?= e($a['service']) ?> &middot; <?= ftime($a['appt_time']) ?></small></div></div><?php endforeach; ?></div></div>
<div class="box mt2"><div class="box-h"><h3>Commission Breakdown</h3></div><div class="tbl-wrap"><table><thead><tr><th>Date</th><th>Client</th><th>Service</th><th>Price</th><th>My Commission</th></tr></thead><tbody><?php foreach ($done as $a): ?><tr><td><?= fdate($a['appt_date'], 'd M Y') ?></td><td><?= e($a['client']) ?></td><td><?= e($a['service']) ?></td><td><?= money($a['price']) ?></td><td><b><?= money($a['price'] * ($p['commission_rate'] ?? 0) / 100) ?></b></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php panel_footer();
