import { File, UploadType } from 'expo-file-system';

import { currentToken, notifyUnauthorized } from '@/lib/session-token';
import { apiUrl, NetworkError, readApiAnswer, UPLOAD_TIMEOUT_MS } from '@/services/api';

// Um arquivo do aparelho enviado à API v1 como multipart (o comprovante),
// como o HelpCheck faz (services/api-upload.ts de lá). No SDK 57 o fetch
// global é o do Expo, que não aceita o { uri, name, type } antigo do React
// Native no FormData ("Unsupported FormDataPart implementation", visto no
// emulador): o upload nativo do expo-file-system (File.upload) manda o
// arquivo, e a resposta é lida como a do apiRequest (readApiAnswer).

export type ApiUploadOptions = {
  /** O arquivo no aparelho (file://...). */
  uri: string;
  /** O campo do multipart que o servidor lê ("payment_proof"). */
  field: string;
  mimeType: string;
  timeoutMs?: number;
};

export async function apiUpload<T>(path: string, options: ApiUploadOptions): Promise<T> {
  const { uri, field, mimeType, timeoutMs = UPLOAD_TIMEOUT_MS } = options;
  const token = currentToken();
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);

  let result;
  try {
    result = await new File(uri).upload(apiUrl(path), {
      httpMethod: 'POST',
      uploadType: UploadType.MULTIPART,
      fieldName: field,
      mimeType,
      headers: { Accept: 'application/json', ...(token ? { Authorization: `Bearer ${token}` } : {}) },
      // A pessoa espera na tela: nada de sessão em segundo plano.
      sessionType: 'foreground',
      signal: controller.signal,
    });
  } catch (error) {
    if (__DEV__ && process.env.NODE_ENV !== 'test') console.warn(`[api] upload ${path} sem resposta:`, error instanceof Error ? error.message : error);
    throw new NetworkError(undefined, { cause: error });
  } finally {
    clearTimeout(timer);
  }

  if (result.status === 401 && token) notifyUnauthorized();

  return readApiAnswer<T>(result.status, result.status >= 200 && result.status < 300, result.body ?? '');
}
