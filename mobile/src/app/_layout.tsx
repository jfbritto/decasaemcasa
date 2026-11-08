import { QueryClientProvider } from '@tanstack/react-query';
import { useFonts } from 'expo-font';
import { Stack } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';
import { StyleSheet } from 'react-native';
import { GestureHandlerRootView } from 'react-native-gesture-handler';

import { usePush } from '@/hooks/use-push';
import { detailHeader } from '@/lib/header';
import { queryClient } from '@/lib/query-client';
import { useAuthStore } from '@/stores/auth-store';
import { colors } from '@/theme/colors';
import { fontAssets } from '@/theme/typography';

// A tela de abertura fica até as fontes carregarem e a sessão voltar do
// chaveiro: o texto nunca pisca na fonte do sistema, e quem já entrou não
// vê o "Entrar" piscar.
SplashScreen.preventAutoHideAsync();

export default function RootLayout() {
  const [fontsLoaded, fontError] = useFonts(fontAssets);
  const status = useAuthStore((s) => s.status);
  const restore = useAuthStore((s) => s.restore);

  useEffect(() => {
    void restore();
  }, [restore]);

  // Com erro de fonte o app abre mesmo assim, na fonte do sistema.
  const ready = Boolean(fontsLoaded || fontError) && status !== 'restoring';
  const signedIn = status === 'signedIn';

  useEffect(() => {
    if (ready) void SplashScreen.hideAsync();
  }, [ready]);

  usePush(ready && signedIn);

  if (!ready) return null;

  return (
    <GestureHandlerRootView style={styles.root} testID="app-root">
      <QueryClientProvider client={queryClient}>
        <StatusBar style="dark" />
        <Stack screenOptions={{ headerShown: false, contentStyle: { backgroundColor: colors.bg } }}>
          <Stack.Screen name="(tabs)" />
          <Stack.Screen name="events/[id]/index" options={detailHeader('Encontro')} />
          <Stack.Screen name="login" options={{ presentation: 'modal' }} />
          {/* As telas da conta (AM27): sem sessão elas não existem, e sair
              com uma delas aberta volta para as abas. */}
          <Stack.Protected guard={signedIn}>
            <Stack.Screen name="events/[id]/register" options={detailHeader('Inscrição')} />
            <Stack.Screen name="inscriptions/[id]/index" options={detailHeader('Minha inscrição')} />
            <Stack.Screen name="inscriptions/[id]/social" options={detailHeader('Contribuição social')} />
          </Stack.Protected>
        </Stack>
      </QueryClientProvider>
    </GestureHandlerRootView>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: colors.bg,
  },
});
