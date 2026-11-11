<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_client();
$ev = [];
foreach (rows(appt_query('a.client_id=?'), [$u['id']]) as $a) $ev[$a['appt_date']][] = ['t' => ftime($a['appt_time']) . ' ' . $a['service'], 's' => $a['status'], 'id' => $a['id'], 'stylist' => $a['stylist'], 'time' => ftime($a['appt_time']) . ' - ' . ftime($a['end_time']), 'price' => money($a['price']), 'service' => $a['service']];
panel_header('My Calendar', $u);
?>
<div class="panel-grid"><div class="box"><div id="cal"></div></div>
  <div class="box"><div class="box-h"><h3 id="dayTitle">Select a day</h3></div><div id="dayList"><p class="muted small">Click any date to see your appointments for that day.</p></div><a href="<?= url('user/book.php') ?>" class="btn btn-gold btn-block mt"><?= icon('plus', 16) ?> Book Appointment</a>
  <div class="mt small muted"><span class="ev" style="display:inline-block">Confirmed</span> <span class="ev completed" style="display:inline-block">Completed</span> <span class="ev cancelled" style="display:inline-block">Cancelled</span></div></div></div>
<script>
const EV=<?= json_encode($ev) ?>;
new Calendar(document.getElementById('cal'),{events:EV,onPick:(k,evs)=>{document.getElementById('dayTitle').textContent=new Date(k+'T00:00').toLocaleDateString('en-GB',{weekday:'long',day:'numeric',month:'long'});
 document.getElementById('dayList').innerHTML=evs.length?evs.map(e=>`<div class="mini"><div class="dt"><b>${e.time.slice(0,5)}</b><small>${e.time.slice(6,8)}</small></div><div><h4>${e.service}</h4><small class="muted">${e.stylist} &middot; ${e.price}</small><br><span class="badge b-${e.s}">${e.s}</span></div></div>`).join(''):'<p class="muted small">No appointments on this day.</p>';}});
</script>
<?php panel_footer();
