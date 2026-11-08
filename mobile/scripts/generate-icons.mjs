#!/usr/bin/env node
/*
 * Ícones e splash do app, a partir do violão do public/favicon.svg do site
 * (spec 2026-11-08-app-mobile-design, AM35): o violão em creme, inclinado,
 * sobre o terracota da marca.
 *
 * Como rodar (de dentro de mobile/):
 *
 *   npm install --no-save sharp
 *   npm run icons
 *
 * O sharp fica fora do package.json de propósito (como no HelpCheck, ele não
 * entra no build do EAS): só quem gera os ícones o instala.
 *
 * Saída:
 *   assets/images/icon.png                    1024, opaco (iOS e o ícone antigo do Android)
 *   assets/images/android-icon-background.png 1024, o gradiente terracota
 *   assets/images/android-icon-foreground.png 1024, o violão no círculo seguro de 66dp
 *   assets/images/android-icon-monochrome.png 1024, o violão em branco (ícones temáticos do Android 13+)
 *   assets/images/splash-icon.png             o violão creme sobre transparente (o fundo vem do app.json)
 *   assets/images/favicon.png                 48
 *   store/google-play/icone-512.png           512
 *   store/google-play/grafico-recursos-1024x500.png
 *   store/app-store/icone-1024.png            1024 (o mesmo icon.png)
 */
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const OUT = path.join(ROOT, 'assets/images');
const STORE = path.join(ROOT, 'store');

const BRAND_LIGHT = '#D5731F';
const BRAND = '#C2621B';
const BRAND_DARK = '#9A4A12';
const TILT = -18;

const LIGHT = { body: '#FDF5EC', dark: '#F5CFA8', darker: '#EEAF73', detail: '#FBE9D7', strings: BRAND };

/** O violão do favicon (viewBox 0 0 64 64), nas cores dadas. */
function guitar(c) {
  return `
    <rect x="29" y="2" width="6" height="28" rx="2" fill="${c.dark}"/>
    <line x1="30" y1="8" x2="34" y2="8" stroke="${c.detail}" stroke-width="1"/>
    <line x1="30" y1="14" x2="34" y2="14" stroke="${c.detail}" stroke-width="1"/>
    <line x1="30" y1="20" x2="34" y2="20" stroke="${c.detail}" stroke-width="1"/>
    <rect x="27" y="0" width="10" height="6" rx="3" fill="${c.darker}"/>
    <circle cx="28" cy="2" r="1.5" fill="${c.detail}"/>
    <circle cx="36" cy="2" r="1.5" fill="${c.detail}"/>
    <circle cx="28" cy="5" r="1.5" fill="${c.detail}"/>
    <circle cx="36" cy="5" r="1.5" fill="${c.detail}"/>
    <ellipse cx="32" cy="36" rx="14" ry="11" fill="${c.body}"/>
    <ellipse cx="32" cy="50" rx="17" ry="13" fill="${c.body}"/>
    <ellipse cx="32" cy="43" rx="11" ry="6" fill="${c.body}"/>
    <circle cx="32" cy="44" r="6" fill="${c.dark}"/>
    <circle cx="32" cy="44" r="5" fill="none" stroke="${c.darker}" stroke-width="1"/>
    <circle cx="32" cy="44" r="7" fill="none" stroke="${c.darker}" stroke-width="0.8"/>
    <rect x="28" y="52" width="8" height="2" rx="1" fill="${c.darker}"/>
    <line x1="31" y1="6" x2="31" y2="52" stroke="${c.strings}" stroke-width="0.5" opacity="0.6"/>
    <line x1="33" y1="6" x2="33" y2="52" stroke="${c.strings}" stroke-width="0.5" opacity="0.6"/>
    <line x1="30" y1="6" x2="30" y2="52" stroke="${c.strings}" stroke-width="0.4" opacity="0.4"/>
    <line x1="34" y1="6" x2="34" y2="52" stroke="${c.strings}" stroke-width="0.4" opacity="0.4"/>`;
}

/** A silhueta em branco, com a boca do violão vazada (o Android só lê o alfa). */
function silhouette() {
  return `
    <defs><mask id="hole"><rect x="0" y="0" width="64" height="64" fill="white"/><circle cx="32" cy="44" r="6" fill="black"/></mask></defs>
    <g mask="url(#hole)" fill="white">
      <rect x="29" y="2" width="6" height="28" rx="2"/>
      <rect x="27" y="0" width="10" height="6" rx="3"/>
      <ellipse cx="32" cy="36" rx="14" ry="11"/>
      <ellipse cx="32" cy="50" rx="17" ry="13"/>
      <ellipse cx="32" cy="43" rx="11" ry="6"/>
    </g>`;
}

/** O violão com `height` px de altura, centrado em (cx, cy), inclinado. */
function placed(content, cx, cy, height) {
  const scale = height / 64;
  return `<g transform="translate(${cx} ${cy}) rotate(${TILT}) scale(${scale}) translate(-32 -32)">${content}</g>`;
}

const gradient = `<defs><linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
  <stop offset="0" stop-color="${BRAND_LIGHT}"/><stop offset="0.55" stop-color="${BRAND}"/><stop offset="1" stop-color="${BRAND_DARK}"/>
</linearGradient></defs>`;

const svg = (w, h, body) => `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">${body}</svg>`;

async function png(file, markup, { opaque = false, resize } = {}) {
  let image = sharp(Buffer.from(markup));
  if (resize) image = image.resize(resize, resize);
  if (opaque) image = image.flatten({ background: BRAND }).removeAlpha();
  await mkdir(path.dirname(file), { recursive: true });
  await writeFile(file, await image.png().toBuffer());
  console.log('ok', path.relative(ROOT, file));
}

const iconSvg = svg(1024, 1024, `${gradient}<rect width="1024" height="1024" fill="url(#bg)"/>${placed(guitar(LIGHT), 474, 505, 660)}`);

await png(path.join(OUT, 'icon.png'), iconSvg, { opaque: true });
await png(path.join(OUT, 'android-icon-background.png'), svg(1024, 1024, `${gradient}<rect width="1024" height="1024" fill="url(#bg)"/>`), { opaque: true });
// Círculo seguro do ícone adaptativo: 66 de 108 dp, ~626 px; o violão
// inclinado com 540 px de altura cabe nele. Os centros puxam para a
// esquerda: inclinado, o corpo do violão pesa à direita.
await png(path.join(OUT, 'android-icon-foreground.png'), svg(1024, 1024, placed(guitar(LIGHT), 481, 510, 540)));
await png(path.join(OUT, 'android-icon-monochrome.png'), svg(1024, 1024, placed(silhouette(), 481, 510, 540)));
await png(path.join(OUT, 'splash-icon.png'), svg(512, 512, placed(guitar(LIGHT), 229, 250, 470)));
await png(path.join(OUT, 'favicon.png'), iconSvg, { opaque: true, resize: 48 });

await png(path.join(STORE, 'app-store/icone-1024.png'), iconSvg, { opaque: true });
await png(path.join(STORE, 'google-play/icone-512.png'), iconSvg, { opaque: true, resize: 512 });
await png(
  path.join(STORE, 'google-play/grafico-recursos-1024x500.png'),
  svg(
    1024,
    500,
    `${gradient}<rect width="1024" height="500" fill="url(#bg)"/>
     ${placed(guitar(LIGHT), 210, 255, 360)}
     <text x="380" y="232" font-family="Georgia, 'Times New Roman', serif" font-size="62" font-weight="700" fill="#FDF5EC">De Casa em Casa</text>
     <text x="383" y="292" font-family="Helvetica, Arial, sans-serif" font-size="30" fill="#FBE9D7">Encontros dentro de casas reais.</text>
     <text x="383" y="334" font-family="Helvetica, Arial, sans-serif" font-size="30" fill="#FBE9D7">Música e conexão de verdade.</text>`,
  ),
  { opaque: true },
);
