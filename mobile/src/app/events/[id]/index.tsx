import { Ionicons } from '@react-native-vector-icons/ionicons';
import { useQuery } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { router, useLocalSearchParams, type Href } from 'expo-router';
import { ActivityIndicator, StyleSheet, View } from 'react-native';

import { LoadError } from '@/components/load-error';
import { Logo } from '@/components/logo';
import { AppText } from '@/components/ui/app-text';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Screen } from '@/components/ui/screen';
import { StatusPill } from '@/components/ui/status-pill';
import { STATE_COPY } from '@/domain/inscription-states';
import { formatLongDate, spotsLabel } from '@/lib/format';
import { queryKeys } from '@/lib/query-client';
import { fetchEvent } from '@/services/events';
import { isSignedIn, useAuthStore } from '@/stores/auth-store';
import { brand, colors, tones } from '@/theme/colors';
import { radius, spacing } from '@/theme/spacing';

const STEPS = [
  { icon: 'create-outline', title: 'Conte sua história', text: 'A inscrição é individual: cada pessoa faz a sua, inclusive crianças e acompanhantes.' },
  { icon: 'people-outline', title: 'Curadoria', text: 'Pessoas reais da equipe leem cada inscrição. O processo é manual.' },
  { icon: 'heart-outline', title: 'Contribuição', text: 'Aprovado(a), você envia o comprovante do Pix dentro do prazo para garantir seu lugar na sala.' },
  { icon: 'home-outline', title: 'Confirmação', text: 'Só os confirmados recebem o endereço e o horário. O encontro é secreto.' },
] as const;

/** O encontro, sem conta; "Quero participar" leva ao login quando preciso. */
export default function EventScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const eventId = Number(id);
  const signedIn = useAuthStore(isSignedIn);
  const query = useQuery({
    queryKey: [...queryKeys.event(eventId), signedIn],
    queryFn: () => fetchEvent(eventId),
  });

  if (query.isPending) {
    return (
      <Screen underHeader>
        <ActivityIndicator color={colors.primary} style={styles.loading} />
      </Screen>
    );
  }
  if (query.isError) {
    return (
      <Screen underHeader>
        <LoadError error={query.error} onRetry={() => void query.refetch()} />
      </Screen>
    );
  }

  const event = query.data;
  const city = event.city || event.title;
  const mine = event.my_inscription;
  const registerHref = `/events/${event.id}/register`;

  const footer = mine ? (
    <Button label="Ver minha inscrição" icon="ticket-outline" onPress={() => router.push(`/inscriptions/${mine.id}` as Href)} />
  ) : event.is_full ? (
    <Button label="Vagas esgotadas" disabled />
  ) : (
    <Button
      label="Quero participar"
      icon="heart"
      testID="participate-button"
      onPress={() => router.push((signedIn ? registerHref : `/login?next=${encodeURIComponent(registerHref)}`) as Href)}
    />
  );

  return (
    <Screen underHeader onRefresh={() => void query.refetch()} refreshing={query.isRefetching} footer={footer}>
      <View style={styles.cover}>
        {event.image_url ? (
          <Image source={{ uri: event.image_url }} style={StyleSheet.absoluteFill} contentFit="cover" />
        ) : (
          <Logo size={96} tone="light" />
        )}
      </View>

      <View style={styles.heading}>
        <AppText variant="display">{city}</AppText>
        {event.title && event.city && event.title !== event.city ? <AppText variant="bodyStrong">{event.title}</AppText> : null}
        <View style={styles.row}>
          <Ionicons name="calendar-outline" size={18} color={colors.textMuted} />
          <AppText variant="body" color={colors.textMuted}>
            {formatLongDate(event.date)}
          </AppText>
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
        {mine ? (
          <View style={styles.mine}>
            <AppText variant="caption">Sua inscrição neste encontro:</AppText>
            <StatusPill label={STATE_COPY[mine.state].label} tone={STATE_COPY[mine.state].tone} />
          </View>
        ) : null}
      </View>

      {event.description ? (
        <Card>
          <AppText variant="body">{event.description}</AppText>
        </Card>
      ) : null}

      <Card>
        <AppText variant="heading">Como funciona</AppText>
        {STEPS.map((step, index) => (
          <View key={step.title} style={styles.step}>
            <View style={styles.stepIcon}>
              <Ionicons name={step.icon} size={20} color={brand[700]} />
            </View>
            <View style={styles.stepText}>
              <AppText variant="bodyStrong">{`${index + 1}. ${step.title}`}</AppText>
              <AppText variant="caption">{step.text}</AppText>
            </View>
          </View>
        ))}
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  loading: {
    marginTop: spacing.xxl,
  },
  cover: {
    height: 200,
    borderRadius: radius.xl,
    overflow: 'hidden',
    backgroundColor: brand[600],
    alignItems: 'center',
    justifyContent: 'center',
  },
  heading: {
    gap: spacing.xs,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
  },
  mine: {
    marginTop: spacing.sm,
    gap: spacing.xs,
  },
  step: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  stepIcon: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: brand[100],
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepText: {
    flex: 1,
    gap: spacing.xxs,
  },
});
