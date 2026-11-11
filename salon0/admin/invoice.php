<?php
require_once __DIR__ . '/../includes/layout.php'; require_once __DIR__ . '/../includes/invoice_view.php';
$u = require_role('admin', 'receptionist');
$a = row(appt_query('a.id=?'), [(int)($_GET['id'] ?? 0)]); $p = $a ? row('SELECT * FROM payments WHERE appointment_id=?', [$a['id']]) : null;
if (!$p) { flash('error', 'Invoice not found.'); redirect('admin/payments.php'); }
panel_header('Invoice', $u); render_invoice($a, $p); panel_footer();
