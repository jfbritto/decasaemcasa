import { Ionicons } from '@react-native-vector-icons/ionicons';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams, type Href } from 'expo-router';
import { useState } from 'react';
import { ActivityIndicator, Alert, StyleSheet, View } from 'react-native';

import { LoadError } from '@/components/load-error';
import { AppText } from '@/components/ui/app-text';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { CheckboxRow } from '@/components/ui/checkbox-row';
import { Notice } from '@/components/ui/notice';
import { Screen } from '@/components/ui/screen';
import { TextField } from '@/components/ui/text-field';
import { maskCpf, maskDate, maskPhone } from '@/domain/masks';
import {
  EMPTY_FORM,
  toPayload,
  validateRegistration,
  type RegistrationErrors,
  type RegistrationForm,
} from '@/domain/registration-form';
import { formatLongDate } from '@/lib/format';
import { queryKeys } from '@/lib/query-client';
import { ApiError, errorMessage, fieldErrors } from '@/services/api';
import { fetchConfig, fetchEvent } from '@/services/events';
import { createInscription } from '@/services/inscriptions';
import { useAuthStore } from '@/stores/auth-store';
import { brand, colors } from '@/theme/colors';
import { radius, spacing } from '@/theme/spacing';

type Step = 1 | 2 | 3;
const STEP_NAMES = ['Manifesto', 'Seus dados', 'Termos'];

/**
 * A inscrição pelo app (spec 2026-11-08-app-mobile-design, AM16), nas etapas
 * da web depois da escolha do encontro: manifesto, dados e termos. O e-mail
 * é o da conta.
 */
export default function RegisterScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const eventId = Number(id);
  const participant = useAuthStore((s) => s.participant);
  const client = useQueryClient();

  const event = useQuery({ queryKey: [...queryKeys.event(eventId), true], queryFn: () => fetchEvent(eventId) });
  const config = useQuery({ queryKey: queryKeys.config, queryFn: fetchConfig, staleTime: Infinity });

  const [step, setStep] = useState<Step>(1);
  const [form, setForm] = useState<RegistrationForm>({ ...EMPTY_FORM, full_name: participant?.name ?? '' });
  const [errors, setErrors] = useState<RegistrationErrors>({});
  const [accepted, setAccepted] = useState(false);
  const [sending, setSending] = useState(false);

  const set = (field: keyof RegistrationForm) => (value: string) => {
    setForm((current) => ({ ...current, [field]: value }));
    if (errors[field]) setErrors((current) => ({ ...current, [field]: undefined }));
  };

  if (event.isPending || config.isPending) {
    return (
      <Screen underHeader>
        <ActivityIndicator color={colors.primary} style={styles.loading} />
      </Screen>
    );
  }
  if (event.isError || config.isError) {
    const retry = () => {
      void event.refetch();
      void config.refetch();
    };
    return (
      <Screen underHeader>
        <LoadError error={event.error ?? config.error} onRetry={retry} />
      </Screen>
    );
  }

  const goToData = () => setStep(2);

  const goToTerms = () => {
    const found = validateRegistration(form);
    setErrors(found);
    if (Object.keys(found).length === 0) setStep(3);
  };

  const submit = async () => {
    if (!accepted) {
      setErrors({ terms_accepted: 'Você deve aceitar os termos.' });
      return;
    }
    setSending(true);
    try {
      const inscription = await createInscription(toPayload(eventId, form));
      await client.invalidateQueries({ queryKey: queryKeys.inscriptions });
      await client.invalidateQueries({ queryKey: queryKeys.events });
      router.replace(`/inscriptions/${inscription.id}?nova=1` as Href);
    } catch (error) {
      setSending(false);
      if (error instanceof ApiError && error.code === 'already_registered' && typeof error.body?.inscription_id === 'number') {
        Alert.alert('Você já está inscrito(a)', 'Você já tem uma inscrição para este encontro. Vamos abrir ela para você.');
        router.replace(`/inscriptions/${error.body.inscription_id}` as Href);
        return;
      }
      const byField = fieldErrors(error);
      if (Object.keys(byField).length > 0) {
        setErrors(byField as RegistrationErrors);
        setStep(byField.terms_accepted && Object.keys(byField).length === 1 ? 3 : 2);
        return;
      }
      Alert.alert('Não foi possível enviar', errorMessage(error));
    }
  };

  const footer =
    step === 1 ? (
      <Button label="Continuar" icon="arrow-forward" onPress={goToData} testID="register-next" />
    ) : step === 2 ? (
      <View style={styles.footerRow}>
        <Button label="Voltar" variant="secondary" onPress={() => setStep(1)} style={styles.flex} />
        <Button label="Continuar" onPress={goToTerms} style={styles.flex} testID="register-next" />
      </View>
    ) : (
      <View style={styles.footerRow}>
        <Button label="Voltar" variant="secondary" onPress={() => setStep(2)} style={styles.flex} />
        <Button label="Enviar" icon="send" loading={sending} disabled={!accepted} onPress={() => void submit()} style={styles.flex} testID="register-submit" />
      </View>
    );

  return (
    <Screen underHeader footer={footer}>
      <View style={styles.progress} accessibilityLabel={`Etapa ${step} de 3: ${STEP_NAMES[step - 1]}`}>
        {STEP_NAMES.map((name, index) => (
          <View key={name} style={styles.progressItem}>
            <View style={[styles.progressBar, { backgroundColor: index < step ? brand[600] : colors.border }]} />
            <AppText variant="caption" color={index < step ? brand[700] : colors.textFaint}>
              {name}
            </AppText>
          </View>
        ))}
      </View>

      <View style={styles.eventLine}>
        <Ionicons name="location-outline" size={18} color={brand[700]} />
        <AppText variant="label">{`${event.data.city || event.data.title} · ${formatLongDate(event.data.date)}`}</AppText>
      </View>

      {step === 1 ? (
        <Card style={styles.manifesto}>
          <View style={styles.quoteMark}>
            <Ionicons name="home" size={28} color={brand[600]} />
          </View>
          <AppText variant="title" center>
            De Casa em Casa
          </AppText>
          <View style={styles.quote}>
            <AppText variant="quote">{`"${config.data.manifesto}"`}</AppText>
          </View>
        </Card>
      ) : null}

      {step === 2 ? (
        <View style={styles.fields}>
          <AppText variant="title">Seus dados</AppText>
          <AppText variant="caption">{`A inscrição fica no e-mail da sua conta: ${participant?.email ?? ''}`}</AppText>
          <TextField testID="field-full_name" label="Nome completo" placeholder="Seu nome completo" value={form.full_name} onChangeText={set('full_name')} error={errors.full_name} autoComplete="name" textContentType="name" />
          <TextField testID="field-cpf" label="CPF" placeholder="000.000.000-00" value={form.cpf} onChangeText={(v) => set('cpf')(maskCpf(v))} error={errors.cpf} keyboardType="number-pad" maxLength={14} />
          <TextField testID="field-birth_date" label="Data de nascimento" placeholder="DD/MM/AAAA" value={form.birth_date} onChangeText={(v) => set('birth_date')(maskDate(v))} error={errors.birth_date} keyboardType="number-pad" maxLength={10} />
          <TextField testID="field-city_neighborhood" label="Bairro e cidade" placeholder="Ex: Savassi, Belo Horizonte" value={form.city_neighborhood} onChangeText={set('city_neighborhood')} error={errors.city_neighborhood} maxLength={255} />
          <TextField testID="field-whatsapp" label="WhatsApp" placeholder="(00) 00000-0000" value={form.whatsapp} onChangeText={(v) => set('whatsapp')(maskPhone(v))} error={errors.whatsapp} keyboardType="phone-pad" maxLength={15} autoComplete="tel" textContentType="telephoneNumber" />
          <TextField testID="field-instagram" label="Instagram (opcional)" placeholder="@seuusuario" value={form.instagram} onChangeText={set('instagram')} error={errors.instagram} autoCapitalize="none" autoCorrect={false} maxLength={255} />
          <TextField
            testID="field-motivation"
            label="Sua história: por que você quer participar?"
            placeholder="Conte um pouco sobre você e por que deseja fazer parte desse encontro..."
            value={form.motivation}
            onChangeText={set('motivation')}
            error={errors.motivation}
            multiline
            counter
            maxLength={5000}
          />
        </View>
      ) : null}

      {step === 3 ? (
        <View style={styles.fields}>
          <AppText variant="title">Termo de responsabilidade</AppText>
          <AppText variant="body" color={colors.textMuted}>
            Leia com atenção antes de confirmar sua inscrição.
          </AppText>
          <Card>
            {config.data.terms.map((term) => (
              <View key={term.title} style={styles.term}>
                <View style={styles.termDot} />
                <AppText variant="body" style={styles.flex}>
                  <AppText variant="bodyStrong">{`${term.title}: `}</AppText>
                  {term.text}
                </AppText>
              </View>
            ))}
          </Card>
          <CheckboxRow
            testID="terms-checkbox"
            checked={accepted}
            onToggle={() => {
              setAccepted((value) => !value);
              setErrors((current) => ({ ...current, terms_accepted: undefined }));
            }}
            label={config.data.terms_acceptance}
          />
          {errors.terms_accepted ? <Notice tone="danger" text={errors.terms_accepted} /> : null}
        </View>
      ) : null}
    </Screen>
  );
}

const styles = StyleSheet.create({
  loading: {
    marginTop: spacing.xxl,
  },
  progress: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  progressItem: {
    flex: 1,
    gap: spacing.xs,
  },
  progressBar: {
    height: 4,
    borderRadius: 2,
  },
  eventLine: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
  },
  manifesto: {
    alignItems: 'center',
    paddingVertical: spacing.xl,
  },
  quoteMark: {
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: brand[100],
    alignItems: 'center',
    justifyContent: 'center',
  },
  quote: {
    borderLeftWidth: 4,
    borderLeftColor: brand[400],
    backgroundColor: brand[50],
    borderTopRightRadius: radius.lg,
    borderBottomRightRadius: radius.lg,
    paddingVertical: spacing.lg,
    paddingHorizontal: spacing.lg,
    alignSelf: 'stretch',
  },
  fields: {
    gap: spacing.lg,
  },
  term: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  termDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: brand[400],
    marginTop: 8,
  },
  footerRow: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  flex: {
    flex: 1,
  },
});
