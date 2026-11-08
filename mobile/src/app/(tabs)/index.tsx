import { useQuery } from '@tanstack/react-query';
import { router, type Href } from 'expo-router';
import { ActivityIndicator, StyleSheet, View } from 'react-native';

import { EventCard } from '@/components/event-card';
import { LoadError } from '@/components/load-error';
import { Logo } from '@/components/logo';
import { AppText } from '@/components/ui/app-text';
import { EmptyState } from '@/components/ui/empty-state';
import { Screen } from '@/components/ui/screen';
import { queryKeys } from '@/lib/query-client';
import { fetchEvents } from '@/services/events';
import { useAuthStore } from '@/stores/auth-store';
import { brand, colors } from '@/theme/colors';
import { radius, spacing } from '@/theme/spacing';

/** "Encontros": os encontros abertos, sem precisar de conta (AM27). */
export default function EventsScreen() {
  const query = useQuery({ queryKey: queryKeys.events, queryFn: fetchEvents });
  const name = useAuthStore((s) => s.participant?.name);
  const firstName = name?.trim().split(/\s+/)[0];

  return (
    <Screen onRefresh={() => void query.refetch()} refreshing={query.isRefetching} testID="events-screen">
      <View style={styles.hero}>
        <Logo size={64} tone="light" />
        <View style={styles.heroText}>
          <AppText variant="caption" color={brand[100]}>
            {firstName ? `Olá, ${firstName}!` : 'Turnê'}
          </AppText>
          <AppText variant="display" color={colors.onPrimary}>
            De Casa em Casa
          </AppText>
          <AppText variant="body" color={brand[50]}>
            Uma turnê que acontece onde a vida acontece. Dentro de casas reais, com pessoas reais.
          </AppText>
        </View>
      </View>

      <AppText variant="heading" accessibilityRole="header">
        Próximos encontros
      </AppText>

      {query.isPending ? (
        <ActivityIndicator color={colors.primary} style={styles.loading} />
      ) : query.isError ? (
        <LoadError error={query.error} onRetry={() => void query.refetch()} />
      ) : query.data.length === 0 ? (
        <EmptyState
          icon="musical-notes-outline"
          title="Nenhum encontro aberto agora"
          text="Fique de olho: as próximas cidades da turnê aparecem aqui assim que as inscrições abrirem."
        />
      ) : (
        query.data.map((event) => (
          <EventCard key={event.id} event={event} onPress={() => router.push(`/events/${event.id}` as Href)} />
        ))
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  hero: {
    backgroundColor: brand[600],
    borderRadius: radius.xl,
    padding: spacing.xl,
    gap: spacing.lg,
  },
  heroText: {
    gap: spacing.xs,
  },
  loading: {
    marginTop: spacing.xxl,
  },
});
