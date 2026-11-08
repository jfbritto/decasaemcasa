import { useQuery } from '@tanstack/react-query';
import { router, type Href } from 'expo-router';
import { ActivityIndicator, StyleSheet } from 'react-native';

import { InscriptionCard } from '@/components/inscription-card';
import { LoadError } from '@/components/load-error';
import { PushCard } from '@/components/push-card';
import { SignInPrompt } from '@/components/sign-in-prompt';
import { AppText } from '@/components/ui/app-text';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/ui/empty-state';
import { Screen } from '@/components/ui/screen';
import { queryKeys } from '@/lib/query-client';
import { fetchInscriptions } from '@/services/inscriptions';
import { isSignedIn, useAuthStore } from '@/stores/auth-store';
import { colors } from '@/theme/colors';
import { spacing } from '@/theme/spacing';

/** "Minhas inscrições": todas as inscrições do e-mail da conta (AM2). */
export default function InscriptionsScreen() {
  const signedIn = useAuthStore(isSignedIn);

  if (!signedIn) {
    return (
      <Screen>
        <AppText variant="title" accessibilityRole="header">
          Minhas inscrições
        </AppText>
        <SignInPrompt
          title="Acompanhe tudo por aqui"
          text="Entre com o e-mail das suas inscrições para ver a situação de cada uma, enviar o comprovante e receber o endereço quando for confirmado(a)."
          next="/inscriptions"
        />
      </Screen>
    );
  }

  return <SignedInList />;
}

function SignedInList() {
  const query = useQuery({ queryKey: queryKeys.inscriptions, queryFn: fetchInscriptions });
  const upcoming = query.data?.filter((i) => i.upcoming) ?? [];
  const past = query.data?.filter((i) => !i.upcoming) ?? [];

  return (
    <Screen onRefresh={() => void query.refetch()} refreshing={query.isRefetching} testID="inscriptions-screen">
      <AppText variant="title" accessibilityRole="header">
        Minhas inscrições
      </AppText>

      {query.isPending ? (
        <ActivityIndicator color={colors.primary} style={styles.loading} />
      ) : query.isError ? (
        <LoadError error={query.error} onRetry={() => void query.refetch()} />
      ) : query.data.length === 0 ? (
        <EmptyState
          icon="ticket-outline"
          title="Você ainda não tem inscrições"
          text="Escolha um encontro e conte sua história. Inscrições feitas pelo site com este e-mail também aparecem aqui."
        >
          <Button label="Ver encontros" icon="home-outline" onPress={() => router.navigate('/' as Href)} />
        </EmptyState>
      ) : (
        <>
          <PushCard />
          {upcoming.length > 0 ? (
            <AppText variant="heading" accessibilityRole="header">
              Próximos encontros
            </AppText>
          ) : null}
          {upcoming.map((inscription) => (
            <InscriptionCard key={inscription.id} inscription={inscription} onPress={() => router.push(`/inscriptions/${inscription.id}` as Href)} />
          ))}
          {past.length > 0 ? (
            <AppText variant="heading" accessibilityRole="header" style={styles.pastTitle}>
              Encontros anteriores
            </AppText>
          ) : null}
          {past.map((inscription) => (
            <InscriptionCard key={inscription.id} inscription={inscription} onPress={() => router.push(`/inscriptions/${inscription.id}` as Href)} />
          ))}
        </>
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  loading: {
    marginTop: spacing.xxl,
  },
  pastTitle: {
    marginTop: spacing.md,
  },
});
