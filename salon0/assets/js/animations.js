/* Elegance Salon - extra motion (animations.js). Works together with animations.css */
(function () {
  /* safety net: if any photo fails to load, show a known-good one instead of an empty box */
  document.addEventListener('error', e => {
    const i = e.target; if (!i || i.tagName !== 'IMG' || i.dataset.retry) return;
    i.dataset.retry = '1'; e.stopPropagation();
    const portrait = i.closest('.sp-photo, .stl .ph, .opt.stp');
    i.src = 'https://images.unsplash.com/photo-' + (portrait ? '1580489944761-15a19d654956' : '1521590832167-7bcbfaa6381f') + '?auto=format&fit=crop&w=900&q=80';
  }, true);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const fine = matchMedia('(hover:hover) and (pointer:fine)').matches;
  const reduce = matchMedia('(prefers-reduced-motion:reduce)').matches;

  /* ---- 1. animated headings: split text into words + letters ---- */
  const BIG = '.page-hero h1, .auth-box h1, .auth-art h2, .sec-head h2, .section h2, .about-sec h2, .cta h2, .next-card h2, .topbar h1, .modal-t, .box-h h3, .cal-h h3, .cinfo h4, .footer h4, .sp-name h3, .svc h3, .feat h3, .stl h3, .summary h3, .box > h3, .card > h3, .invoice h2';
  const io = new IntersectionObserver(es => es.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('in-view'); io.unobserve(e.target); }
  }), { threshold: .2 });

  function splitNode(root) {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
    const nodes = []; let n; while ((n = walker.nextNode())) if (n.nodeValue.trim()) nodes.push(n);
    let idx = 0;
    nodes.forEach(t => {
      const frag = document.createDocumentFragment();
      t.nodeValue.split(/(\s+)/).forEach(part => {
        if (!part) return;
        if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(' ')); return; }
        const w = document.createElement('span'); w.className = 'wd'; w.setAttribute('aria-hidden', 'true');
        [...part].forEach(c => { const s = document.createElement('span'); s.className = 'ch'; s.style.setProperty('--ci', idx++); s.textContent = c; w.appendChild(s); });
        frag.appendChild(w);
      });
      t.parentNode.replaceChild(frag, t);
    });
    return idx;
  }

  function usesTextClip(el) {
    // headings painted with background-clip:text (shimmer) would turn invisible if split
    const bad = x => { const c = getComputedStyle(x); return c.webkitTextFillColor === 'rgba(0, 0, 0, 0)' || c.backgroundClip === 'text' || c.webkitBackgroundClip === 'text'; };
    return bad(el) || $$('*', el).some(bad);
  }

  $$(BIG).forEach(h => {
    if (h.classList.contains('hx') || h.closest('.hero') || h.closest('.cal-h') || h.id || h.hasAttribute('data-text') || h.querySelector('[id],[data-text]')) return; // skip headings that other scripts rewrite
    if (!h.textContent.trim() || h.querySelector('.w, input, select, textarea, button')) return;
    if (usesTextClip(h)) { h.classList.add('hx-line'); io.observe(h); return; } // shimmer headings keep their own effect + get the underline
    const label = h.textContent.trim().replace(/\s+/g, ' ');
    const total = splitNode(h);
    if (!total) return;
    h.setAttribute('aria-label', label);
    h.classList.add('hx');
    if (total > 40) h.classList.add('sm-h');
    io.observe(h);
  });
  // fast stagger for long headings so they don't take forever
  $$('.hx').forEach(h => { const n = h.querySelectorAll('.ch').length; if (n > 34) $$('.ch', h).forEach((c, i) => c.style.setProperty('--ci', (i * 34 / n).toFixed(1))); });

  /* ---- 2. scroll progress bar ---- */
  if (!reduce) {
    const bar = document.createElement('div'); bar.id = 'scrollBar'; document.body.appendChild(bar);
    const upd = () => { const h = document.documentElement.scrollHeight - innerHeight; bar.style.transform = 'scaleX(' + (h > 0 ? Math.min(scrollY / h, 1) : 0) + ')'; };
    upd(); addEventListener('scroll', upd, { passive: true }); addEventListener('resize', upd);
  }

  /* ---- 3. cursor glow + dot (desktop mouse only) ---- */
  if (fine && !reduce) {
    const glow = document.createElement('div'); glow.className = 'cursor-glow';
    const dot = document.createElement('div'); dot.className = 'cursor-dot';
    document.body.append(glow, dot);
    let x = innerWidth / 2, y = innerHeight / 2, gx = x, gy = y;
    addEventListener('mousemove', e => { x = e.clientX; y = e.clientY; glow.classList.add('on'); dot.classList.add('on'); dot.style.transform = `translate(${x}px,${y}px)`; }, { passive: true });
    document.addEventListener('mouseleave', () => { glow.classList.remove('on'); dot.classList.remove('on'); });
    (function loop() { gx += (x - gx) * .12; gy += (y - gy) * .12; glow.style.transform = `translate(${gx}px,${gy}px)`; requestAnimationFrame(loop); })();
    document.addEventListener('mouseover', e => { dot.classList.toggle('big', !!e.target.closest('a, button, .btn, .svc, .stl, .opt, .slot, .day, [data-open]')); });
  }

  /* ---- 4. mouse spotlight on cards / stat boxes ---- */
  if (fine && !reduce) {
    $$('.stat, .box, .card, .feat, .svc, .next-card').forEach(c => c.addEventListener('mousemove', e => {
      const r = c.getBoundingClientRect();
      c.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100).toFixed(1) + '%');
      c.style.setProperty('--my', ((e.clientY - r.top) / r.height * 100).toFixed(1) + '%');
    }));
  }

  /* ---- 5. tilt for extra elements (stat boxes, options, dashboard boxes handled lightly) ---- */
  if (fine && !reduce) {
    $$('.opt, .notif, .invoice, .demo').forEach(c => {
      c.addEventListener('mousemove', e => {
        const r = c.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
        c.style.rotate = ''; c.style.transformOrigin = 'center';
        c.style.transform = `perspective(800px) rotateX(${(-y * 4).toFixed(2)}deg) rotateY(${(x * 4).toFixed(2)}deg)`;
      });
      c.addEventListener('mouseleave', () => { c.style.transform = ''; });
    });
  }

  /* ---- 6. extra reveal for common blocks that have no .rv class ---- */
  $$('.box, .invoice, .prof-card, .contact-grid > *, .auth-box .demo').forEach((el, i) => {
    if (el.classList.contains('rv') || el.closest('.modal') || el.closest('.hero')) return;
    if (el.getBoundingClientRect().top < innerHeight) return; // already visible: leave it alone
    el.style.opacity = '0'; el.style.transform = 'translateY(40px)';
    el.style.transition = 'opacity .8s var(--ease), transform .8s var(--ease)';
    el.dataset.rd = ((i % 3) * .1).toFixed(1);
    const show = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { setTimeout(() => { el.style.opacity = ''; el.style.transform = ''; }, (+el.dataset.rd) * 1000); show.disconnect(); } }), { threshold: .08 });
    show.observe(el);
  });

  /* ---- 7. number pop when counters finish + stagger for dynamically added rows ---- */
  const mo = new MutationObserver(ms => ms.forEach(m => m.addedNodes.forEach(n => {
    if (n.nodeType !== 1) return;
    if (n.matches?.('tr, .slot, .opt, .day, .notif')) { n.style.animationDelay = (Math.min([...n.parentNode.children].indexOf(n), 14) * 0.03) + 's'; }
  })));
  $$('tbody, .slots, .pick, .cal').forEach(t => mo.observe(t, { childList: true }));
})();
