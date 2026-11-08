// Máscaras dos campos do formulário de inscrição: o que a pessoa vê enquanto
// digita, no mesmo formato do site.

/** 000.000.000-00 */
export function maskCpf(value: string): string {
  const d = value.replace(/\D/g, '').slice(0, 11);
  if (d.length <= 3) return d;
  if (d.length <= 6) return `${d.slice(0, 3)}.${d.slice(3)}`;
  if (d.length <= 9) return `${d.slice(0, 3)}.${d.slice(3, 6)}.${d.slice(6)}`;
  return `${d.slice(0, 3)}.${d.slice(3, 6)}.${d.slice(6, 9)}-${d.slice(9)}`;
}

/** (00) 00000-0000, ou (00) 0000-0000 com 10 dígitos. */
export function maskPhone(value: string): string {
  const d = value.replace(/\D/g, '').slice(0, 11);
  if (d.length === 0) return '';
  if (d.length <= 2) return `(${d}`;
  if (d.length <= 6) return `(${d.slice(0, 2)}) ${d.slice(2)}`;
  if (d.length <= 10) return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`;
  return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`;
}

/** DD/MM/AAAA */
export function maskDate(value: string): string {
  const d = value.replace(/\D/g, '').slice(0, 8);
  if (d.length <= 2) return d;
  if (d.length <= 4) return `${d.slice(0, 2)}/${d.slice(2)}`;
  return `${d.slice(0, 2)}/${d.slice(2, 4)}/${d.slice(4)}`;
}

/**
 * DD/MM/AAAA para AAAA-MM-DD, o que a API espera; null quando a data não
 * existe (31/02) ou não está completa.
 */
export function brDateToIso(value: string): string | null {
  const match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(value.trim());
  if (!match) return null;
  const [, dd, mm, yyyy] = match;
  const day = Number(dd);
  const month = Number(mm);
  const year = Number(yyyy);
  const date = new Date(Date.UTC(year, month - 1, day));
  if (date.getUTCFullYear() !== year || date.getUTCMonth() !== month - 1 || date.getUTCDate() !== day) return null;
  return `${yyyy}-${mm}-${dd}`;
}

/** A data de nascimento é anterior a hoje (a regra "before:today" da API). */
export function isPastIsoDate(iso: string, now: Date = new Date()): boolean {
  const today = now.toISOString().slice(0, 10);
  return iso < today && iso >= '1900-01-01';
}

/**
 * "35,50" ou "35.50" para 35.5; null quando não é um valor. Aceita o
 * separador de milhar ("1.200,00").
 */
export function parseMoney(value: string): number | null {
  const text = value.trim().replace(/^R\$\s*/i, '');
  if (!text) return null;
  const normalized = text.includes(',') ? text.replace(/\./g, '').replace(',', '.') : text;
  if (!/^\d+(\.\d{1,2})?$/.test(normalized)) return null;
  return Number(normalized);
}
