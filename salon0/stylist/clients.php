<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin', 'receptionist');
if (is_post()) { csrf_check(); $act = post('action'); $id = (int)post('id');
    if ($act === 'save') {
        $name = post('name'); $email = strtolower(post('email')); $uname = strtolower(post('username')) ?: strstr($email, '@', true);
        if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) flash('error', 'Name and a valid email are required.');
        elseif (val('SELECT 1 FROM users WHERE (email=? OR username=?) AND id<>?', [$email, $uname, $id])) flash('error', 'Email or username already exists.');
        elseif ($id) { q("UPDATE users SET name=?,email=?,username=?,phone=?,address=?,pref_stylist_id=?,notes=? WHERE id=? AND role='client'", [$name, $email, $uname, post('phone'), post('address'), (int)post('pref_stylist_id') ?: null, post('notes'), $id]); if (post('password')) q('UPDATE users SET password=? WHERE id=?', [password_hash(post('password'), PASSWORD_DEFAULT), $id]); flash('success', 'Client updated.'); }
        else { q("INSERT INTO users (name,username,email,phone,address,pref_stylist_id,notes,password,role) VALUES (?,?,?,?,?,?,?,?,'client')", [$name, $uname, $email, post('phone'), post('address'), (int)post('pref_stylist_id') ?: null, post('notes'), password_hash(post('password') ?: 'client123', PASSWORD_DEFAULT)]); flash('success', 'Client added (default password: client123 unless you set one).'); }
    } elseif ($act === 'delete' && $u['role'] === 'admin') { if (val('SELECT 1 FROM appointments WHERE client_id=?', [$id])) { q('UPDATE users SET status=0 WHERE id=?', [$id]); flash('success', 'Client has appointment history, so the account was deactivated instead of deleted.'); } else { q("DELETE FROM users WHERE id=? AND role='client'", [$id]); flash('success', 'Client deleted.'); } }
    elseif ($act === 'toggle') { q("UPDATE users SET status=1-status WHERE id=? AND role='client'", [$id]); flash('success', 'Client status changed.'); }
    redirect('admin/clients.php');
}
$clients = rows("SELECT u.*, (SELECT COUNT(*) FROM appointments a WHERE a.client_id=u.id) AS visits, (SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN appointments a ON a.id=p.appointment_id WHERE a.client_id=u.id) AS spent, (SELECT name FROM users s WHERE s.id=u.pref_stylist_id) AS pref, (SELECT GROUP_CONCAT(DISTINCT sv.name) FROM appointments a JOIN services sv ON sv.id=a.service_id WHERE a.client_id=u.id) AS availed FROM users u WHERE u.role='client' ORDER BY u.id DESC");
$stylists = rows("SELECT id,name FROM users WHERE role='stylist'");
panel_header('Clients', $u);
?>
<div class="toolbar"><input type="search" class="grow" placeholder="Search clients by name, phone, email…" data-search="#clTbl"><button class="btn btn-gold btn-sm" data-open="clModal" data-reset><?= icon('plus', 16) ?> Add Client</button></div>
<div class="box"><div class="tbl-wrap"><table id="clTbl"><thead><tr><th>Client</th><th>Contact</th><th>Services Availed</th><th>Visits</th><th>Spent</th><th>Preferred Stylist</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($clients as $c): ?><tr><td><b><?= e($c['name']) ?></b><br><small class="muted">@<?= e($c['username']) ?></small></td><td><?= e($c['phone']) ?><br><small class="muted"><?= e($c['email']) ?></small></td><td class="small"><?= e($c['availed'] ?: '-') ?></td><td><?= $c['visits'] ?></td><td><?= money($c['spent']) ?></td><td><?= e($c['pref'] ?: '-') ?></td><td><?= $c['status'] ? '<span class="badge b-ok">Active</span>' : '<span class="badge b-cancelled">Inactive</span>' ?></td>
<td><div class="acts"><button class="btn btn-ghost btn-xs" data-open="clModal" data-json='<?= e(json_encode(['id' => $c['id'], 'name' => $c['name'], 'username' => $c['username'], 'email' => $c['email'], 'phone' => $c['phone'], 'address' => $c['address'], 'pref_stylist_id' => $c['pref_stylist_id'], 'notes' => $c['notes'], 'password' => ''])) ?>'><?= icon('edit', 13) ?></button>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn btn-ghost btn-xs"><?= $c['status'] ? 'Deactivate' : 'Activate' ?></button></form>
<?php if ($u['role'] === 'admin'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn btn-red btn-xs" data-confirm="Delete this client?"><?= icon('trash', 13) ?></button></form><?php endif; ?></div></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php modal_open('clModal', 'Client Profile', 'lg'); ?>
<form method="post" data-validate novalidate><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id">
 <div class="row2"><div class="fg"><label>Full Name *</label><input type="text" name="name" required></div><div class="fg"><label>Username</label><input type="text" name="username" placeholder="auto from email"></div></div>
 <div class="row2"><div class="fg"><label>Email *</label><input type="email" name="email" required></div><div class="fg"><label>Phone</label><input type="tel" name="phone"></div></div>
 <div class="row2"><div class="fg"><label>Address</label><input type="text" name="address"></div><div class="fg"><label>Preferred Stylist</label><select name="pref_stylist_id"><option value="">None</option><?php foreach ($stylists as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?></select></div></div>
 <div class="row2"><div class="fg"><label>Preferences / Notes</label><textarea name="notes" style="min-height:70px"></textarea></div><div class="fg"><label>Password</label><input type="password" name="password" placeholder="Leave blank to keep / default client123"></div></div>
 <button class="btn btn-gold btn-block">Save Client</button></form><?php modal_close(); panel_footer();
