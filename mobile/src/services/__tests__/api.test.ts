import { onUnauthorized, setCurrentToken } from '@/lib/session-token';
import { ApiError, apiRequest, errorMessage, fieldErrors, NetworkError, readApiAnswer } from '@/services/api';
import { mockFetch } from '@/test-utils/fetch-mock';

beforeEach(() => {
  process.env.EXPO_PUBLIC_API_URL = 'https://decasaemcasa.example/';
});

describe('apiRequest', () => {
  it('chama /api/v1 com JSON e o token da sessão', async () => {
    setCurrentToken('tok-123');
    const { calls } = mockFetch({ 'GET /me': { status: 200, body: { id: 1 } } });

    await expect(apiRequest('/me')).resolves.toEqual({ id: 1 });

    expect(calls[0].url).toBe('https://decasaemcasa.example/api/v1/me');
    expect(calls[0].headers).toMatchObject({ Accept: 'application/json', Authorization: 'Bearer tok-123', 'Content-Type': 'application/json' });
  });

  it('POST sem dados vai com corpo {}; GET e DELETE, sem corpo', async () => {
    // O firewall da hospedagem (HostGator) recusa com 406 o POST sem
    // Content-Length, e o fetch do Expo no iOS não manda esse cabeçalho
    // quando o corpo é vazio. Com corpo de texto, as duas plataformas mandam.
    setCurrentToken('tok-123');
    const { calls } = mockFetch({
      'POST /auth/logout': { status: 204 },
      'GET /me': { status: 200, body: { id: 1 } },
      'DELETE /push-tokens/abc': { status: 204 },
    });

    await apiRequest('/auth/logout', { method: 'POST' });
    await apiRequest('/me');
    await apiRequest('/push-tokens/abc', { method: 'DELETE' });

    expect(calls.map((call) => [call.method, call.body])).toEqual([
      ['POST', {}],
      ['GET', undefined],
      ['DELETE', undefined],
    ]);
  });

  it('rota anônima não leva o token', async () => {
    setCurrentToken('tok-123');
    const { calls } = mockFetch({ 'GET /config': { status: 200, body: {} } });

    await apiRequest('/config', { anonymous: true });

    expect(calls[0].headers.Authorization).toBeUndefined();
  });

  it('um 422 vira ApiError com os erros por campo e o code', async () => {
    mockFetch({
      'POST /inscriptions': {
        status: 422,
        body: { message: 'O CPF informado é inválido.', errors: { cpf: ['O CPF informado é inválido.'] } },
      },
    });

    const error = (await apiRequest('/inscriptions', { method: 'POST', body: {} }).catch((e) => e)) as ApiError;

    expect(error).toBeInstanceOf(ApiError);
    expect(error.status).toBe(422);
    expect(fieldErrors(error)).toEqual({ cpf: 'O CPF informado é inválido.' });
    expect(errorMessage(error)).toBe('O CPF informado é inválido.');
  });

  it('um 409 guarda o corpo (o inscription_id da que já existe)', async () => {
    mockFetch({ 'POST /inscriptions': { status: 409, body: { message: 'Já tem.', code: 'already_registered', inscription_id: 9 } } });

    const error = (await apiRequest('/inscriptions', { method: 'POST', body: {} }).catch((e) => e)) as ApiError;

    expect(error.code).toBe('already_registered');
    expect(error.body?.inscription_id).toBe(9);
  });

  it('sem resposta é NetworkError', async () => {
    mockFetch({});

    const error = await apiRequest('/events').catch((e) => e);

    expect(error).toBeInstanceOf(NetworkError);
    expect(errorMessage(error)).toBe('Sem conexão com o servidor. Confira a internet e tente de novo.');
  });

  it('um 401 com token avisa a sessão; sem token, não', async () => {
    const listener = jest.fn();
    const stop = onUnauthorized(listener);
    mockFetch({ 'GET /me': { status: 401, body: { message: 'Unauthenticated.' } } });

    setCurrentToken(null);
    await apiRequest('/me').catch(() => undefined);
    expect(listener).not.toHaveBeenCalled();

    setCurrentToken('revogado');
    await apiRequest('/me').catch(() => undefined);
    expect(listener).toHaveBeenCalledTimes(1);
    stop();
  });
});

describe('readApiAnswer e errorMessage', () => {
  it('204 é undefined', () => {
    expect(readApiAnswer(204, true, '')).toBeUndefined();
  });

  it('2xx que não é JSON não veio da API (Wi-Fi com login)', () => {
    expect(() => readApiAnswer(200, true, '<html>login</html>')).toThrow(NetworkError);
  });

  it('frases para 429 e 500', () => {
    expect(errorMessage(new ApiError(429, 'Too Many Attempts.'))).toBe('Muitas tentativas seguidas. Espere um minuto e tente de novo.');
    expect(errorMessage(new ApiError(500, 'Server Error'))).toBe('O servidor está com problemas agora. Tente de novo em instantes.');
    expect(errorMessage(new ApiError(404, 'HTTP 404'))).toBe('Não deu certo. Tente de novo em instantes.');
  });
});
