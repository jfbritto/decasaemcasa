import { router, useLocalSearchParams, type Href } from 'expo-router';
import { useEffect, useRef, useState } from 'react';
import { StyleSheet, View, type TextInput } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Logo } from '@/components/logo';
import { AppText } from '@/components/ui/app-text';
import { Button } from '@/components/ui/button';
import { Notice } from '@/components/ui/notice';
import { Screen } from '@/components/ui/screen';
import { TextField } from '@/components/ui/text-field';
import { errorMessage, fieldErrors } from '@/services/api';
import { requestLoginCode, verifyLoginCode } from '@/services/auth';
import { isSignedIn, useAuthStore } from '@/stores/auth-store';
import { colors } from '@/theme/colors';
import { spacing } from '@/theme/spacing';

const RESEND_AFTER_SECONDS = 60;
/** O código do e-mail tem 6; o da conta de revisão das lojas, 12 ou mais (a API aceita até 32). */
const CODE_MAX_DIGITS = 32;

/** Só caminhos do próprio app, nunca um endereço de fora. */
function safeNext(value: string | undefined): string | null {
  return value && value.startsWith('/') && !value.startsWith('//') ? value : null;
}

/**
 * "Entrar" (spec 2026-11-08-app-mobile-design, AM3 e AM4): e-mail, depois o
 * código de 6 números que chega nele. Sem senha. A conta nasce no primeiro
 * código certo.
 */
export default function LoginScreen() {
  const { next } = useLocalSearchParams<{ next?: string }>();
  const target = safeNext(next);
  const insets = useSafeAreaInsets();
  const signIn = useAuthStore((s) => s.signIn);
  const signedIn = useAuthStore(isSignedIn);

  const [step, setStep] = useState<'email' | 'code'>('email');
  const [email, setEmail] = useState('');
  const [code, setCode] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [done, setDone] = useState(false);
  // O reenvio libera num horário (não numa contagem de passos): certo
  // mesmo com o app em segundo plano enquanto a pessoa abre o e-mail.
  const [resendAt, setResendAt] = useState(0);
  const [now, setNow] = useState(() => Date.now());
  const codeRef = useRef<TextInput>(null);

  useEffect(() => {
    if (resendAt <= Date.now()) return;
    const timer = setInterval(() => {
      setNow(Date.now());
      if (Date.now() >= resendAt) clearInterval(timer);
    }, 1000);
    return () => clearInterval(timer);
  }, [resendAt]);

  const resendIn = Math.max(0, Math.ceil((resendAt - now) / 1000));

  // Navega só depois que a sessão abriu e as telas da conta existem
  // (Stack.Protected do layout raiz).
  useEffect(() => {
    if (!done || !signedIn) return;
    if (target) {
      router.replace(target as Href);
    } else if (router.canGoBack()) {
      router.back();
    } else {
      router.replace('/inscriptions' as Href);
    }
  }, [done, signedIn, target]);

  const sendCode = async () => {
    setError(null);
    if (!/^[\x21-\x7E]+$/.test(email.trim())) {
      // A API recusa acento (Participant::ASCII_EMAIL): avisa antes.
      setError('Digite o e-mail sem acentos nem espaços.');
      return;
    }
    if (!/^\S+@\S+\.\S+$/.test(email.trim())) {
      setError('Informe um e-mail válido.');
      return;
    }
    setBusy(true);
    try {
      await requestLoginCode(email);
      setStep('code');
      setCode('');
      setNow(Date.now());
      setResendAt(Date.now() + RESEND_AFTER_SECONDS * 1000);
      setTimeout(() => codeRef.current?.focus(), 300);
    } catch (e) {
      setError(fieldErrors(e).email ?? errorMessage(e));
    } finally {
      setBusy(false);
    }
  };

  const verify = async (value = code) => {
    setError(null);
    if (value.trim().length < 6) {
      setError('Digite os 6 números do código.');
      return;
    }
    setBusy(true);
    try {
      const session = await verifyLoginCode(email, value);
      await signIn(session.token, session.participant);
      setDone(true);
    } catch (e) {
      setError(fieldErrors(e).code ?? errorMessage(e));
      setBusy(false);
    }
  };

  return (
    <Screen
      footer={
        <Button
          label={step === 'email' ? 'Receber código' : 'Entrar'}
          icon={step === 'email' ? 'mail-outline' : 'log-in-outline'}
          loading={busy}
          onPress={() => void (step === 'email' ? sendCode() : verify())}
          testID="login-submit"
        />
      }
    >
      <View style={[styles.close, { marginTop: insets.top > 0 ? 0 : spacing.sm }]}>
        <Button label="Fechar" variant="ghost" icon="close" onPress={() => router.back()} style={styles.closeButton} />
      </View>
      <View style={styles.header}>
        <Logo size={72} />
        <AppText variant="title" center>
          {step === 'email' ? 'Entrar no De Casa em Casa' : 'Confira seu e-mail'}
        </AppText>
        <AppText variant="body" color={colors.textMuted} center>
          {step === 'email'
            ? 'Use o e-mail das suas inscrições (ou o que você vai usar). Enviamos um código de 6 números, sem senha.'
            : `Enviamos um código de 6 números para ${email.trim().toLowerCase()}. Ele vale por 10 minutos.`}
        </AppText>
      </View>

      {error ? <Notice tone="danger" icon="alert-circle-outline" text={error} testID="login-error" /> : null}

      {step === 'email' ? (
        <TextField
          testID="login-email"
          label="E-mail"
          placeholder="seu@email.com"
          value={email}
          onChangeText={setEmail}
          keyboardType="email-address"
          autoCapitalize="none"
          autoCorrect={false}
          autoComplete="email"
          textContentType="emailAddress"
          returnKeyType="send"
          onSubmitEditing={() => void sendCode()}
        />
      ) : (
        <>
          <TextField
            ref={codeRef}
            testID="login-code"
            label="Código"
            placeholder="000000"
            value={code}
            onChangeText={(value) => {
              const digits = value.replace(/\D/g, '').slice(0, CODE_MAX_DIGITS);
              // Chegou de uma vez (colado, ou o iOS preencheu do e-mail): envia
              // sozinho. Digitando, a pessoa toca em "Entrar" (o código da
              // conta de revisão das lojas tem mais de 6 números).
              const arrivedAtOnce = digits.length - code.length >= 2;
              setCode(digits);
              if (digits.length === 6 && arrivedAtOnce && !busy) void verify(digits);
            }}
            keyboardType="number-pad"
            autoComplete="one-time-code"
            textContentType="oneTimeCode"
            maxLength={CODE_MAX_DIGITS}
            style={styles.codeInput}
          />
          <View style={styles.links}>
            <Button
              label={resendIn > 0 ? `Reenviar código em ${resendIn}s` : 'Reenviar código'}
              variant="ghost"
              disabled={resendIn > 0 || busy}
              onPress={() => void sendCode()}
            />
            <Button
              label="Usar outro e-mail"
              variant="ghost"
              onPress={() => {
                setStep('email');
                setError(null);
              }}
            />
          </View>
          <AppText variant="caption" center>
            Não chegou? Olhe a caixa de spam ou promoções.
          </AppText>
        </>
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  close: {
    alignItems: 'flex-end',
  },
  closeButton: {
    paddingHorizontal: spacing.md,
  },
  header: {
    alignItems: 'center',
    gap: spacing.md,
  },
  codeInput: {
    fontSize: 28,
    letterSpacing: 10,
    textAlign: 'center',
  },
  links: {
    gap: spacing.xs,
  },
});
