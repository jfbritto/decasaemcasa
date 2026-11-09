import type { NativeStackNavigationOptions } from 'expo-router';

import { brand, colors } from '@/theme/colors';
import { fonts } from '@/theme/typography';

/** O cabeçalho nativo das telas de detalhe: creme, sem sombra, voltar terracota. */
export function detailHeader(title: string): NativeStackNavigationOptions {
  return {
    headerShown: true,
    title,
    headerBackTitle: 'Voltar',
    headerShadowVisible: false,
    headerTintColor: brand[700],
    headerStyle: { backgroundColor: colors.bg },
    headerTitleStyle: { fontFamily: fonts.semibold, color: colors.text },
    contentStyle: { backgroundColor: colors.bg },
  };
}
