import { Ionicons } from '@react-native-vector-icons/ionicons';
import { Image } from 'expo-image';
import { Pressable, StyleSheet, View } from 'react-native';

import { DateBadge } from '@/components/date-badge';
import { Logo } from '@/components/logo';
import { AppText } from '@/components/ui/app-text';
import { formatLongDate, spotsLabel } from '@/lib/format';
import { brand, colors, tones } from '@/theme/colors';
import { radius, spacing } from '@/theme/spacing';
import type { EventSummary } from '@/types/api';

/** Um encontro na lista: capa (ou o violão), cidade, data e vagas. */
export function EventCard({ event, onPress }: { event: EventSummary; onPress: () => void }) {
  const city = event.city || event.title;
  const subtitle = event.title && event.city && event.title !== event.city ? event.title : null;

  return (
    <Pressable
      testID={`event-${event.id}`}
      accessibilityRole="button"
      accessibilityLabel={`${city}, ${formatLongDate(event.date)}${event.is_full ? ', vagas esgotadas' : ''}`}
      onPress={onPress}
      style={({ pressed }) => [styles.card, pressed ? styles.pressed : null]}
    >
      <View style={styles.cover}>
        {event.image_url ? (
          <Image source={{ uri: event.image_url }} style={StyleSheet.absoluteFill} contentFit="cover" transition={200} />
        ) : (
          <View style={styles.placeholder}>
            <Logo size={72} tone="light" />
          </View>
        )}
        <View style={styles.badgeSpot}>
          <DateBadge iso={event.date} inverted />
        </View>
      </View>
      <View style={styles.body}>
        <AppText variant="title" numberOfLines={1}>
          {city}
        </AppText>
        {subtitle ? (
          <AppText variant="caption" numberOfLines={1}>
            {subtitle}
          </AppText>
        ) : null}
        <View style={styles.row}>
          <Ionicons name="calendar-outline" size={16} color={colors.textMuted} />
          <AppText variant="caption">{formatLongDate(event.date)}</AppText>
        </View>
        {event.is_full ? (
          <AppText variant="label" color={tones.danger.text}>
            Vagas esgotadas
          </AppText>
        ) : event.available_spots !== null ? (
          <AppText variant="label" color={brand[700]}>
            {spotsLabel(event.available_spots)}
          </AppText>
        ) : null}
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.xl,
    overflow: 'hidden',
    shadowColor: '#5A2A0B',
    shadowOpacity: 0.08,
    shadowRadius: 18,
    shadowOffset: { width: 0, height: 8 },
    elevation: 3,
  },
  pressed: {
    opacity: 0.92,
    transform: [{ scale: 0.99 }],
  },
  cover: {
    height: 168,
    backgroundColor: brand[600],
  },
  placeholder: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: brand[600],
  },
  badgeSpot: {
    position: 'absolute',
    left: spacing.lg,
    bottom: spacing.lg,
  },
  body: {
    padding: spacing.lg,
    gap: spacing.xs,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
  },
});
