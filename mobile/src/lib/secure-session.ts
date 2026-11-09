import * as SecureStore from 'expo-secure-store';

import type { Participant } from '@/types/api';

// A sessão guardada no chaveiro (iOS) e no Keystore (Android): o token do
// Sanctum e o participante, para abrir o app já logado e sem rede.

const TOKEN_KEY = 'decasaemcasa.token';
const PARTICIPANT_KEY = 'decasaemcasa.participant';

const OPTIONS: SecureStore.SecureStoreOptions = {
  keychainAccessible: SecureStore.AFTER_FIRST_UNLOCK,
};

export type StoredSession = { token: string; participant: Participant | null };

export async function readSession(): Promise<StoredSession | null> {
  try {
    const token = await SecureStore.getItemAsync(TOKEN_KEY, OPTIONS);
    if (!token) return null;
    const raw = await SecureStore.getItemAsync(PARTICIPANT_KEY, OPTIONS);
    return { token, participant: raw ? (JSON.parse(raw) as Participant) : null };
  } catch (error) {
    console.warn('[sessão] não foi possível ler a sessão guardada', error);
    return null;
  }
}

export async function saveSession(session: StoredSession): Promise<void> {
  await SecureStore.setItemAsync(TOKEN_KEY, session.token, OPTIONS);
  await saveParticipant(session.participant);
}

export async function saveParticipant(participant: Participant | null): Promise<void> {
  if (participant) {
    await SecureStore.setItemAsync(PARTICIPANT_KEY, JSON.stringify(participant), OPTIONS);
  } else {
    await SecureStore.deleteItemAsync(PARTICIPANT_KEY, OPTIONS);
  }
}

export async function clearSession(): Promise<void> {
  try {
    await SecureStore.deleteItemAsync(TOKEN_KEY, OPTIONS);
    await SecureStore.deleteItemAsync(PARTICIPANT_KEY, OPTIONS);
  } catch (error) {
    console.warn('[sessão] não foi possível apagar a sessão guardada', error);
  }
}
