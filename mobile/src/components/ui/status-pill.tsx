import { StyleSheet, View } from 'react-native';

import { AppText } from '@/components/ui/app-text';
import { tones, type Tone } from '@/theme/colors';
import { radius, spacing } from '@/theme/spacing';

/** O selo do estado: ponto na cor do estado e o rótulo sobre o tom claro. */
export function StatusPill({ label, tone, testID }: { label: string; tone: Tone; testID?: string }) {
  const palette = tones[tone];

  return (
    <View testID={testID} style={[styles.pill, { backgroundColor: palette.soft, borderColor: palette.border }]}>
      <View style={[styles.dot, { backgroundColor: palette.solid }]} />
      <AppText variant="label" color={palette.text}>
        {label}
      </AppText>
    </View>
  );
}

const styles = StyleSheet.create({
  pill: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    gap: spacing.xs + 2,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
    borderRadius: radius.full,
    borderWidth: 1,
  },
  dot: {
    width: 8,
    height: 8,
    borderRadius: 4,
  },
});
