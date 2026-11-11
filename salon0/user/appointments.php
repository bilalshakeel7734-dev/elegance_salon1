<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/appt_actions.php';
$u = require_client();
handle_appt_post($u);
$booked = null;
if (!empty($_SESSION['booked_id'])) { $booked = row(appt_query('a.id=? AND a.client_id=?'), [$_SESSION['booked_id'], $u['id']]); unset($_SESSION['booked_id']); }
$f = $_GET['f'] ?? 'all';
$all = rows(appt_query('a.client_id=?'), [$u['id']]);
$list = array_filter($all, fn($a) => match ($f) { 'upcoming' => $a['status'] === 'confirmed' && appt_is_future($a), 'completed' => $a['status'] === 'completed', 'cancelled' => $a['status'] === 'cancelled', default => true });
panel_header('My Appointments', $u);
?>
<div class="toolbar"><div class="tabs" style="margin:0;border:0;flex:1">
  <?php foreach (['all' => 'All', 'upcoming' => 'Upcoming', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $k => $l): ?><a href="?f=<?= $k ?>"><button type="button" class="<?= $f === $k ? 'on' : '' ?>"><?= $l ?></button></a><?php endforeach; ?></div>
  <a href="<?= url('user/book.php') ?>" class="btn btn-gold btn-sm"><?= icon('plus', 16) ?> New Booking</a></div>
<div class="box"><?php if (!$list): ?><div class="empty"><?= icon('calendar', 44) ?><p>No appointments found.</p><a href="<?= url('user/book.php') ?>" class="btn btn-gold btn-sm mt">Book Now</a></div><?php else: ?>
<div class="tbl-wrap"><table><thead><tr><th>Ref</th><th>Service</th><th>Stylist</th><th>Date &amp; Time</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($list as $a): $can = $a['status'] === 'confirmed' && appt_is_future($a); ?>
<tr><td><b><?= e($a['ref']) ?></b></td><td><?= e($a['service']) ?></td><td><?= e($a['stylist']) ?></td><td><?= fdate($a['appt_date'], 'd M Y') ?><br><small class="muted"><?= ftime($a['appt_time']) ?> - <?= ftime($a['end_time']) ?></small></td><td><?= money($a['price']) ?></td><td><?= badge($a['status']) ?></td>
<td><div class="acts">
  <?php if ($can): ?><button class="btn btn-ghost btn-xs" data-open="rescModal" data-resc='<?= resc_json($a) ?>'>Reschedule</button>
  <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn btn-red btn-xs" data-confirm="Cancel this appointment?">Cancel</button></form><?php endif; ?>
  <?php if ($a['status'] !== 'cancelled'): if ($a['fb_id']): ?><span class="badge b-ok">Feedback given ✓</span><?php else: ?><button class="btn btn-gold btn-xs" data-open="fbModal" data-json='<?= e(json_encode(['id' => $a['id'], 'fbinfo' => $a['service'] . ' with ' . $a['stylist']])) ?>'><?= icon('star', 13) ?> Feedback</button><?php endif; endif; ?>
  <?php if ($a['pay_id']): ?><a class="btn btn-dark btn-xs" href="<?= url('user/invoice.php?id=' . $a['id']) ?>">Invoice</a><?php endif; ?>
</div></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>

<?php reschedule_modal();
modal_open('fbModal', 'How was your experience?', 'sm'); ?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="feedback"><input type="hidden" name="id" value="">
  <p class="muted center small" data-text="fbinfo"></p>
  <div class="rate"><?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" name="rating" id="rt<?= $i ?>" value="<?= $i ?>" required><label for="rt<?= $i ?>" title="<?= $i ?> stars">★</label><?php endfor; ?></div>
  <div class="fg"><textarea name="comment" placeholder="Tell us what you loved or how we can improve…"></textarea></div>
  <button class="btn btn-gold btn-block">Submit Feedback</button><p class="hint center mt">You can submit feedback only once per appointment.</p></form>
<?php modal_close();
if ($booked): modal_open('bookedModal', '', 'sm'); ?>
<div class="center"><svg class="success-ring" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45"/><path d="M30 52l14 14 27-30"/></svg>
  <h3 style="font-size:1.7rem">Booking Confirmed!</h3><p class="muted small">Your appointment has been booked successfully. A confirmation was sent to your notifications.</p>
  <div class="confirm-card"><div><span>Reference</span><b><?= e($booked['ref']) ?></b></div><div><span>Service</span><b><?= e($booked['service']) ?></b></div><div><span>Stylist</span><b><?= e($booked['stylist']) ?></b></div><div><span>Date</span><b><?= fdate($booked['appt_date']) ?></b></div><div><span>Time</span><b><?= ftime($booked['appt_time']) ?> - <?= ftime($booked['end_time']) ?></b></div><div><span>Total</span><b><?= money($booked['price']) ?></b></div></div>
  <div class="acts" style="justify-content:center"><button class="btn btn-gold btn-sm" data-close data-open="fbModal" data-json='<?= e(json_encode(['id' => $booked['id'], 'fbinfo' => $booked['service'] . ' with ' . $booked['stylist']])) ?>'><?= icon('star', 15) ?> Give Feedback</button><button class="btn btn-ghost btn-sm" data-close>Done</button></div></div>
<?php modal_close(); ?><script>window.addEventListener('load',()=>setTimeout(()=>openModal('bookedModal'),500));</script><?php endif;
panel_footer();
