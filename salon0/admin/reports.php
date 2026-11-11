<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin');
$from = $_GET['from'] ?? date('Y-m-01', strtotime('-60 days')); $to = $_GET['to'] ?? date('Y-m-d', strtotime('+30 days'));
$ap = rows(appt_query('a.appt_date BETWEEN ? AND ?'), [$from, $to]);
$byStatus = []; $svc = []; $hours = []; $sty = [];
foreach ($ap as $a) { $byStatus[$a['status']] = ($byStatus[$a['status']] ?? 0) + 1; if ($a['status'] === 'cancelled') continue;
    $svc[$a['service']]['n'] = ($svc[$a['service']]['n'] ?? 0) + 1; $svc[$a['service']]['rev'] = ($svc[$a['service']]['rev'] ?? 0) + ($a['status'] === 'completed' ? $a['price'] : 0);
    $h = (int)substr($a['appt_time'], 0, 2); $hours[$h] = ($hours[$h] ?? 0) + 1;
    $sty[$a['stylist']]['n'] = ($sty[$a['stylist']]['n'] ?? 0) + 1; if ($a['status'] === 'completed') { $sty[$a['stylist']]['done'] = ($sty[$a['stylist']]['done'] ?? 0) + 1; $sty[$a['stylist']]['rev'] = ($sty[$a['stylist']]['rev'] ?? 0) + $a['price']; } }
uasort($svc, fn($a, $b) => $b['n'] <=> $a['n']); ksort($hours);
$rates = array_column(rows('SELECT user_id,commission_rate FROM stylist_profiles'), 'commission_rate', 'user_id');
$rate = []; foreach (rows("SELECT name,id FROM users WHERE role='stylist'") as $r) $rate[$r['name']] = $rates[$r['id']] ?? 0;
$pays = rows('SELECT p.amount,p.paid_at FROM payments p WHERE DATE(p.paid_at) BETWEEN ? AND ?', [$from, $to]);
$rev = array_sum(array_column($pays, 'amount')); $months = []; foreach ($pays as $p) { $k = date('M Y', strtotime($p['paid_at'])); $months[$k] = ($months[$k] ?? 0) + $p['amount']; }
$stock = rows('SELECT * FROM inventory ORDER BY quantity*unit_cost DESC LIMIT 6');
$mxS = max(1, ...array_values(array_column($svc, 'n') ?: [1])); $mxH = max(1, ...array_values($hours ?: [1])); $mxM = max(1, ...array_values($months ?: [1]));
panel_header('Reports & Analytics', $u);
?>
<form class="toolbar" method="get"><label class="lbl" style="margin:0">From</label><input type="date" name="from" value="<?= e($from) ?>" style="width:auto"><label class="lbl" style="margin:0">To</label><input type="date" name="to" value="<?= e($to) ?>" style="width:auto"><button class="btn btn-gold btn-sm">Generate</button><button type="button" class="btn btn-ghost btn-sm" onclick="print()"><?= icon('print', 15) ?> Print</button></form>
<div class="stats"><div class="stat"><div class="si"><?= icon('calendar', 26) ?></div><div><b><?= count($ap) ?></b><span>Appointments</span></div></div><div class="stat green"><div class="si"><?= icon('money', 26) ?></div><div><b><?= money($rev) ?></b><span>Revenue</span></div></div><div class="stat blue"><div class="si"><?= icon('check', 26) ?></div><div><b><?= $byStatus['completed'] ?? 0 ?></b><span>Completed</span></div></div><div class="stat red"><div class="si"><?= icon('x', 26) ?></div><div><b><?= $byStatus['cancelled'] ?? 0 ?></b><span>Cancelled</span></div></div></div>
<div class="grid g2">
 <div class="box"><div class="box-h"><h3>Popular Services</h3></div><div class="bars"><?php foreach ($svc as $n => $d): ?><div class="bar-r"><span><?= e($n) ?></span><div class="bar"><i data-w="<?= round($d['n'] / $mxS * 100) ?>"></i></div><b><?= $d['n'] ?></b></div><?php endforeach; if (!$svc) echo '<p class="muted">No data.</p>'; ?></div></div>
 <div class="box" style="margin-top:0"><div class="box-h"><h3>Peak Booking Hours</h3></div><div class="cols"><?php foreach ($hours as $h => $n): ?><div class="col"><i data-h="<?= round($n / $mxH * 85) ?>" data-v="<?= $n ?>"></i><?= date('gA', strtotime("$h:00")) ?></div><?php endforeach; ?></div></div></div>
<div class="grid g2 mt2">
 <div class="box" style="margin-top:0"><div class="box-h"><h3>Revenue by Month</h3></div><div class="cols"><?php foreach ($months as $m => $v): ?><div class="col"><i data-h="<?= round($v / $mxM * 85) ?>" data-v="<?= number_format($v) ?>"></i><?= $m ?></div><?php endforeach; if (!$months) echo '<p class="muted">No payments in range.</p>'; ?></div></div>
 <div class="box" style="margin-top:0"><div class="box-h"><h3>Inventory Usage / Value</h3></div><div class="tbl-wrap"><table><thead><tr><th>Item</th><th>Qty</th><th>Value</th></tr></thead><tbody><?php foreach ($stock as $i): ?><tr><td><?= e($i['name']) ?></td><td><?= $i['quantity'] ?></td><td><?= money($i['quantity'] * $i['unit_cost']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
<div class="box mt2"><div class="box-h"><h3>Staff Performance &amp; Commission</h3></div><div class="tbl-wrap"><table><thead><tr><th>Stylist</th><th>Appointments</th><th>Completed</th><th>Revenue Generated</th><th>Commission %</th><th>Commission Earned</th></tr></thead><tbody>
<?php foreach ($sty as $n => $d): ?><tr><td><b><?= e($n) ?></b></td><td><?= $d['n'] ?></td><td><?= $d['done'] ?? 0 ?></td><td><?= money($d['rev'] ?? 0) ?></td><td><?= $rate[$n] ?? 0 ?>%</td><td><b><?= money(($d['rev'] ?? 0) * ($rate[$n] ?? 0) / 100) ?></b></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php panel_footer();
