<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_client();
if (is_post()) {
    csrf_check();
    try {
        $id = create_appointment($u['id'], (int)post('stylist_id'), (int)post('service_id'), post('date'), post('time'), post('notes'));
        $_SESSION['booked_id'] = $id; redirect('user/appointments.php');
    } catch (Exception $ex) { flash('error', $ex->getMessage()); redirect('user/book.php'); }
}
$services = rows('SELECT id,name,category,price,duration,image,icon,audience FROM services WHERE active=1 ORDER BY id');
$stylists = rows('SELECT u.id,u.name,p.title,p.photo,p.work_days,p.start_time,p.end_time FROM users u JOIN stylist_profiles p ON p.user_id=u.id WHERE u.role="stylist" AND u.status=1');
foreach ($stylists as &$s) { $s['service_ids'] = array_map('intval', array_column(rows('SELECT service_id FROM stylist_services WHERE stylist_id=?', [$s['id']]), 'service_id')); $s['days_label'] = days_label($s['work_days']); $s['hours'] = ftime($s['start_time']) . ' - ' . ftime($s['end_time']); } unset($s);
panel_header('Book Appointment', $u);
?>
<div class="steps" id="steps"><div class="step on"><i>1</i><span>Service</span></div><div class="step"><i>2</i><span>Stylist</span></div><div class="step"><i>3</i><span>Date &amp; Time</span></div><div class="step"><i>4</i><span>Confirm</span></div></div>
<form method="post" id="bookForm"><?= csrf_field() ?>
<input type="hidden" name="service_id" id="fSvc"><input type="hidden" name="stylist_id" id="fSty"><input type="hidden" name="date" id="fDate"><input type="hidden" name="time" id="fTime">
<div class="wiz"><div class="box" style="margin:0">
  <div class="pane on" data-p="1"><h3 class="mt" style="margin-bottom:6px">Choose a service</h3><p class="muted small" style="margin-bottom:18px">Manicure, pedicure, hair styling, facial and more.</p>
    <div class="pick"><?php foreach ($services as $s): ?><div class="opt" data-svc="<?= $s['id'] ?>"><div class="oi"><?= img_tag($s['image'], $s['name']) ?></div><div class="ob"><h4><?= e($s['name']) ?></h4><small><?= $s['duration'] ?> min &middot; <?= aud_label($s['audience']) ?></small> &middot; <b style="color:var(--gold-d)"><?= money($s['price']) ?></b></div></div><?php endforeach; ?></div></div>
  <div class="pane" data-p="2"><h3 style="margin-bottom:6px">Choose your stylist</h3><p class="muted small" style="margin-bottom:18px">Only stylists who perform your selected service are shown.</p><div class="pick" id="styList"></div></div>
  <div class="pane" data-p="3"><h3 style="margin-bottom:6px">Pick date &amp; time</h3><p class="muted small" id="schedNote" style="margin-bottom:14px"></p>
    <div class="grid g2" style="gap:24px"><div id="miniCal"></div><div><div class="lbl" id="slotTitle">Select a date to see available times</div><div class="slots" id="slots"></div></div></div></div>
  <div class="pane" data-p="4"><h3 style="margin-bottom:14px">Review &amp; confirm</h3><div class="confirm-card" id="reviewCard"></div>
    <div class="fg"><label>Special requests (optional)</label><textarea name="notes" placeholder="Any preferences or allergies we should know about?"></textarea></div>
    <div class="note-box">Free cancellation or rescheduling up to your appointment time from My Appointments.</div></div>
  <div class="wiz-nav"><button type="button" class="btn btn-ghost" id="prev" style="visibility:hidden">&larr; Back</button><button type="button" class="btn btn-gold" id="next" disabled>Continue &rarr;</button></div>
</div>
<aside class="box summary" style="margin:0"><h3>Booking Summary</h3>
  <div class="sum-row"><span>Service</span><b id="sSvc">-</b></div><div class="sum-row"><span>Stylist</span><b id="sSty">-</b></div><div class="sum-row"><span>Date</span><b id="sDate">-</b></div><div class="sum-row"><span>Time</span><b id="sTime">-</b></div><div class="sum-row"><span>Duration</span><b id="sDur">-</b></div>
  <div class="sum-total"><span>Total</span><b id="sTot">Rs 0</b></div></aside></div></form>
<script>
const SV=<?= json_encode($services) ?>, ST=<?= json_encode($stylists) ?>, PREF=<?= (int)$u['pref_stylist_id'] ?>, MON=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
let step=1, S={svc:null,sty:null,date:null,time:null}, cal=null;
const money=n=>'Rs '+Number(n).toLocaleString(), $id=i=>document.getElementById(i);
function show(n){step=n;$$('.pane').forEach(p=>p.classList.toggle('on',+p.dataset.p===n));$$('.step').forEach((s,i)=>{s.classList.toggle('on',i+1===n);s.classList.toggle('done',i+1<n)});
  $id('prev').style.visibility=n>1?'visible':'hidden';$id('next').textContent=n===4?'Confirm Booking ✓':'Continue →';check();
  if(n===2)drawSty();if(n===3)drawCal();if(n===4)review();scrollTo({top:0,behavior:'smooth'});}
function check(){$id('next').disabled=!((step===1&&S.svc)||(step===2&&S.sty)||(step===3&&S.time)||step===4);}
function sum(){const s=SV.find(x=>x.id==S.svc),t=ST.find(x=>x.id==S.sty);$id('sSvc').textContent=s?s.name:'-';$id('sSty').textContent=t?t.name:'-';$id('sDate').textContent=S.date?new Date(S.date+'T00:00').toLocaleDateString('en-GB',{weekday:'short',day:'numeric',month:'short',year:'numeric'}):'-';$id('sTime').textContent=S.time?fmtTime(S.time):'-';$id('sDur').textContent=s?s.duration+' min':'-';$id('sTot').textContent=money(s?s.price:0);}
$$('[data-svc]').forEach(o=>o.onclick=()=>{$$('[data-svc]').forEach(x=>x.classList.remove('sel'));o.classList.add('sel');if(S.svc!=o.dataset.svc){S.svc=+o.dataset.svc;const t=ST.find(x=>x.id==S.sty);if(!t||!t.service_ids.includes(S.svc))S.sty=null;S.date=S.time=null;}sum();check();});
function drawSty(){const l=ST.filter(t=>t.service_ids.includes(S.svc));$id('styList').innerHTML=l.length?l.map(t=>`<div class="opt stp ${S.sty==t.id?'sel':''}" data-sty="${t.id}"><div class="oi"><img src="${t.photo}" alt="" onerror="this.classList.add('img-fail')"></div><div class="ob"><h4>${t.name}${t.id==PREF?' ★':''}</h4><small>${t.title}</small><br><small>${t.days_label}<br>${t.hours}</small></div></div>`).join(''):'<div class="empty">No stylist available for this service.</div>';
  $$('[data-sty]').forEach(o=>o.onclick=()=>{$$('[data-sty]').forEach(x=>x.classList.remove('sel'));o.classList.add('sel');if(S.sty!=o.dataset.sty){S.sty=+o.dataset.sty;S.date=S.time=null;}sum();check();});}
function drawCal(){const t=ST.find(x=>x.id==S.sty),days=t.work_days.split(',').map(Number);$id('schedNote').innerHTML=`${t.name} works <b>${t.days_label}</b>, ${t.hours}. Unavailable days are greyed out.`;
  const today=new Date().toISOString().slice(0,10).replace(/-/g,'-');const td=new Date();const tiso=td.getFullYear()+'-'+String(td.getMonth()+1).padStart(2,'0')+'-'+String(td.getDate()).padStart(2,'0');
  cal=new Calendar($id('miniCal'),{mini:true,isDisabled:(k,d)=>k<tiso||!days.includes((d.getDay()+6)%7+1),onPick:async k=>{S.date=k;S.time=null;sum();check();$id('slotTitle').textContent='Loading times…';$id('slots').innerHTML='';
    const sl=await fetchSlots(S.sty,S.svc,k);$id('slotTitle').textContent=sl.length?'Available times':'No free slots on this day, please pick another date.';
    $id('slots').innerHTML=sl.map(s=>`<button type="button" class="slot" data-t="${s.time}">${s.label}</button>`).join('');
    $$('.slot').forEach(b=>b.onclick=()=>{$$('.slot').forEach(x=>x.classList.remove('sel'));b.classList.add('sel');S.time=b.dataset.t;sum();check();});}});if(S.date)cal.select(S.date);}
function review(){const s=SV.find(x=>x.id==S.svc),t=ST.find(x=>x.id==S.sty);$id('reviewCard').innerHTML=`<div><span>Service</span><b>${s.name}</b></div><div><span>Stylist</span><b>${t.name}</b></div><div><span>Date</span><b>${$id('sDate').textContent}</b></div><div><span>Time</span><b>${fmtTime(S.time)}</b></div><div><span>Duration</span><b>${s.duration} min</b></div><div><span>Total</span><b>${money(s.price)}</b></div>`;}
$id('next').onclick=()=>{if(step<4)show(step+1);else{$id('fSvc').value=S.svc;$id('fSty').value=S.sty;$id('fDate').value=S.date;$id('fTime').value=S.time;$id('next').disabled=true;$id('next').textContent='Booking…';$id('bookForm').submit();}};
$id('prev').onclick=()=>show(step-1);
const q=new URLSearchParams(location.search);
if(q.get('stylist')&&!q.get('service')){const t=ST.find(x=>x.id==q.get('stylist'));if(t){const first=$$('[data-svc]').filter(o=>t.service_ids.includes(+o.dataset.svc));$$('[data-svc]').forEach(o=>{if(!t.service_ids.includes(+o.dataset.svc))o.style.display='none'});}}
if(q.get('service')){const o=$(`[data-svc="${q.get('service')}"]`);if(o){o.click();if(q.get('stylist')&&ST.find(x=>x.id==q.get('stylist'))?.service_ids.includes(+q.get('service'))){S.sty=+q.get('stylist');show(3);}else show(2);}}
else if(q.get('stylist')){S.sty=+q.get('stylist');}
</script>
<?php panel_footer();
