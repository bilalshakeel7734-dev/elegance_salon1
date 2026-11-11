<?php
require_once __DIR__ . '/includes/layout.php';
require_login();
$services = rows('SELECT * FROM services WHERE active=1 ORDER BY id');
$cats = array_values(array_unique(array_column($services, 'category')));
$cnt = ['all' => count($services), 'women' => 0, 'men' => 0];
foreach ($services as $s) { if ($s['audience'] !== 'men') $cnt['women']++; if ($s['audience'] !== 'women') $cnt['men']++; }
$for = in_array($_GET['for'] ?? '', ['women', 'men'], true) ? $_GET['for'] : 'all';
site_header('Our Services', 'services');
?>
<section class="page-hero" style="--bg:url(<?= site_img('services') ?>)"><div class="container"><h1>Our Services</h1><p>Hair, nails, skin, grooming and spa treatments, crafted for both women and men.</p><div class="crumbs">HOME / SERVICES</div></div></section>
<section class="section svc-page"><div class="container">
  <div class="aud-wrap rv"><div class="aud-tabs"><span class="ind"></span>
    <?php foreach (['all' => 'All', 'women' => 'Women', 'men' => 'Men'] as $k => $lbl): ?><button type="button" data-aud="<?= $k ?>" class="<?= $for === $k ? 'on' : '' ?>"><?= $lbl ?> <em><?= $cnt[$k] ?></em></button><?php endforeach; ?></div></div>
  <div class="chips rv"><button type="button" class="on" data-f="all">All</button><?php foreach ($cats as $c): ?><button type="button" data-f="<?= e($c) ?>"><?= e($c) ?></button><?php endforeach; ?></div>
  <div class="svc-empty" id="svcEmpty" hidden>No services in this selection yet. Try another category.</div>
  <div class="grid g3 svc-grid">
    <?php foreach ($services as $s): $st = rows('SELECT u.name FROM stylist_services ss JOIN users u ON u.id=ss.stylist_id WHERE ss.service_id=?', [$s['id']]); ?>
    <article class="svc rv" data-cat="<?= e($s['category']) ?>" data-aud="<?= e($s['audience']) ?>">
      <div class="img" role="button" tabindex="0" aria-label="View details"><?= img_tag($s['image'], $s['name']) ?><span class="tag"><?= e($s['category']) ?></span><?= aud_badge($s['audience']) ?></div><div class="ico"><?= icon($s['icon'], 22) ?></div>
      <div class="bd"><h3><?= e($s['name']) ?></h3><p><?= e($s['description']) ?></p>
        <div class="meta"><div><span class="price"><?= money($s['price']) ?></span><br><span class="dur"><?= icon('clock', 14) ?> <?= $s['duration'] ?> min</span></div>
          <div class="acts"><button class="btn btn-ghost btn-xs" data-open="svcModal" data-json='<?= e(json_encode(['name' => $s['name'], 'description' => $s['description'], 'benefits' => $s['benefits'], 'price' => money($s['price']), 'duration' => $s['duration'] . ' min', 'audience' => aud_label($s['audience']), 'stylists' => implode(', ', array_column($st, 'name')), 'image' => $s['image'], 'id' => $s['id']])) ?>'>Details</button><a href="<?= url('user/book.php?service=' . $s['id']) ?>" class="btn btn-gold btn-xs">Book</a></div></div></div>
    </article>
    <?php endforeach; ?>
  </div>
</div></section>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const cards = [...document.querySelectorAll('.svc[data-cat]')], empty = document.getElementById('svcEmpty'),
        chips = [...document.querySelectorAll('.chips button')], grid = document.querySelector('.svc-grid');
  let A = <?= json_encode($for) ?>, C = 'all', timer = null;
  const inAud = c => A === 'all' || c.dataset.aud === 'unisex' || c.dataset.aud === A;
  const inCat = c => C === 'all' || c.dataset.cat === C;

  // category chips: show how many services each has for the selected audience, dim the empty ones
  const refreshChips = () => chips.forEach(b => {
    const f = b.dataset.f, n = cards.filter(c => inAud(c) && (f === 'all' || c.dataset.cat === f)).length;
    let em = b.querySelector('em'); if (!em) { em = document.createElement('em'); b.appendChild(em); }
    em.textContent = n; b.classList.toggle('dim', n === 0 && f !== 'all');
  });

  const apply = (animate = true) => {
    refreshChips();
    clearTimeout(timer);
    const show = cards.filter(c => inAud(c) && inCat(c)), hide = cards.filter(c => !show.includes(c));
    const finish = () => {
      hide.forEach(c => { c.hidden = true; c.classList.remove('leaving'); });
      show.forEach((c, i) => {
        c.hidden = false; c.classList.remove('leaving', 'pop'); c.classList.add('in');
        if (animate) { c.style.setProperty('--i', i + 1); void c.offsetWidth; c.classList.add('pop'); }
      });
      empty.hidden = show.length > 0;
      grid.classList.remove('busy');
    };
    if (!animate) return finish();
    // 1) cards that are going away fade out, 2) the new ones glide in one after another
    const going = hide.filter(c => !c.hidden);
    grid.classList.add('busy');
    going.forEach(c => c.classList.add('leaving'));
    going.length ? (timer = setTimeout(finish, 260)) : finish();
  };

  // Women / Men / All
  document.addEventListener('audchange', e => {
    A = e.detail;
    const url = new URL(location.href); A === 'all' ? url.searchParams.delete('for') : url.searchParams.set('for', A);
    history.replaceState(null, '', url);
    // if the chosen category has nothing for this audience, go back to "All"
    if (C !== 'all' && !cards.some(c => inAud(c) && c.dataset.cat === C)) { C = 'all'; chips.forEach(x => x.classList.toggle('on', x.dataset.f === 'all')); }
    apply();
  });
  // categories
  chips.forEach(b => b.addEventListener('click', () => {
    if (b.classList.contains('on')) return;
    chips.forEach(x => x.classList.toggle('on', x === b)); C = b.dataset.f; apply();
  }));
  // click on a card photo opens the details window too
  grid.addEventListener('keydown', e => { if ((e.key === 'Enter' || e.key === ' ') && e.target.matches('.svc .img')) { e.preventDefault(); e.target.closest('.svc').querySelector('[data-open=svcModal]')?.click(); } });
  grid.addEventListener('click', e => {
    if (e.target.closest('a,button')) return;
    const img = e.target.closest('.svc .img'); if (img) img.closest('.svc').querySelector('[data-open=svcModal]')?.click();
  });
  apply(false);
});
</script>
<?php modal_open('svcModal', 'Service', 'lg'); ?>
  <div style="border-radius:16px;overflow:hidden;height:230px;background:var(--gold-l);margin-bottom:18px"><img id="svcImg" src="" alt="" style="width:100%;height:100%;object-fit:cover"></div>
  <h3 style="font-size:1.8rem" data-text="name"></h3><p class="muted mt" data-text="description"></p>
  <div class="confirm-card"><div><span>Price</span><b data-text="price"></b></div><div><span>Duration</span><b data-text="duration"></b></div><div><span>Suitable for</span><b data-text="audience"></b></div><div><span>Benefits</span><b data-text="benefits"></b></div><div><span>Available stylists</span><b data-text="stylists"></b></div></div>
  <a id="svcBook" class="btn btn-gold btn-block" href="#">Book This Service</a>
<?php modal_close(); ?>
<script>document.addEventListener('click',e=>{const b=e.target.closest('[data-open=svcModal]');if(!b)return;const d=JSON.parse(b.dataset.json);document.getElementById('svcImg').src=d.image;document.getElementById('svcBook').href=BASE+'/user/book.php?service='+d.id;});</script>
<?php site_footer();
