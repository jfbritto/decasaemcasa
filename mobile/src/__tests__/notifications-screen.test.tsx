import { fireEvent, screen } from 'expo-router/testing-library';

import * as authService from '@/services/auth';
import * as eventsService from '@/services/events';
import * as inscriptionsService from '@/services/inscriptions';
import * as notificationsService from '@/services/notifications';
import { configFixture, inscriptionFixture, participantFixture } from '@/test-utils/fixtures';
import { renderApp, settle } from '@/test-utils/render-app';
import type { AppNotification } from '@/types/api';

jest.mock('@/services/auth', () => ({
  requestLoginCode: jest.fn(),
  verifyLoginCode: jest.fn(),
  fetchMe: jest.fn(),
  logout: jest.fn(),
  deleteAccount: jest.fn(),
  deviceName: () => 'Celular de teste',
}));
jest.mock('@/services/notifications', () => ({
  fetchNotifications: jest.fn(),
  markNotificationRead: jest.fn(),
  markAllNotificationsRead: jest.fn(),
}));
jest.mock('@/services/events', () => ({ fetchEvents: jest.fn(), fetchEvent: jest.fn(), fetchConfig: jest.fn() }));
jest.mock('@/services/inscriptions', () => ({ fetchInscriptions: jest.fn(), fetchInscription: jest.fn() }));

const auth = jest.mocked(authService);
const notifications = jest.mocked(notificationsService);
const events = jest.mocked(eventsService);
const inscriptions = jest.mocked(inscriptionsService);

const notice = (overrides: Partial<AppNotification>): AppNotification => ({
  id: 1,
  kind: 'approved',
  title: 'Sua participação foi aprovada!',
  body: 'Envie o comprovante em até 24 horas para garantir seu lugar na sala em Serra.',
  inscription_id: 41,
  read: false,
  created_at: new Date(Date.now() - 5 * 60_000).toISOString(),
  ...overrides,
});

beforeEach(() => {
  jest.clearAllMocks();
  auth.fetchMe.mockResolvedValue(participantFixture);
  events.fetchConfig.mockResolvedValue(configFixture);
  events.fetchEvents.mockResolvedValue([]);
  inscriptions.fetchInscription.mockResolvedValue(inscriptionFixture());
  notifications.markNotificationRead.mockResolvedValue();
  notifications.markAllNotificationsRead.mockResolvedValue();
  notifications.fetchNotifications.mockResolvedValue({
    data: [
      notice({ id: 2 }),
      notice({ id: 1, kind: 'received', title: 'Recebemos sua inscrição', body: 'Sua história chegou.', read: true, created_at: new Date(Date.now() - 26 * 3600_000).toISOString() }),
    ],
    unread_count: 1,
  });
});

afterEach(() => {
  jest.useRealTimers();
});

describe('Avisos', () => {
  it('lista cada aviso com o tempo e marca os não lidos', async () => {
    await renderApp('/notifications', { signedIn: true });

    expect(screen.getByText('Sua participação foi aprovada!')).toBeOnTheScreen();
    expect(screen.getByText('há 5 min')).toBeOnTheScreen();
    expect(screen.getByText('Recebemos sua inscrição')).toBeOnTheScreen();
    expect(screen.getByText(/^ontem às \d{2}h\d{2}$/)).toBeOnTheScreen();
    expect(screen.getByTestId('unread-2')).toBeOnTheScreen();
    expect(screen.queryByTestId('unread-1')).not.toBeOnTheScreen();
  });

  it('a aba mostra quantos não foram lidos', async () => {
    await renderApp('/', { signedIn: true });

    expect(screen.getByLabelText('Avisos, 1 não lidos')).toBeOnTheScreen();
  });

  it('tocar no aviso marca como lido e abre a inscrição', async () => {
    const app = await renderApp('/notifications', { signedIn: true });

    await fireEvent.press(screen.getByTestId('notification-2'));
    await settle();

    expect(notifications.markNotificationRead).toHaveBeenCalledWith(2);
    expect(app.getPathname()).toBe('/inscriptions/41');
  });

  it('marcar todos como lidos zera o número da aba', async () => {
    await renderApp('/notifications', { signedIn: true });

    await fireEvent.press(screen.getByTestId('read-all'));
    await settle();

    expect(notifications.markAllNotificationsRead).toHaveBeenCalled();
    expect(screen.queryByTestId('unread-2')).not.toBeOnTheScreen();
    expect(screen.queryByTestId('read-all')).not.toBeOnTheScreen();
    expect(screen.getByLabelText('Avisos')).toBeOnTheScreen();
  });

  it('sem avisos ainda', async () => {
    notifications.fetchNotifications.mockResolvedValue({ data: [], unread_count: 0 });
    await renderApp('/notifications', { signedIn: true });

    expect(screen.getByText('Nenhum aviso ainda')).toBeOnTheScreen();
  });

  it('sem conta convida a entrar e não pede avisos', async () => {
    await renderApp('/notifications');

    expect(screen.getByText('Seus avisos num lugar só')).toBeOnTheScreen();
    expect(notifications.fetchNotifications).not.toHaveBeenCalled();
  });
});
