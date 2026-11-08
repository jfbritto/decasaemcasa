import { act, renderRouter } from 'expo-router/testing-library';

import AccountScreen from '@/app/(tabs)/account';
import EventsScreen from '@/app/(tabs)/index';
import InscriptionsScreen from '@/app/(tabs)/inscriptions';
import TabsLayout from '@/app/(tabs)/_layout';
import RootLayout from '@/app/_layout';
import EventScreen from '@/app/events/[id]/index';
import RegisterScreen from '@/app/events/[id]/register';
import InscriptionScreen from '@/app/inscriptions/[id]/index';
import SocialRequestScreen from '@/app/inscriptions/[id]/social';
import LoginScreen from '@/app/login';
import { participantFixture } from '@/test-utils/fixtures';
import { secureItems } from '@/test-utils/secure-store-mock';

// O app inteiro, com as rotas de verdade, para os testes de tela. Os
// serviços (rede) são trocados por jest.mock em cada arquivo de teste.

export const APP_ROUTES = {
  _layout: RootLayout,
  '(tabs)/_layout': TabsLayout,
  '(tabs)/index': EventsScreen,
  '(tabs)/inscriptions': InscriptionsScreen,
  '(tabs)/account': AccountScreen,
  'events/[id]/index': EventScreen,
  'events/[id]/register': RegisterScreen,
  'inscriptions/[id]/index': InscriptionScreen,
  'inscriptions/[id]/social': SocialRequestScreen,
  login: LoginScreen,
};

/** Deixa as promessas e os timers falsos andarem (o renderRouter usa timers falsos). */
export async function settle(ms = 50): Promise<void> {
  await act(async () => {
    await jest.advanceTimersByTimeAsync(ms);
  });
}

/** Abre o app em `url`; com `signedIn`, já com a sessão guardada no chaveiro. */
export async function renderApp(url: string, { signedIn = false }: { signedIn?: boolean } = {}) {
  if (signedIn) {
    secureItems().set('decasaemcasa.token', 'tok-teste');
    secureItems().set('decasaemcasa.participant', JSON.stringify(participantFixture));
  }
  // O render da Testing Library 14 é assíncrono: esperar antes de outro act.
  const result = renderRouter(APP_ROUTES as never, { initialUrl: url });
  await result;
  await settle();
  await settle();
  return { getPathname: () => result.getPathname() };
}
