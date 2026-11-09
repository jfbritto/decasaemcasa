import type { ReactNode } from 'react';
import { StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';

import { colors } from '@/theme/colors';
import { radius, spacing } from '@/theme/spacing';

export function Card({ children, style, testID }: { children: ReactNode; style?: StyleProp<ViewStyle>; testID?: string }) {
  return (
    <View testID={testID} style={[styles.card, style]}>
      {children}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.xl,
    padding: spacing.lg,
    gap: spacing.md,
    shadowColor: '#5A2A0B',
    shadowOpacity: 0.06,
    shadowRadius: 16,
    shadowOffset: { width: 0, height: 6 },
    elevation: 2,
  },
});
