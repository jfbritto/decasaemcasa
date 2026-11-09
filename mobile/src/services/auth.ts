import * as Device from 'expo-device';

import { apiRequest } from '@/services/api';
import type { Participant } from '@/types/api';

// Login por código no e-mail (spec 2026-11-08-app-mobile-design, AM3 a AM8).

export async function requestLoginCode(email: string): Promise<void> {
  await apiRequest<unknown>('/auth/code', { method: 'POST', body: { email: email.trim() }, anonymous: true });
}

export async function verifyLoginCode(email: string, code: string): Promise<{ token: string; participant: Participant }> {
  return apiRequest('/auth/verify', {
    method: 'POST',
    body: { email: email.trim(), code: code.trim(), device_name: deviceName() },
    anonymous: true,
  });
}

export async function fetchMe(): Promise<Participant> {
  return apiRequest<Participant>('/me');
}

/** Revoga o token deste aparelho e apaga o token de push dele. */
export async function logout(pushToken: string | null): Promise<void> {
  await apiRequest<void>('/auth/logout', {
    method: 'POST',
    body: pushToken ? { push_token: pushToken } : {},
    timeoutMs: 5_000,
  });
}

export async function deleteAccount(): Promise<void> {
  await apiRequest<void>('/account', { method: 'DELETE' });
}

/** "iPhone 15", ou o nome do aparelho quando o sistema o dá. Nunca vazio. */
export function deviceName(): string {
  const name = Device.deviceName || Device.modelName;
  return (name ?? 'Celular').slice(0, 120);
}
