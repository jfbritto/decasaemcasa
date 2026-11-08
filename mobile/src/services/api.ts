import { serverUrl } from '@/lib/env';
import { currentToken, notifyUnauthorized } from '@/lib/session-token';

// O cliente da API v1 do De Casa em Casa, no contrato do HelpCheck
// (services/api.ts de lá): ApiError para resposta não 2xx (com os erros do
// 422 por campo e o `code` das recusas de regra), NetworkError quando não
// houve resposta (sem sinal, tempo esgotado, página de login do Wi-Fi).

export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

export type FieldErrors = Record<string, string[]>;

export type ApiRequestOptions = {
  method?: HttpMethod;
  /** Vai como JSON. */
  body?: unknown;
  /** Multipart (o comprovante), enviado como está no lugar de `body`. */
  form?: FormData;
  /** Desiste depois disso e lança NetworkError. */
  timeoutMs?: number;
  /** Sem o token da sessão (as rotas públicas também aceitam com ele). */
  anonymous?: boolean;
};

/** O servidor respondeu com status fora de 2xx. */
export class ApiError extends Error {
  readonly status: number;
  /** Erros de validação do Laravel (422), por campo. */
  readonly errors?: FieldErrors;
  /** Motivo de máquina de uma recusa de regra, ex.: 'already_registered'. */
  readonly code?: string;
  /** O JSON inteiro (ex.: o inscription_id de um 409). */
  readonly body?: Record<string, unknown>;

  constructor(status: number, message: string, errors?: FieldErrors, code?: string, body?: Record<string, unknown>) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errors = errors;
    this.code = code;
    this.body = body;
  }
}

/** O pedido não teve resposta útil da API. */
export class NetworkError extends Error {
  constructor(message = 'Sem conexão com o servidor', options?: { cause?: unknown }) {
    super(message, options);
    this.name = 'NetworkError';
  }
}

export const DEFAULT_TIMEOUT_MS = 20_000;
/** Uma foto de comprovante num sinal fraco demora mais que um JSON. */
export const UPLOAD_TIMEOUT_MS = 120_000;

export function apiUrl(path: string): string {
  return `${serverUrl()}/api/v1${path}`;
}

/**
 * Pedido JSON à API v1. Sem novas tentativas: quem chama decide o que uma
 * falha significa. Resolve `undefined` num 204. Um 401 com token avisa a
 * sessão (o token foi revogado, a conta excluída em outro aparelho).
 */
export async function apiRequest<T>(path: string, options: ApiRequestOptions = {}): Promise<T> {
  const { method = 'GET', body, form, timeoutMs = DEFAULT_TIMEOUT_MS, anonymous = false } = options;
  const token = anonymous ? null : currentToken();

  const headers: Record<string, string> = { Accept: 'application/json' };
  if (!form) headers['Content-Type'] = 'application/json';
  if (token) headers.Authorization = `Bearer ${token}`;

  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);

  let response: Response;
  let text = '';
  try {
    response = await fetch(apiUrl(path), {
      method,
      headers,
      body: form ?? (body === undefined ? undefined : JSON.stringify(body)),
      signal: controller.signal,
    });
    if (response.status !== 204) text = await response.text();
  } catch (error) {
    throw new NetworkError(undefined, { cause: error });
  } finally {
    clearTimeout(timer);
  }

  if (response.status === 401 && token) notifyUnauthorized();

  return readApiAnswer<T>(response.status, response.ok, text);
}

export function readApiAnswer<T>(status: number, ok: boolean, text: string): T {
  if (status === 204 || (ok && !text)) return undefined as T;

  const data = parseJson(text);

  if (!ok) {
    const payload = isRecord(data) ? data : {};
    throw new ApiError(
      status,
      typeof payload.message === 'string' ? payload.message : `HTTP ${status}`,
      isRecord(payload.errors) ? (payload.errors as FieldErrors) : undefined,
      typeof payload.code === 'string' ? payload.code : undefined,
      isRecord(data) ? data : undefined,
    );
  }

  // Um 2xx que não é JSON não veio da API (a página de login de um Wi-Fi).
  if (data === undefined) throw new NetworkError('A resposta não é JSON');

  return data as T;
}

/** A frase para a pessoa: a da API quando há, senão uma sobre a conexão. */
export function errorMessage(error: unknown, fallback = 'Não deu certo. Tente de novo em instantes.'): string {
  if (error instanceof NetworkError) return 'Sem conexão com o servidor. Confira a internet e tente de novo.';
  if (error instanceof ApiError) {
    if (error.status === 429) return 'Muitas tentativas seguidas. Espere um minuto e tente de novo.';
    if (error.status >= 500) return 'O servidor está com problemas agora. Tente de novo em instantes.';
    if (error.message && !error.message.startsWith('HTTP ')) return error.message;
  }
  return fallback;
}

/** O primeiro erro de cada campo de um 422. */
export function fieldErrors(error: unknown): Record<string, string> {
  if (!(error instanceof ApiError) || !error.errors) return {};
  const result: Record<string, string> = {};
  for (const [field, messages] of Object.entries(error.errors)) {
    if (messages[0]) result[field] = messages[0];
  }
  return result;
}

function parseJson(text: string): unknown {
  if (!text) return undefined;
  try {
    return JSON.parse(text);
  } catch {
    return undefined;
  }
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}
