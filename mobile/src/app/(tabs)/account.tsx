import { Ionicons } from '@react-native-vector-icons/ionicons';
import { useQuery } from '@tanstack/react-query';
import Constants from 'expo-constants';
import * as WebBrowser from 'expo-web-browser';
import { useState, type ComponentProps } from 'react';
import { Alert, Linking, Pressable, StyleSheet, View } from 'react-native';

import { PushCard } from '@/components/push-card';
import { SignInPrompt } from '@/components/sign-in-prompt';
import { AppText } from '@/components/ui/app-text';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Screen } from '@/components/ui/screen';
import { queryKeys } from '@/lib/query-client';
import { errorMessage } from '@/services/api';
import { fetchConfig } from '@/services/events';
import { isSignedIn, useAuthStore } from '@/stores/auth-store';
import { brand, colors } from '@/theme/colors';
import { radius, spacing } from '@/theme/spacing';

/** "Perfil": a conta, os avisos, a ajuda e a exclusão da conta (AM8 e AM28). */
export default function AccountScreen() {
  const signedIn = useAuthStore(isSignedIn);
  const participant = useAuthStore((s) => s.participant);
  const signOut = useAuthStore((s) => s.signOut);
  const deleteAccount = useAuthStore((s) => s.deleteAccount);
  const config = useQuery({ queryKey: queryKeys.config, queryFn: fetchConfig, staleTime: Infinity });
  const [leaving, setLeaving] = useState(false);

  const links = config.data?.links;
  const whatsapp = config.data?.support.whatsapp_link;

  const confirmSignOut = () =>
    Alert.alert('Sair da conta', 'Você pode entrar de novo quando quiser, com um novo código no e-mail.', [
      { text: 'Ficar', style: 'cancel' },
      {
        text: 'Sair',
        style: 'destructive',
        onPress: async () => {
          setLeaving(true);
          await signOut();
          setLeaving(false);
        },
      },
    ]);

  const confirmDelete = () =>
    Alert.alert(
      'Excluir minha conta',
      'A conta do app é apagada agora, em todos os aparelhos. Suas inscrições continuam com a equipe da turnê; para removê-las também, fale com a equipe pelo WhatsApp.',
      [
        { text: 'Manter conta', style: 'cancel' },
        {
          text: 'Excluir',
          style: 'destructive',
          onPress: async () => {
            setLeaving(true);
            try {
              await deleteAccount();
              Alert.alert('Conta excluída', 'Sua conta do app foi apagada. Obrigado por caminhar com a gente!');
            } catch (error) {
              Alert.alert('Não foi possível excluir', errorMessage(error));
            } finally {
              setLeaving(false);
            }
          },
        },
      ],
    );

  return (
    <Screen testID="account-screen">
      <AppText variant="title" accessibilityRole="header">
        Perfil
      </AppText>

      {signedIn ? (
        <Card>
          <View style={styles.identity}>
            <View style={styles.avatar}>
              <AppText variant="title" color={brand[700]}>
                {(participant?.name || participant?.email || '?').trim().charAt(0).toUpperCase()}
              </AppText>
            </View>
            <View style={styles.flex}>
              {participant?.name ? <AppText variant="bodyStrong">{participant.name}</AppText> : null}
              <AppText variant="caption" testID="account-email">
                {participant?.email}
              </AppText>
            </View>
          </View>
          <PushCard compact />
        </Card>
      ) : (
        <SignInPrompt title="Sua área no De Casa em Casa" text="Entre com seu e-mail para acompanhar suas inscrições e receber os avisos no celular." next="/account" />
      )}

      {signedIn ? <PushCard /> : null}

      <Card style={styles.menu}>
        {whatsapp ? <MenuRow icon="logo-whatsapp" label="Falar com a equipe" onPress={() => void Linking.openURL(whatsapp)} /> : null}
        {links ? <MenuRow icon="globe-outline" label="Abrir o site" onPress={() => void WebBrowser.openBrowserAsync(links.website)} /> : null}
        {links ? <MenuRow icon="shield-checkmark-outline" label="Política de privacidade" onPress={() => void WebBrowser.openBrowserAsync(links.privacy)} /> : null}
      </Card>

      {signedIn ? (
        <View style={styles.actions}>
          <Button label="Sair" variant="secondary" icon="log-out-outline" loading={leaving} onPress={confirmSignOut} testID="sign-out" />
          <Button label="Excluir minha conta" variant="ghost" onPress={confirmDelete} disabled={leaving} testID="delete-account" />
        </View>
      ) : null}

      <View style={styles.footer}>
        <AppText variant="caption" center>
          {`De Casa em Casa ${Constants.expoConfig?.version ?? ''}`.trim()}
        </AppText>
        <AppText variant="caption" center color={colors.textFaint}>
          Feito com carinho pela HelpFlux
        </AppText>
      </View>
    </Screen>
  );
}

function MenuRow({ icon, label, onPress }: { icon: ComponentProps<typeof Ionicons>['name']; label: string; onPress: () => void }) {
  return (
    <Pressable accessibilityRole="button" accessibilityLabel={label} onPress={onPress} style={({ pressed }) => [styles.row, pressed ? styles.rowPressed : null]}>
      <Ionicons name={icon} size={22} color={brand[700]} />
      <AppText variant="body" style={styles.flex}>
        {label}
      </AppText>
      <Ionicons name="chevron-forward" size={18} color={colors.textFaint} />
    </Pressable>
  );
}

const styles = StyleSheet.create({
  identity: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  avatar: {
    width: 52,
    height: 52,
    borderRadius: 26,
    backgroundColor: brand[100],
    alignItems: 'center',
    justifyContent: 'center',
  },
  flex: {
    flex: 1,
  },
  menu: {
    paddingVertical: spacing.xs,
    gap: 0,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    minHeight: 52,
    paddingHorizontal: spacing.xs,
    borderRadius: radius.md,
  },
  rowPressed: {
    backgroundColor: colors.surfaceSoft,
  },
  actions: {
    gap: spacing.sm,
  },
  footer: {
    gap: spacing.xxs,
    marginTop: spacing.md,
  },
});
