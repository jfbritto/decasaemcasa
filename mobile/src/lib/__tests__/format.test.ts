import {
  dateBadge,
  formatDateTime,
  formatDeadline,
  formatLongDate,
  formatMoney,
  formatRelative,
  formatRemaining,
  formatShortDate,
  saoPauloParts,
  spotsLabel,
} from '@/lib/format';

describe('datas no horário de Brasília', () => {
  it('lê o ISO com qualquer fuso e mostra em Brasília', () => {
    // 01:30 UTC do dia 13 é 22:30 do dia 12 em Brasília.
    expect(saoPauloParts('2026-12-13T01:30:00Z')).toEqual({ year: 2026, month: 11, day: 12, weekday: 6, hour: 22, minute: 30 });
    expect(saoPauloParts('2026-12-12T22:30:00-03:00')).toEqual(saoPauloParts('2026-12-13T01:30:00Z'));
  });

  it('texto que não é data não quebra', () => {
    expect(saoPauloParts(null)).toBeNull();
    expect(saoPauloParts('ontem')).toBeNull();
    expect(formatLongDate(undefined)).toBe('');
    expect(dateBadge('xx')).toBeNull();
  });

  it('formata como a pessoa lê', () => {
    expect(formatLongDate('2026-12-12T20:00:00-03:00')).toBe('sábado, 12 de dezembro');
    expect(formatShortDate('2026-03-04T10:00:00-03:00')).toBe('04/03/2026');
    expect(formatDateTime('2026-03-04T09:05:00-03:00')).toBe('04/03/2026 às 09h05');
    expect(dateBadge('2026-12-12T20:00:00-03:00')).toEqual({ day: '12', month: 'dez', weekday: 'sáb' });
  });

  it('o prazo no texto da página de status da web', () => {
    expect(formatDeadline('2026-11-15T17:30:00Z')).toBe('até às 14h30 de 15/11');
  });
});

describe('formatRemaining', () => {
  it.each([
    [2 * 3600_000 + 5 * 60_000 + 9_000, '2h 05min 09s'],
    [4 * 60_000 + 30_000, '4min 30s'],
    [59_000, '0min 59s'],
    [999, '0min 00s'],
  ])('%d ms', (ms, expected) => {
    expect(formatRemaining(ms)).toBe(expected);
  });

  it('acabou é null', () => {
    expect(formatRemaining(0)).toBeNull();
    expect(formatRemaining(-5)).toBeNull();
    expect(formatRemaining(Number.NaN)).toBeNull();
  });
});

describe('formatMoney e vagas', () => {
  it('reais com vírgula e milhar', () => {
    expect(formatMoney('35.50')).toBe('R$ 35,50');
    expect(formatMoney(100)).toBe('R$ 100,00');
    expect(formatMoney(1234.5)).toBe('R$ 1.234,50');
    expect(formatMoney(null)).toBe('');
  });

  it('singular e plural', () => {
    expect(spotsLabel(1)).toBe('1 vaga restante');
    expect(spotsLabel(4)).toBe('4 vagas restantes');
  });
});

describe('formatRelative', () => {
  const now = new Date('2026-11-09T15:00:00Z'); // 12h00 em Brasília

  it.each([
    ['2026-11-09T14:59:30Z', 'agora'],
    ['2026-11-09T14:55:00Z', 'há 5 min'],
    ['2026-11-09T12:00:00Z', 'há 3 h'],
    ['2026-11-09T03:30:00Z', 'há 11 h'],
    ['2026-11-09T02:30:00Z', 'ontem às 23h30'],
    ['2026-11-08T12:05:00Z', 'ontem às 09h05'],
    ['2026-11-01T22:10:00Z', '01/11 às 19h10'],
  ])('%s', (iso, expected) => {
    expect(formatRelative(iso, now)).toBe(expected);
  });

  it('sem data, nada', () => {
    expect(formatRelative(null, now)).toBe('');
  });
});
