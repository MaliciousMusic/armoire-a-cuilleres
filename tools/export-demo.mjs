// Version de démonstration statique du site, pour la faire consulter avant la mise en ligne (GitHub Pages).
// Les pages sont rendues par le serveur PHP local, puis réécrites pour vivre sous un sous-dossier.
// Les formulaires, la carte de fidélité et l'espace équipe ont besoin du serveur : ils ne marchent pas sur la démo.
//
//   node tools/export-demo.mjs <dossier de sortie> <adresse publique de la démo> [serveur local]
//   ex. node tools/export-demo.mjs demo-dist https://maliciousmusic.github.io/armoire-a-cuilleres http://localhost:8080
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const [out, publicUrl, local = 'http://localhost:8080'] = process.argv.slice(2);
if (!out || !publicUrl) {
  console.error('usage : node tools/export-demo.mjs <dossier de sortie> <adresse publique> [serveur local]');
  process.exit(1);
}
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const base = publicUrl.replace(/\/$/, '');
const prefix = new URL(base).pathname.replace(/\/$/, ''); // « /armoire-a-cuilleres »
const NOTE = {
  fr: 'Aperçu de démonstration : les formulaires, la carte de fidélité et l’espace équipe ne fonctionnent pas sur cette version.',
  en: 'Demo preview: forms, the loyalty card and the staff area do not work on this version.',
  de: 'Demo-Vorschau: Formulare, Treuekarte und Mitarbeiterbereich funktionieren in dieser Version nicht.',
  es: 'Vista previa: los formularios, la tarjeta de fidelidad y el espacio del equipo no funcionan en esta versión.',
  zh: '演示预览：此版本中的表单、会员卡和员工后台无法使用。',
};

// Les adresses absolues du site (« /fr/… », « /assets/… ») passent sous le sous-dossier de la démo
const rewrite = (s) => s
  .split(local).join(base)
  .replace(/((?:href|src|action|poster|content)=")\/(?!\/)/g, `$1${prefix}/`)
  .replace(/([\s,"])\/assets\//g, `$1${prefix}/assets/`)
  .replace(/"\/(fr|en|de|es|zh)\//g, `"${prefix}/$1/`)
  .replace(/url\((["']?)\/(?!\/)/g, `url($1${prefix}/`);

const page = (html, lang) => rewrite(html)
  .replace(/<meta name="robots" content="[^"]*">/, '<meta name="robots" content="noindex, nofollow">')
  .replace('</head>', `<style>.demo-note{position:fixed;left:12px;bottom:calc(var(--dock) + 12px);z-index:70;max-width:min(420px,calc(100vw - 24px));margin:0;padding:.6rem .9rem;border-radius:12px;background:var(--caramel);color:var(--cacao-2);font-family:var(--f-display);font-size:.95rem;line-height:1.35;box-shadow:0 10px 30px -12px rgba(0,0,0,.5)}.demo-note__ok{margin-left:.4rem;padding:.15rem .7rem;border:0;border-radius:8px;background:var(--cacao-2);color:var(--creme);font:inherit;font-weight:600;cursor:pointer}</style>\n</head>`)
  .replace(/(<body[^>]*>)/, `$1\n<p class="demo-note" role="note" hidden>${NOTE[lang] || NOTE.fr} <button type="button" class="demo-note__ok">OK</button></p>`)
  // l'encart se ferme d'un clic et ne revient plus pendant la visite
  .replace('</body>', `<script>(()=>{const n=document.querySelector('.demo-note');let seen=false;try{seen=sessionStorage.getItem('demo-note')==='1'}catch(e){}if(seen)return;n.hidden=false;n.querySelector('button').addEventListener('click',()=>{n.remove();try{sessionStorage.setItem('demo-note','1')}catch(e){}})})()</script>\n</body>`);

const write = (rel, data) => {
  const f = path.join(out, rel);
  fs.mkdirSync(path.dirname(f), { recursive: true });
  fs.writeFileSync(f, data);
};

fs.rmSync(out, { recursive: true, force: true });

// 1. les fichiers publics (sans le PHP ni la configuration Apache)
const copy = (from, to) => {
  for (const e of fs.readdirSync(from, { withFileTypes: true })) {
    const a = path.join(from, e.name), b = path.join(to, e.name);
    if (e.isDirectory()) { copy(a, b); continue; }
    if (['index.php', '.htaccess', 'robots.txt'].includes(e.name)) continue;
    fs.mkdirSync(to, { recursive: true });
    if (/\.(css|js|webmanifest)$/.test(e.name)) fs.writeFileSync(b, rewrite(fs.readFileSync(a, 'utf8')));
    else fs.copyFileSync(a, b);
  }
};
copy(path.join(root, 'www'), out);

// 2. toutes les pages du plan du site, dans les cinq langues, plus la page 404
const sitemap = await (await fetch(local + '/sitemap.xml')).text();
const urls = [...sitemap.matchAll(/<loc>([^<]+)<\/loc>/g)].map((m) => new URL(m[1]).pathname);
for (const u of urls) {
  const r = await fetch(local + u);
  if (!r.ok) throw new Error(`${u} : ${r.status}`);
  write(path.join(u, 'index.html'), page(await r.text(), u.split('/')[1]));
}
const nf = await fetch(local + '/fr/page-introuvable-demo/');
write('404.html', page(await nf.text(), 'fr'));

// 3. l'accueil sans langue mène à la version française ; pas d'indexation ; pas de traitement Jekyll
write('index.html', `<!doctype html><meta charset="utf-8"><meta name="robots" content="noindex"><title>L’Armoire à Cuillères</title><meta http-equiv="refresh" content="0; url=${prefix}/fr/"><a href="${prefix}/fr/">L’Armoire à Cuillères</a>\n`);
write('robots.txt', 'User-agent: *\nDisallow: /\n');
write('.nojekyll', '');
console.log(`${urls.length} pages exportées dans ${out} pour ${base}`);
