// Construit les fichiers publiés : src/css → www/assets/css, src/js → www/assets/js (minifiés),
// et copie les librairies (jsQR, qrcode-generator). Lancer : node tools/build.mjs
import { transform } from 'esbuild';
import { readFile, writeFile, readdir, mkdir, copyFile } from 'node:fs/promises';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const nm = join(root, 'tools', 'node_modules');
const out = (p) => join(root, 'www', 'assets', p);

for (const [kind, loader] of [['css', 'css'], ['js', 'js']]) {
  await mkdir(out(kind), { recursive: true });
  for (const f of await readdir(join(root, 'src', kind))) {
    const src = await readFile(join(root, 'src', kind, f), 'utf8');
    const r = await transform(src, { loader, minify: true, target: kind === 'js' ? 'es2020' : ['chrome100', 'safari15', 'firefox100'], legalComments: 'none' });
    await writeFile(out(`${kind}/${f}`), r.code);
    console.log(`${kind}/${f}`.padEnd(22), String(src.length).padStart(7), '→', String(r.code.length).padStart(7), 'octets');
  }
}

await mkdir(out('vendor'), { recursive: true });
for (const [from, to] of [['jsqr/dist/jsQR.js', 'jsqr.js'], ['qrcode-generator/qrcode.js', 'qrcode.js']]) {
  const r = await transform(await readFile(join(nm, from), 'utf8'), { loader: 'js', minify: true, legalComments: 'inline' });
  await writeFile(out('vendor/' + to), r.code);
  console.log(('vendor/' + to).padEnd(22), String(r.code.length).padStart(17), 'octets');
}
