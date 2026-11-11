<?php
// Shared POST handlers: cancel / reschedule / feedback (client) — staff reuse cancel/reschedule via $staff=true
function handle_appt_post(array $u, bool $staff = false): void {
    if (!is_post()) return;
    csrf_check();
    $act = post('action'); $id = (int)post('id');
    $a = row('SELECT a.*, s.name AS service FROM appointments a JOIN services s ON s.id=a.service_id WHERE a.id=?', [$id]);
    $back = $staff ? 'admin/appointments.php' : 'user/appointments.php';
    if (!$a || (!$staff && $a['client_id'] != $u['id']) || ($staff && $u['role'] === 'stylist' && $a['stylist_id'] != $u['id'])) { flash('error', 'Appointment not found.'); redirect($back); }
    if ($act === 'cancel') {
        if ($a['status'] !== 'confirmed') flash('error', 'Only confirmed appointments can be cancelled.');
        else { q("UPDATE appointments SET status='cancelled' WHERE id=?", [$id]);
            notify($a['client_id'], 'Appointment Cancelled', "Your {$a['service']} appointment on " . fdate($a['appt_date'], 'd M Y') . ' has been cancelled.', 'warning');
            notify($a['stylist_id'], 'Appointment Cancelled', "{$a['service']} on " . fdate($a['appt_date'], 'd M Y') . ' at ' . ftime($a['appt_time']) . ' was cancelled.', 'warning'); flash('success', 'Appointment cancelled.'); }
    } elseif ($act === 'reschedule') {
        $d = post('date'); $t = post('time');
        if ($a['status'] !== 'confirmed') flash('error', 'Only confirmed appointments can be rescheduled.');
        elseif (!in_array($t, array_column(slot_list($a['stylist_id'], $a['service_id'], $d, $id), 'time'))) flash('error', 'That time slot is not available. Please choose another.');
        else { $dur = (int)val('SELECT duration FROM services WHERE id=?', [$a['service_id']]); $end = date('H:i', strtotime("$t +$dur minutes"));
            q('UPDATE appointments SET appt_date=?, appt_time=?, end_time=? WHERE id=?', [$d, $t, $end, $id]);
            notify($a['client_id'], 'Appointment Rescheduled', "Your {$a['service']} appointment is now on " . fdate($d, 'd M Y') . ' at ' . ftime($t) . '.', 'success');
            notify($a['stylist_id'], 'Appointment Rescheduled', "{$a['service']} moved to " . fdate($d, 'd M Y') . ' at ' . ftime($t) . '.', 'info'); flash('success', 'Appointment rescheduled successfully.'); }
    } elseif ($act === 'feedback' && !$staff) {
        $r = (int)post('rating');
        if ($a['status'] === 'cancelled') flash('error', 'Feedback is not available for cancelled appointments.');
        elseif (val('SELECT 1 FROM feedback WHERE appointment_id=?', [$id])) flash('warning', 'You have already submitted feedback for this appointment.');
        elseif ($r < 1 || $r > 5) flash('error', 'Please choose a star rating.');
        else { q('INSERT INTO feedback (appointment_id,client_id,rating,comment) VALUES (?,?,?,?)', [$id, $u['id'], $r, post('comment')]); flash('success', 'Thank you for your feedback!'); }
    } elseif ($act === 'status' && $staff) {
        $st = post('status');
        if (in_array($st, ['confirmed', 'completed', 'cancelled'])) { q('UPDATE appointments SET status=? WHERE id=?', [$st, $id]); notify($a['client_id'], 'Appointment ' . ucfirst($st), "Your {$a['service']} appointment on " . fdate($a['appt_date'], 'd M Y') . " is now $st.", $st === 'cancelled' ? 'warning' : 'success'); flash('success', 'Status updated.'); }
    }
    redirect($back . ($_POST['qs'] ?? ''));
}
// Reschedule modal markup + JS (shared by client and staff pages)
function reschedule_modal(): void {
    modal_open('rescModal', 'Reschedule Appointment', 'lg'); ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="reschedule"><input type="hidden" name="id" id="rId"><input type="hidden" name="date" id="rDate"><input type="hidden" name="time" id="rTime">
      <p class="muted small" id="rInfo" style="margin-bottom:14px"></p>
      <div class="grid g2" style="gap:22px"><div id="rCal"></div><div><div class="lbl" id="rTitle">Select a new date</div><div class="slots" id="rSlots"></div></div></div>
      <button class="btn btn-gold btn-block mt" id="rBtn" disabled>Confirm New Time</button></form>
    <?php modal_close(); ?>
<script>
(function(){let cal,cur;const td=new Date(),tiso=td.getFullYear()+'-'+String(td.getMonth()+1).padStart(2,'0')+'-'+String(td.getDate()).padStart(2,'0');
document.addEventListener('click',e=>{const b=e.target.closest('[data-resc]');if(!b)return;cur=JSON.parse(b.dataset.resc);document.getElementById('rId').value=cur.id;document.getElementById('rInfo').innerHTML=`<b>${cur.service}</b> with ${cur.stylist}. Currently ${cur.when}.`;
 document.getElementById('rSlots').innerHTML='';document.getElementById('rTitle').textContent='Select a new date';document.getElementById('rBtn').disabled=true;
 const days=cur.days.split(',').map(Number);cal=new Calendar(document.getElementById('rCal'),{mini:true,isDisabled:(k,d)=>k<tiso||!days.includes((d.getDay()+6)%7+1),onPick:async k=>{document.getElementById('rDate').value=k;document.getElementById('rTime').value='';document.getElementById('rBtn').disabled=true;
 const sl=await fetchSlots(cur.stylist_id,cur.service_id,k,cur.id);document.getElementById('rTitle').textContent=sl.length?'Available times':'No free slots on this day';
 document.getElementById('rSlots').innerHTML=sl.map(s=>`<button type="button" class="slot" data-t="${s.time}">${s.label}</button>`).join('');
 document.querySelectorAll('#rSlots .slot').forEach(x=>x.onclick=()=>{document.querySelectorAll('#rSlots .slot').forEach(y=>y.classList.remove('sel'));x.classList.add('sel');document.getElementById('rTime').value=x.dataset.t;document.getElementById('rBtn').disabled=false;});}});});})();
</script>
<?php }
function resc_json(array $a): string {
    $d = val('SELECT work_days FROM stylist_profiles WHERE user_id=?', [$a['stylist_id']]);
    return e(json_encode(['id' => $a['id'], 'service' => $a['service'], 'stylist' => $a['stylist'], 'stylist_id' => $a['stylist_id'], 'service_id' => $a['service_id'], 'days' => $d, 'when' => fdate($a['appt_date'], 'd M Y') . ' at ' . ftime($a['appt_time'])]));
}
