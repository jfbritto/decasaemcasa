// Datas, horas e dinheiro como a pessoa lê, em português e no horário de
// Brasília (o dos encontros). O Brasil não tem horário de verão desde 2019:
// UTC-3 fixo, o que deixa a conta igual em qualquer aparelho e nos testes,
// sem depender do Intl do Hermes.

const SAO_PAULO_OFFSET_MS = -3 * 60 * 60 * 1000;

const WEEKDAYS = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
const WEEKDAYS_SHORT = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
const MONTHS = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
const MONTHS_SHORT = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

type Parts = { year: number; month: number; day: number; weekday: number; hour: number; minute: number };

/** As partes da data no horário de Brasília; null para texto que não é data. */
export function saoPauloParts(iso: string | null | undefined): Parts | null {
  if (!iso) return null;
  const time = Date.parse(iso);
  if (Number.isNaN(time)) return null;
  const local = new Date(time + SAO_PAULO_OFFSET_MS);
  return {
    year: local.getUTCFullYear(),
    month: local.getUTCMonth(),
    day: local.getUTCDate(),
    weekday: local.getUTCDay(),
    hour: local.getUTCHours(),
    minute: local.getUTCMinutes(),
  };
}

const pad = (n: number) => String(n).padStart(2, '0');

/** "sábado, 14 de novembro" */
export function formatLongDate(iso: string | null | undefined): string {
  const p = saoPauloParts(iso);
  return p ? `${WEEKDAYS[p.weekday]}, ${p.day} de ${MONTHS[p.month]}` : '';
}

/** { day: "14", month: "nov", weekday: "sáb" }, para o selo de data dos cartões. */
export function dateBadge(iso: string | null | undefined): { day: string; month: string; weekday: string } | null {
  const p = saoPauloParts(iso);
  return p ? { day: String(p.day), month: MONTHS_SHORT[p.month], weekday: WEEKDAYS_SHORT[p.weekday] } : null;
}

/** "14/11/2026" */
export function formatShortDate(iso: string | null | undefined): string {
  const p = saoPauloParts(iso);
  return p ? `${pad(p.day)}/${pad(p.month + 1)}/${p.year}` : '';
}

/** "até às 14h30 de 15/11", o texto do prazo da página de status da web. */
export function formatDeadline(iso: string | null | undefined): string {
  const p = saoPauloParts(iso);
  return p ? `até às ${pad(p.hour)}h${pad(p.minute)} de ${pad(p.day)}/${pad(p.month + 1)}` : '';
}

/** "14/11/2026 às 19h05" */
export function formatDateTime(iso: string | null | undefined): string {
  const p = saoPauloParts(iso);
  return p ? `${pad(p.day)}/${pad(p.month + 1)}/${p.year} às ${pad(p.hour)}h${pad(p.minute)}` : '';
}

/**
 * O tempo que falta, no formato da web: "2h 05min 09s", "4min 30s";
 * null quando acabou.
 */
export function formatRemaining(ms: number): string | null {
  if (!(ms > 0)) return null;
  const total = Math.floor(ms / 1000);
  const h = Math.floor(total / 3600);
  const m = Math.floor((total % 3600) / 60);
  const s = total % 60;
  return `${h > 0 ? `${h}h ` : ''}${h > 0 ? pad(m) : m}min ${pad(s)}s`;
}

/**
 * Quando um aviso chegou, como a pessoa lê: "agora", "há 5 min", "há 3 h",
 * "ontem às 19h05" ou "14/11 às 09h30" (dias no horário de Brasília).
 */
export function formatRelative(iso: string | null | undefined, now: Date = new Date()): string {
  const p = saoPauloParts(iso);
  if (!p || !iso) return '';
  const diff = now.getTime() - Date.parse(iso);
  if (diff < 60_000) return 'agora';
  if (diff < 60 * 60_000) return `há ${Math.floor(diff / 60_000)} min`;

  const today = saoPauloParts(now.toISOString())!;
  const dayNumber = (x: { year: number; month: number; day: number }) => Date.UTC(x.year, x.month, x.day) / 86_400_000;
  const daysAgo = dayNumber(today) - dayNumber(p);
  const time = `${pad(p.hour)}h${pad(p.minute)}`;

  if (daysAgo === 0) return `há ${Math.floor(diff / (60 * 60_000))} h`;
  if (daysAgo === 1) return `ontem às ${time}`;
  return `${pad(p.day)}/${pad(p.month + 1)} às ${time}`;
}

/** "R$ 35,50" a partir de "35.50" ou 35.5. */
export function formatMoney(value: string | number | null | undefined): string {
  const amount = typeof value === 'string' ? Number(value) : value;
  if (amount === null || amount === undefined || Number.isNaN(amount)) return '';
  const [int, cents] = amount.toFixed(2).split('.');
  return `R$ ${int.replace(/\B(?=(\d{3})+(?!\d))/g, '.')},${cents}`;
}

/** "1 vaga restante", "5 vagas restantes" */
export function spotsLabel(spots: number): string {
  return spots === 1 ? '1 vaga restante' : `${spots} vagas restantes`;
}
