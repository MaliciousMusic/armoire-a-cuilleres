/* ==========================================================================
   L'Armoire à Cuillères — l'espace équipe (une seule page, sans framework)
   Tickets en direct · Fidélité (scanner QR) · La carte · Équipe · Réglages · Comptes · Journal
   Tout le texte venant de la base passe par textContent (jamais d'innerHTML avec des données).
   Source : src/js/admin.js → www/assets/js/admin.js (node tools/build.mjs)
   ========================================================================== */
(() => {
  'use strict';
  const A = JSON.parse(document.getElementById('adm').textContent);
  const ME = A.user;
  const isAdmin = ME.role === 'admin';
  const view = document.getElementById('view');
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  /* ---------- Petits outils */
  function h(tag, attrs, ...kids) {
    const [name, ...cls] = tag.split('.');
    const n = document.createElement(name || 'div');
    if (cls.length) n.className = cls.join(' ');
    if (attrs && (typeof attrs !== 'object' || attrs instanceof Node || Array.isArray(attrs))) { kids.unshift(attrs); attrs = null; }
    for (const [k, v] of Object.entries(attrs || {})) {
      if (v == null || v === false) continue;
      if (k.startsWith('on')) n.addEventListener(k.slice(2), v);
      else if (k === 'text') n.textContent = v;
      else if (k === 'value') n.value = v;
      else if (k === 'checked') n.checked = !!v;
      else n.setAttribute(k, v === true ? '' : v);
    }
    const add = (c) => { if (c == null || c === false) return; if (Array.isArray(c)) c.forEach(add); else n.append(c instanceof Node ? c : document.createTextNode(String(c))); };
    kids.forEach(add);
    return n;
  }
  const api = async (method, path, body, isForm) => {
    const opt = { method, headers: { Accept: 'application/json', 'X-CSRF-Token': A.csrf }, credentials: 'same-origin' };
    if (body) { if (isForm) opt.body = body; else { opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify(body); } }
    const r = await fetch('/admin/api' + path, opt);
    if (r.status === 401) { location.reload(); throw new Error('Connexion requise'); }
    let j = {};
    try { j = await r.json(); } catch { j = { ok: false, error: 'Réponse illisible du serveur' }; }
    if (!r.ok && j.ok !== false) j.ok = false;
    return j;
  };
  const must = (j) => { if (!j.ok) { toast(j.error || 'Erreur', true); throw new Error(j.error); } return j; };
  const toastBox = $('[data-toast]');
  let toastT;
  function toast(msg, bad) {
    toastBox.textContent = msg;
    toastBox.className = 'toast' + (bad ? ' toast--bad' : '');
    toastBox.hidden = false;
    clearTimeout(toastT);
    toastT = setTimeout(() => { toastBox.hidden = true; }, 3500);
  }
  const euro = (c) => (c == null ? '' : (c / 100).toFixed(2).replace('.', ',') + ' €');
  const when = (ts) => {
    if (!ts) return '';
    const d = new Date(ts * 1000), now = new Date();
    const same = d.toDateString() === now.toDateString();
    return same ? 'aujourd’hui ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : d.toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit' });
  };
  const dateFr = (iso) => (iso ? new Date(iso + 'T12:00:00').toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' }) : '');
  const trf = (v) => { if (!v) return ''; try { const o = typeof v === 'string' ? JSON.parse(v) : v; return o.fr || ''; } catch { return v; } };
  const trObj = (v) => { if (!v) return {}; try { return typeof v === 'string' ? JSON.parse(v) : v; } catch { return { fr: v }; } };
  const loadScript = (src) => new Promise((ok, ko) => { if ($(`script[src="${src}"]`)) return ok(); const s = h('script', { src }); s.onload = ok; s.onerror = ko; document.head.append(s); });
  const page = (title, ...kids) => { view.replaceChildren(h('h1.v-title', title), ...kids.flat().filter((k) => k != null && k !== false)); view.focus({ preventScroll: true }); scrollTo(0, 0); };

  /** Champ traduisible : français obligatoire, autres langues dépliables */
  function trField(label, value, { area = false, required = false } = {}) {
    const v = trObj(value);
    const inputs = {};
    const mk = (l) => (inputs[l] = h(area ? 'textarea' : 'input', { name: l, value: v[l] || '', rows: area ? 3 : null, required: l === 'fr' && required ? true : null, lang: l === 'zh' ? 'zh-Hans' : l }));
    const box = h('div.tr',
      h('label', label + ' (français)', mk('fr')),
      h('details', h('summary', 'Traductions (anglais, allemand, espagnol, chinois)'),
        Object.keys(A.langs).filter((l) => l !== 'fr').map((l) => h('label', A.langs[l], mk(l))),
        h('p.muted', 'Laissées vides, ces langues affichent le texte français.')));
    box.val = () => Object.fromEntries(Object.entries(inputs).map(([l, i]) => [l, i.value.trim()]));
    return box;
  }
  function modal(title, body, onSave, saveLabel = 'Enregistrer') {
    const dlg = h('dialog.modal');
    const form = h('form', { method: 'dialog' },
      h('h2', title), body,
      h('div.row.row--end', h('button.b', { type: 'button', onclick: () => dlg.close() }, 'Annuler'), h('button.b.b--main', { type: 'submit' }, saveLabel)));
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = $('button[type=submit]', form);
      btn.disabled = true;
      try { if ((await onSave()) !== false) dlg.close(); } catch { /* le toast a parlé */ } finally { btn.disabled = false; }
    });
    dlg.append(form);
    dlg.addEventListener('close', () => dlg.remove());
    document.body.append(dlg);
    dlg.showModal();
    return dlg;
  }
  const confirmBox = (msg) => new Promise((ok) => {
    const dlg = modal('Confirmer', h('p', msg), () => { ok(true); }, 'Oui');
    dlg.addEventListener('close', () => ok(false));
  });

  /* ---------- Le direct : nouveaux tickets (son, titre, notification) */
  const badge = $('[data-badge]');
  let lastUnread = -1, since = Math.floor(Date.now() / 1000), pollFails = 0;
  const baseTitle = document.title;
  function ding() {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      [880, 1320].forEach((f, i) => {
        const o = ctx.createOscillator(), g = ctx.createGain();
        o.frequency.value = f; o.type = 'sine';
        g.gain.setValueAtTime(0.0001, ctx.currentTime + i * 0.18);
        g.gain.exponentialRampToValueAtTime(0.25, ctx.currentTime + i * 0.18 + 0.02);
        g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + i * 0.18 + 0.5);
        o.connect(g).connect(ctx.destination); o.start(ctx.currentTime + i * 0.18); o.stop(ctx.currentTime + i * 0.18 + 0.55);
      });
    } catch { /* pas de son */ }
  }
  async function poll() {
    try {
      const j = await api('GET', '/poll?since=' + since);
      if (!j.ok) return;
      pollFails = 0;
      badge.hidden = !j.unread;
      badge.textContent = j.unread;
      document.title = (j.unread ? `(${j.unread}) ` : '') + baseTitle;
      if (lastUnread >= 0 && j.unread > lastUnread) {
        ding();
        toast('Nouveau message ou nouvelle commande !');
        if ('Notification' in window && Notification.permission === 'granted') new Notification('L’Armoire · espace équipe', { body: 'Un ticket attend une réponse.', icon: '/assets/icons/icon-192.png' });
      }
      if (j.changed && (location.hash.startsWith('#/tickets') || location.hash === '#/' || location.hash === '')) refreshCurrent(true);
      lastUnread = j.unread;
      since = j.now;
      dash = j;
    } catch { pollFails++; }
  }
  let dash = null;
  setInterval(() => { if (!document.hidden || pollFails < 3) poll(); }, 10000);
  document.addEventListener('visibilitychange', () => !document.hidden && poll());
  poll();

  /* ---------- Routeur */
  const routes = [
    [/^#?\/?$/, viewHome],
    [/^#\/tickets$/, viewTickets],
    [/^#\/tickets\/(\d+)$/, viewTicket],
    [/^#\/fidelite$/, viewLoyalty],
    [/^#\/ardoise$/, viewArdoise],
    [/^#\/carte$/, viewMenu],
    [/^#\/equipe$/, viewTeam],
    [/^#\/reglages$/, viewSettings],
    [/^#\/comptes$/, viewUsers],
    [/^#\/journal$/, viewAudit],
    [/^#\/moi$/, viewMe],
  ];
  let current = null;
  function go() {
    stopCamera();
    const hash = location.hash || '#/';
    $$('[data-nav]').forEach((a) => a.classList.toggle('on', hash.startsWith('#/' + a.dataset.nav)));
    for (const [re, fn] of routes) {
      const m = hash.match(re);
      if (m) { current = () => fn(...m.slice(1)); return current(); }
    }
    location.hash = '#/';
  }
  let refreshing = false;
  async function refreshCurrent(silent) {
    if (refreshing || !current || $('dialog[open]') || (document.activeElement && /TEXTAREA|INPUT|SELECT/.test(document.activeElement.tagName) && document.activeElement.value)) return;
    refreshing = true;
    try { await current(silent); } finally { refreshing = false; }
  }
  addEventListener('hashchange', go);

  /* ---------- Accueil */
  async function viewHome() {
    const j = await api('GET', '/poll?since=0');
    page(`Bonjour ${ME.name.split(' ')[0]} !`,
      h('div.tiles',
        h('a.tile.tile--cacao', { href: '#/tickets' }, h('strong', String(j.unread || 0)), h('span', 'ticket(s) à lire')),
        h('a.tile.tile--rose', { href: '#/tickets' }, h('strong', String(j.open || 0)), h('span', 'commande(s) en cours')),
        h('a.tile.tile--sauge', { href: '#/fidelite' }, h('strong', String(j.stampsToday || 0)), h('span', 'passage(s) fidélité aujourd’hui')),
        h('a.tile.tile--taupe', { href: '#/fidelite' }, h('strong', String(j.cards || 0)), h('span', 'carte(s) de fidélité'))),
      h('div.row',
        h('a.b.b--main.b--big', { href: '#/fidelite' }, 'Scanner une carte de fidélité'),
        h('a.b.b--big', { href: '#/tickets' }, 'Voir les tickets'),
        h('a.b.b--big', { href: A.site, target: '_blank', rel: 'noopener' }, 'Voir le site')),
      'Notification' in window && Notification.permission === 'default'
        ? h('p', h('button.b', { type: 'button', onclick: async (e) => { await Notification.requestPermission(); e.target.remove(); } }, 'Recevoir une notification à chaque nouveau ticket'))
        : null);
  }

  /* ---------- Tickets */
  const filters = { status: 'ouverts', type: '', q: '' };
  async function viewTickets(silent) {
    const qs = new URLSearchParams(filters).toString();
    const j = must(await api('GET', '/tickets?' + qs));
    const list = h('div.tlist', j.tickets.length ? j.tickets.map((t) => h('a.titem' + (t.unread_staff ? '.titem--new' : ''), { href: '#/tickets/' + t.id },
      h('span.titem__ref', t.ref || '#' + t.id),
      h('span.pill.pill--' + t.status, A.statuses[t.status]),
      h('strong.titem__name', t.name + (t.company ? ' · ' + t.company : '')),
      h('span.titem__type', A.types[t.type] || t.type),
      t.wanted_date ? h('span.titem__date', 'pour ' + dateFr(t.wanted_date)) : null,
      h('span.titem__when', 'mis à jour ' + when(t.updated_at)))) : h('p.muted', 'Aucun ticket ici pour le moment.'));
    if (silent && $('.tlist')) return $('.tlist').replaceWith(list);
    const sel = (name, opts) => h('select', { onchange: (e) => { filters[name] = e.target.value; viewTickets(); } },
      Object.entries(opts).map(([v, l]) => h('option', { value: v, selected: filters[name] === v ? true : null }, l)));
    const search = h('input', { type: 'search', placeholder: 'Nom, e-mail, téléphone, référence…', value: filters.q });
    search.addEventListener('change', () => { filters.q = search.value; viewTickets(); });
    page('Tickets',
      h('div.row.filters',
        sel('status', { ouverts: 'En cours (non terminés)', '': 'Tous', ...A.statuses }),
        sel('type', { '': 'Tous les types', ...A.types }),
        search),
      list);
  }

  async function viewTicket(id, silent) {
    const j = must(await api('GET', '/tickets/' + id));
    const t = j.ticket;
    const thread = h('ol.thread', j.messages.length ? j.messages.map((m) => m.author === 'system'
      ? h('li.msg.msg--system', `${m.user_name || 'L’équipe'} → ${A.statuses[m.body.slice(7)] || m.body} · ${when(m.created_at)}`)
      : h('li.msg' + (m.author === 'client' ? '.msg--client' : m.internal ? '.msg--note' : '.msg--staff'),
        h('span.msg__by', m.author === 'client' ? t.name : (m.user_name || 'L’équipe') + (m.internal ? ' · note interne' : '')),
        h('p.pre', m.body), h('time', when(m.created_at)))) : h('li.muted', 'Pas encore d’échange.'));
    if (silent && $('.thread')) { $('.thread').replaceWith(thread); return; }
    const body = h('textarea', { rows: 4, placeholder: 'Votre réponse au client (il la verra sur sa page de suivi et la recevra par e-mail)' });
    const internal = h('input', { type: 'checkbox' });
    const statusBtns = h('div.seg', Object.entries(A.statuses).map(([k, l]) => h('button.b.b--sm' + (k === t.status ? '.b--main' : ''), { type: 'button', 'aria-pressed': String(k === t.status),
      onclick: async () => { must(await api('POST', '/tickets/' + id, { status: k })); toast('Statut : ' + l); viewTicket(id); } }, l)));
    const send = async () => {
      if (!body.value.trim()) return toast('Le message est vide', true);
      must(await api('POST', '/tickets/' + id, { body: body.value, internal: internal.checked }));
      toast(internal.checked ? 'Note enregistrée' : 'Réponse envoyée au client');
      viewTicket(id);
    };
    const info = (k, v) => (v ? h('div', h('dt', k), h('dd', v)) : null);
    page(`${t.ref} · ${A.types[t.type] || t.type}`,
      h('p', h('a', { href: '#/tickets' }, '← Tous les tickets')),
      h('div.grid2',
        h('section.card',
          h('h2', t.name + (t.company ? ' · ' + t.company : '')),
          h('dl.dl',
            info('Reçu', when(t.created_at)),
            info('Pour le', t.wanted_date ? dateFr(t.wanted_date) : ''),
            info('Personnes / parts', t.guests),
            info('Gâteau', t.product),
            info('Langue', A.langs[t.lang])),
          t.message ? h('p.pre.quote', t.message) : null,
          h('div.row',
            t.phone ? h('a.b', { href: 'tel:' + t.phone }, 'Appeler ' + t.phone) : null,
            t.email ? h('a.b', { href: 'mailto:' + t.email + '?subject=' + encodeURIComponent(t.ref) }, 'Écrire à ' + t.email) : null),
          h('p.muted', 'Page de suivi du client : ', h('button.b.b--sm', { type: 'button', onclick: () => navigator.clipboard.writeText(t.track_url).then(() => toast('Lien copié')) }, 'Copier le lien'))),
        h('section.card',
          h('h2', 'Statut'), statusBtns,
          h('h2', 'Échanges'), thread,
          h('label', 'Répondre', body),
          h('label.check', internal, h('span', 'Note interne (le client ne la voit pas)')),
          h('button.b.b--main', { type: 'button', onclick: send }, 'Envoyer'))));
  }

  /* ---------- Fidélité : scanner, chercher, créer */
  let stream = null, scanning = false;
  function stopCamera() {
    scanning = false;
    if (stream) { stream.getTracks().forEach((t) => t.stop()); stream = null; }
  }
  async function viewLoyalty() {
    const panel = h('div.lpanel');
    const video = h('video.scan__video', { playsinline: true, muted: true });
    const scanBox = h('div.scan', { hidden: true }, video, h('div.scan__frame'), h('button.b.b--sm.scan__stop', { type: 'button', onclick: () => { stopCamera(); scanBox.hidden = true; } }, 'Fermer la caméra'));
    const code = h('input', { type: 'text', placeholder: 'Code de la carte (6 lettres) ou nom', autocapitalize: 'characters' });
    const results = h('div.cards');

    async function openCard(scan) {
      const j = await api('POST', '/loyalty/scan', { scan });
      if (!j.ok) return toast(j.error || 'Carte inconnue', true);
      showCard(j.card);
    }
    function showCard(c) {
      const pct = Math.min(c.stamps, c.goal);
      const stamp = async (force) => {
        const j = await api('POST', `/loyalty/${c.id}/stamp`, { force });
        if (j.error === 'recent') {
          if (await confirmBox(`Un tampon a déjà été posé il y a ${j.minutes} min. En ajouter un autre quand même ?`)) return stamp(true);
          return;
        }
        must(j);
        showCard(j.card);
        toast(`+1 pour ${c.first_name} (${j.card.stamps}/${j.card.goal})`);
        if (j.card.ready) ding();
      };
      panel.replaceChildren(h('section.card.lcard' + (c.ready ? '.lcard--ready' : ''),
        h('div.lcard__head', h('h2', c.first_name), h('span.muted', 'Code ' + c.code)),
        h('div.spoons', Array.from({ length: c.goal }, (_, i) => h('span' + (i < pct ? '.on' : '') + (i === c.goal - 1 ? '.gift' : '')))),
        h('p.big', `${c.stamps} / ${c.goal}`),
        c.ready ? h('p.alert.alert--ok', `Récompense disponible : ${c.reward}`) : h('p.muted', `Encore ${c.goal - c.stamps} passage(s) avant : ${c.reward}`),
        h('div.row',
          h('button.b.b--main.b--big', { type: 'button', onclick: () => stamp(false) }, '+1 passage'),
          c.ready ? h('button.b.b--gold.b--big', { type: 'button', onclick: async () => { if (!(await confirmBox(`Offrir « ${c.reward} » à ${c.first_name} ?`))) return; const j = must(await api('POST', `/loyalty/${c.id}/redeem`)); showCard(j.card); toast('Récompense offerte, compteur remis à zéro'); } }, 'Offrir la récompense') : null),
        isAdmin ? h('details', h('summary', 'Corriger / supprimer (gérant)'),
          h('div.row',
            h('button.b.b--sm', { type: 'button', onclick: async () => showCard(must(await api('POST', `/loyalty/${c.id}/adjust`, { delta: -1 })).card) }, '−1 tampon'),
            h('button.b.b--sm', { type: 'button', onclick: async () => showCard(must(await api('POST', `/loyalty/${c.id}/adjust`, { delta: 1 })).card) }, '+1 (correction)'),
            h('button.b.b--sm.b--danger', { type: 'button', onclick: async () => { if (await confirmBox('Supprimer définitivement cette carte ?')) { must(await api('POST', `/loyalty/${c.id}/delete`)); panel.replaceChildren(); toast('Carte supprimée'); search(''); } } }, 'Supprimer la carte'))) : null,
        h('h3', 'Derniers passages'),
        h('ul.hist', c.history.map((e) => h('li', `${{ stamp: '+1 passage', reward: 'Récompense offerte', adjust: 'Correction ' + (e.delta > 0 ? '+' : '') + e.delta, create: 'Carte créée' }[e.kind] || e.kind} · ${when(e.created_at)}${e.by_name ? ' · ' + e.by_name : ''}`)))));
      panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    async function startCamera() {
      if (!navigator.mediaDevices?.getUserMedia) return toast('Caméra indisponible : utilisez le code de la carte', true);
      try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 } }, audio: false });
      } catch { return toast('Autorisez la caméra pour scanner (ou tapez le code)', true); }
      scanBox.hidden = false;
      video.srcObject = stream;
      await video.play();
      scanning = true;
      let detector = null;
      if ('BarcodeDetector' in window) {
        try { detector = new window.BarcodeDetector({ formats: ['qr_code'] }); } catch { detector = null; }
      }
      if (!detector) await loadScript(A.jsqr);
      const cv = document.createElement('canvas');
      const ctx = cv.getContext('2d', { willReadFrequently: true });
      let last = '';
      const tick = async () => {
        if (!scanning) return;
        let text = null;
        try {
          if (detector) { const r = await detector.detect(video); text = r[0]?.rawValue || null; }
          else if (video.videoWidth) {
            const w = 480, hh = Math.round((video.videoHeight / video.videoWidth) * w);
            cv.width = w; cv.height = hh;
            ctx.drawImage(video, 0, 0, w, hh);
            const r = window.jsQR(ctx.getImageData(0, 0, w, hh).data, w, hh, { inversionAttempts: 'dontInvert' });
            text = r?.data || null;
          }
        } catch { /* image suivante */ }
        if (text && text !== last) {
          last = text;
          if (navigator.vibrate) navigator.vibrate(60);
          stopCamera();
          scanBox.hidden = true;
          return openCard(text);
        }
        setTimeout(tick, detector ? 120 : 200);
      };
      tick();
    }

    async function search(q) {
      const j = must(await api('GET', '/loyalty?q=' + encodeURIComponent(q)));
      results.replaceChildren(...(j.cards.length ? j.cards.map((c) => h('button.citem', { type: 'button', onclick: async () => showCard(must(await api('GET', '/loyalty/' + c.id)).card) },
        h('strong', c.first_name), h('span.muted', c.code), h('span', `${c.stamps}/${j.goal}`), c.stamps >= j.goal ? h('span.pill.pill--confirme', 'récompense') : null)) : [h('p.muted', 'Aucune carte trouvée.')]));
    }
    code.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); const v = code.value.trim(); /^[A-Za-z0-9]{6}$/.test(v) ? openCard(v) : search(v); } });

    const newCard = () => {
      const first = h('input', { required: true, minlength: 2, autocomplete: 'off' });
      const email = h('input', { type: 'email', autocomplete: 'off' });
      modal('Nouvelle carte de fidélité', [h('label', 'Prénom du client', first), h('label', 'E-mail (facultatif)', email)], async () => {
        const j = must(await api('POST', '/loyalty/new', { first_name: first.value, email: email.value }));
        await loadScript(A.qrcode);
        const q = window.qrcode(0, 'M'); q.addData(j.url); q.make();
        const qr = h('div.qr'); qr.innerHTML = q.createSvgTag({ cellSize: 6, margin: 2, scalable: true }); // SVG produit par la librairie, à partir de notre adresse
        setTimeout(() => modal('Carte créée : ' + j.card.first_name, [h('p', 'Le client scanne ce QR code avec son téléphone : sa carte s’ouvre et il la garde (écran d’accueil ou favori).'), qr, h('p.muted', 'Code de secours : ' + j.card.code)], () => { showCard(j.card); }, 'Terminé'), 50);
      }, 'Créer la carte');
    };

    page('Fidélité',
      h('div.row',
        h('button.b.b--main.b--big', { type: 'button', onclick: startCamera }, 'Scanner le QR code'),
        h('button.b.b--big', { type: 'button', onclick: newCard }, 'Créer une carte au comptoir')),
      scanBox,
      h('div.row', code, h('button.b', { type: 'button', onclick: () => { const v = code.value.trim(); /^[A-Za-z0-9]{6}$/.test(v) ? openCard(v) : search(v); } }, 'Chercher')),
      panel,
      h('h2', 'Cartes récentes'),
      results);
    search('');
  }

  /* ---------- L'ardoise : les pâtisseries du jour, mises à jour chaque matin */
  async function viewArdoise() {
    const j = must(await api('GET', '/ardoise'));
    const a = j.ardoise;
    const rows = h('div.ard');
    const addRow = (it = {}) => {
      const name = trField('Pâtisserie', it.name, { required: true });
      const price = h('input', { inputmode: 'decimal', placeholder: 'ex. 5,50', value: it.price_cents != null ? (it.price_cents / 100).toFixed(2).replace('.', ',') : '' });
      const als = (it.allergens || '').split(',');
      const boxes = Object.entries(A.allergens).map(([k, l]) => h('label.check', h('input', { type: 'checkbox', value: k, checked: als.includes(k) }), h('span', l)));
      const row = h('section.card.ard__row', name, h('div.ard__line', h('label', 'Prix', price),
        h('button.b.b--sm.b--danger', { type: 'button', onclick: () => row.remove() }, 'Retirer')),
        h('details', h('summary', 'Allergènes'), h('div.tags', boxes)));
      row.val = () => ({ name: name.val(), price: price.value, allergens: boxes.map((b) => $('input', b)).filter((i) => i.checked).map((i) => i.value).join(',') });
      rows.append(row);
      return row;
    };
    (a.items || []).forEach(addRow);
    const visible = h('input', { type: 'checkbox', checked: a.visible !== false });
    const note = trField('Signature (sous l’ardoise)', a.note);
    const save = async () => {
      const items = [...rows.children].map((r) => r.val()).filter((x) => x.name.fr);
      must(await api('POST', '/ardoise', { visible: visible.checked, note: note.val(), items }));
      toast('Ardoise publiée · le site est à jour');
      viewArdoise();
    };
    page('Ardoise du jour',
      h('p.muted', a.date ? 'Dernière mise à jour : ' + new Date(a.date + 'T12:00:00').toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' }) : 'Pas encore mise à jour.'),
      h('label.check', visible, h('span', 'Afficher l’ardoise sur le site')),
      rows,
      h('div.row', h('button.b', { type: 'button', onclick: () => addRow().scrollIntoView({ behavior: 'smooth', block: 'center' }) }, '+ Ajouter une pâtisserie'),
        h('button.b', { type: 'button', onclick: async () => { if (await confirmBox('Vider l’ardoise ?')) rows.replaceChildren(); } }, 'Tout effacer')),
      h('section.card', note),
      h('div.sticky-save', h('button.b.b--main.b--big', { type: 'button', onclick: save }, 'Publier l’ardoise')));
  }

  /* ---------- La carte (menu) */
  async function viewMenu() {
    const j = must(await api('GET', '/menu'));
    const menu = j.menu;
    const save = async (path, data) => { const r = must(await api('POST', path, data)); toast('Enregistré · le site est à jour'); return r; };
    const editSection = (s = {}) => {
      const title = trField('Titre', s.title, { required: true });
      const note = trField('Petite note sous le titre', s.note, { area: true });
      const price = h('input', { inputmode: 'decimal', value: s.price_cents != null ? (s.price_cents / 100).toFixed(2).replace('.', ',') : '' });
      const visible = h('input', { type: 'checkbox', checked: s.id ? !!s.visible : true });
      modal(s.id ? 'Modifier la rubrique' : 'Nouvelle rubrique', [title, note, h('label', 'Prix commun à toute la rubrique (facultatif, ex. 5,20)', price), h('label.check', visible, h('span', 'Visible sur le site'))],
        async () => { await save('/menu/section', { id: s.id, title: title.val(), note: note.val(), price: price.value, visible: visible.checked }); viewMenu(); });
    };
    const editItem = (sid, it = {}) => {
      const name = trField('Nom', it.name, { required: true });
      const descr = trField('Description', it.descr, { area: true });
      const price = h('input', { inputmode: 'decimal', value: it.price_cents != null ? (it.price_cents / 100).toFixed(2).replace('.', ',') : '' });
      const tags = (it.tags || '').split(',');
      const boxes = Object.entries(A.tags).map(([k, l]) => h('label.check', h('input', { type: 'checkbox', value: k, checked: tags.includes(k) }), h('span', l)));
      const als = (it.allergens || '').split(',');
      const alBoxes = Object.entries(A.allergens).map(([k, l]) => h('label.check', h('input', { type: 'checkbox', value: k, checked: als.includes(k) }), h('span', l)));
      const visible = h('input', { type: 'checkbox', checked: it.id ? !!it.visible : true });
      const section = h('select', menu.map((s) => h('option', { value: s.id, selected: s.id === sid ? true : null }, trf(s.title))));
      modal(it.id ? 'Modifier l’article' : 'Nouvel article', [name, descr, h('label', 'Prix (ex. 4,50 ; vide si prix de la rubrique)', price), h('label', 'Rubrique', section), h('fieldset.tags', h('legend', 'Étiquettes'), boxes), h('fieldset.tags', h('legend', 'Allergènes (obligatoire : vérifiez chaque recette)'), alBoxes), h('label.check', visible, h('span', 'Disponible (visible sur le site)'))],
        async () => { await save('/menu/item', { id: it.id, section_id: +section.value, name: name.val(), descr: descr.val(), price: price.value, tags: boxes.map((b) => $('input', b)).filter((i) => i.checked).map((i) => i.value).join(','), allergens: alBoxes.map((b) => $('input', b)).filter((i) => i.checked).map((i) => i.value).join(','), visible: visible.checked }); viewMenu(); });
    };
    const move = async (arr, i, d, apply) => {
      const k = i + d;
      if (k < 0 || k >= arr.length) return;
      [arr[i], arr[k]] = [arr[k], arr[i]];
      await api('POST', '/menu/reorder', apply());
      viewMenu();
    };
    page('La carte',
      h('p.muted', isAdmin ? 'Chaque modification est en ligne immédiatement. Décochez « disponible » pour masquer un article épuisé.' : 'Vous pouvez masquer un article épuisé ; les autres modifications sont réservées au gérant.'),
      isAdmin ? h('p', h('button.b.b--main', { type: 'button', onclick: () => editSection() }, '+ Nouvelle rubrique')) : null,
      menu.map((s, si) => h('section.card.msec' + (s.visible ? '' : '.is-off'),
        h('div.msec__head',
          h('h2', trf(s.title) + (s.price_cents != null ? ' · ' + euro(s.price_cents) : '') + (s.visible ? '' : ' (masquée)')),
          isAdmin ? h('div.row.row--tight',
            h('button.b.b--sm', { type: 'button', title: 'Monter', 'aria-label': 'Monter la rubrique', onclick: () => move(menu, si, -1, () => ({ sections: menu.map((x) => x.id) })) }, '↑'),
            h('button.b.b--sm', { type: 'button', title: 'Descendre', 'aria-label': 'Descendre la rubrique', onclick: () => move(menu, si, 1, () => ({ sections: menu.map((x) => x.id) })) }, '↓'),
            h('button.b.b--sm', { type: 'button', onclick: () => editSection(s) }, 'Modifier'),
            h('button.b.b--sm', { type: 'button', onclick: () => editItem(s.id) }, '+ Article'),
            h('button.b.b--sm.b--danger', { type: 'button', onclick: async () => { if (await confirmBox(`Supprimer la rubrique « ${trf(s.title)} » et tous ses articles ?`)) { await save(`/menu/section/${s.id}/delete`); viewMenu(); } } }, 'Supprimer')) : null),
        h('ul.mlist', s.items.map((it, ii) => h('li.mitem' + (it.visible ? '' : '.is-off'),
          h('label.switch', { title: 'Disponible' }, h('input', { type: 'checkbox', checked: !!it.visible, onchange: async (e) => { await save(`/menu/item/${it.id}/visible`, { visible: e.target.checked }); e.target.closest('li').classList.toggle('is-off', !e.target.checked); } }), h('span', { 'aria-hidden': 'true' })),
          h('span.mitem__name', trf(it.name), it.descr ? h('small', trf(it.descr)) : null, h('small.mitem__al', it.allergens ? 'Allergènes : ' + it.allergens.split(',').map((a) => A.allergens[a] || a).join(', ') : 'Aucun allergène renseigné')),
          h('span.mitem__price', it.price_cents != null ? (it.is_extra ? '+ ' : '') + euro(it.price_cents) : ''),
          isAdmin ? h('span.row.row--tight',
            h('button.b.b--sm', { type: 'button', 'aria-label': 'Monter', onclick: () => move(s.items, ii, -1, () => ({ items: { [s.id]: s.items.map((x) => x.id) } })) }, '↑'),
            h('button.b.b--sm', { type: 'button', 'aria-label': 'Descendre', onclick: () => move(s.items, ii, 1, () => ({ items: { [s.id]: s.items.map((x) => x.id) } })) }, '↓'),
            h('button.b.b--sm', { type: 'button', onclick: () => editItem(s.id, it) }, 'Modifier'),
            h('button.b.b--sm.b--danger', { type: 'button', 'aria-label': 'Supprimer', onclick: async () => { if (await confirmBox(`Supprimer « ${trf(it.name)} » ?`)) { await save(`/menu/item/${it.id}/delete`); viewMenu(); } } }, '×')) : null))))));
  }

  /* ---------- Équipe */
  async function viewTeam() {
    const j = must(await api('GET', '/team'));
    const team = j.team;
    const edit = (m = {}) => {
      const name = h('input', { value: m.name || '', required: true });
      const role = trField('Rôle', m.role, { required: true });
      const bio = trField('Quelques mots', m.bio, { area: true });
      const tint = h('select', ['cacao', 'rose', 'sauge', 'taupe', 'sand'].map((t) => h('option', { value: t, selected: m.tint === t ? true : null }, { cacao: 'Chocolat', rose: 'Vieux rose', sauge: 'Sauge', taupe: 'Taupe', sand: 'Caramel' }[t])));
      const visible = h('input', { type: 'checkbox', checked: m.id ? !!m.visible : true });
      modal(m.id ? 'Modifier le portrait' : 'Nouveau portrait', [h('label', 'Prénom', name), role, bio, h('label', 'Couleur du fond (sans photo)', tint), h('label.check', visible, h('span', 'Visible sur le site'))],
        async () => { must(await api('POST', '/team', { id: m.id, name: name.value, role: role.val(), bio: bio.val(), tint: tint.value, visible: visible.checked })); toast('Enregistré'); viewTeam(); });
    };
    const upload = (m) => {
      const input = h('input', { type: 'file', accept: 'image/jpeg,image/png,image/webp' });
      input.addEventListener('change', async () => {
        if (!input.files[0]) return;
        const fd = new FormData(); fd.append('photo', input.files[0]);
        toast('Envoi de la photo…');
        must(await api('POST', `/team/${m.id}/photo`, fd, true));
        toast('Photo enregistrée'); viewTeam();
      });
      input.click();
    };
    const order = async (i, d) => {
      const k = i + d; if (k < 0 || k >= team.length) return;
      [team[i], team[k]] = [team[k], team[i]];
      const m = team[k];
      must(await api('POST', '/team', { id: m.id, name: m.name, role: trObj(m.role), bio: trObj(m.bio), tint: m.tint, visible: !!m.visible, order: team.map((x) => x.id) }));
      viewTeam();
    };
    page('L’équipe',
      h('p', h('button.b.b--main', { type: 'button', onclick: () => edit() }, '+ Nouveau portrait')),
      h('div.people', team.map((m, i) => h('section.card.person' + (m.visible ? '' : '.is-off'),
        m.photo ? h('img', { src: '/assets/img/equipe/' + m.photo, alt: '', width: 150, height: 188 }) : h('div.person__ph.tint-' + m.tint, m.name.slice(0, 1)),
        h('h2', m.name), h('p.muted', trf(m.role)),
        h('div.row.row--tight',
          h('button.b.b--sm', { type: 'button', onclick: () => edit(m) }, 'Modifier'),
          h('button.b.b--sm', { type: 'button', onclick: () => upload(m) }, m.photo ? 'Changer la photo' : 'Ajouter une photo'),
          h('button.b.b--sm', { type: 'button', 'aria-label': 'Avancer', onclick: () => order(i, -1) }, '←'),
          h('button.b.b--sm', { type: 'button', 'aria-label': 'Reculer', onclick: () => order(i, 1) }, '→'),
          h('button.b.b--sm.b--danger', { type: 'button', onclick: async () => { if (await confirmBox(`Retirer le portrait de ${m.name} ?`)) { must(await api('POST', `/team/${m.id}/delete`)); viewTeam(); } } }, 'Retirer'))))));
  }

  /* ---------- Réglages */
  async function viewSettings() {
    const j = must(await api('GET', '/settings'));
    const S = j.settings;
    const inp = (label, value, attrs = {}) => { const i = h('input', { value: value ?? '', ...attrs }); const l = h('label', label, i); l.input = i; return l; };
    const chk = (label, value) => { const i = h('input', { type: 'checkbox', checked: !!value }); const l = h('label.check', i, h('span', label)); l.input = i; return l; };
    const hhmm = (m) => String(Math.floor(m / 60)).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0');
    const mins = (v) => { const [a, b] = String(v).split(':').map(Number); return a * 60 + (b || 0); };

    const shop = { name: inp('Nom', S.shop.name), phone: inp('Téléphone', S.shop.phone, { type: 'tel' }), email: inp('E-mail', S.shop.email, { type: 'email' }), street: inp('Rue', S.shop.street), postal: inp('Code postal', S.shop.postal), city: inp('Ville', S.shop.city),
      lat: inp('Latitude', S.shop.lat, { inputmode: 'decimal' }), lng: inp('Longitude', S.shop.lng, { inputmode: 'decimal' }), maps_url: inp('Lien Google Maps', S.shop.maps_url, { type: 'url' }), owner: inp('Responsable (mentions légales)', S.shop.owner), siren: inp('SIREN', S.shop.siren), since: inp('Depuis (année)', S.shop.since, { inputmode: 'numeric' }) };
    const social = { instagram: inp('Instagram', S.social.instagram, { type: 'url' }), facebook: inp('Facebook', S.social.facebook, { type: 'url' }), tiktok: inp('TikTok (vide = masqué)', S.social.tiktok, { type: 'url' }) };
    const dayNames = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    const order = [1, 2, 3, 4, 5, 6, 0];
    const dayRows = {};
    order.forEach((d) => {
      const slots = S.hours.days[d] || [];
      const closed = h('input', { type: 'checkbox', checked: !slots.length });
      const s1a = h('input', { type: 'time', value: slots[0] ? hhmm(slots[0][0]) : '13:00' }), s1b = h('input', { type: 'time', value: slots[0] ? hhmm(slots[0][1]) : '19:00' });
      const s2a = h('input', { type: 'time', value: slots[1] ? hhmm(slots[1][0]) : '' }), s2b = h('input', { type: 'time', value: slots[1] ? hhmm(slots[1][1]) : '' });
      dayRows[d] = { closed, s1a, s1b, s2a, s2b, row: h('div.hrow', h('strong', dayNames[d]), h('label.check', closed, h('span', 'Fermé')), h('span.hrow__t', s1a, '–', s1b), h('span.hrow__t.muted', 'et', s2a, '–', s2b)) };
    });
    const closures = (S.hours.closures || []).slice();
    const closuresBox = h('div');
    const drawClosures = () => closuresBox.replaceChildren(...closures.map((c, i) => h('div.row.row--tight',
      h('input', { type: 'date', value: c.from, onchange: (e) => { c.from = e.target.value; } }), '→',
      h('input', { type: 'date', value: c.to, onchange: (e) => { c.to = e.target.value; } }),
      h('input', { placeholder: 'Motif (congés…)', value: c.label || '', onchange: (e) => { c.label = e.target.value; } }),
      h('button.b.b--sm.b--danger', { type: 'button', onclick: () => { closures.splice(i, 1); drawClosures(); } }, '×'))),
      h('button.b.b--sm', { type: 'button', onclick: () => { const d = new Date().toISOString().slice(0, 10); closures.push({ from: d, to: d, label: '' }); drawClosures(); } }, '+ Fermeture exceptionnelle'));
    drawClosures();
    const brunch = { enabled: chk('Afficher le brunch du dimanche', S.brunch.enabled), price: inp('Prix par personne', (S.brunch.price_cents / 100).toFixed(2).replace('.', ','), { inputmode: 'decimal' }), from: inp('À partir de', S.brunch.from) };
    const orders = { enabled: chk('Accepter les commandes de gâteaux en ligne', S.orders.enabled), lead: inp('Délai minimum (heures)', S.orders.lead_hours, { type: 'number', min: 0, max: 720 }) };
    const loyalty = { goal: inp('Nombre de passages pour la récompense', S.loyalty.goal, { type: 'number', min: 2, max: 30 }), reward: trField('Récompense', S.loyalty.reward, { required: true }), cool: inp('Délai minimum entre deux tampons (minutes)', S.loyalty.cooldown_min, { type: 'number', min: 0, max: 1440 }) };
    const rating = { show: chk('Afficher la note Google', S.rating.show), value: inp('Note (sur 5)', S.rating.value, { inputmode: 'decimal' }), count: inp('Nombre d’avis', S.rating.count, { type: 'number' }) };
    const ann = trField('Annonce en haut du site (vide = aucune)', S.announcement);

    const collect = () => ({
      shop: Object.fromEntries(Object.entries(shop).map(([k, l]) => [k, l.input.value])),
      social: Object.fromEntries(Object.entries(social).map(([k, l]) => [k, l.input.value.trim()])),
      hours: {
        days: [0, 1, 2, 3, 4, 5, 6].map((d) => {
          const r = dayRows[d];
          if (r.closed.checked) return [];
          const out = [[mins(r.s1a.value), mins(r.s1b.value)]];
          if (r.s2a.value && r.s2b.value) out.push([mins(r.s2a.value), mins(r.s2b.value)]);
          return out;
        }),
        closures,
      },
      brunch: { enabled: brunch.enabled.input.checked, price: brunch.price.input.value, from: brunch.from.input.value },
      orders: { enabled: orders.enabled.input.checked, lead_hours: orders.lead.input.value },
      loyalty: { goal: loyalty.goal.input.value, reward: loyalty.reward.val(), cooldown_min: loyalty.cool.input.value },
      rating: { show: rating.show.input.checked, value: rating.value.input.value, count: rating.count.input.value },
      announcement: ann.val(),
    });
    const form = h('form.settings',
      h('section.card', h('h2', 'La boutique'), h('div.fgrid', Object.values(shop))),
      h('section.card', h('h2', 'Horaires'), order.map((d) => dayRows[d].row), h('h3', 'Fermetures exceptionnelles'), closuresBox),
      h('section.card', h('h2', 'Réseaux sociaux'), h('div.fgrid', Object.values(social))),
      h('section.card', h('h2', 'Brunch'), h('div.fgrid', Object.values(brunch))),
      h('section.card', h('h2', 'Commandes de gâteaux'), h('div.fgrid', Object.values(orders))),
      h('section.card', h('h2', 'Carte de fidélité'), h('div.fgrid', loyalty.goal, loyalty.cool), loyalty.reward),
      h('section.card', h('h2', 'Note Google'), h('div.fgrid', Object.values(rating))),
      h('section.card', h('h2', 'Annonce'), ann),
      h('div.sticky-save', h('button.b.b--main.b--big', { type: 'submit' }, 'Enregistrer les réglages')));
    form.addEventListener('submit', async (e) => { e.preventDefault(); must(await api('POST', '/settings', { settings: collect() })); toast('Réglages enregistrés · le site est à jour'); });
    page('Réglages', form);
  }

  /* ---------- Comptes */
  async function viewUsers() {
    const j = must(await api('GET', '/users'));
    const edit = (u = {}) => {
      const name = h('input', { value: u.name || '', required: true });
      const email = h('input', { type: 'email', value: u.email || '', required: true });
      const role = h('select', h('option', { value: 'staff', selected: u.role !== 'admin' ? true : null }, 'Collaborateur (tickets, fidélité, disponibilités)'), h('option', { value: 'admin', selected: u.role === 'admin' ? true : null }, 'Gérant (tous les droits)'));
      const pass = h('input', { type: 'password', autocomplete: 'new-password', minlength: 10, placeholder: u.id ? 'Laisser vide pour ne pas changer' : '10 caractères au moins' });
      const active = h('input', { type: 'checkbox', checked: u.id ? !!u.active : true });
      modal(u.id ? 'Modifier le compte' : 'Nouveau compte', [h('label', 'Nom', name), h('label', 'E-mail (identifiant)', email), h('label', 'Rôle', role), h('label', 'Mot de passe', pass), h('label.check', active, h('span', 'Compte actif'))],
        async () => { must(await api('POST', '/users', { id: u.id, name: name.value, email: email.value, role: role.value, password: pass.value, active: active.checked })); toast('Compte enregistré'); viewUsers(); });
    };
    page('Comptes',
      h('p', h('button.b.b--main', { type: 'button', onclick: () => edit() }, '+ Nouveau compte')),
      h('div.table', j.users.map((u) => h('div.trow' + (u.active ? '' : '.is-off'),
        h('strong', u.name), h('span', u.email), h('span.pill', u.role === 'admin' ? 'Gérant' : 'Collaborateur'),
        h('span.muted', u.last_login ? 'vu ' + when(u.last_login) : 'jamais connecté'),
        h('button.b.b--sm', { type: 'button', onclick: () => edit(u) }, 'Modifier')))));
  }

  /* ---------- Journal */
  async function viewAudit() {
    const j = must(await api('GET', '/audit'));
    page('Journal', h('p.muted', 'Les 200 dernières actions de l’équipe (sécurité).'),
      h('div.table', j.log.map((a) => h('div.trow', h('span.muted', when(a.created_at)), h('strong', a.name || '—'), h('span', a.action), h('span', a.detail || ''), h('span.muted', a.ip || '')))));
  }

  /* ---------- Mon compte */
  function viewMe() {
    const cur = h('input', { type: 'password', autocomplete: 'current-password', required: true });
    const nw = h('input', { type: 'password', autocomplete: 'new-password', minlength: 10, required: true });
    const form = h('form.card', h('h2', 'Changer mon mot de passe'), h('label', 'Mot de passe actuel', cur), h('label', 'Nouveau mot de passe (10 caractères au moins)', nw), h('button.b.b--main', { type: 'submit' }, 'Changer'));
    form.addEventListener('submit', async (e) => { e.preventDefault(); must(await api('POST', '/password', { current: cur.value, new: nw.value })); form.reset(); toast('Mot de passe changé'); });
    page('Mon compte', h('p', `${ME.name} · ${ME.email} · ${ME.role === 'admin' ? 'Gérant' : 'Collaborateur'}`), form);
  }

  go();
})();
