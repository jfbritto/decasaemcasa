import { useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams, type Href } from 'expo-router';
import { useState } from 'react';
import { Alert } from 'react-native';

import { AppText } from '@/components/ui/app-text';
import { Button } from '@/components/ui/button';
import { Notice } from '@/components/ui/notice';
import { Screen } from '@/components/ui/screen';
import { TextField } from '@/components/ui/text-field';
import { parseMoney } from '@/domain/masks';
import { formatMoney } from '@/lib/format';
import { queryKeys } from '@/lib/query-client';
import { errorMessage, fieldErrors } from '@/services/api';
import { fetchConfig } from '@/services/events';
import { submitSocialRequest } from '@/services/inscriptions';
import { colors } from '@/theme/colors';

const REASON_MIN = 20;
const REASON_MAX = 1000;

/** O pedido de contribuição social, com as regras e as mensagens da web. */
export default function SocialRequestScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const inscriptionId = Number(id);
  const client = useQueryClient();
  const config = useQuery({ queryKey: queryKeys.config, queryFn: fetchConfig, staleTime: Infinity });

  const [reason, setReason] = useState('');
  const [amount, setAmount] = useState('');
  const [errors, setErrors] = useState<{ reason?: string; amount?: string }>({});
  const [sending, setSending] = useState(false);

  const submit = async () => {
    const found: { reason?: string; amount?: string } = {};
    const value = parseMoney(amount);
    if (!reason.trim()) found.reason = 'Conte brevemente sua situação.';
    else if (reason.trim().length < REASON_MIN) found.reason = 'Sua justificativa deve ter pelo menos 20 caracteres.';
    if (!amount.trim()) found.amount = 'Informe o valor que você consegue contribuir.';
    else if (value === null) found.amount = 'Informe um valor numérico.';
    setErrors(found);
    if (Object.keys(found).length > 0 || value === null) return;

    setSending(true);
    try {
      const inscription = await submitSocialRequest(inscriptionId, reason, value);
      client.setQueryData(queryKeys.inscription(inscriptionId), inscription);
      void client.invalidateQueries({ queryKey: queryKeys.inscriptions, exact: true });
      Alert.alert('Solicitação enviada!', 'A equipe vai analisar e logo retornaremos. Enquanto isso, o prazo do comprovante fica pausado.');
      // Aberta direto (por um link), não há para onde voltar: vai para a inscrição.
      if (router.canGoBack()) router.back();
      else router.replace(`/inscriptions/${inscriptionId}` as Href);
    } catch (error) {
      setSending(false);
      const byField = fieldErrors(error);
      if (byField.reason || byField.amount) {
        setErrors({ reason: byField.reason, amount: byField.amount });
        return;
      }
      Alert.alert('Não foi possível enviar', errorMessage(error));
    }
  };

  return (
    <Screen
      underHeader
      footer={<Button label="Enviar solicitação" icon="send" loading={sending} onPress={() => void submit()} testID="social-submit" />}
    >
      <AppText variant="title">Contribuição social</AppText>
      <AppText variant="body" color={colors.textMuted}>
        {`Não consegue contribuir com o valor de referência de ${formatMoney(config.data?.reference_amount ?? 100)} neste momento? Conte sua situação e o valor que consegue. Sua história será analisada pela equipe.`}
      </AppText>
      <TextField
        testID="social-reason"
        label="Explique brevemente por que você precisa da contribuição social."
        placeholder="Conte sua situação para nossa equipe analisar."
        value={reason}
        onChangeText={(text) => {
          setReason(text);
          if (errors.reason) setErrors((current) => ({ ...current, reason: undefined }));
        }}
        error={errors.reason}
        multiline
        counter
        maxLength={REASON_MAX}
      />
      <TextField
        testID="social-amount"
        label="Qual valor você consegue contribuir? (R$)"
        placeholder="Ex: 50,00"
        value={amount}
        onChangeText={(text) => {
          setAmount(text.replace(/[^\d.,]/g, ''));
          if (errors.amount) setErrors((current) => ({ ...current, amount: undefined }));
        }}
        error={errors.amount}
        keyboardType="decimal-pad"
        maxLength={9}
      />
      <Notice
        tone="curation"
        icon="information-circle-outline"
        text="Esta solicitação será analisada pela equipe. A vaga só fica confirmada após a aprovação e o envio do comprovante. Enquanto ela estiver em análise, o prazo do comprovante fica pausado."
      />
    </Screen>
  );
}
