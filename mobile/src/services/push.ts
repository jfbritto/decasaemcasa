import { isRunningInExpoGo } from 'expo';
import Constants from 'expo-constants';
import * as Device from 'expo-device';
import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

import { apiRequest } from '@/services/api';
import { deviceName } from '@/services/auth';

// Push pelo serviço do Expo (spec 2026-11-08-app-mobile-design, AM20, AM24 e
// AM32), versão enxuta do services/push.ts do HelpCheck:
// - o expo-notifications só é carregado quando o build pode receber push:
//   nunca no Expo Go (sem push remoto desde o SDK 53) nem no simulador;
// - a permissão só é pedida com um toque da pessoa ("Ativar avisos");
// - nada aqui lança para quem chama: push é conveniência, e o e-mail segue.

type NotificationsModule = typeof import('expo-notifications');

export type PushUnavailableReason = 'expo_go' | 'simulator' | 'no_project_id' | 'no_package';

export type PushCapability = { available: true; reason: null } | { available: false; reason: PushUnavailableReason };

export type PushPermission = 'granted' | 'denied' | 'undetermined' | 'unavailable';

export const ANDROID_CHANNEL_ID = 'default';

const PUSH_TOKEN_KEY = 'decasaemcasa.push-token';

let loaded: NotificationsModule | null | undefined;

function notificationsModule(): NotificationsModule | null {
  if (loaded === undefined) {
    try {
      // require tardio de propósito: um import rodaria o módulo (e o aviso
      // do Expo Go) na abertura do app.
      // eslint-disable-next-line @typescript-eslint/no-require-imports
      loaded = require('expo-notifications') as NotificationsModule;
    } catch {
      loaded = null;
    }
  }
  return loaded;
}

function projectId(): string | null {
  const id = Constants.expoConfig?.extra?.eas?.projectId ?? Constants.easConfig?.projectId;
  return typeof id === 'string' && id.trim() !== '' ? id.trim() : null;
}

export function pushCapability(): PushCapability {
  if (isRunningInExpoGo()) return { available: false, reason: 'expo_go' };
  if ((Platform.OS !== 'ios' && Platform.OS !== 'android') || !Device.isDevice) {
    return { available: false, reason: 'simulator' };
  }
  if (!projectId()) return { available: false, reason: 'no_project_id' };
  if (!notificationsModule()) return { available: false, reason: 'no_package' };
  return { available: true, reason: null };
}

function availableModule(): NotificationsModule | null {
  return pushCapability().available ? notificationsModule() : null;
}

type PermissionSettings = Awaited<ReturnType<NotificationsModule['getPermissionsAsync']>>;

function permissionOf(module: NotificationsModule, settings: PermissionSettings): PushPermission {
  // A autorização provisória do iOS entrega em silêncio: conta como dada.
  const provisional = module.IosAuthorizationStatus?.PROVISIONAL;
  if (settings.granted || (provisional !== undefined && settings.ios?.status === provisional)) return 'granted';
  if (settings.status === 'denied') return 'denied';
  return 'undetermined';
}

export async function getPushPermission(): Promise<PushPermission> {
  const module = availableModule();
  if (!module) return 'unavailable';
  try {
    return permissionOf(module, await module.getPermissionsAsync());
  } catch {
    return 'undetermined';
  }
}

/** Pede a permissão (só depois de um toque da pessoa) e, dada, registra o aparelho. */
export async function askPushPermission(): Promise<PushPermission> {
  const module = availableModule();
  if (!module) return 'unavailable';
  try {
    await ensureAndroidChannel(module);
    const current = await module.getPermissionsAsync();
    let permission = permissionOf(module, current);
    if (permission !== 'granted' && current.canAskAgain !== false) {
      permission = permissionOf(module, await module.requestPermissionsAsync());
    }
    if (permission === 'granted') await registerPushToken();
    return permission;
  } catch {
    return 'undetermined';
  }
}

async function ensureAndroidChannel(module: NotificationsModule): Promise<void> {
  if (Platform.OS !== 'android') return;
  await module.setNotificationChannelAsync(ANDROID_CHANNEL_ID, {
    name: 'Avisos da inscrição',
    importance: module.AndroidImportance.HIGH,
  });
}

export type PushRegistration = 'registered' | 'unavailable' | 'no_permission' | 'failed';

/**
 * Manda o token deste aparelho para o servidor (POST /push-tokens), só com
 * a permissão já dada. Chamado ao entrar e a cada abertura do app (o
 * servidor atualiza o last_seen_at). Nunca lança.
 */
export async function registerPushToken(): Promise<PushRegistration> {
  const module = availableModule();
  const id = projectId();
  if (!module || !id) return 'unavailable';
  try {
    if (permissionOf(module, await module.getPermissionsAsync()) !== 'granted') return 'no_permission';
    await ensureAndroidChannel(module);
    const token = (await module.getExpoPushTokenAsync({ projectId: id })).data;
    if (typeof token !== 'string' || token === '') return 'failed';
    await apiRequest<void>('/push-tokens', {
      method: 'POST',
      body: { token, platform: Platform.OS, device_name: deviceName() },
      timeoutMs: 15_000,
    });
    await SecureStore.setItemAsync(PUSH_TOKEN_KEY, token);
    return 'registered';
  } catch {
    return 'failed';
  }
}

/** O token que este aparelho mandou por último (o "Sair" o apaga no servidor). */
export async function storedPushToken(): Promise<string | null> {
  try {
    return await SecureStore.getItemAsync(PUSH_TOKEN_KEY);
  } catch {
    return null;
  }
}

export async function forgetPushToken(): Promise<void> {
  try {
    await SecureStore.deleteItemAsync(PUSH_TOKEN_KEY);
  } catch {
    // nada a fazer: o servidor apaga o token morto no próximo envio
  }
}

/** O id da inscrição que um push leva (AM24), ou null. */
export function inscriptionIdOf(data: unknown): number | null {
  if (typeof data !== 'object' || data === null) return null;
  const raw = (data as Record<string, unknown>).inscription_id;
  const id = typeof raw === 'string' ? Number(raw) : raw;
  return typeof id === 'number' && Number.isInteger(id) && id > 0 ? id : null;
}

/**
 * Mostra o push com o app aberto e entrega o toque (o que abriu o app e os
 * seguintes) a `onTap`. Devolve a função que para tudo. Nunca lança.
 */
export function listenToPush(onTap: (data: unknown) => void): () => void {
  const module = availableModule();
  if (!module) return () => undefined;
  const subscriptions: { remove: () => void }[] = [];
  const take = (response: import('expo-notifications').NotificationResponse | null) => {
    if (!response || response.actionIdentifier !== module.DEFAULT_ACTION_IDENTIFIER) return;
    onTap(response.notification.request.content.data);
  };
  try {
    module.setNotificationHandler({
      handleNotification: async () => ({
        shouldShowBanner: true,
        shouldShowList: true,
        shouldPlaySound: true,
        shouldSetBadge: false,
      }),
    });
    take(module.getLastNotificationResponse());
    module.clearLastNotificationResponse();
    subscriptions.push(module.addNotificationResponseReceivedListener(take));
  } catch (error) {
    if (__DEV__) console.warn('[push] não foi possível ouvir os avisos', error);
  }
  return () => {
    for (const subscription of subscriptions) subscription.remove();
  };
}
