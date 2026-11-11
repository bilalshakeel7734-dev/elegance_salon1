<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin', 'receptionist');
$list = rows('SELECT f.*, c.name AS client, s.name AS service, st.name AS stylist, a.appt_date FROM feedback f JOIN users c ON c.id=f.client_id JOIN appointments a ON a.id=f.appointment_id JOIN services s ON s.id=a.service_id JOIN users st ON st.id=a.stylist_id ORDER BY f.id DESC');
$avg = $list ? round(array_sum(array_column($list, 'rating')) / count($list), 1) : 0;
panel_header('Client Feedback', $u);
?>
<div class="stats"><div class="stat"><div class="si"><?= icon('star', 26) ?></div><div><b><?= $avg ?> / 5</b><span>Average Rating</span></div></div><div class="stat green"><div class="si"><?= icon('message', 26) ?></div><div><b><?= count($list) ?></b><span>Reviews</span></div></div></div>
<?php if (!$list): ?><div class="box empty"><?= icon('star', 44) ?><p>No feedback yet.</p></div><?php endif; foreach ($list as $f): ?>
<div class="notif unread"><div class="ni"><?= e(initials($f['client'])) ?></div><div><h4><?= e($f['client']) ?> <?= stars((int)$f['rating']) ?></h4><p class="small"><?= e($f['comment'] ?: 'No comment.') ?></p><small><?= e($f['service']) ?> with <?= e($f['stylist']) ?> &middot; <?= fdate($f['appt_date'], 'd M Y') ?></small></div></div><?php endforeach; ?>
<?php panel_footer();
