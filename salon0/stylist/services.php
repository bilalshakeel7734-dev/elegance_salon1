<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin');
if (is_post()) { csrf_check(); $act = post('action'); $id = (int)post('id');
    if ($act === 'save') {
        if (!post('name') || (int)post('price') <= 0 || (int)post('duration') <= 0) flash('error', 'Name, price and duration are required.');
        else { $d = [post('name'), post('category'), post('description'), post('benefits'), (int)post('price'), (int)post('duration'), post('image'), post('icon') ?: 'sparkle', in_array(post('audience'), ['women', 'men', 'unisex'], true) ? post('audience') : 'unisex', (int)isset($_POST['active'])];
            if ($id) q('UPDATE services SET name=?,category=?,description=?,benefits=?,price=?,duration=?,image=?,icon=?,audience=?,active=? WHERE id=?', [...$d, $id]); else q('INSERT INTO services (name,category,description,benefits,price,duration,image,icon,audience,active) VALUES (?,?,?,?,?,?,?,?,?,?)', $d); flash('success', 'Service saved.'); }
    } elseif ($act === 'delete') { if (val('SELECT 1 FROM appointments WHERE service_id=?', [$id])) { q('UPDATE services SET active=0 WHERE id=?', [$id]); flash('success', 'Service has bookings, so it was hidden instead of deleted.'); } else { q('DELETE FROM services WHERE id=?', [$id]); q('DELETE FROM stylist_services WHERE service_id=?', [$id]); flash('success', 'Service deleted.'); } }
    redirect('admin/services.php');
}
$list = rows('SELECT s.*, (SELECT COUNT(*) FROM appointments a WHERE a.service_id=s.id) AS bookings FROM services s ORDER BY id');
panel_header('Services', $u);
?>
<div class="toolbar"><input type="search" class="grow" placeholder="Search services…" data-search="#svTbl"><button class="btn btn-gold btn-sm" data-open="svModal" data-reset><?= icon('plus', 16) ?> Add Service</button></div>
<div class="box"><div class="tbl-wrap"><table id="svTbl"><thead><tr><th>Service</th><th>Category</th><th>Price</th><th>Duration</th><th>Bookings</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($list as $s): ?><tr><td><div style="display:flex;gap:12px;align-items:center"><div style="width:48px;height:48px;border-radius:10px;overflow:hidden;background:var(--gold-l)"><?= img_tag($s['image'], '', '') ?></div><b><?= e($s['name']) ?></b></div></td><td><?= e($s['category']) ?><br><small class="muted"><?= aud_label($s['audience'] ?? 'unisex') ?></small></td><td><?= money($s['price']) ?></td><td><?= $s['duration'] ?> min</td><td><?= $s['bookings'] ?></td><td><?= $s['active'] ? '<span class="badge b-ok">Active</span>' : '<span class="badge b-cancelled">Hidden</span>' ?></td>
<td><div class="acts"><button class="btn btn-ghost btn-xs" data-open="svModal" data-json='<?= e(json_encode($s)) ?>'><?= icon('edit', 13) ?></button><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button class="btn btn-red btn-xs" data-confirm="Delete this service?"><?= icon('trash', 13) ?></button></form></div></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php modal_open('svModal', 'Service Details', 'lg'); ?>
<form method="post" data-validate novalidate><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id">
 <div class="row2"><div class="fg"><label>Name *</label><input type="text" name="name" required></div><div class="fg"><label>Category</label><input type="text" name="category" placeholder="Hair, Nails, Skin…"></div></div>
 <div class="row3"><div class="fg"><label>Price (Rs) *</label><input type="number" name="price" min="1" required></div><div class="fg"><label>Duration (min) *</label><input type="number" name="duration" min="15" step="15" required></div><div class="fg"><label>Icon</label><select name="icon"><?php foreach (['scissors', 'razor', 'hand', 'foot', 'sparkle', 'drop', 'leaf', 'crown', 'lotus', 'feather'] as $i): ?><option><?= $i ?></option><?php endforeach; ?></select></div></div>
 <div class="fg"><label>For</label><select name="audience"><option value="women">Women</option><option value="men">Men</option><option value="unisex">Women &amp; Men</option></select></div>
 <div class="fg"><label>Image URL</label><input type="text" name="image" placeholder="https://…"></div><div class="fg"><label>Description</label><textarea name="description" style="min-height:70px"></textarea></div>
 <div class="fg"><label>Benefits (separate with |)</label><input type="text" name="benefits"></div><label class="chk fg"><input type="checkbox" name="active" value="1" checked> Active (visible to clients)</label>
 <button class="btn btn-gold btn-block">Save Service</button></form><?php modal_close(); panel_footer();
