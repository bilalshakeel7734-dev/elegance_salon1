<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_client();
$pays = rows('SELECT p.*, a.id AS aid, a.ref, a.appt_date, s.name AS service FROM payments p JOIN appointments a ON a.id=p.appointment_id JOIN services s ON s.id=a.service_id WHERE a.client_id=? ORDER BY p.id DESC', [$u['id']]);
panel_header('Payments & Invoices', $u);
?>
<div class="stats"><div class="stat"><div class="si"><?= icon('money', 26) ?></div><div><b><?= money(array_sum(array_column($pays, 'amount'))) ?></b><span>Total Paid</span></div></div><div class="stat green"><div class="si"><?= icon('file', 26) ?></div><div><b><?= count($pays) ?></b><span>Invoices</span></div></div></div>
<div class="box"><?php if (!$pays): ?><div class="empty"><?= icon('card', 44) ?><p>No payments recorded yet. Payments appear here after your service is completed.</p></div><?php else: ?>
<div class="tbl-wrap"><table><thead><tr><th>Invoice</th><th>Service</th><th>Date</th><th>Method</th><th>Amount</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($pays as $p): ?><tr><td><b><?= e($p['invoice_no']) ?></b></td><td><?= e($p['service']) ?></td><td><?= fdate($p['appt_date'], 'd M Y') ?></td><td><?= e(ucfirst($p['method'])) ?></td><td><b><?= money($p['amount']) ?></b></td><td><?= badge($p['status']) ?></td><td><a href="<?= url('user/invoice.php?id=' . $p['aid']) ?>" class="btn btn-ghost btn-xs"><?= icon('eye', 14) ?> View</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>
<?php panel_footer();
