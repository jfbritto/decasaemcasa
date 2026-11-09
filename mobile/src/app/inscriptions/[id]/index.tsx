import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useLocalSearchParams } from 'expo-router';
import * as WebBrowser from 'expo-web-browser';
import { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, StyleSheet, View } from 'react-native';

import { ConfirmedInfo } from '@/components/confirmed-info';
import { DateBadge } from '@/components/date-badge';
import { LoadError } from '@/components/load-error';
import { PaymentSection } from '@/components/payment-section';
import { SocialRequestSection } from '@/components/social-request-section';
import { SupportCard } from '@/components/support-card';
import { AppText } from '@/components/ui/app-text';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Notice } from '@/components/ui/notice';
import { Screen } from '@/components/ui/screen';
import { StatusPill } from '@/components/ui/status-pill';
import { STATE_COPY, stateMessage } from '@/domain/inscription-states';
import { formatLongDate } from '@/lib/format';
import { queryKeys } from '@/lib/query-client';
import { ApiError, errorMessage } from '@/services/api';
import { fetchConfig } from '@/services/events';
import { cancelInscription, fetchInscription, uploadPaymentProof, type ProofFile } from '@/services/inscriptions';
import { colors, tones } from '@/theme/colors';
import { spacing } from '@/theme/spacing';
import type { Inscription } from '@/types/api';

/** Depois do prazo, o cron do servidor leva até um minuto: busca de novo depois disso (a web espera 75 s). */
const AFTER_DEADLINE_REFETCH_MS = 75_000;

/** A inscrição: a página de status da web, no app (AM13 a AM15 e AM28). */
export default function InscriptionScreen() {
  const { id, nova } = useLocalSearchParams<{ id: string; nova?: string }>();
  const inscriptionId = Number(id);
  const client = useQueryClient();
  const [justUploaded, setJustUploaded] = useState(false);
  const refetchTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const query = useQuery({ queryKey: queryKeys.inscription(inscriptionId), queryFn: () => fetchInscription(inscriptionId) });
  const config = useQuery({ queryKey: queryKeys.config, queryFn: fetchConfig, staleTime: Infinity });

  useEffect(
    () => () => {
      if (refetchTimer.current) clearTimeout(refetchTimer.current);
    },
    [],
  );

  const store = useCallback(
    (inscription: Inscription) => {
      client.setQueryData(queryKeys.inscription(inscription.id), inscription);
      void client.invalidateQueries({ queryKey: queryKeys.inscriptions, exact: true });
    },
    [client],
  );

  const upload = useMutation({
    mutationFn: (file: ProofFile) => uploadPaymentProof(inscriptionId, file),
    onSuccess: (inscription) => {
      store(inscription);
      setJustUploaded(true);
    },
    onError: (error) => {
      Alert.alert('Comprovante não enviado', errorMessage(error));
      if (error instanceof ApiError && error.status === 422) void query.refetch();
    },
  });

  const cancel = useMutation({
    mutationFn: () => cancelInscription(inscriptionId),
    onSuccess: store,
    onError: (error) => Alert.alert('Não foi possível cancelar', errorMessage(error)),
  });

  const onDeadline = useCallback(() => {
    if (refetchTimer.current) clearTimeout(refetchTimer.current);
    refetchTimer.current = setTimeout(() => void query.refetch(), AFTER_DEADLINE_REFETCH_MS);
  }, [query]);

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

  const inscription = query.data;
  const copy = STATE_COPY[inscription.state];
  const event = inscription.event;
  const tone = tones[copy.tone];

  const confirmCancel = () =>
    Alert.alert('Cancelar inscrição', 'Tem certeza que deseja cancelar sua inscrição? Esta ação não pode ser desfeita.', [
      { text: 'Não, manter', style: 'cancel' },
      { text: 'Sim, cancelar', style: 'destructive', onPress: () => cancel.mutate() },
    ]);

  return (
    <Screen underHeader onRefresh={() => void query.refetch()} refreshing={query.isRefetching} testID="inscription-screen">
      {nova === '1' ? <Notice tone="success" icon="checkmark-circle" title="Inscrição realizada com sucesso!" text="Acompanhe tudo por aqui. Avisamos por e-mail a cada novidade." /> : null}
      {justUploaded ? (
        <Notice
          tone="success"
          icon="checkmark-circle"
          title="Deu tudo certo!"
          text="Recebemos seu comprovante. Assim que a equipe conferir, sua participação será confirmada e o endereço aparece aqui."
          testID="upload-success"
        />
      ) : null}

      <Card>
        <View style={styles.header}>
          <DateBadge iso={event?.date ?? null} />
          <View style={styles.headerText}>
            <AppText variant="title">{event?.city || event?.title || 'Encontro'}</AppText>
            {event?.date ? <AppText variant="caption">{formatLongDate(event.date)}</AppText> : null}
            {event?.title && event.city && event.title !== event.city ? <AppText variant="caption">{event.title}</AppText> : null}
          </View>
        </View>
        <StatusPill label={copy.label} tone={copy.tone} testID="state-pill" />
        <View style={[styles.message, { backgroundColor: tone.soft, borderColor: tone.border }]} testID="state-message">
          {stateMessage(inscription).map((paragraph) => (
            <AppText key={paragraph} variant="body">
              {paragraph}
            </AppText>
          ))}
        </View>
      </Card>

      {inscription.can.upload_proof ? (
        <PaymentSection
          inscription={inscription}
          contributionNote={config.data?.contribution_note ?? null}
          onUpload={async (file) => {
            await upload.mutateAsync(file).catch(() => undefined);
          }}
          uploading={upload.isPending}
          onDeadline={onDeadline}
        />
      ) : null}

      {inscription.can.upload_proof ? <SocialRequestSection inscription={inscription} referenceAmount={config.data?.reference_amount ?? 100} /> : null}

      <ConfirmedInfo inscription={inscription} />

      {inscription.can.cancel ? (
        <Button label="Cancelar minha inscrição" variant="danger" loading={cancel.isPending} onPress={confirmCancel} testID="cancel-button" />
      ) : null}

      <SupportCard />

      <Button label="Ver no navegador" variant="ghost" icon="open-outline" onPress={() => void WebBrowser.openBrowserAsync(inscription.status_url)} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  loading: {
    marginTop: spacing.xxl,
  },
  header: {
    flexDirection: 'row',
    gap: spacing.md,
    alignItems: 'center',
  },
  headerText: {
    flex: 1,
    gap: spacing.xxs,
  },
  message: {
    borderWidth: 1,
    borderRadius: 16,
    padding: spacing.lg,
    gap: spacing.md,
  },
});
