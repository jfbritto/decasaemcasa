import { Ionicons } from '@react-native-vector-icons/ionicons';
import { Linking, Platform, StyleSheet, View } from 'react-native';

import { AppText } from '@/components/ui/app-text';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Notice } from '@/components/ui/notice';
import { formatLongDate } from '@/lib/format';
import { brand, colors } from '@/theme/colors';
import { spacing } from '@/theme/spacing';
import type { Inscription } from '@/types/api';

/** O endereço do mapa do celular: Apple Maps no iPhone, Google Maps no resto. */
export function mapsUrl(address: string, os: string = Platform.OS): string {
  const query = encodeURIComponent(address);
  return os === 'ios' ? `https://maps.apple.com/?q=${query}` : `https://www.google.com/maps/search/?api=1&query=${query}`;
}

/** "Informações do Encontro", só para confirmado (AM15). */
export function ConfirmedInfo({ inscription }: { inscription: Inscription }) {
  const event = inscription.event;
  if (inscription.state !== 'confirmed' || !event) return null;

  return (
    <Card testID="confirmed-info">
      <View style={styles.title}>
        <Ionicons name="location" size={22} color={brand[600]} />
        <AppText variant="heading">Informações do encontro</AppText>
      </View>

      {event.address ? (
        <>
          <Notice
            tone="danger"
            icon="warning-outline"
            text="Não divulgue este endereço. Este encontro é secreto. Somente pessoas com nome na lista poderão entrar."
          />
          <Row icon="home-outline" label="Endereço" value={event.address} selectable />
          {event.arrival_time ? <Row icon="time-outline" label="Horário de chegada" value={event.arrival_time} /> : null}
          <Row icon="calendar-outline" label="Data" value={formatLongDate(event.date)} />
          <Button label="Abrir no mapa" icon="map-outline" variant="secondary" onPress={() => Linking.openURL(mapsUrl(event.address!))} />
        </>
      ) : (
        <AppText variant="body" color={colors.textMuted}>
          As informações de endereço serão disponibilizadas em breve.
        </AppText>
      )}
    </Card>
  );
}

function Row({ icon, label, value, selectable }: { icon: 'home-outline' | 'time-outline' | 'calendar-outline'; label: string; value: string; selectable?: boolean }) {
  return (
    <View style={styles.row}>
      <Ionicons name={icon} size={20} color={colors.textMuted} style={styles.rowIcon} />
      <View style={styles.flex}>
        <AppText variant="caption">{label}</AppText>
        <AppText variant="bodyStrong" selectable={selectable}>
          {value}
        </AppText>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  title: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  row: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  rowIcon: {
    marginTop: 2,
  },
  flex: {
    flex: 1,
  },
});
