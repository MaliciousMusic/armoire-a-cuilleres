/* ==========================================================================
   L'Armoire à Cuillères — le site (sans framework)
   En-tête, vidéo du hero, statut « ouvert / fermé », apparitions, formulaires, carte de fidélité,
   suivi des demandes, et chargement différé de la carte satellite (js/map.js).
   Source : src/js/site.js → www/assets/js/site.js (node tools/build.mjs)
   ========================================================================== */
(() => {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const D = JSON.parse($('#ac-data')?.textContent || '{}');
  const T = D.t || {};
  const fill = (s, v) => String(s || '').replace(/\{(\w+)\}/g, (m, k) => (v && v[k] != null ? v[k] : m));
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const store = {
    get: (k) => { try { return localStorage.getItem(k); } catch { return null; } },
    set: (k, v) => { try { localStorage.setItem(k, v); } catch { /* navigation privée */ } },
    del: (k) => { try { localStorage.removeItem(k); } catch { /* idem */ } },
  };
  const el = (tag, attrs = {}, ...kids) => {
    const n = document.createElement(tag);
    for (const [k, v] of Object.entries(attrs)) if (v != null) k === 'text' ? (n.textContent = v) : n.setAttribute(k, v);
    for (const c of kids) if (c != null) n.append(c);
    return n;
  };

  /* ---------- Apparitions douces (jamais de déplacement de mise en page : seulement opacité et léger glissé) */
  const reveals = $$('.reveal');
  reveals.forEach((n) => { if (n.getBoundingClientRect().top < innerHeight) n.classList.add('is-in'); });
  document.documentElement.classList.add('js');
  if ('IntersectionObserver' in window && !reduced) {
    const io = new IntersectionObserver((es) => es.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } }), { rootMargin: '0px 0px -6% 0px' });
    reveals.forEach((n) => n.classList.contains('is-in') || io.observe(n));
  } else reveals.forEach((n) => n.classList.add('is-in'));

  /* ---------- Les barres de pastilles : une pastille active glisse d'une rubrique à l'autre.
     Elle est posée en absolu dans sa barre (transform + taille) : rien ne bouge autour, pas de saut. */
  const bars = $$('[data-pills]').map((bar) => ({ bar, ind: $('.pills__ind', bar), links: $$('.pill[data-spy], .pill[aria-current]', bar), active: null }));
  const place = (b) => {
    const a = b.active;
    if (!b.ind) return;
    if (!a || !a.offsetParent) { b.ind.classList.remove('is-on'); return; }
    b.ind.style.setProperty('--x', a.offsetLeft + 'px');
    b.ind.style.setProperty('--y', a.offsetTop + 'px');
    b.ind.style.setProperty('--w', a.offsetWidth + 'px');
    b.ind.style.setProperty('--h', a.offsetHeight + 'px');
    b.ind.classList.add('is-on');
  };
  const activate = (b, a) => {
    if (b.active === a) return;
    if (b.active) b.active.classList.remove('is-active');
    b.active = a || null;
    if (a) a.classList.add('is-active');
    place(b);
    // une barre qui défile (la carte sur mobile) amène sa pastille au centre, sans faire bouger la page
    if (a && b.bar.hasAttribute('data-pills-scroll') && b.bar.scrollWidth > b.bar.clientWidth) {
      b.bar.scrollTo({ left: a.offsetLeft - (b.bar.clientWidth - a.offsetWidth) / 2, behavior: reduced ? 'auto' : 'smooth' });
    }
  };
  bars.forEach((b) => {
    activate(b, b.links.find((a) => a.getAttribute('aria-current') === 'page'));
    requestAnimationFrame(() => requestAnimationFrame(() => b.bar.classList.add('is-ready'))); // pas d'animation au premier placement
  });
  if ('ResizeObserver' in window) { const ro = new ResizeObserver(() => bars.forEach(place)); bars.forEach((b) => ro.observe(b.bar)); }
  if (document.fonts) document.fonts.ready.then(() => bars.forEach(place));
  // Suivi de la rubrique lue
  const spyIds = [...new Set(bars.flatMap((b) => b.links.map((a) => a.dataset.spy).filter(Boolean)))];
  const spied = spyIds.map((id) => document.getElementById(id)).filter(Boolean)
    .sort((x, y) => (x.compareDocumentPosition(y) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1));
  if (spied.length && 'IntersectionObserver' in window) {
    const visible = new Set();
    const update = () => {
      // la dernière rubrique (dans l'ordre de la page) qui traverse la ligne de lecture
      const cur = spied.filter((s) => visible.has(s)).pop();
      bars.forEach((b) => {
        const own = b.links.filter((a) => a.dataset.spy);
        if (!own.length) return;
        const match = cur ? own.find((a) => a.dataset.spy === cur.id || (cur.closest && cur.parentElement && cur.parentElement.closest('#' + a.dataset.spy))) : null;
        // la barre de la carte garde sa rubrique quand on lit ailleurs ; les autres barres suivent la page
        if (match || !b.bar.hasAttribute('data-pills-scroll')) activate(b, match || b.links.find((a) => a.getAttribute('aria-current') === 'page'));
      });
    };
    const io = new IntersectionObserver((es) => { es.forEach((e) => (e.isIntersecting ? visible.add(e.target) : visible.delete(e.target))); update(); }, { rootMargin: '-38% 0px -58% 0px' });
    spied.forEach((s) => io.observe(s));
  }

  /* ---------- Menu plein écran (mobile, tablette) : depuis l'en-tête ou depuis le dock */
  const hd = $('[data-hd]');
  // Le dégradé de l'en-tête revient dès qu'on a quitté le haut de la page
  if (hd) { const onScroll = () => hd.classList.toggle('is-scrolled', scrollY > 8); onScroll(); addEventListener('scroll', onScroll, { passive: true }); }
  const sheet = $('[data-sheet]');
  const burger = $('[data-burger]');
  if (sheet && burger) {
    const label = $('[data-burger-label]', burger);
    let lastFocus = null;
    const setOpen = (open) => {
      sheet.hidden = !open;
      hd.classList.toggle('is-open', open);
      burger.setAttribute('aria-expanded', String(open));
      label.textContent = open ? T.close : T.menu;
      document.documentElement.classList.toggle('no-scroll', open);
      if (open) { lastFocus = document.activeElement; const first = $('a', sheet); if (first) first.focus({ preventScroll: true }); } else if (lastFocus) lastFocus.focus({ preventScroll: true });
    };
    burger.addEventListener('click', () => setOpen(sheet.hidden));
    $$('[data-burger-dock]').forEach((b) => b.addEventListener('click', () => setOpen(true)));
    $$('a', sheet).forEach((a) => a.addEventListener('click', () => setOpen(false)));
    addEventListener('keydown', (e) => { if (e.key === 'Escape' && !sheet.hidden) setOpen(false); });
    matchMedia('(min-width: 1180px)').addEventListener('change', (m) => { if (m.matches && !sheet.hidden) setOpen(false); });
  }
  // Les petits panneaux de l'en-tête (langues, horaires) se referment en cliquant ailleurs ou avec Échap
  document.addEventListener('click', (e) => $$('details.langs[open], details.hd__hours[open]').forEach((d) => d.contains(e.target) || d.removeAttribute('open')));
  addEventListener('keydown', (e) => { if (e.key === 'Escape') $$('details.langs[open], details.hd__hours[open]').forEach((d) => { d.removeAttribute('open'); $('summary', d).focus(); }); });

  /* ---------- La galerie de l'accueil : une photo à la fois, en fondu ; elle défile seule, rien à cliquer */
  $$('[data-gal]').forEach((g) => {
    const imgs = $$('.gal__img', g);
    let i = 0;
    const show = (k) => {
      i = k % imgs.length;
      imgs.forEach((im, j) => im.classList.toggle('is-on', j === i));
      const next = imgs[(i + 1) % imgs.length];
      if (next) next.loading = 'eager'; // la suivante est prête avant son tour
    };
    if (imgs.length > 1) { show(0); setInterval(() => { if (!document.hidden) show(i + 1); }, 5000); }
  });

  /* ---------- Le titre du hero : les phrases se relaient (le lieu, le brunch, les gâteaux, les jours d'ouverture) */
  const rot = $('[data-rot]');
  if (rot) {
    const items = $$('.rot__item', rot);
    let i = 0;
    if (items.length > 1) setInterval(() => {
      if (document.hidden) return;
      const prev = items[i];
      i = (i + 1) % items.length;
      prev.classList.remove('is-on');
      prev.classList.add('is-out');
      setTimeout(() => prev.classList.remove('is-out'), 500);
      items[i].classList.add('is-on');
    }, 3400);
  }

  /* ---------- Ouvert / fermé, à l'heure de Paris */
  const paris = () => {
    const p = Object.fromEntries(new Intl.DateTimeFormat('en-GB', { timeZone: 'Europe/Paris', weekday: 'short', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' })
      .formatToParts(new Date()).map((x) => [x.type, x.value]));
    return { day: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].indexOf(p.weekday), min: +p.hour * 60 + +p.minute, iso: `${p.year}-${p.month}-${p.day}` };
  };
  const fmtH = (m) => {
    const h = Math.floor(m / 60), mm = m % 60;
    if (D.lang === 'en') return (h % 12 || 12) + (mm ? ':' + String(mm).padStart(2, '0') : '') + (h < 12 ? 'am' : 'pm');
    if (D.lang === 'fr') return h + 'h' + (mm ? String(mm).padStart(2, '0') : '');
    return h + ':' + String(mm).padStart(2, '0');
  };
  const addDays = (iso, k) => { const d = new Date(iso + 'T12:00:00Z'); d.setUTCDate(d.getUTCDate() + k); return d.toISOString().slice(0, 10); };
  const statut = () => {
    const H = D.hours; if (!H) return null;
    const now = paris();
    const closed = (iso) => (H.closures || []).some((c) => iso >= c.from && iso <= c.to);
    const slots = closed(now.iso) ? [] : H.days[now.day] || [];
    for (const [a, b] of slots) if (now.min >= a && now.min < b) return b - now.min <= 30 ? { cls: 'is-soon', text: fill(T.soon, { h: fmtH(b) }), short: T.shortSoon } : { cls: 'is-open', text: fill(T.open, { h: fmtH(b) }), short: T.shortOpen };
    for (let k = 0; k < 14; k++) {
      const iso = addDays(now.iso, k), day = (now.day + k) % 7;
      if (closed(iso)) continue;
      const next = (H.days[day] || []).find(([a]) => k > 0 || a > now.min);
      if (!next) continue;
      const h = fmtH(next[0]);
      const when = k === 0 ? fill(T.today, { h }) : k === 1 ? fill(T.tomorrow, { h }) : fill(T.day, { day: D.days[day], h });
      return { cls: 'is-closed', text: fill(T.closed, { when }), short: T.shortClosed };
    }
    return null;
  };
  const showStatus = () => {
    const s = statut();
    if (!s) return;
    $$('[data-status]').forEach((n) => {
      n.classList.remove('is-open', 'is-soon', 'is-closed');
      n.classList.add(s.cls);
      const t = $('[data-status-text]', n);
      if (t) t.textContent = s.text;
      const sh = $('[data-status-short]', n);
      if (sh) sh.textContent = s.short;
      n.hidden = false;
    });
    fitStatus();
  };
  // Dans l'en-tête, la phrase entière seulement si elle tient ; sinon le mot seul
  const fitStatus = () => {
    const n = $('.hd__status'), side = n && n.closest('.hd__side');
    if (!side) return;
    n.classList.remove('is-short');
    if (side.scrollWidth > side.clientWidth + 1) n.classList.add('is-short');
  };
  showStatus();
  setInterval(showStatus, 60000);
  addEventListener('resize', fitStatus, { passive: true });

  /* ---------- La vidéo du hero : en boucle, lue nativement (aucun échange de source, aucun bouton).
     Elle reste figée seulement si l'on a demandé moins d'animations ou d'économiser les données. */
  const video = $('[data-hero-video]');
  if (video) {
    const conn = navigator.connection || {};
    if (reduced || conn.saveData) { video.removeAttribute('autoplay'); video.pause(); }
    else video.play().catch(() => {});
  }

  /* ---------- Bandeaux en vague : le ruban est fixe, le texte court dessus de droite à gauche, sans arrêt */
  $$('[data-wave]').forEach((box) => {
    const svg = $('svg', box), above = $('.wave__above', svg), below = $('.wave__below', svg), path = $('.wave__ribbon', svg), tp = $('[data-wave-text]', svg), text = $('text', svg);
    const unit = ($('template[data-wave-unit]', box)?.innerHTML || tp.textContent).replace(/&amp;/g, '&');
    // le texte suit sa propre ligne, décalée pour que les lettres soient centrées dans l'épaisseur du ruban
    const line = path.cloneNode();
    line.removeAttribute('class');
    line.setAttribute('fill', 'none');
    line.id = path.id + '-t';
    path.after(line);
    tp.setAttribute('href', '#' + line.id);
    text.setAttribute('dominant-baseline', 'alphabetic');
    // une mesure hors du ruban : sur la ligne, les lettres qui dépassent son bout ne compteraient pas
    const probe = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    probe.setAttribute('class', 'wave__text');
    probe.setAttribute('visibility', 'hidden');
    svg.appendChild(probe);
    const ctx = document.createElement('canvas').getContext('2d');
    let unitLen = 0, off = 0, last = 0, on = false;
    // une sinusoïde en courbes de Bézier (tangentes exactes) : pas d'angles, donc les lettres ne tremblent pas en glissant
    const curve = (W, mid, A, L, k) => {
      const h = L / 16, y = (x) => mid + A * Math.sin(k * x), dy = (x) => A * k * Math.cos(k * x);
      let d = `M${(-L).toFixed(2)} ${y(-L).toFixed(2)}`;
      for (let x = -L; x < W + L; x += h) {
        const x2 = x + h;
        d += `C${(x + h / 3).toFixed(2)} ${(y(x) + (h / 3) * dy(x)).toFixed(2)} ${(x2 - h / 3).toFixed(2)} ${(y(x2) - (h / 3) * dy(x2)).toFixed(2)} ${x2.toFixed(2)} ${y(x2).toFixed(2)}`;
      }
      return d;
    };
    const build = () => {
      const W = box.clientWidth, H = box.clientHeight;
      if (!W || !H) return;
      svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
      const thick = H * 0.46, A = H * 0.16, L = Math.max(460, Math.min(900, W / 1.7)), k = (2 * Math.PI) / L;
      const size = Math.round(thick * 0.56);
      const d = curve(W, H / 2, A, L, k);
      path.setAttribute('d', d);
      above.setAttribute('d', `${d}L${(W + L).toFixed(1)} 0L${(-L).toFixed(1)} 0Z`);
      below.setAttribute('d', `${d}L${(W + L).toFixed(1)} ${H + 1}L${(-L).toFixed(1)} ${H + 1}Z`);
      path.style.strokeWidth = thick + 'px';
      text.style.fontSize = probe.style.fontSize = size + 'px';
      // centrage vertical : la hauteur réelle des lettres affichées (majuscules ou non, selon la langue)
      const cs = getComputedStyle(text);
      ctx.font = `${cs.fontStyle} ${cs.fontWeight} ${size}px ${cs.fontFamily}`;
      const sample = unit.replace(/✦/g, '').trim();
      const m = ctx.measureText(cs.textTransform === 'uppercase' ? sample.toUpperCase() : sample);
      const shift = (m.actualBoundingBoxAscent - m.actualBoundingBoxDescent) / 2 || size * 0.35;
      line.setAttribute('d', curve(W, H / 2 + shift, A, L, k));
      // longueur exacte d'une répétition (les espaces de jonction compris) : la boucle retombe pile au même endroit
      probe.textContent = unit.repeat(2);
      const two = probe.getComputedTextLength();
      probe.textContent = unit.repeat(3);
      unitLen = probe.getComputedTextLength() - two || 600;
      tp.textContent = unit.repeat(Math.ceil((line.getTotalLength() + unitLen) / unitLen) + 2);
      off %= unitLen;
    };
    const tick = (t) => {
      if (!on) return;
      const dt = last ? Math.min(64, t - last) : 16;
      last = t;
      off -= dt * 0.07;
      if (off <= -unitLen) off += unitLen;
      tp.setAttribute('startOffset', off.toFixed(2));
      requestAnimationFrame(tick);
    };
    build();
    // la barre d'adresse du téléphone change la hauteur de l'écran, pas la largeur : on ne reconstruit qu'au changement de largeur
    let lastW = box.clientWidth;
    if ('ResizeObserver' in window) new ResizeObserver(() => { if (box.clientWidth !== lastW) { lastW = box.clientWidth; build(); } }).observe(box);
    if (document.fonts) document.fonts.ready.then(build);
    if (!reduced) {
      new IntersectionObserver(([e]) => {
        if (e.isIntersecting && !on) { on = true; last = 0; requestAnimationFrame(tick); } else if (!e.isIntersecting) on = false;
      }, { rootMargin: '100px 0px' }).observe(box);
    }
  });

  /* ---------- Les carrés des grands crus : au choix, une croquée dans le coin (et quelques miettes) */
  const bite = (el, keep) => {
    el.classList.remove('is-bitten');
    void el.offsetWidth; // relance l'animation
    el.classList.add('is-bitten');
    if (!reduced) {
      const body = el.querySelector('.carre__body, :scope > span') || el;
      const cs = getComputedStyle(body);
      for (let i = 0; i < 6; i++) {
        const c = document.createElement('i');
        c.className = 'crumb';
        c.style.setProperty('--dx', (8 + Math.random() * 26).toFixed(0) + 'px');
        c.style.setProperty('--dy', (-10 - Math.random() * 26).toFixed(0) + 'px');
        c.style.setProperty('--r', (Math.random() * 300 - 150).toFixed(0) + 'deg');
        c.style.setProperty('--s', (3 + Math.random() * 4).toFixed(1) + 'px');
        c.style.setProperty('--crumb', cs.getPropertyValue('--c').trim() || '#4A2A1B');
        el.append(c);
        setTimeout(() => c.remove(), 900);
      }
    }
    if (!keep) { clearTimeout(el._bite); el._bite = setTimeout(() => el.classList.remove('is-bitten'), 2200); }
  };
  /* ---------- Le nuancier des grands crus : la fiche et la jauge du cru touché (le carré choisi est croqué) */
  $$('[data-nuancier]').forEach((nu) => {
    const label = JSON.parse($('[data-cocoa-label]', nu)?.textContent || '"{n} %"');
    const f = (k) => $(`[data-f="${k}"]`, nu);
    const meter = f('fill');
    const setMeter = (pc, color) => { meter.style.setProperty('--p', pc + '%'); meter.style.setProperty('--c', color); };
    if (meter) setMeter(meter.dataset.pc, meter.dataset.color);
    const squares = $$('.carre', nu);
    const pick = (b, withBite) => {
      const c = JSON.parse(b.dataset.cru);
      squares.forEach((x) => { x.setAttribute('aria-checked', String(x === b)); x.tabIndex = x === b ? 0 : -1; if (x !== b) x.classList.remove('is-bitten'); });
      f('name').textContent = c.name;
      f('pc').textContent = fill(label, { n: c.pc });
      f('notes').textContent = c.notes;
      f('bio').hidden = !c.bio;
      f('al').innerHTML = c.al || ''; // texte produit par le serveur à partir de la carte
      setMeter(c.pc, c.color);
      if (withBite) bite(b, true);
    };
    squares.forEach((b, i) => {
      b.addEventListener('click', () => pick(b, true));
      b.addEventListener('keydown', (e) => {
        const d = e.key === 'ArrowRight' || e.key === 'ArrowDown' ? 1 : e.key === 'ArrowLeft' || e.key === 'ArrowUp' ? -1 : 0;
        if (!d) return;
        e.preventDefault();
        const n = squares[(i + d + squares.length) % squares.length];
        n.focus(); pick(n, true);
      });
      b.tabIndex = b.getAttribute('aria-checked') === 'true' ? 0 : -1;
    });
  });

  /* ---------- La barre de la carte : la molette fait défiler les rubriques (ordinateur) */
  $$('.menu__cats').forEach((bar) => bar.addEventListener('wheel', (e) => {
    if (Math.abs(e.deltaY) <= Math.abs(e.deltaX) || bar.scrollWidth <= bar.clientWidth) return;
    e.preventDefault();
    bar.scrollLeft += e.deltaY;
  }, { passive: false }));

  /* ---------- Allergènes : un panneau sous la barre ; les produits qui en contiennent passent en retrait */
  const alBtns = $$('[data-al-toggle]');
  if (alBtns.length) {
    const open = $('[data-al-open]'), panel = $('#al-panel'), count = $('[data-al-count]'), reset = $('[data-al-reset]');
    const items = $$('[data-al]');
    const setPanel = (v) => { panel.hidden = !v; open.setAttribute('aria-expanded', String(v)); };
    open.addEventListener('click', () => setPanel(panel.hidden));
    $('[data-al-close]').addEventListener('click', () => { setPanel(false); open.focus(); });
    addEventListener('keydown', (e) => { if (e.key === 'Escape' && !panel.hidden) { setPanel(false); open.focus(); } });
    const apply = () => {
      const sel = alBtns.filter((b) => b.getAttribute('aria-pressed') === 'true').map((b) => b.dataset.alToggle);
      items.forEach((it) => {
        const has = (it.dataset.al || '').split(',').filter(Boolean);
        it.classList.toggle('is-dim', sel.some((a) => has.includes(a)));
      });
      count.hidden = !sel.length;
      count.textContent = sel.length;
      open.classList.toggle('is-on', sel.length > 0);
      reset.hidden = !sel.length;
    };
    alBtns.forEach((b) => b.addEventListener('click', () => { b.setAttribute('aria-pressed', String(b.getAttribute('aria-pressed') !== 'true')); apply(); }));
    reset.addEventListener('click', () => { alBtns.forEach((b) => b.setAttribute('aria-pressed', 'false')); apply(); });
  }

  /* ---------- Formulaires : envoi sans recharger, erreurs claires */
  const post = async (url, form) => {
    const data = Object.fromEntries(new FormData(form).entries());
    const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(data), credentials: 'same-origin' });
    let j = {};
    try { j = await r.json(); } catch { /* réponse vide */ }
    return { ok: r.ok && j.ok, j };
  };
  const showErrors = (box, j) => {
    box.className = 'form__msg';
    box.replaceChildren();
    const list = j && j.errors ? Object.values(j.errors) : [fill(T.generic, { phone: D.phone })];
    box.append(el('strong', { text: T.errTitle }), el('ul', {}, ...list.map((m) => el('li', { text: m }))));
    box.hidden = false;
    box.focus?.();
  };
  const busy = (form, on) => {
    const b = $('button[type=submit]', form);
    if (!b) return;
    const tx = $('span', b) || b;
    if (on) { b.dataset.label = tx.textContent; tx.textContent = T.sending; b.disabled = true; } else { tx.textContent = b.dataset.label || T.send; b.disabled = false; }
  };

  $$('[data-ticket-form]').forEach((form) => {
    // particulier / professionnel
    const sync = () => form.classList.toggle('is-pro', $('[data-who]:checked', form)?.value === 'gateau_pro');
    $$('[data-who]', form).forEach((r) => r.addEventListener('change', sync));
    sync();
    // pas de date avant le délai de préparation
    const lead = +form.dataset.lead || 48;
    $$('[data-min-lead]', form).forEach((d) => { d.min = new Date(Date.now() + lead * 3600e3).toISOString().slice(0, 10); });
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const box = $('[data-form-msg]', form);
      busy(form, true);
      try {
        const { ok, j } = await post(form.action, form);
        if (!ok) return showErrors(box, j);
        const done = el('div', { class: 'form__msg form__msg--ok', role: 'status' },
          el('strong', { text: T.okTitle }), el('p', { text: fill(T.okText, { ref: j.ref }) }), el('a', { class: 'btn', href: j.url, text: T.okLink }));
        form.replaceChildren(done);
        done.scrollIntoView({ block: 'center', behavior: reduced ? 'auto' : 'smooth' });
      } catch { showErrors(box); } finally { busy(form, false); }
    });
  });

  /* ---------- Carte de fidélité */
  const KEY = 'ac-carte';
  const loyaltyForm = $('[data-loyalty-form]');
  if (loyaltyForm) {
    loyaltyForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      busy(loyaltyForm, true);
      try {
        const { ok, j } = await post(loyaltyForm.action, loyaltyForm);
        if (!ok) return showErrors($('[data-form-msg]', loyaltyForm), j);
        store.set(KEY, j.token);
        location.href = j.url;
      } catch { showErrors($('[data-form-msg]', loyaltyForm)); } finally { busy(loyaltyForm, false); }
    });
  }
  const mine = store.get(KEY);
  if (mine && /^[\w-]{20,64}$/.test(mine)) {
    const href = D.loyaltyUrl + '?c=' + encodeURIComponent(mine);
    $$('[data-my-card]').forEach((p) => { p.hidden = false; $('a', p).href = href; });
    $$('[data-loyalty-cta]').forEach((a) => { a.href = href; });
  }
  $$('[data-forget-card]').forEach((a) => a.addEventListener('click', () => store.del(KEY)));

  const card = $('[data-card]');
  if (card) {
    const token = card.dataset.card;
    store.set(KEY, token);
    const qrBox = $('[data-qr]', card);
    const drawQr = () => {
      if (!window.qrcode) return setTimeout(drawQr, 80);
      const q = window.qrcode(0, 'M');
      q.addData(qrBox.dataset.qr);
      q.make();
      qrBox.innerHTML = q.createSvgTag({ cellSize: 6, margin: 0, scalable: true });
    };
    drawQr();
    const refresh = async () => {
      if (document.hidden) return;
      try {
        const r = await fetch('/api/loyalty?c=' + encodeURIComponent(token), { headers: { Accept: 'application/json' } });
        const j = await r.json();
        if (!j.ok) return;
        const c = j.card;
        $$('[data-spoons] span', card).forEach((s, i) => s.classList.toggle('on', i < Math.min(c.stamps, c.goal)));
        $('[data-count]', card).textContent = fill(T.count, { n: c.stamps, goal: c.goal });
        const msg = $('[data-msg]', card);
        msg.textContent = c.ready ? fill(T.ready, { reward: c.reward }) : fill(T.left, { n: c.goal - c.stamps, reward: c.reward });
        msg.classList.toggle('lcard__msg--ready', c.ready);
      } catch { /* hors ligne : on réessaiera */ }
    };
    setInterval(refresh, 15000);
    document.addEventListener('visibilitychange', refresh);
  }

  /* ---------- Suivi d'une demande : la conversation se met à jour toute seule */
  const track = $('[data-track]');
  if (track) {
    const token = track.dataset.track;
    const thread = $('[data-thread]', track);
    const render = (t) => {
      const pill = $('[data-status-pill]', track);
      pill.textContent = D.status[t.status] || t.status;
      pill.className = 'spill spill--' + t.status;
      const order = ['nouveau', 'en_cours', 'confirme', 'pret', 'termine'];
      const cur = order.indexOf(t.status === 'devis' ? 'en_cours' : t.status);
      $$('[data-progress] li', track).forEach((li, i) => li.classList.toggle('done', cur >= 0 && i <= cur));
      thread.replaceChildren(...(t.messages.length ? t.messages.map((m) => m.author === 'system'
        ? el('li', { class: 'msg msg--system', text: fill(T.changed, { status: D.status[m.body.slice(7)] || '' }) })
        : el('li', { class: 'msg msg--' + (m.author === 'client' ? 'me' : 'team') },
          el('span', { class: 'msg__by', text: m.author === 'client' ? T.you : (m.by || T.team) }),
          el('p', { class: 'pre', text: m.body }),
          el('time', { datetime: new Date(m.at * 1000).toISOString(), text: new Date(m.at * 1000).toLocaleString(D.lang === 'zh' ? 'zh-CN' : D.lang, { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }) })))
        : [el('li', { class: 'thread__empty', text: T.empty })]));
    };
    const refresh = async () => {
      if (document.hidden) return;
      try {
        const j = await (await fetch('/api/ticket?t=' + encodeURIComponent(token), { headers: { Accept: 'application/json' } })).json();
        if (j.ok) render(j.ticket);
      } catch { /* réessaiera */ }
    };
    setInterval(refresh, 20000);
    document.addEventListener('visibilitychange', refresh);
    const reply = $('[data-reply-form]', track);
    if (reply) reply.addEventListener('submit', async (e) => {
      e.preventDefault();
      busy(reply, true);
      try {
        const { ok, j } = await post(reply.action, reply);
        if (!ok) return showErrors($('[data-form-msg]', reply), j);
        reply.reset();
        render(j.ticket);
      } catch { showErrors($('[data-form-msg]', reply)); } finally { busy(reply, false); }
    });
  }

})();
