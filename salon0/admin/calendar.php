<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin', 'receptionist', 'stylist');
$w = $u['role'] === 'stylist' ? 'a.stylist_id=' . (int)$u['id'] : '1=1'; $ev = [];
foreach (rows(appt_query($w)) as $a) $ev[$a['appt_date']][] = ['t' => ftime($a['appt_time']) . ' ' . $a['client'], 's' => $a['status'], 'client' => $a['client'], 'service' => $a['service'], 'stylist' => $a['stylist'], 'time' => ftime($a['appt_time']) . ' - ' . ftime($a['end_time'])];
panel_header('Calendar', $u);
?>
<div class="panel-grid"><div class="box"><div id="cal"></div></div><div class="box"><div class="box-h"><h3 id="dayTitle">Select a day</h3></div><div id="dayList"><p class="muted small">Click a date to see the day's appointments and shifts.</p></div>
<div class="mt small muted"><span class="ev" style="display:inline-block">Confirmed</span> <span class="ev completed" style="display:inline-block">Completed</span> <span class="ev cancelled" style="display:inline-block">Cancelled</span></div></div></div>
<script>
const EV=<?= json_encode($ev) ?>;
new Calendar(document.getElementById('cal'),{events:EV,onPick:(k,evs)=>{document.getElementById('dayTitle').textContent=new Date(k+'T00:00').toLocaleDateString('en-GB',{weekday:'long',day:'numeric',month:'long'});
 document.getElementById('dayList').innerHTML=evs.length?evs.map(e=>`<div class="mini"><div class="dt"><b>${e.time.slice(0,5)}</b><small>${e.time.slice(6,8)}</small></div><div><h4>${e.client}</h4><small class="muted">${e.service} &middot; ${e.stylist}</small><br><span class="badge b-${e.s}">${e.s}</span></div></div>`).join(''):'<p class="muted small">No appointments on this day.</p>';}});
</script>
<?php panel_footer();
