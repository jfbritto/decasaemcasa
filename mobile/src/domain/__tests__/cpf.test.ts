import { readFileSync } from 'node:fs';
import { join } from 'node:path';

import { cleanCpf, isValidCpf } from '@/domain/cpf';

// Os mesmos casos do PHPUnit (tests/Unit/CpfCasesTest.php): o app nunca
// aceita o que a API recusa (spec 2026-11-08-app-mobile-design, AM30).
const fixture = JSON.parse(readFileSync(join(__dirname, '../../../../tests/fixtures/cpf-cases.json'), 'utf8')) as {
  cases: { input: string; valid: boolean; note?: string }[];
};

describe('isValidCpf (tests/fixtures/cpf-cases.json)', () => {
  it.each(fixture.cases.map((c) => [c.input === '' ? '(vazio)' : c.input, c.input, c.valid] as const))('%s', (_label, input, valid) => {
    expect(isValidCpf(input)).toBe(valid);
  });

  it('tem casos dos dois lados', () => {
    expect(fixture.cases.some((c) => c.valid)).toBe(true);
    expect(fixture.cases.some((c) => !c.valid)).toBe(true);
  });
});

describe('cleanCpf', () => {
  it('fica só com os dígitos', () => {
    expect(cleanCpf(' 529.982.247-25 ')).toBe('52998224725');
  });
});
