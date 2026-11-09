import { apiRequest } from '@/services/api';
import type { NotificationList } from '@/types/api';

// A aba "Avisos" (spec 2026-11-08-app-mobile-design, AM41 a AM43): um aviso
// por e-mail recebido, do mais novo ao mais antigo, e o "lido".

export async function fetchNotifications(): Promise<NotificationList> {
  return apiRequest<NotificationList>('/notifications');
}

export async function markNotificationRead(id: number): Promise<void> {
  await apiRequest<void>(`/notifications/${id}/read`, { method: 'POST' });
}

export async function markAllNotificationsRead(): Promise<void> {
  await apiRequest<void>('/notifications/read-all', { method: 'POST' });
}
