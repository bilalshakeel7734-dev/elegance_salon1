<?php
function render_invoice(array $a, array $p): void { ?>
<div class="invoice"><div class="inv-h"><div><a class="brand"><span class="brand-mark"><?= icon('scissors', 22) ?></span><span>Elegance<small>Salon &amp; Spa</small></span></a><p class="muted small mt"><?= SITE_ADDRESS ?><br><?= SITE_PHONE ?> &middot; <?= SITE_EMAIL ?></p></div>
  <div style="text-align:right"><h2>INVOICE</h2><p class="small"><b><?= e($p['invoice_no']) ?></b><br>Date: <?= fdate($p['paid_at'], 'd M Y') ?><br><?= badge($p['status']) ?></p></div></div>
  <div class="row2" style="margin-bottom:24px"><div><div class="lbl">Billed to</div><b><?= e($a['client']) ?></b><br><span class="muted small"><?= e($a['cphone']) ?></span></div><div><div class="lbl">Appointment</div><b><?= e($a['ref']) ?></b><br><span class="muted small"><?= fdate($a['appt_date']) ?>, <?= ftime($a['appt_time']) ?><br>Stylist: <?= e($a['stylist']) ?></span></div></div>
  <div class="tbl-wrap"><table><thead><tr><th>Description</th><th>Duration</th><th style="text-align:right">Amount</th></tr></thead><tbody><tr><td><?= e($a['service']) ?></td><td><?= (int)((strtotime($a['end_time']) - strtotime($a['appt_time'])) / 60) ?> min</td><td style="text-align:right"><?= money($a['price']) ?></td></tr></tbody></table></div>
  <div class="inv-total">Paid via <?= e(ucfirst($p['method'])) ?><br>Total <b><?= money($p['amount']) ?></b></div>
  <p class="center muted small mt2">Thank you for choosing Elegance Salon. We look forward to seeing you again!</p>
  <div class="center mt no-print"><button class="btn btn-gold btn-sm" onclick="window.print()"><?= icon('print', 16) ?> Print / Save as PDF</button></div></div>
<?php }
