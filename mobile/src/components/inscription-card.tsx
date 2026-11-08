import { Ionicons } from '@react-native-vector-icons/ionicons';
import { Pressable, StyleSheet, View } from 'react-native';

import { DateBadge } from '@/components/date-badge';
import { AppText } from '@/components/ui/app-text';
import { StatusPill } from '@/components/ui/status-pill';
import { STATE_COPY, stateHint } from '@/domain/inscription-states';
import { colors } from '@/theme/colors';
import { radius, spacing } from '@/theme/spacing';
import type { Inscription } from '@/types/api';

/** Uma inscrição na lista: data, cidade, estado e o que importa agora. */
export function InscriptionCard({ inscription, onPress }: { inscription: Inscription; onPress: () => void }) {
  const copy = STATE_COPY[inscription.state];
  const city = inscription.event?.city || inscription.event?.title || 'Encontro';
  const hint = stateHint(inscription);

  return (
    <Pressable
      testID={`inscription-${inscription.id}`}
      accessibilityRole="button"
      accessibilityLabel={`${city}, ${copy.label}`}
      onPress={onPress}
      style={({ pressed }) => [styles.card, pressed ? styles.pressed : null, inscription.upcoming ? null : styles.past]}
    >
      <DateBadge iso={inscription.event?.date ?? null} />
      <View style={styles.body}>
        <AppText variant="heading" numberOfLines={1}>
          {city}
        </AppText>
        <StatusPill label={copy.label} tone={copy.tone} />
        {hint ? <AppText variant="caption">{hint}</AppText> : null}
      </View>
      <Ionicons name="chevron-forward" size={20} color={colors.textFaint} />
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    backgroundColor: colors.surface,
    borderRadius: radius.xl,
    padding: spacing.lg,
    shadowColor: '#5A2A0B',
    shadowOpacity: 0.06,
    shadowRadius: 14,
    shadowOffset: { width: 0, height: 6 },
    elevation: 2,
  },
  pressed: {
    opacity: 0.92,
  },
  past: {
    opacity: 0.75,
  },
  body: {
    flex: 1,
    gap: spacing.xs + 2,
  },
});
