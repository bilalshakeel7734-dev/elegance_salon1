<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin', 'receptionist');
$pays = rows('SELECT p.*, a.id AS aid, a.ref, a.appt_date, s.name AS service, c.name AS client FROM payments p JOIN appointments a ON a.id=p.appointment_id JOIN services s ON s.id=a.service_id JOIN users c ON c.id=a.client_id ORDER BY p.id DESC');
$pending = rows(appt_query("a.status='confirmed' AND a.appt_date<=?", 'a.appt_date'), [date('Y-m-d')]);
$total = array_sum(array_column($pays, 'amount')); $month = array_sum(array_map(fn($p) => date('Ym', strtotime($p['paid_at'])) === date('Ym') ? $p['amount'] : 0, $pays));
panel_header('Payments & Invoices', $u);
?>
<div class="stats"><div class="stat green"><div class="si"><?= icon('money', 26) ?></div><div><b><?= money($total) ?></b><span>Total Received</span></div></div><div class="stat"><div class="si"><?= icon('card', 26) ?></div><div><b><?= money($month) ?></b><span>This Month</span></div></div><div class="stat red"><div class="si"><?= icon('clock', 26) ?></div><div><b><?= count($pending) ?></b><span>Pending Payments</span></div></div></div>
<?php if ($pending): ?><div class="box"><div class="box-h"><h3>Pending Payments <small class="muted">(today / overdue)</small></h3></div><div class="tbl-wrap"><table><tbody><?php foreach ($pending as $a): ?><tr><td><b><?= e($a['ref']) ?></b></td><td><?= e($a['client']) ?></td><td><?= e($a['service']) ?></td><td><?= fdate($a['appt_date'], 'd M Y') ?></td><td><?= money($a['price']) ?></td><td><a class="btn btn-gold btn-xs" href="<?= url('admin/appointments.php?date=' . $a['appt_date']) ?>">Collect</a></td></tr><?php endforeach; ?></tbody></table></div></div><?php endif; ?>
<div class="box"><div class="toolbar"><input type="search" class="grow" placeholder="Search payments…" data-search="#pyTbl"></div><div class="tbl-wrap"><table id="pyTbl"><thead><tr><th>Invoice</th><th>Client</th><th>Service</th><th>Date</th><th>Method</th><th>Amount</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($pays as $p): ?><tr><td><b><?= e($p['invoice_no']) ?></b></td><td><?= e($p['client']) ?></td><td><?= e($p['service']) ?></td><td><?= fdate($p['paid_at'], 'd M Y') ?></td><td><?= e(ucfirst($p['method'])) ?></td><td><b><?= money($p['amount']) ?></b></td><td><?= badge($p['status']) ?></td><td><a class="btn btn-ghost btn-xs" href="<?= url('admin/invoice.php?id=' . $p['aid']) ?>"><?= icon('eye', 13) ?> Invoice</a></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php panel_footer();
