import { apiRequest } from '@/services/api';
import type { AppConfig, EventDetail, EventSummary } from '@/types/api';

// Encontros e textos públicos (spec 2026-11-08-app-mobile-design, AM10).

export async function fetchEvents(): Promise<EventSummary[]> {
  const answer = await apiRequest<{ data: EventSummary[] }>('/events');
  return answer.data;
}

/** Com a sessão, diz se a pessoa já tem inscrição no encontro. */
export async function fetchEvent(id: number): Promise<EventDetail> {
  return apiRequest<EventDetail>(`/events/${id}`);
}

export async function fetchConfig(): Promise<AppConfig> {
  return apiRequest<AppConfig>('/config', { anonymous: true });
}
