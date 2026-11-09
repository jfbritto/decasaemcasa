// Paleta do De Casa em Casa (spec 2026-11-08-app-mobile-design, AM29): a do
// violão do favicon do site. A cor da marca (terracota) é para navegação,
// botões e identidade; ela NUNCA representa estado. Estado usa `tones`
// abaixo, nas mesmas cores da página de status da web (amarelo em
// curadoria, azul aguardando pagamento, verde confirmado, laranja fila de
// espera, vermelho não aprovada, cinza cancelada).

export const brand = {
  50: '#FDF5EC',
  100: '#FBE9D7',
  200: '#F5CFA8',
  300: '#EEAF73',
  400: '#E88A2D',
  500: '#D5731F',
  600: '#C2621B',
  700: '#9A4A12',
  800: '#7A3A10',
  900: '#5A2A0B',
} as const;

export const colors = {
  /** O fundo das telas: creme, como o gradiente amber-50 do site. */
  bg: '#FBF6EF',
  surface: '#FFFFFF',
  /** Linhas e blocos dentro de um cartão. */
  surfaceSoft: '#F7EFE4',
  text: '#2B1D12',
  textMuted: '#6E5A48',
  /** Só decoração e desabilitado, nunca informação. */
  textFaint: '#A08B78',
  /** Divisórias: decoração. */
  border: '#EADFD1',
  /** A linha de um campo ou botão secundário: 3:1 sobre bg e surface (WCAG 1.4.11). */
  control: '#9C8670',
  primary: brand[600],
  primaryPressed: brand[700],
  onPrimary: '#FFFFFF',
  danger: '#DC2626',
  dangerPressed: '#B91C1C',
  dangerSoft: '#FEF2F2',
  dangerBorder: '#FECACA',
  /** O verde do botão "Falar no WhatsApp" (a marca do WhatsApp, não um estado). */
  whatsapp: '#16A34A',
} as const;

export type Tone = 'curation' | 'payment' | 'success' | 'waiting' | 'danger' | 'neutral';

/**
 * Os tons de estado: `solid` marca (ponto, ícone), `soft` é o fundo de um
 * aviso e `text` o texto sobre ele (a cor pura sobre o tom claro é fraca
 * demais para ler).
 */
export const tones: Readonly<Record<Tone, { solid: string; soft: string; text: string; border: string }>> = {
  curation: { solid: '#CA8A04', soft: '#FEFCE8', text: '#854D0E', border: '#FDE68A' },
  payment: { solid: '#2563EB', soft: '#EFF6FF', text: '#1E40AF', border: '#BFDBFE' },
  success: { solid: '#16A34A', soft: '#F0FDF4', text: '#166534', border: '#BBF7D0' },
  waiting: { solid: '#EA580C', soft: '#FFF7ED', text: '#9A3412', border: '#FED7AA' },
  danger: { solid: '#DC2626', soft: '#FEF2F2', text: '#991B1B', border: '#FECACA' },
  neutral: { solid: '#64748B', soft: '#F1F5F9', text: '#334155', border: '#E2E8F0' },
};
