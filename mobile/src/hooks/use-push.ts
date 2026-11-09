import { router, type Href } from 'expo-router';
import { useEffect } from 'react';

import { openNotification } from '@/lib/open-notification';
import { queryClient, queryKeys } from '@/lib/query-client';
import { inscriptionIdOf, listenToPush, notificationIdOf, registerPushToken } from '@/services/push';

/**
 * Com a sessão aberta: manda o token deste aparelho (se os avisos já foram
 * permitidos); um push que chega com o app aberto atualiza "Avisos" e as
 * inscrições na hora; o toque num push marca o aviso como lido e abre a
 * inscrição (AM24 e AM44).
 */
export function usePush(active: boolean): void {
  useEffect(() => {
    if (!active) return;
    void registerPushToken();
    return listenToPush({
      onReceived: () => {
        void queryClient.invalidateQueries({ queryKey: queryKeys.notifications });
        void queryClient.invalidateQueries({ queryKey: queryKeys.inscriptions });
      },
      onTap: (data) => {
        void queryClient.invalidateQueries({ queryKey: queryKeys.inscriptions });
        const notificationId = notificationIdOf(data);
        const inscriptionId = inscriptionIdOf(data);
        if (notificationId !== null) {
          openNotification({ id: notificationId, inscription_id: inscriptionId });
        } else if (inscriptionId !== null) {
          // Push de antes da aba "Avisos": só abre a inscrição.
          router.push(`/inscriptions/${inscriptionId}` as Href);
        }
      },
    });
  }, [active]);
}
