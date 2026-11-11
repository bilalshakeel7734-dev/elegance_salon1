<?php
// Pages shared between client panel and staff panel
function page_notifications(array $u): void {
    $self = is_staff($u) ? 'admin/notifications.php' : 'user/notifications.php';
    if (is_post()) { csrf_check();
        if (post('action') === 'read_all') q('UPDATE notifications SET is_read=1 WHERE user_id=?', [$u['id']]);
        if (post('action') === 'delete') q('DELETE FROM notifications WHERE id=? AND user_id=?', [(int)post('id'), $u['id']]);
        if (post('action') === 'clear') q('DELETE FROM notifications WHERE user_id=?', [$u['id']]);
        redirect($self); }
    $list = rows('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 100', [$u['id']]);
    panel_header('Notifications', $u); ?>
    <div class="toolbar"><div class="grow muted"><?= count(array_filter($list, fn($n) => !$n['is_read'])) ?> unread</div>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read_all"><button class="btn btn-ghost btn-sm">Mark all as read</button></form>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="clear"><button class="btn btn-red btn-sm" data-confirm="Delete all notifications?">Clear all</button></form></div>
    <?php if (!$list): ?><div class="box empty"><?= icon('bell', 44) ?><p>You're all caught up. No notifications.</p></div><?php endif;
    foreach ($list as $n): ?>
      <div class="notif <?= $n['is_read'] ? '' : 'unread' ?>"><div class="ni"><?= icon($n['type'] === 'success' ? 'check' : ($n['type'] === 'warning' ? 'alert' : 'bell'), 20) ?></div>
        <div><h4><?= e($n['title']) ?></h4><p class="small"><?= e($n['message']) ?></p><small><?= date('d M Y, h:i A', strtotime($n['created_at'])) ?></small></div>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $n['id'] ?>"><button class="btn btn-xs btn-ghost" title="Delete"><?= icon('trash', 14) ?></button></form></div>
    <?php endforeach;
    q('UPDATE notifications SET is_read=1 WHERE user_id=?', [$u['id']]);
    panel_footer();
}

function page_profile(array $u): void {
    $self = is_staff($u) ? 'admin/profile.php' : 'user/profile.php';
    if (is_post()) { csrf_check();
        if (post('action') === 'profile') {
            $name = post('name'); $email = strtolower(post('email')); $phone = post('phone'); $err = null;
            if (strlen($name) < 3) $err = 'Please enter your full name.';
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Enter a valid email address.';
            elseif (val('SELECT 1 FROM users WHERE email=? AND id<>?', [$email, $u['id']])) $err = 'That email is used by another account.';
            if ($err) flash('error', $err);
            else {
                $ps = (int)post('pref_stylist_id') ?: null; $pv = (int)post('pref_service_id') ?: null;
                q('UPDATE users SET name=?,email=?,phone=?,gender=?,dob=?,address=?,pref_stylist_id=?,pref_service_id=? WHERE id=?', [$name, $email, $phone, post('gender'), post('dob'), post('address'), $u['role'] === 'client' ? $ps : null, $u['role'] === 'client' ? $pv : null, $u['id']]);
                flash('success', 'Profile updated successfully.');
            }
        } elseif (post('action') === 'password') {
            $cur = $_POST['current'] ?? ''; $new = $_POST['password'] ?? '';
            if (!password_verify($cur, $u['password'])) flash('error', 'Current password is incorrect.');
            elseif (strlen($new) < 6) flash('error', 'New password must be at least 6 characters.');
            elseif ($new !== ($_POST['confirm'] ?? '')) flash('error', 'New passwords do not match.');
            else { q('UPDATE users SET password=? WHERE id=?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]); flash('success', 'Password changed successfully.'); }
        }
        redirect($self);
    }
    $stylists = rows("SELECT id,name FROM users WHERE role='stylist' AND status=1"); $services = rows('SELECT id,name FROM services WHERE active=1');
    $tab = $_GET['tab'] ?? 'details';
    panel_header('Profile Settings', $u); ?>
    <div class="prof">
      <div class="box prof-card"><div class="avatar"><?= e(initials($u['name'])) ?></div><h3><?= e($u['name']) ?></h3><p class="muted small">@<?= e($u['username']) ?></p><div class="mt"><span class="badge b-<?= e($u['role']) ?>"><?= e(ucfirst($u['role'])) ?></span></div>
        <div class="sched" style="text-align:left;margin-top:20px"><div><?= icon('mail', 15) ?> <?= e($u['email']) ?></div><div><?= icon('phone', 15) ?> <?= e($u['phone']) ?></div><div><?= icon('calendar', 15) ?> Member since <?= fdate($u['created_at'], 'M Y') ?></div></div></div>
      <div class="box"><div class="tabs"><button class="<?= $tab === 'details' ? 'on' : '' ?>" data-tab="t1">Personal Details</button><button class="<?= $tab === 'password' ? 'on' : '' ?>" data-tab="t2">Change Password</button></div>
        <div class="tab <?= $tab === 'details' ? 'on' : '' ?>" id="t1"><form method="post" data-validate novalidate><?= csrf_field() ?><input type="hidden" name="action" value="profile">
          <div class="row2"><div class="fg"><label>Full Name</label><input type="text" name="name" required value="<?= e($u['name']) ?>"></div><div class="fg"><label>Username</label><input type="text" value="<?= e($u['username']) ?>" disabled></div></div>
          <div class="row2"><div class="fg"><label>Email</label><input type="email" name="email" required value="<?= e($u['email']) ?>"></div><div class="fg"><label>Phone</label><input type="tel" name="phone" value="<?= e($u['phone']) ?>"></div></div>
          <div class="row2"><div class="fg"><label>Gender</label><select name="gender"><option value="">Prefer not to say</option><?php foreach (['female', 'male', 'other'] as $g): ?><option value="<?= $g ?>" <?= $u['gender'] === $g ? 'selected' : '' ?>><?= ucfirst($g) ?></option><?php endforeach; ?></select></div><div class="fg"><label>Date of Birth</label><input type="date" name="dob" value="<?= e($u['dob']) ?>"></div></div>
          <div class="fg"><label>Address</label><input type="text" name="address" value="<?= e($u['address']) ?>"></div>
          <?php if ($u['role'] === 'client'): ?><div class="row2"><div class="fg"><label>Preferred Stylist</label><select name="pref_stylist_id"><option value="">No preference</option><?php foreach ($stylists as $s): ?><option value="<?= $s['id'] ?>" <?= $u['pref_stylist_id'] == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
            <div class="fg"><label>Favourite Service</label><select name="pref_service_id"><option value="">No preference</option><?php foreach ($services as $s): ?><option value="<?= $s['id'] ?>" <?= $u['pref_service_id'] == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></div></div><?php endif; ?>
          <button class="btn btn-gold"><?= icon('check', 16) ?> Save Changes</button></form></div>
        <div class="tab <?= $tab === 'password' ? 'on' : '' ?>" id="t2"><form method="post" data-validate novalidate><?= csrf_field() ?><input type="hidden" name="action" value="password">
          <div class="fg"><label>Current Password</label><input type="password" name="current" required></div>
          <div class="row2"><div class="fg"><label>New Password</label><input type="password" name="password" required data-strength="#pst"><div class="strength" id="pst"><i></i></div></div><div class="fg"><label>Confirm New Password</label><input type="password" name="confirm" required></div></div>
          <button class="btn btn-gold"><?= icon('lock', 16) ?> Update Password</button></form></div>
      </div></div>
    <?php panel_footer();
}
