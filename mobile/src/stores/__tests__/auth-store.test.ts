import { queryClient } from '@/lib/query-client';
import { currentToken } from '@/lib/session-token';
import { apiRequest } from '@/services/api';
import { useAuthStore } from '@/stores/auth-store';
import { mockFetch } from '@/test-utils/fetch-mock';
import { participantFixture } from '@/test-utils/fixtures';
import { secureItems } from '@/test-utils/secure-store-mock';

beforeEach(() => {
  process.env.EXPO_PUBLIC_API_URL = 'https://decasaemcasa.example';
});

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

describe('a sessão', () => {
  it('sem sessão guardada abre deslogado', async () => {
    await useAuthStore.getState().restore();

    expect(useAuthStore.getState().status).toBe('signedOut');
  });

  it('entrar guarda o token no chaveiro e limpa o cache da conta anterior', async () => {
    queryClient.setQueryData(['inscriptions'], ['de outra pessoa']);

    await useAuthStore.getState().signIn('tok-1', participantFixture);

    expect(useAuthStore.getState()).toMatchObject({ status: 'signedIn', participant: participantFixture });
    expect(currentToken()).toBe('tok-1');
    expect(secureItems().get('decasaemcasa.token')).toBe('tok-1');
    expect(queryClient.getQueryData(['inscriptions'])).toBeUndefined();
  });

  it('abre logado com a sessão guardada e atualiza a conta', async () => {
    secureItems().set('decasaemcasa.token', 'tok-guardado');
    secureItems().set('decasaemcasa.participant', JSON.stringify(participantFixture));
    mockFetch({ 'GET /me': { status: 200, body: { ...participantFixture, name: 'Maria V.' } } });

    await useAuthStore.getState().restore();

    expect(useAuthStore.getState().status).toBe('signedIn');
    expect(useAuthStore.getState().participant?.name).toBe('Maria V.');
  });

  it('sem rede a sessão guardada fica', async () => {
    secureItems().set('decasaemcasa.token', 'tok-guardado');
    mockFetch({});

    await useAuthStore.getState().restore();

    expect(useAuthStore.getState().status).toBe('signedIn');
  });

  it('um 401 (token revogado) encerra a sessão no aparelho', async () => {
    await useAuthStore.getState().signIn('tok-1', participantFixture);
    mockFetch({ 'GET /inscriptions': { status: 401, body: { message: 'Unauthenticated.' } } });

    await apiRequest('/inscriptions').catch(() => undefined);
    await flush();

    expect(useAuthStore.getState().status).toBe('signedOut');
    expect(secureItems().has('decasaemcasa.token')).toBe(false);
    expect(currentToken()).toBeNull();
  });

  it('sair revoga no servidor com o token de push do aparelho', async () => {
    await useAuthStore.getState().signIn('tok-1', participantFixture);
    secureItems().set('decasaemcasa.push-token', 'ExponentPushToken[abc]');
    const { calls } = mockFetch({ 'POST /auth/logout': { status: 204 } });

    await useAuthStore.getState().signOut();

    expect(calls[0]).toMatchObject({ method: 'POST', body: { push_token: 'ExponentPushToken[abc]' } });
    expect(calls[0].headers.Authorization).toBe('Bearer tok-1');
    expect(useAuthStore.getState().status).toBe('signedOut');
    expect(secureItems().size).toBe(0);
  });

  it('sair sem rede sai do aparelho mesmo assim', async () => {
    await useAuthStore.getState().signIn('tok-1', participantFixture);
    mockFetch({});

    await useAuthStore.getState().signOut();

    expect(useAuthStore.getState().status).toBe('signedOut');
  });

  it('excluir a conta: recusada pelo servidor, a conta fica', async () => {
    await useAuthStore.getState().signIn('tok-1', participantFixture);
    mockFetch({ 'DELETE /account': { status: 500, body: { message: 'Server Error' } } });

    await expect(useAuthStore.getState().deleteAccount()).rejects.toThrow();
    expect(useAuthStore.getState().status).toBe('signedIn');

    mockFetch({ 'DELETE /account': { status: 204 } });
    await useAuthStore.getState().deleteAccount();
    expect(useAuthStore.getState().status).toBe('signedOut');
  });
});
