import { brDateToIso, isPastIsoDate, maskCpf, maskDate, maskPhone, parseMoney } from '@/domain/masks';

describe('maskCpf', () => {
  it.each([
    ['', ''],
    ['529', '529'],
    ['5299', '529.9'],
    ['5299822', '529.982.2'],
    ['52998224725', '529.982.247-25'],
    ['529.982.247-25999', '529.982.247-25'],
    ['abc52998', '529.98'],
  ])('%s vira %s', (input, expected) => {
    expect(maskCpf(input)).toBe(expected);
  });
});

describe('maskPhone', () => {
  it.each([
    ['', ''],
    ['2', '(2'],
    ['27', '(27'],
    ['279', '(27) 9'],
    ['2733334444', '(27) 3333-4444'],
    ['27999990000', '(27) 99999-0000'],
    ['(27) 99999-00001', '(27) 99999-0000'],
  ])('%s vira %s', (input, expected) => {
    expect(maskPhone(input)).toBe(expected);
  });
});

describe('maskDate e brDateToIso', () => {
  it('mascara enquanto digita', () => {
    expect(maskDate('0403')).toBe('04/03');
    expect(maskDate('04031992')).toBe('04/03/1992');
    expect(maskDate('040319921')).toBe('04/03/1992');
  });

  it('converte só datas que existem', () => {
    expect(brDateToIso('04/03/1992')).toBe('1992-03-04');
    expect(brDateToIso('29/02/2024')).toBe('2024-02-29');
    expect(brDateToIso('29/02/2023')).toBeNull();
    expect(brDateToIso('31/04/2000')).toBeNull();
    expect(brDateToIso('4/3/1992')).toBeNull();
    expect(brDateToIso('')).toBeNull();
  });

  it('a data de nascimento é anterior a hoje', () => {
    const now = new Date('2026-11-08T12:00:00Z');
    expect(isPastIsoDate('1992-03-04', now)).toBe(true);
    expect(isPastIsoDate('2026-11-08', now)).toBe(false);
    expect(isPastIsoDate('2027-01-01', now)).toBe(false);
    expect(isPastIsoDate('1850-01-01', now)).toBe(false);
  });
});

describe('parseMoney', () => {
  it.each([
    ['50', 50],
    ['35,5', 35.5],
    ['35,50', 35.5],
    ['35.50', 35.5],
    ['1.200,00', 1200],
    ['R$ 80', 80],
    ['', null],
    ['abc', null],
    ['10,555', null],
    ['-5', null],
  ])('%s vira %s', (input, expected) => {
    expect(parseMoney(input)).toBe(expected);
  });
});
