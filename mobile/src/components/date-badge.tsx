import { StyleSheet, View } from 'react-native';

import { AppText } from '@/components/ui/app-text';
import { dateBadge } from '@/lib/format';
import { brand } from '@/theme/colors';
import { radius, spacing } from '@/theme/spacing';
import { fonts } from '@/theme/typography';

/** O selo de data dos cartões: dia grande, mês e dia da semana. */
export function DateBadge({ iso, inverted }: { iso: string | null; inverted?: boolean }) {
  const badge = dateBadge(iso);
  if (!badge) return null;
  const fg = inverted ? brand[50] : brand[700];

  return (
    <View style={[styles.badge, { backgroundColor: inverted ? 'rgba(0,0,0,0.28)' : brand[100] }]}>
      <AppText variant="caption" color={fg} style={styles.month}>
        {badge.month.toUpperCase()}
      </AppText>
      <AppText color={fg} style={styles.day}>
        {badge.day}
      </AppText>
      <AppText variant="caption" color={fg}>
        {badge.weekday}
      </AppText>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    width: 60,
    paddingVertical: spacing.sm,
    borderRadius: radius.md,
    alignItems: 'center',
  },
  month: {
    fontFamily: fonts.bold,
    letterSpacing: 1,
  },
  day: {
    fontFamily: fonts.display,
    fontSize: 26,
    lineHeight: 30,
  },
});
