<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin', 'receptionist');
if (is_post()) { csrf_check(); q('DELETE FROM messages WHERE id=?', [(int)post('id')]); flash('success', 'Message deleted.'); redirect('admin/messages.php'); }
$list = rows('SELECT * FROM messages ORDER BY id DESC');
panel_header('Contact Messages', $u);
?>
<?php if (!$list): ?><div class="box empty"><?= icon('message', 44) ?><p>No messages yet.</p></div><?php endif; foreach ($list as $m): ?>
<div class="notif unread"><div class="ni"><?= e(initials($m['name'])) ?></div><div><h4><?= e($m['name']) ?> <small class="muted">&lt;<?= e($m['email']) ?>&gt;</small></h4><p class="small"><b><?= e($m['subject']) ?></b></p><p class="small"><?= nl2br(e($m['message'])) ?></p><small><?= date('d M Y, h:i A', strtotime($m['created_at'])) ?></small></div>
<form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="btn btn-red btn-xs" data-confirm="Delete message?"><?= icon('trash', 13) ?></button></form></div><?php endforeach; panel_footer();
