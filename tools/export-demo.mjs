// Version de démonstration statique du site, pour la faire consulter avant la mise en ligne (GitHub Pages).
// Les pages sont rendues par le serveur PHP local, puis réécrites pour vivre sous un sous-dossier.
// Les formulaires, la carte de fidélité et l’espace équipe ont besoin du serveur : ils ne marchent pas sur cette copie.
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

// Les adresses absolues du site (« /fr/… », « /assets/… ») passent sous le sous-dossier de la démo
const rewrite = (s) => s
  .split(local).join(base)
  .replace(/((?:href|src|action|poster|content)=")\/(?!\/)/g, `$1${prefix}/`)
  .replace(/([\s,"])\/assets\//g, `$1${prefix}/assets/`)
  .replace(/"\/(fr|en|de|es|zh)\//g, `"${prefix}/$1/`)
  .replace(/url\((["']?)\/(?!\/)/g, `url($1${prefix}/`);
// la démo ne doit pas être indexée : elle ferait concurrence au vrai site
const page = (html) => rewrite(html)
  .replace(/<meta name="robots" content="[^"]*">/, '<meta name="robots" content="noindex, nofollow">');

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
  write(path.join(u, 'index.html'), page(await r.text()));
}
const nf = await fetch(local + '/fr/page-introuvable-demo/');
write('404.html', page(await nf.text()));

// 3. l'accueil sans langue mène à la version française ; pas d'indexation ; pas de traitement Jekyll
write('index.html', `<!doctype html><meta charset="utf-8"><meta name="robots" content="noindex"><title>L’Armoire à Cuillères</title><meta http-equiv="refresh" content="0; url=${prefix}/fr/"><a href="${prefix}/fr/">L’Armoire à Cuillères</a>\n`);
write('robots.txt', 'User-agent: *\nDisallow: /\n');
write('.nojekyll', '');
console.log(`${urls.length} pages exportées dans ${out} pour ${base}`);
