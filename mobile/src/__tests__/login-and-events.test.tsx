import { fireEvent, screen } from 'expo-router/testing-library';

import * as authService from '@/services/auth';
import * as eventsService from '@/services/events';
import * as inscriptionsService from '@/services/inscriptions';
import { useAuthStore } from '@/stores/auth-store';
import { configFixture, eventDetailFixture, eventFixture, participantFixture } from '@/test-utils/fixtures';
import { renderApp, settle } from '@/test-utils/render-app';
import { ApiError } from '@/services/api';

jest.mock('@/services/auth', () => ({
  requestLoginCode: jest.fn(),
  verifyLoginCode: jest.fn(),
  fetchMe: jest.fn(),
  logout: jest.fn(),
  deleteAccount: jest.fn(),
  deviceName: () => 'Celular de teste',
}));
jest.mock('@/services/events', () => ({ fetchEvents: jest.fn(), fetchEvent: jest.fn(), fetchConfig: jest.fn() }));
jest.mock('@/services/inscriptions', () => ({ fetchInscriptions: jest.fn(), fetchInscription: jest.fn() }));

const auth = jest.mocked(authService);
const events = jest.mocked(eventsService);
const inscriptions = jest.mocked(inscriptionsService);

beforeEach(() => {
  jest.clearAllMocks();
  events.fetchEvents.mockResolvedValue([eventFixture, { ...eventFixture, id: 4, city: 'Vila Velha', is_full: true, available_spots: 0 }]);
  events.fetchEvent.mockResolvedValue(eventDetailFixture);
  events.fetchConfig.mockResolvedValue(configFixture);
  auth.fetchMe.mockResolvedValue(participantFixture);
  inscriptions.fetchInscriptions.mockResolvedValue([]);
});

afterEach(() => {
  jest.useRealTimers();
});

describe('Encontros (sem conta)', () => {
  it('lista os encontros abertos com vagas e lotação', async () => {
    await renderApp('/');

    expect(screen.getByText('De Casa em Casa')).toBeOnTheScreen();
    expect(screen.getByText('Serra')).toBeOnTheScreen();
    expect(screen.getByText('4 vagas restantes')).toBeOnTheScreen();
    expect(screen.getByText('Vila Velha')).toBeOnTheScreen();
    expect(screen.getByText('Vagas esgotadas')).toBeOnTheScreen();
  });

  it('nenhum encontro aberto', async () => {
    events.fetchEvents.mockResolvedValue([]);
    await renderApp('/');

    expect(screen.getByText('Nenhum encontro aberto agora')).toBeOnTheScreen();
  });

  it('o detalhe explica como funciona e "Quero participar" pede o login', async () => {
    const app = await renderApp('/');

    await fireEvent.press(screen.getByTestId('event-3'));
    await settle();
    expect(app.getPathname()).toBe('/events/3');
    expect(screen.getByText('Como funciona')).toBeOnTheScreen();
    expect(screen.getByText('Uma noite de música numa casa no alto da Serra.')).toBeOnTheScreen();

    await fireEvent.press(screen.getByTestId('participate-button'));
    await settle();
    expect(app.getPathname()).toBe('/login');
    expect(screen.getByText('Entrar no De Casa em Casa')).toBeOnTheScreen();
  });

  it('quem já tem inscrição no encontro vê a inscrição, não o botão', async () => {
    events.fetchEvent.mockResolvedValue({ ...eventDetailFixture, my_inscription: { id: 41, state: 'approved' } });
    await renderApp('/events/3', { signedIn: true });

    expect(screen.getByText('Aguardando pagamento')).toBeOnTheScreen();
    expect(screen.getByText('Ver minha inscrição')).toBeOnTheScreen();
    expect(screen.queryByTestId('participate-button')).not.toBeOnTheScreen();
  });
});

describe('Entrar com código no e-mail', () => {
  it('e-mail, código de 6 números e volta para onde estava, já logado', async () => {
    auth.requestLoginCode.mockResolvedValue();
    auth.verifyLoginCode.mockResolvedValue({ token: 'tok-novo', participant: participantFixture });
    const app = await renderApp('/inscriptions');

    expect(screen.getByText('Acompanhe tudo por aqui')).toBeOnTheScreen();
    await fireEvent.press(screen.getByTestId('sign-in-button'));
    await settle();

    await fireEvent.changeText(screen.getByTestId('login-email'), 'maria@velunora.example');
    await fireEvent.press(screen.getByTestId('login-submit'));
    await settle(400);

    expect(auth.requestLoginCode).toHaveBeenCalledWith('maria@velunora.example');
    expect(screen.getByText('Confira seu e-mail')).toBeOnTheScreen();

    // O sexto número envia sozinho.
    await fireEvent.changeText(screen.getByTestId('login-code'), '123456');
    await settle();
    await settle();

    expect(auth.verifyLoginCode).toHaveBeenCalledWith('maria@velunora.example', '123456');
    expect(useAuthStore.getState().status).toBe('signedIn');
    expect(app.getPathname()).toBe('/inscriptions');
    expect(screen.getByText('Você ainda não tem inscrições')).toBeOnTheScreen();
  });

  it('digitando número a número não envia sozinho; o código longo da revisão vai inteiro pelo "Entrar"', async () => {
    auth.requestLoginCode.mockResolvedValue();
    auth.verifyLoginCode.mockResolvedValue({ token: 'tok-revisao', participant: participantFixture });
    await renderApp('/login');

    await fireEvent.changeText(screen.getByTestId('login-email'), 'revisao@velunora.example');
    await fireEvent.press(screen.getByTestId('login-submit'));
    await settle(400);

    let typed = '';
    for (const digit of '246813579024') {
      typed += digit;
      await fireEvent.changeText(screen.getByTestId('login-code'), typed);
    }
    await settle();
    expect(auth.verifyLoginCode).not.toHaveBeenCalled();
    expect(screen.getByTestId('login-code').props.value).toBe('246813579024');

    await fireEvent.press(screen.getByTestId('login-submit'));
    await settle();
    expect(auth.verifyLoginCode).toHaveBeenCalledWith('revisao@velunora.example', '246813579024');
  });

  it('e-mail com acento é avisado antes de ir para a API', async () => {
    await renderApp('/login');

    await fireEvent.changeText(screen.getByTestId('login-email'), 'maria@gmáil.com');
    await fireEvent.press(screen.getByTestId('login-submit'));
    await settle();

    expect(screen.getByText('Digite o e-mail sem acentos nem espaços.')).toBeOnTheScreen();
    expect(auth.requestLoginCode).not.toHaveBeenCalled();
  });

  it('código errado mostra a mensagem da API e fica na tela', async () => {
    auth.requestLoginCode.mockResolvedValue();
    auth.verifyLoginCode.mockRejectedValue(
      new ApiError(422, 'Código inválido.', { code: ['Código inválido ou expirado. Confira os números ou peça um novo código.'] }),
    );
    await renderApp('/login');

    await fireEvent.changeText(screen.getByTestId('login-email'), 'maria@velunora.example');
    await fireEvent.press(screen.getByTestId('login-submit'));
    await settle(400);
    await fireEvent.changeText(screen.getByTestId('login-code'), '000000');
    await settle();

    expect(screen.getByText('Código inválido ou expirado. Confira os números ou peça um novo código.')).toBeOnTheScreen();
    expect(useAuthStore.getState().status).toBe('signedOut');
  });

  it('e-mail com erro de digitação traz a sugestão da API', async () => {
    auth.requestLoginCode.mockRejectedValue(
      new ApiError(422, 'Verifique.', { email: ['Verifique seu e-mail. Você quis dizer maria@gmail.com?'] }),
    );
    await renderApp('/login');

    await fireEvent.changeText(screen.getByTestId('login-email'), 'maria@gmail.con');
    await fireEvent.press(screen.getByTestId('login-submit'));
    await settle();

    expect(screen.getByText('Verifique seu e-mail. Você quis dizer maria@gmail.com?')).toBeOnTheScreen();
  });

  it('o reenvio do código espera 60 segundos', async () => {
    auth.requestLoginCode.mockResolvedValue();
    await renderApp('/login');

    await fireEvent.changeText(screen.getByTestId('login-email'), 'maria@velunora.example');
    await fireEvent.press(screen.getByTestId('login-submit'));
    await settle(400);

    expect(screen.getByText(/Reenviar código em \d+s/)).toBeOnTheScreen();
    await settle(61_000);
    expect(screen.getByText('Reenviar código')).toBeOnTheScreen();
  });
});
