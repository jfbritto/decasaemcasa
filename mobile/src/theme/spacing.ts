// Escala de 4 pt. Usar estes valores no lugar de números soltos.
export const spacing = {
  xxs: 2,
  xs: 4,
  sm: 8,
  md: 12,
  lg: 16,
  xl: 24,
  xxl: 32,
  xxxl: 48,
} as const;

export const radius = {
  sm: 10,
  md: 14,
  lg: 18,
  xl: 24,
  full: 999,
} as const;

// Alvo mínimo de toque (Apple HIG 44 pt, Material 48 dp).
export const touchTarget = 44;

/** Altura de um botão e dos campos principais. */
export const controlHeight = 52;
