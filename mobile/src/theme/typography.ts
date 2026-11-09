// Subpastas por peso de propósito: o índice do pacote exige todos os TTF, e o
// Metro empacotaria todos (como no HelpCheck).
import { Figtree_400Regular } from '@expo-google-fonts/figtree/400Regular';
import { Figtree_500Medium } from '@expo-google-fonts/figtree/500Medium';
import { Figtree_600SemiBold } from '@expo-google-fonts/figtree/600SemiBold';
import { Figtree_700Bold } from '@expo-google-fonts/figtree/700Bold';
// Fraunces só nos títulos (a voz "de casa", calorosa); a interface é Figtree,
// a fonte do site (tailwind.config.js). Spec 2026-11-08-app-mobile-design, AM29.
import { Fraunces_600SemiBold } from '@expo-google-fonts/fraunces/600SemiBold';
import { Fraunces_700Bold } from '@expo-google-fonts/fraunces/700Bold';
import type { TextStyle } from 'react-native';

import { colors } from './colors';

// O mapa passado ao useFonts() do layout raiz. Cada peso é uma família: o
// Android não escolhe a face só pelo fontWeight.
export const fontAssets = {
  Figtree_400Regular,
  Figtree_500Medium,
  Figtree_600SemiBold,
  Figtree_700Bold,
  Fraunces_600SemiBold,
  Fraunces_700Bold,
};

export const fonts = {
  regular: 'Figtree_400Regular',
  medium: 'Figtree_500Medium',
  semibold: 'Figtree_600SemiBold',
  bold: 'Figtree_700Bold',
  display: 'Fraunces_700Bold',
  displaySoft: 'Fraunces_600SemiBold',
} as const satisfies Record<string, keyof typeof fontAssets>;

export type TextVariant = 'display' | 'title' | 'heading' | 'body' | 'bodyStrong' | 'label' | 'caption' | 'button' | 'input' | 'quote';

// Sem fontWeight: a família já escolhe a face, e um negrito por cima faz a
// plataforma sintetizar um falso negrito.
export const textVariants: Record<TextVariant, TextStyle> = {
  display: { fontFamily: fonts.display, fontSize: 32, lineHeight: 38, color: colors.text, letterSpacing: -0.4 },
  title: { fontFamily: fonts.display, fontSize: 24, lineHeight: 30, color: colors.text, letterSpacing: -0.2 },
  heading: { fontFamily: fonts.displaySoft, fontSize: 19, lineHeight: 25, color: colors.text },
  body: { fontFamily: fonts.regular, fontSize: 16, lineHeight: 24, color: colors.text },
  bodyStrong: { fontFamily: fonts.semibold, fontSize: 16, lineHeight: 24, color: colors.text },
  label: { fontFamily: fonts.semibold, fontSize: 14, lineHeight: 20, color: colors.text },
  caption: { fontFamily: fonts.medium, fontSize: 13, lineHeight: 18, color: colors.textMuted },
  button: { fontFamily: fonts.bold, fontSize: 16, lineHeight: 20, color: colors.text },
  // Sem lineHeight: no iOS ele desloca o texto de um TextInput de uma linha.
  input: { fontFamily: fonts.regular, fontSize: 16, color: colors.text },
  quote: { fontFamily: fonts.displaySoft, fontSize: 20, lineHeight: 30, color: colors.text },
};
