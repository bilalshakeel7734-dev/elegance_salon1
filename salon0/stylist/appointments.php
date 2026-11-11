<?php
require_once __DIR__ . '/../includes/layout.php'; require_once __DIR__ . '/../includes/appt_actions.php';
$u = require_role('admin', 'receptionist', 'stylist');
$isSt = $u['role'] === 'stylist'; $canPay = !$isSt;
if (is_post() && post('action') === 'new' && !$isSt) {
    csrf_check();
    try { create_appointment((int)post('client_id'), (int)post('stylist_id'), (int)post('service_id'), post('date'), post('time'), post('notes')); flash('success', 'Appointment booked and client notified.'); }
    catch (Exception $ex) { flash('error', $ex->getMessage()); }
    redirect('admin/appointments.php');
}
if (is_post() && post('action') === 'pay' && $canPay) {
    csrf_check(); $a = row('SELECT * FROM appointments WHERE id=?', [(int)post('id')]);
    if ($a && !val('SELECT 1 FROM payments WHERE appointment_id=?', [$a['id']])) {
        q('INSERT INTO payments (appointment_id,invoice_no,amount,method,status) VALUES (?,?,?,?,?)', [$a['id'], 'INV-' . (1000 + $a['id']), (int)post('amount'), post('method', 'cash'), 'paid']);
        q("UPDATE appointments SET status='completed' WHERE id=?", [$a['id']]);
        notify($a['client_id'], 'Payment Received', 'Thank you! Your payment was received. Invoice INV-' . (1000 + $a['id']) . ' is available in your account.', 'success'); flash('success', 'Payment recorded, appointment completed and invoice generated.');
    } else flash('error', 'Payment already recorded or appointment not found.');
    redirect('admin/appointments.php');
}
handle_appt_post($u, true);
$f = $_GET['f'] ?? 'all'; $date = $_GET['date'] ?? '';
$where = '1=1'; $par = [];
if ($isSt) { $where .= ' AND a.stylist_id=?'; $par[] = $u['id']; }
if (in_array($f, ['confirmed', 'completed', 'cancelled'])) { $where .= ' AND a.status=?'; $par[] = $f; }
if ($f === 'today') { $where .= ' AND a.appt_date=?'; $par[] = date('Y-m-d'); }
if ($date) { $where .= ' AND a.appt_date=?'; $par[] = $date; }
$list = rows(appt_query($where, 'a.appt_date DESC, a.appt_time DESC'), $par);
$clients = rows("SELECT id,name,phone FROM users WHERE role='client' AND status=1 ORDER BY name");
$services = rows('SELECT id,name,price FROM services WHERE active=1'); $stylists = rows("SELECT id,name FROM users WHERE role='stylist' AND status=1");
$ss = []; foreach (rows('SELECT * FROM stylist_services') as $r) $ss[$r['stylist_id']][] = (int)$r['service_id'];
panel_header($isSt ? 'My Appointments' : 'Appointments', $u);
?>
<div class="toolbar"><input type="search" class="grow" placeholder="Search client, service, stylist…" data-search="#apTbl">
  <select onchange="location='?f='+this.value"><?php foreach (['all' => 'All', 'today' => 'Today', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $k => $l): ?><option value="<?= $k ?>" <?= $f === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
  <?php if (!$isSt): ?><button class="btn btn-gold btn-sm" data-open="newModal"><?= icon('plus', 16) ?> New Appointment</button><?php endif; ?></div>
<div class="box"><?php if (!$list): ?><div class="empty"><?= icon('calendar', 44) ?><p>No appointments found.</p></div><?php else: ?>
<div class="tbl-wrap"><table id="apTbl"><thead><tr><th>Ref</th><th>Client</th><th>Service</th><th>Stylist</th><th>Date &amp; Time</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($list as $a): $conf = $a['status'] === 'confirmed'; ?>
<tr><td><b><?= e($a['ref']) ?></b></td><td><?= e($a['client']) ?><br><small class="muted"><?= e($a['cphone']) ?></small></td><td><?= e($a['service']) ?></td><td><?= e($a['stylist']) ?></td><td><?= fdate($a['appt_date'], 'd M Y') ?><br><small class="muted"><?= ftime($a['appt_time']) ?> - <?= ftime($a['end_time']) ?></small></td><td><?= money($a['price']) ?></td><td><?= badge($a['status']) ?></td>
<td><div class="acts">
 <?php if ($conf && $canPay): ?><button class="btn btn-gold btn-xs" data-open="payModal" data-json='<?= e(json_encode(['id' => $a['id'], 'amount' => $a['price'], 'payinfo' => $a['client'] . ' - ' . $a['service']])) ?>'><?= icon('money', 13) ?> Collect</button><?php endif; ?>
 <?php if ($conf && $isSt): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= $a['id'] ?>"><input type="hidden" name="status" value="completed"><button class="btn btn-gold btn-xs" data-confirm="Mark this appointment as completed?"><?= icon('check', 13) ?> Complete</button></form><?php endif; ?>
 <?php if ($conf): ?><button class="btn btn-ghost btn-xs" data-open="rescModal" data-resc='<?= resc_json($a) ?>'>Reschedule</button>
 <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn btn-red btn-xs" data-confirm="Cancel this appointment?">Cancel</button></form><?php endif; ?>
 <?php if ($a['pay_id'] && $canPay): ?><a class="btn btn-dark btn-xs" href="<?= url('admin/invoice.php?id=' . $a['id']) ?>">Invoice</a><?php endif; ?>
</div></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>
<?php reschedule_modal();
if (!$isSt): modal_open('newModal', 'New Appointment', 'lg'); ?>
<form method="post" data-validate novalidate><?= csrf_field() ?><input type="hidden" name="action" value="new"><input type="hidden" name="time" id="nTime">
  <div class="row2"><div class="fg"><label>Client</label><select name="client_id" required><option value="">Select client</option><?php foreach ($clients as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?> (<?= e($c['phone']) ?>)</option><?php endforeach; ?></select></div>
    <div class="fg"><label>Service</label><select name="service_id" id="nSvc" required><option value="">Select service</option><?php foreach ($services as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name']) ?> - <?= money($s['price']) ?></option><?php endforeach; ?></select></div></div>
  <div class="row2"><div class="fg"><label>Stylist</label><select name="stylist_id" id="nSty" required><option value="">Select service first</option></select></div><div class="fg"><label>Date</label><input type="date" name="date" id="nDate" min="<?= date('Y-m-d') ?>" required></div></div>
  <div class="fg"><div class="lbl" id="nTitle">Available times</div><div class="slots" id="nSlots"><span class="hint">Choose service, stylist and date.</span></div></div>
  <div class="fg"><label>Notes</label><textarea name="notes" style="min-height:60px"></textarea></div><button class="btn btn-gold btn-block">Book Appointment</button></form>
<?php modal_close(); endif;
if ($canPay): modal_open('payModal', 'Collect Payment', 'sm'); ?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="pay"><input type="hidden" name="id"><p class="muted small" data-text="payinfo"></p>
  <div class="fg mt"><label>Amount (Rs)</label><input type="number" name="amount" min="0" required></div><div class="fg"><label>Method</label><select name="method"><option value="cash">Cash</option><option value="card">Card</option><option value="online">Online Transfer</option></select></div>
  <button class="btn btn-gold btn-block">Record Payment &amp; Complete</button></form><?php modal_close(); endif; ?>
<script>
const SS=<?= json_encode($ss) ?>, STY=<?= json_encode($stylists) ?>;
const nSvc=document.getElementById('nSvc'),nSty=document.getElementById('nSty'),nDate=document.getElementById('nDate');
async function nLoad(){if(!nSvc||!nSvc.value||!nSty.value||!nDate.value)return;const sl=await fetchSlots(nSty.value,nSvc.value,nDate.value);document.getElementById('nTime').value='';
 document.getElementById('nSlots').innerHTML=sl.length?sl.map(s=>`<button type="button" class="slot" data-t="${s.time}">${s.label}</button>`).join(''):'<span class="hint">No free slots, try another date or stylist.</span>';
 document.querySelectorAll('#nSlots .slot').forEach(b=>b.onclick=()=>{document.querySelectorAll('#nSlots .slot').forEach(x=>x.classList.remove('sel'));b.classList.add('sel');document.getElementById('nTime').value=b.dataset.t;});}
nSvc?.addEventListener('change',()=>{nSty.innerHTML='<option value="">Select stylist</option>'+STY.filter(s=>(SS[s.id]||[]).includes(+nSvc.value)).map(s=>`<option value="${s.id}">${s.name}</option>`).join('');nLoad();});
nSty?.addEventListener('change',nLoad);nDate?.addEventListener('change',nLoad);
document.querySelector('#newModal form')?.addEventListener('submit',e=>{if(!document.getElementById('nTime').value){e.preventDefault();showErr(document.getElementById('nDate'),'Please select a time slot');}});
</script>
<?php panel_footer();
