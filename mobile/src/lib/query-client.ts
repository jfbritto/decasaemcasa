import { focusManager, QueryClient } from '@tanstack/react-query';
import { AppState, type AppStateStatus } from 'react-native';

/**
 * O "foco" do React Query é o app em primeiro plano, como a TanStack
 * documenta para React Native (o padrão escuta o visibilitychange do
 * navegador, que o React Native nunca dispara). Volta ao app = os dados da
 * tela aberta são buscados de novo.
 */
export function appStateFocus(setFocused: (focused?: boolean) => void): () => void {
  const subscription = AppState.addEventListener('change', (state: AppStateStatus) => {
    setFocused(state === 'active');
  });
  return () => subscription.remove();
}

focusManager.setEventListener(appStateFocus);

/**
 * O cache único do app. Fica fora do layout para a sessão limpá-lo ao sair:
 * dados de uma conta nunca aparecem para a próxima pessoa no mesmo celular.
 */
export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      staleTime: 30_000,
    },
  },
});

export const queryKeys = {
  config: ['config'] as const,
  events: ['events'] as const,
  event: (id: number) => ['events', id] as const,
  inscriptions: ['inscriptions'] as const,
  inscription: (id: number) => ['inscriptions', id] as const,
};
