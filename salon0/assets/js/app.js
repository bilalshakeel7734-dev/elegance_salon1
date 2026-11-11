/* Elegance Salon - global behaviour */
(function () {
  const $ = (s, c = document) => c.querySelector(s), $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const BASE = document.body.dataset.base || '';
  window.$ = $; window.$$ = $$; window.BASE = BASE;

  // preloader
  const hidePre = () => $('#preloader')?.classList.add('done');
  window.addEventListener('load', () => setTimeout(hidePre, 350)); setTimeout(hidePre, 2500);

  // navbar
  const nav = $('#nav');
  if (nav) {
    const solidAlways = !document.body.classList.contains('home-top');
    const onScroll = () => { nav.classList.toggle('scrolled', scrollY > 40 || solidAlways); $('#toTop')?.classList.toggle('show', scrollY > 500); };
    onScroll(); addEventListener('scroll', onScroll, { passive: true });
    // mobile drawer: backdrop, scroll-lock, Esc / outside click / link click / resize to close
    const burger = $('#burger'), menu = $('#menu');
    if (burger && menu) {
      const bd = document.createElement('div'); bd.className = 'menu-bd'; nav.appendChild(bd);
      const icon = burger.innerHTML, X = '<svg class="ic" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>';
      const setMenu = open => {
        menu.classList.toggle('open', open); document.body.classList.toggle('menu-open', open);
        burger.setAttribute('aria-expanded', open); burger.innerHTML = open ? X : icon;
      };
      burger.setAttribute('aria-expanded', 'false'); burger.setAttribute('aria-controls', 'menu');
      burger.addEventListener('click', () => setMenu(!menu.classList.contains('open')));
      bd.addEventListener('click', () => setMenu(false));
      $$('#menu a').forEach(a => a.addEventListener('click', () => setMenu(false)));
      addEventListener('keydown', e => { if (e.key === 'Escape') setMenu(false); });
      addEventListener('resize', () => { if (innerWidth > 991) setMenu(false); });
    }
  }
  $('#toTop')?.addEventListener('click', () => scrollTo({ top: 0, behavior: 'smooth' }));
  $$('.dd').forEach(d => d.addEventListener('click', e => { if (e.target.closest('.avatar-btn')) d.classList.toggle('open'); }));
  document.addEventListener('click', e => { if (!e.target.closest('.dd')) $$('.dd.open').forEach(d => d.classList.remove('open')); });

  // panel sidebar
  const side = $('#side'), bd = $('#sideBd');
  const toggleSide = f => { side?.classList.toggle('open', f); bd?.classList.toggle('open', f); };
  $('#sideBtn')?.addEventListener('click', () => toggleSide(!side.classList.contains('open')));
  bd?.addEventListener('click', () => toggleSide(false));

  // reveal on scroll
  const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } }), { threshold: .12 });
  $$('.rv').forEach((el, i) => { el.style.setProperty('--d', (el.dataset.d || (i % 4) * .08) + 's'); io.observe(el); });

  // counters
  const cio = new IntersectionObserver(es => es.forEach(e => {
    if (!e.isIntersecting) return; cio.unobserve(e.target);
    const el = e.target, to = +el.dataset.count, suf = el.dataset.suf || '', t0 = performance.now();
    const step = t => { const p = Math.min((t - t0) / 1400, 1); el.textContent = Math.floor(to * (1 - Math.pow(1 - p, 3))).toLocaleString() + suf; if (p < 1) requestAnimationFrame(step); }; requestAnimationFrame(step);
  }), { threshold: .4 });
  $$('[data-count]').forEach(el => cio.observe(el));
  // bars
  setTimeout(() => { $$('.bar i[data-w]').forEach(b => b.style.width = b.dataset.w + '%'); $$('.col i[data-h]').forEach(b => b.style.height = b.dataset.h + '%'); }, 400);


  // ===== MOTION PACK =====
  // floating gold particles
  $$('.hero, .auth-art, .page-hero, .cta, .testi-sec').forEach(host => {
    const box = document.createElement('div'); box.className = 'particles';
    for (let i = 0; i < 16; i++) {
      const p = document.createElement('i'), s = 5 + Math.random() * 14;
      p.style.cssText = `left:${Math.random() * 100}%;width:${s}px;height:${s}px;--dx:${(Math.random() * 80 - 40).toFixed(0)}px;animation-duration:${(8 + Math.random() * 10).toFixed(1)}s;animation-delay:${(-Math.random() * 14).toFixed(1)}s`;
      box.appendChild(p);
    }
    host.appendChild(box);
  });

  // button ripple
  document.addEventListener('click', e => {
    const b = e.target.closest('.btn, .demo button, .filters button'); if (!b) return;
    if (getComputedStyle(b).position === 'static') b.style.position = 'relative'; b.style.overflow = 'hidden';
    const r = b.getBoundingClientRect(), d = Math.max(r.width, r.height), s = document.createElement('span');
    s.className = 'ripple'; s.style.cssText = `width:${d}px;height:${d}px;left:${e.clientX - r.left - d / 2}px;top:${e.clientY - r.top - d / 2}px`;
    b.appendChild(s); setTimeout(() => s.remove(), 700);
  });

  // 3D tilt on cards (mouse devices only)
  if (matchMedia('(hover:hover) and (pointer:fine)').matches && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
    $$('.svc, .stl, .feat, .testi').forEach(c => {
      c.addEventListener('mouseenter', () => { c.style.transition = 'transform .15s ease-out, box-shadow .4s'; c.style.transitionDelay = '0s'; });
      c.addEventListener('mousemove', e => {
        const r = c.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
        c.style.setProperty('--mx', ((x + .5) * 100).toFixed(1) + '%'); c.style.setProperty('--my', ((y + .5) * 100).toFixed(1) + '%');
        c.style.transform = `perspective(900px) rotateX(${(-y * 7).toFixed(2)}deg) rotateY(${(x * 7).toFixed(2)}deg) translateY(-8px)`;
      });
      c.addEventListener('mouseleave', () => { c.style.transition = ''; c.style.transitionDelay = ''; c.style.transform = ''; });
    });
  }

  // magnetic CTA buttons (mouse devices only)
  if (matchMedia('(hover:hover) and (pointer:fine)').matches && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
    $$('.cta .btn').forEach(b => {
      b.addEventListener('mousemove', e => {
        const r = b.getBoundingClientRect();
        b.style.setProperty('--tx', ((e.clientX - r.left - r.width / 2) * .18).toFixed(1) + 'px');
        b.style.setProperty('--ty', ((e.clientY - r.top - r.height / 2) * .25).toFixed(1) + 'px');
      });
      b.addEventListener('mouseleave', () => { b.style.removeProperty('--tx'); b.style.removeProperty('--ty'); });
    });
  }

  // about image: subtle mouse parallax (mouse devices only)
  if (matchMedia('(hover:hover) and (pointer:fine)').matches && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
    $$('.about-sec .pic').forEach(p => {
      p.addEventListener('mousemove', e => { const r = p.getBoundingClientRect(); p.style.setProperty('--px', ((e.clientX - r.left) / r.width - .5).toFixed(2)); p.style.setProperty('--py', ((e.clientY - r.top) / r.height - .5).toFixed(2)); });
      p.addEventListener('mouseleave', () => { p.style.removeProperty('--px'); p.style.removeProperty('--py'); });
    });
  }

  // Women / Men audience switch (sliding indicator + swaps panels or lets the page filter itself)
  $$('.aud-tabs').forEach(tabs => {
    const btns = $$('button', tabs), ind = $('.ind', tabs);
    const move = b => { if (!b || !ind) return; ind.style.width = b.offsetWidth + 'px'; ind.style.transform = 'translateX(' + b.offsetLeft + 'px)'; };
    const cur = () => $('button.on', tabs) || btns[0];
    btns.forEach(b => b.addEventListener('click', () => {
      if (b.classList.contains('on')) return;
      btns.forEach(x => x.classList.toggle('on', x === b)); move(b);
      tabs.dispatchEvent(new CustomEvent('audchange', { detail: b.dataset.aud, bubbles: true }));
    }));
    move(cur()); setTimeout(() => move(cur()), 120);
    addEventListener('load', () => move(cur())); addEventListener('resize', () => move(cur())); document.fonts?.ready.then(() => move(cur()));
  });
  document.addEventListener('audchange', e => {
    const sec = e.target.closest('section'); if (!sec) return;
    $$('[data-panel]', sec).forEach(p => { const on = p.dataset.panel === e.detail; p.hidden = !on; p.classList.remove('swap'); if (on) { void p.offsetWidth; p.classList.add('swap'); } });
    const all = $('#svcAll', sec); if (all && all.dataset.href) all.href = all.dataset.href + '?for=' + e.detail;
  });

  // smooth page fade-out on internal navigation
  document.addEventListener('click', e => {
    const a = e.target.closest('a[href]'); if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.button) return;
    const h = a.getAttribute('href');
    if (!h || h[0] === '#' || a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-open') || a.hasAttribute('data-confirm') || /^(javascript:|mailto:|tel:)/i.test(h)) return;
    if (a.origin !== location.origin) return;
    e.preventDefault(); document.body.classList.add('leaving'); setTimeout(() => { location.href = a.href; }, 230);
  });
  addEventListener('pageshow', e => { if (e.persisted) document.body.classList.remove('leaving'); });

  // toasts
  $$('.toast').forEach(t => { const close = () => { t.classList.add('out'); setTimeout(() => t.remove(), 400); }; t.querySelector('.toast-x').onclick = close; setTimeout(close, 6000); });

  // modals
  window.openModal = id => { const m = typeof id === 'string' ? $('#' + id) : id; if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; } return m; };
  window.closeModal = m => { (m || $('.modal.open'))?.classList.remove('open'); if (!$('.modal.open')) document.body.style.overflow = ''; };
  document.addEventListener('click', e => {
    const o = e.target.closest('[data-open]'); if (o) { e.preventDefault(); const m = openModal(o.dataset.open); fillModal(m, o); }
    if (e.target.closest('[data-close]') || e.target.classList.contains('modal')) closeModal(e.target.closest('.modal'));
    const c = e.target.closest('[data-confirm]'); if (c && !confirm(c.dataset.confirm)) e.preventDefault();
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
  // fill form fields from data-* JSON on the trigger button
  function fillModal(m, btn) {
    if (!btn.dataset.json) { if (btn.dataset.reset !== undefined) { $('form', m)?.reset(); const id = $('[name=id]', m); if (id) id.value = ''; } return; }
    const d = JSON.parse(btn.dataset.json);
    Object.entries(d).forEach(([k, v]) => {
      $$('[name="' + k + '"]', m).forEach(f => {
        if (f.type === 'radio') f.checked = f.value == v; else if (f.type === 'checkbox') f.checked = !!+v; else f.value = v ?? '';
      });
      $$('[data-text="' + k + '"]', m).forEach(n => n.textContent = v);
    });
    if (d.work_days !== undefined) $$('[name="work_days[]"]', m).forEach(c => c.checked = String(d.work_days).split(',').includes(c.value));
    if (d.service_ids !== undefined) $$('[name="service_ids[]"]', m).forEach(c => c.checked = String(d.service_ids).split(',').includes(c.value));
    m.dispatchEvent(new CustomEvent('filled', { detail: d }));
  }

  // password toggle
  $$('.eye').forEach(b => b.addEventListener('click', () => { const i = b.parentElement.querySelector('input'); i.type = i.type === 'password' ? 'text' : 'password'; b.style.opacity = i.type === 'text' ? 1 : .6; }));
  // password strength
  $$('[data-strength]').forEach(i => i.addEventListener('input', () => {
    const v = i.value; let s = 0; if (v.length >= 6) s++; if (v.length >= 10) s++; if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++; if (/\d/.test(v)) s++; if (/[^A-Za-z0-9]/.test(v)) s++;
    const bar = $(i.dataset.strength + ' i'); if (bar) { bar.style.width = s * 20 + '%'; bar.style.background = ['#d1495b', '#d1495b', '#d98e1f', '#d98e1f', '#2f9e6b', '#2f9e6b'][s]; }
  }));
  // client-side form validation (server validates again)
  $$('form[data-validate]').forEach(f => f.addEventListener('submit', e => {
    let ok = true; $$('.err.js', f).forEach(x => x.remove());
    $$('[required]', f).forEach(i => { if (!i.value.trim() && i.type !== 'hidden' && !i.disabled) { ok = false; showErr(i, 'This field is required'); } });
    const pw = $('[name=password]', f), cp = $('[name=confirm]', f);
    if (pw && cp && pw.value !== cp.value) { ok = false; showErr(cp, 'Passwords do not match'); }
    if (!ok) { e.preventDefault(); $('.err.js', f)?.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
  }));
  function showErr(i, m) { const s = document.createElement('span'); s.className = 'err js'; s.textContent = m; (i.closest('.fg') || i.parentElement).appendChild(s); }
  window.showErr = showErr;

  // tabs
  $$('.tabs').forEach(t => $$('button', t).forEach(b => b.addEventListener('click', () => {
    $$('button', t).forEach(x => x.classList.toggle('on', x === b)); $$('.tab').forEach(p => p.classList.toggle('on', p.id === b.dataset.tab));
  })));
  // filters (services / any list with data-cat)
  $$('.filters').forEach(f => f.addEventListener('click', e => {
    const b = e.target.closest('button'); if (!b) return; $$('button', f).forEach(x => x.classList.toggle('on', x === b));
    $$('[data-cat]').forEach(c => { const show = b.dataset.f === 'all' || c.dataset.cat === b.dataset.f; c.style.display = show ? '' : 'none'; if (show) { c.classList.remove('in'); void c.offsetWidth; c.classList.add('in'); } });
  }));
  // table live search
  $$('[data-search]').forEach(i => i.addEventListener('input', () => { const t = $(i.dataset.search), v = i.value.toLowerCase(); $$('tbody tr', t).forEach(r => r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none'); }));
  // demo credential buttons
  $$('[data-fill]').forEach(b => b.addEventListener('click', () => { const [u, p] = b.dataset.fill.split('|'); $('[name=login]').value = u; $('[name=password]').value = p; }));

  // ======== calendar widget ========
  // new Calendar(el, {events:{'2026-10-31':[{t:'text',s:'status'}]}, mini:false, onPick:fn, isDisabled:fn})
  window.Calendar = function (el, o) {
    const MN = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    let cur = new Date(); cur.setDate(1); let sel = null;
    const iso = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    const todayIso = iso(new Date());
    el.innerHTML = '<div class="cal-h"><h3></h3><div class="cal-nav"><button type="button" data-p>&lsaquo;</button><button type="button" data-n>&rsaquo;</button></div></div><div class="cal' + (o.mini ? ' mini-cal' : '') + '"></div>';
    const h = $('h3', el), g = $('.cal', el);
    function draw() {
      h.textContent = MN[cur.getMonth()] + ' ' + cur.getFullYear(); g.innerHTML = '';
      ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'].forEach(d => g.insertAdjacentHTML('beforeend', '<div class="dow">' + d + '</div>'));
      const first = (new Date(cur.getFullYear(), cur.getMonth(), 1).getDay() + 6) % 7, days = new Date(cur.getFullYear(), cur.getMonth() + 1, 0).getDate();
      for (let i = 0; i < first; i++) g.insertAdjacentHTML('beforeend', '<div class="day off"></div>');
      for (let d = 1; d <= days; d++) {
        const dt = new Date(cur.getFullYear(), cur.getMonth(), d), k = iso(dt), evs = (o.events || {})[k] || [];
        const dis = o.isDisabled ? o.isDisabled(k, dt) : false;
        const div = document.createElement('div'); div.className = 'day' + (k === todayIso ? ' today' : '') + (k === sel ? ' sel' : '') + (dis ? ' dis' : ''); div.dataset.d = k;
        div.innerHTML = '<span class="n">' + d + '</span>' + (o.mini ? '' : evs.slice(0, 2).map(e => '<span class="ev ' + e.s + '" title="' + e.t + '">' + e.t + '</span>').join('') + (evs.length > 2 ? '<span class="more">+' + (evs.length - 2) + ' more</span>' : ''));
        if (!dis) div.onclick = () => { sel = k; draw(); o.onPick && o.onPick(k, evs); };
        g.appendChild(div);
      }
    }
    $('[data-p]', el).onclick = () => { cur.setMonth(cur.getMonth() - 1); draw(); };
    $('[data-n]', el).onclick = () => { cur.setMonth(cur.getMonth() + 1); draw(); };
    draw(); return { select: k => { sel = k; draw(); }, redraw: draw };
  };
  window.fmtTime = t => { let [h, m] = t.split(':').map(Number); const ap = h >= 12 ? 'PM' : 'AM'; h = h % 12 || 12; return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ' ' + ap; };
  window.fetchSlots = async (stylist, service, date, exclude = 0) => (await (await fetch(`${BASE}/api/slots.php?stylist=${stylist}&service=${service}&date=${date}&exclude=${exclude}`)).json()).slots || [];
})();
