import { router, type Href } from 'expo-router';

import { queryClient, queryKeys } from '@/lib/query-client';
import { markNotificationRead } from '@/services/notifications';
import type { NotificationList } from '@/types/api';

/**
 * Abre um aviso (da aba "Avisos" ou do toque no push, AM43 e AM44): marca
 * como lido já no cache (o número da aba cai na hora), avisa o servidor e
 * leva à inscrição. Se o servidor falhar, a lista é buscada de novo.
 */
export function openNotification(notification: { id: number; inscription_id: number | null }): void {
  queryClient.setQueryData<NotificationList>(queryKeys.notifications, (old) => {
    if (!old) return old;
    const wasUnread = old.data.some((item) => item.id === notification.id && !item.read);
    return {
      data: old.data.map((item) => (item.id === notification.id ? { ...item, read: true } : item)),
      unread_count: Math.max(0, old.unread_count - (wasUnread ? 1 : 0)),
    };
  });

  markNotificationRead(notification.id).catch(() => {
    void queryClient.invalidateQueries({ queryKey: queryKeys.notifications });
  });

  if (notification.inscription_id) {
    router.push(`/inscriptions/${notification.inscription_id}` as Href);
  }
}
