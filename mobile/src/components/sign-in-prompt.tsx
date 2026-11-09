import { router, type Href } from 'expo-router';
import { StyleSheet, View } from 'react-native';

import { Logo } from '@/components/logo';
import { AppText } from '@/components/ui/app-text';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { colors } from '@/theme/colors';
import { spacing } from '@/theme/spacing';

/** O convite para entrar, nas abas que precisam da conta (AM27). */
export function SignInPrompt({ title, text, next }: { title: string; text: string; next?: string }) {
  return (
    <Card style={styles.card}>
      <View style={styles.logo}>
        <Logo size={56} />
      </View>
      <AppText variant="title" center>
        {title}
      </AppText>
      <AppText variant="body" color={colors.textMuted} center>
        {text}
      </AppText>
      <Button
        label="Entrar com meu e-mail"
        icon="mail-outline"
        onPress={() => router.push((next ? `/login?next=${encodeURIComponent(next)}` : '/login') as Href)}
        testID="sign-in-button"
      />
      <AppText variant="caption" center>
        Sem senha: enviamos um código para o seu e-mail.
      </AppText>
    </Card>
  );
}

const styles = StyleSheet.create({
  card: {
    alignItems: 'stretch',
    paddingVertical: spacing.xl,
  },
  logo: {
    alignItems: 'center',
  },
});
