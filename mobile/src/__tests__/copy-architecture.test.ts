import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join, relative } from 'node:path';

// A copy do De Casa em Casa (spec 2026-11-08-app-mobile-design, AM25 e AM33,
// e a memória de copy do projeto): nenhum texto do app usa travessão (tem
// "cara de IA", nas palavras do João) nem "cadeira" (o certo é "seu lugar na
// sala"), e nada promete atualização por WhatsApp, que é só canal de
// contato. Comentários podem ter travessão: o teste lê só as strings.

const SRC = join(__dirname, '..');

function sourceFiles(dir: string): string[] {
  return readdirSync(dir).flatMap((name) => {
    const path = join(dir, name);
    if (statSync(path).isDirectory()) return name === '__tests__' || name === 'test-utils' ? [] : sourceFiles(path);
    return /\.(ts|tsx)$/.test(name) ? [path] : [];
  });
}

/** As strings de um arquivo: entre aspas simples, duplas, crases e o texto entre tags JSX. */
function literals(source: string): string[] {
  const withoutComments = source.replace(/\/\*[\s\S]*?\*\//g, '').replace(/(^|[^:])\/\/.*$/gm, '$1');
  const quoted = withoutComments.match(/'(?:[^'\\\n]|\\.)*'|"(?:[^"\\\n]|\\.)*"|`(?:[^`\\]|\\.)*`/g) ?? [];
  const jsxText = withoutComments.match(/>[^<>{}]*[A-Za-zÀ-ú][^<>{}]*</g) ?? [];
  return [...quoted, ...jsxText];
}

const files = sourceFiles(SRC);

it('encontra os arquivos do app', () => {
  expect(files.length).toBeGreaterThan(30);
});

it.each(files.map((file) => [relative(SRC, file), file]))('%s: sem travessão, sem "cadeira", sem prometer WhatsApp', (_name, file) => {
  for (const text of literals(readFileSync(file, 'utf8'))) {
    expect(text).not.toMatch(/[—–]/);
    expect(text).not.toMatch(/cadeira/i);
    expect(text).not.toMatch(/(avis|atualiz)\w* (por|pelo) whatsapp/i);
  }
});
