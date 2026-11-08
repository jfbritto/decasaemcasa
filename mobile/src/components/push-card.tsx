import { Ionicons } from '@react-native-vector-icons/ionicons';
import { useEffect, useState } from 'react';
import { Linking, StyleSheet, View } from 'react-native';

import { AppText } from '@/components/ui/app-text';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { askPushPermission, getPushPermission, type PushPermission } from '@/services/push';
import { brand, colors } from '@/theme/colors';
import { spacing } from '@/theme/spacing';

/**
 * "Ative os avisos" (spec 2026-11-08-app-mobile-design, AM32): a permissão
 * só é pedida com o toque. Some quando o build não recebe push (Expo Go,
 * simulador) ou quando já está ativo. `compact` mostra só a linha do Perfil.
 */
export function PushCard({ compact }: { compact?: boolean }) {
  const [permission, setPermission] = useState<PushPermission | null>(null);
  const [asking, setAsking] = useState(false);

  useEffect(() => {
    let alive = true;
    getPushPermission().then((value) => {
      if (alive) setPermission(value);
    });
    return () => {
      alive = false;
    };
  }, []);

  if (permission === null || permission === 'unavailable') return null;
  // A linha do Perfil só confirma os avisos ligados; o convite é o cartão.
  if (compact) {
    return permission === 'granted' ? (
      <View style={styles.row}>
        <Ionicons name="notifications" size={20} color={brand[600]} />
        <AppText variant="body">Avisos no celular ativados</AppText>
      </View>
    ) : null;
  }
  if (permission === 'granted') return null;

  const ask = async () => {
    if (permission === 'denied') {
      await Linking.openSettings();
      return;
    }
    setAsking(true);
    setPermission(await askPushPermission());
    setAsking(false);
  };

  return (
    <Card testID="push-card">
      <View style={styles.row}>
        <Ionicons name="notifications-outline" size={24} color={brand[600]} />
        <AppText variant="bodyStrong" style={styles.flex}>
          Saiba na hora quando sua inscrição mudar
        </AppText>
      </View>
      <AppText variant="body" color={colors.textMuted}>
        {permission === 'denied'
          ? 'Os avisos estão desligados nos ajustes do celular. Ligue para receber a aprovação e a confirmação assim que acontecerem.'
          : 'Avisamos aqui no celular quando sua inscrição for aprovada, confirmada ou mudar de situação.'}
      </AppText>
      <Button
        label={permission === 'denied' ? 'Abrir os ajustes' : 'Ativar avisos'}
        variant="secondary"
        icon="notifications-outline"
        loading={asking}
        onPress={ask}
      />
    </Card>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  flex: {
    flex: 1,
  },
});
