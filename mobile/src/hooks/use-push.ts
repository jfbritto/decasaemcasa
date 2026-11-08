import { router, type Href } from 'expo-router';
import { useEffect } from 'react';

import { queryClient, queryKeys } from '@/lib/query-client';
import { inscriptionIdOf, listenToPush, registerPushToken } from '@/services/push';

/**
 * Com a sessão aberta: manda o token deste aparelho (se os avisos já foram
 * permitidos) e abre a inscrição do push tocado (AM24).
 */
export function usePush(active: boolean): void {
  useEffect(() => {
    if (!active) return;
    void registerPushToken();
    return listenToPush((data) => {
      const id = inscriptionIdOf(data);
      if (id === null) return;
      void queryClient.invalidateQueries({ queryKey: queryKeys.inscriptions });
      router.push(`/inscriptions/${id}` as Href);
    });
  }, [active]);
}
