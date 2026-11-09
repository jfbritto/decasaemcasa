import * as Clipboard from 'expo-clipboard';
import * as DocumentPicker from 'expo-document-picker';
import * as ImagePicker from 'expo-image-picker';
import { act, fireEvent, screen } from 'expo-router/testing-library';
import { Alert, Linking } from 'react-native';

import { ApiError } from '@/services/api';
import * as authService from '@/services/auth';
import * as eventsService from '@/services/events';
import * as inscriptionsService from '@/services/inscriptions';
import {
  approvedFixture,
  configFixture,
  eventDetailFixture,
  inscriptionFixture,
  participantFixture,
} from '@/test-utils/fixtures';
import { renderApp, settle } from '@/test-utils/render-app';

jest.mock('@/services/auth', () => ({
  requestLoginCode: jest.fn(),
  verifyLoginCode: jest.fn(),
  fetchMe: jest.fn(),
  logout: jest.fn(),
  deleteAccount: jest.fn(),
  deviceName: () => 'Celular de teste',
}));
jest.mock('@/services/notifications', () => ({
  fetchNotifications: jest.fn(async () => ({ data: [], unread_count: 0 })),
  markNotificationRead: jest.fn(async () => undefined),
  markAllNotificationsRead: jest.fn(async () => undefined),
}));
jest.mock('@/services/events', () => ({ fetchEvents: jest.fn(), fetchEvent: jest.fn(), fetchConfig: jest.fn() }));
jest.mock('@/services/inscriptions', () => ({
  fetchInscriptions: jest.fn(),
  fetchInscription: jest.fn(),
  createInscription: jest.fn(),
  uploadPaymentProof: jest.fn(),
  submitSocialRequest: jest.fn(),
  cancelInscription: jest.fn(),
}));

const auth = jest.mocked(authService);
const events = jest.mocked(eventsService);
const inscriptions = jest.mocked(inscriptionsService);

/** Aperta o botão de um Alert.alert pelo texto (dentro do act: o onPress muda estado). */
async function pressAlertButton(alert: jest.SpyInstance, text: string) {
  const buttons = alert.mock.calls.at(-1)?.[2] as { text: string; onPress?: () => unknown }[];
  await act(async () => {
    await buttons.find((button) => button.text === text)?.onPress?.();
  });
}

let alert: jest.SpyInstance;

beforeEach(() => {
  jest.clearAllMocks();
  alert = jest.spyOn(Alert, 'alert').mockImplementation(() => undefined);
  auth.fetchMe.mockResolvedValue(participantFixture);
  events.fetchConfig.mockResolvedValue(configFixture);
  events.fetchEvent.mockResolvedValue(eventDetailFixture);
  events.fetchEvents.mockResolvedValue([]);
});

afterEach(() => {
  alert.mockRestore();
  jest.useRealTimers();
});

describe('Minhas inscrições', () => {
  it('separa os próximos encontros dos anteriores', async () => {
    inscriptions.fetchInscriptions.mockResolvedValue([
      approvedFixture({ id: 1 }),
      inscriptionFixture({ id: 2, state: 'confirmed', upcoming: false, event: { ...inscriptionFixture().event!, city: 'Guarapari' } }),
    ]);
    await renderApp('/inscriptions', { signedIn: true });

    expect(screen.getByText('Próximos encontros')).toBeOnTheScreen();
    expect(screen.getByText('Aguardando pagamento')).toBeOnTheScreen();
    expect(screen.getByText('Envie o comprovante para garantir seu lugar na sala.')).toBeOnTheScreen();
    expect(screen.getByText('Encontros anteriores')).toBeOnTheScreen();
    expect(screen.getByText('Guarapari')).toBeOnTheScreen();
  });
});

describe('A inscrição', () => {
  it('em curadoria: a mensagem da web e o cancelar', async () => {
    inscriptions.fetchInscription.mockResolvedValue(inscriptionFixture());
    await renderApp('/inscriptions/41', { signedIn: true });

    expect(screen.getByText('Em curadoria')).toBeOnTheScreen();
    expect(screen.getByText(/Recebemos sua história! Aqui do nosso lado/)).toBeOnTheScreen();
    expect(screen.queryByTestId('payment-card')).not.toBeOnTheScreen();
    expect(screen.getByTestId('cancel-button')).toBeOnTheScreen();
  });

  it('aprovado: prazo com contagem, Pix com copiar e a contribuição social', async () => {
    inscriptions.fetchInscription.mockResolvedValue(approvedFixture());
    await renderApp('/inscriptions/41', { signedIn: true });

    expect(screen.getByText(/Prazo para envio do comprovante: até às/)).toBeOnTheScreen();
    expect(screen.getByTestId('countdown')).toHaveTextContent(/Tempo restante: \d+h \d{2}min \d{2}s/);
    expect(screen.getByTestId('pix-key')).toHaveTextContent('pix@velunora.example');

    await fireEvent.press(screen.getByTestId('copy-pix'));
    await settle();
    expect(Clipboard.setStringAsync).toHaveBeenCalledWith('pix@velunora.example');
    expect(screen.getByText('Chave copiada!')).toBeOnTheScreen();

    expect(screen.getByText('Não consegue contribuir com o valor de referência neste momento?')).toBeOnTheScreen();
  });

  it('ao zerar o prazo avisa e busca a inscrição de novo depois do cron', async () => {
    inscriptions.fetchInscription.mockResolvedValue(
      approvedFixture({ payment: { ...approvedFixture().payment!, deadline_at: new Date(Date.now() + 3_000).toISOString() } }),
    );
    await renderApp('/inscriptions/41', { signedIn: true });
    expect(inscriptions.fetchInscription).toHaveBeenCalledTimes(1);

    await settle(4_000);
    expect(screen.getByTestId('countdown-ended')).toBeOnTheScreen();

    inscriptions.fetchInscription.mockResolvedValue(inscriptionFixture({ state: 'expired', status: 'fila_de_espera' }));
    await settle(76_000);
    expect(inscriptions.fetchInscription).toHaveBeenCalledTimes(2);
    expect(screen.getByText(/Não recebemos seu comprovante dentro do prazo/)).toBeOnTheScreen();
  });

  it('comprovante pela câmera: a foto reduzida vai para a API', async () => {
    inscriptions.fetchInscription.mockResolvedValue(approvedFixture());
    inscriptions.uploadPaymentProof.mockResolvedValue(approvedFixture({ payment: { ...approvedFixture().payment!, proof_sent: true } }));
    jest.mocked(ImagePicker.launchCameraAsync).mockResolvedValue({
      canceled: false,
      assets: [{ uri: 'file:///camera/foto.jpg', width: 4000, height: 3000 }],
    } as never);
    await renderApp('/inscriptions/41', { signedIn: true });

    await fireEvent.press(screen.getByTestId('proof-camera'));
    await settle();
    await settle();

    expect(inscriptions.uploadPaymentProof).toHaveBeenCalledWith(41, {
      uri: 'file:///cache/reduzida.jpg',
      name: 'comprovante.jpg',
      mimeType: 'image/jpeg',
    });
    expect(screen.getByTestId('upload-success')).toBeOnTheScreen();
    expect(screen.getByTestId('proof-sent')).toBeOnTheScreen();
  });

  it('PDF acima de 5 MB nem sai do celular', async () => {
    inscriptions.fetchInscription.mockResolvedValue(approvedFixture());
    jest.mocked(DocumentPicker.getDocumentAsync).mockResolvedValue({
      canceled: false,
      assets: [{ uri: 'file:///docs/extrato.pdf', name: 'extrato.pdf', mimeType: 'application/pdf', size: 6 * 1024 * 1024 }],
    } as never);
    await renderApp('/inscriptions/41', { signedIn: true });

    await fireEvent.press(screen.getByTestId('proof-file'));
    await settle();

    expect(inscriptions.uploadPaymentProof).not.toHaveBeenCalled();
    expect(alert).toHaveBeenCalledWith('Comprovante', 'O arquivo deve ter no máximo 5MB.');
  });

  it('prazo vencido entre abrir e enviar: a mensagem da API', async () => {
    inscriptions.fetchInscription.mockResolvedValue(approvedFixture());
    inscriptions.uploadPaymentProof.mockRejectedValue(
      new ApiError(422, 'O prazo para envio do comprovante terminou e sua inscrição voltou para a fila de espera.', undefined, 'deadline_passed'),
    );
    jest.mocked(ImagePicker.launchImageLibraryAsync).mockResolvedValue({
      canceled: false,
      assets: [{ uri: 'file:///galeria/print.png', width: 800, height: 1600 }],
    } as never);
    await renderApp('/inscriptions/41', { signedIn: true });

    await fireEvent.press(screen.getByTestId('proof-library'));
    await settle();
    await settle();

    expect(alert).toHaveBeenCalledWith(
      'Comprovante não enviado',
      'O prazo para envio do comprovante terminou e sua inscrição voltou para a fila de espera.',
    );
    expect(inscriptions.fetchInscription).toHaveBeenCalledTimes(2);
  });

  it('pedido social em análise pausa o prazo', async () => {
    inscriptions.fetchInscription.mockResolvedValue(
      approvedFixture({
        can: { upload_proof: true, request_social: false, cancel: true },
        payment: { ...approvedFixture().payment!, paused: true },
        social_request: { status: 'pendente', reason: 'Estou sem trabalho neste mês.', amount: '40.00', admin_message: null, submitted_at: null },
      }),
    );
    await renderApp('/inscriptions/41', { signedIn: true });

    expect(screen.getByTestId('deadline-paused')).toBeOnTheScreen();
    expect(screen.queryByTestId('countdown')).not.toBeOnTheScreen();
    expect(screen.getByText('Solicitação de contribuição social em análise')).toBeOnTheScreen();
    expect(screen.getByText('Valor proposto: R$ 40,00')).toBeOnTheScreen();
  });

  it('confirmado: endereço com o aviso de segredo, sem cancelar', async () => {
    inscriptions.fetchInscription.mockResolvedValue(
      inscriptionFixture({
        state: 'confirmed',
        status: 'confirmado',
        can: { upload_proof: false, request_social: false, cancel: false },
        event: { ...inscriptionFixture().event!, address: 'Rua das Velas, 10, Serra', arrival_time: '19h30' },
      }),
    );
    await renderApp('/inscriptions/41', { signedIn: true });

    expect(screen.getByText('Confirmado')).toBeOnTheScreen();
    expect(screen.getByText('Rua das Velas, 10, Serra')).toBeOnTheScreen();
    expect(screen.getByText('19h30')).toBeOnTheScreen();
    expect(screen.getByText(/Não divulgue este endereço/)).toBeOnTheScreen();
    expect(screen.queryByTestId('cancel-button')).not.toBeOnTheScreen();
  });

  it('cancelar pede confirmação e mostra o novo estado', async () => {
    inscriptions.fetchInscription.mockResolvedValue(inscriptionFixture());
    inscriptions.cancelInscription.mockResolvedValue(
      inscriptionFixture({ state: 'cancelled_by_participant', status: 'cancelado', can: { upload_proof: false, request_social: false, cancel: false } }),
    );
    await renderApp('/inscriptions/41', { signedIn: true });

    await fireEvent.press(screen.getByTestId('cancel-button'));
    expect(alert).toHaveBeenCalledWith('Cancelar inscrição', expect.stringContaining('não pode ser desfeita'), expect.any(Array));
    expect(inscriptions.cancelInscription).not.toHaveBeenCalled();

    await pressAlertButton(alert, 'Sim, cancelar');
    await settle();

    expect(inscriptions.cancelInscription).toHaveBeenCalledWith(41);
    expect(screen.getByText('Cancelada')).toBeOnTheScreen();
    expect(screen.queryByTestId('cancel-button')).not.toBeOnTheScreen();
  });

  it('sem sessão a inscrição não abre', async () => {
    inscriptions.fetchInscription.mockResolvedValue(inscriptionFixture());
    const app = await renderApp('/inscriptions/41');

    expect(app.getPathname()).not.toBe('/inscriptions/41');
    expect(inscriptions.fetchInscription).not.toHaveBeenCalled();
  });
});

describe('Contribuição social', () => {
  it('confere a justificativa e o valor antes de enviar', async () => {
    inscriptions.fetchInscription.mockResolvedValue(approvedFixture());
    inscriptions.submitSocialRequest.mockResolvedValue(approvedFixture());
    await renderApp('/inscriptions/41/social', { signedIn: true });

    await fireEvent.changeText(screen.getByTestId('social-reason'), 'curta');
    await fireEvent.press(screen.getByTestId('social-submit'));
    await settle();
    expect(screen.getByText('Sua justificativa deve ter pelo menos 20 caracteres.')).toBeOnTheScreen();
    expect(screen.getByText('Informe o valor que você consegue contribuir.')).toBeOnTheScreen();
    expect(inscriptions.submitSocialRequest).not.toHaveBeenCalled();

    await fireEvent.changeText(screen.getByTestId('social-reason'), 'Estou sem trabalho neste mês e consigo pouco.');
    await fireEvent.changeText(screen.getByTestId('social-amount'), '35,50');
    await fireEvent.press(screen.getByTestId('social-submit'));
    await settle();

    expect(inscriptions.submitSocialRequest).toHaveBeenCalledWith(41, 'Estou sem trabalho neste mês e consigo pouco.', 35.5);
    expect(alert).toHaveBeenCalledWith('Solicitação enviada!', expect.any(String));
  });
});

describe('Inscrever-se pelo app', () => {
  async function fillData() {
    await fireEvent.changeText(screen.getByTestId('field-cpf'), '52998224725');
    await fireEvent.changeText(screen.getByTestId('field-birth_date'), '04031992');
    await fireEvent.changeText(screen.getByTestId('field-city_neighborhood'), 'Centro, Vitória');
    await fireEvent.changeText(screen.getByTestId('field-whatsapp'), '27999990000');
    await fireEvent.changeText(screen.getByTestId('field-motivation'), 'Quero viver esse encontro com o coração aberto.');
  }

  it('manifesto, dados (com as regras da web) e termos', async () => {
    inscriptions.createInscription.mockResolvedValue(inscriptionFixture({ id: 77 }));
    inscriptions.fetchInscription.mockResolvedValue(inscriptionFixture({ id: 77 }));
    const app = await renderApp('/events/3/register', { signedIn: true });

    expect(screen.getByText(`"${configFixture.manifesto}"`)).toBeOnTheScreen();
    await fireEvent.press(screen.getByTestId('register-next'));
    await settle();

    // O nome vem da conta; o CPF inválido não passa.
    expect(screen.getByTestId('field-full_name').props.value).toBe('Maria Velunora');
    await fillData();
    await fireEvent.changeText(screen.getByTestId('field-cpf'), '12345678900');
    await fireEvent.press(screen.getByTestId('register-next'));
    await settle();
    expect(screen.getByText('O CPF informado é inválido.')).toBeOnTheScreen();

    await fireEvent.changeText(screen.getByTestId('field-cpf'), '52998224725');
    expect(screen.getByTestId('field-cpf').props.value).toBe('529.982.247-25');
    await fireEvent.press(screen.getByTestId('register-next'));
    await settle();

    expect(screen.getByText('Termo de responsabilidade')).toBeOnTheScreen();
    expect(screen.getByText(/Contribuição: O valor mínimo é de R\$ 100,00 por participante\./)).toBeOnTheScreen();
    await fireEvent.press(screen.getByTestId('terms-checkbox'));
    await fireEvent.press(screen.getByTestId('register-submit'));
    await settle();
    await settle();

    expect(inscriptions.createInscription).toHaveBeenCalledWith({
      event_id: 3,
      full_name: 'Maria Velunora',
      cpf: '529.982.247-25',
      birth_date: '1992-03-04',
      city_neighborhood: 'Centro, Vitória',
      whatsapp: '(27) 99999-0000',
      instagram: '',
      motivation: 'Quero viver esse encontro com o coração aberto.',
      terms_accepted: true,
    });
    expect(app.getPathname()).toBe('/inscriptions/77');
    expect(screen.getByText('Inscrição realizada com sucesso!')).toBeOnTheScreen();
  });

  it('já inscrito(a) no encontro: abre a inscrição que existe', async () => {
    inscriptions.createInscription.mockRejectedValue(
      new ApiError(409, 'Você já tem uma inscrição para este encontro.', undefined, 'already_registered', { inscription_id: 41 }),
    );
    inscriptions.fetchInscription.mockResolvedValue(inscriptionFixture());
    const app = await renderApp('/events/3/register', { signedIn: true });

    await fireEvent.press(screen.getByTestId('register-next'));
    await settle();
    await fillData();
    await fireEvent.press(screen.getByTestId('register-next'));
    await settle();
    await fireEvent.press(screen.getByTestId('terms-checkbox'));
    await fireEvent.press(screen.getByTestId('register-submit'));
    await settle();
    await settle();

    expect(alert).toHaveBeenCalledWith('Você já está inscrito(a)', expect.any(String));
    expect(app.getPathname()).toBe('/inscriptions/41');
  });

  it('erro de campo da API volta para os dados', async () => {
    inscriptions.createInscription.mockRejectedValue(
      new ApiError(422, 'Erro', { birth_date: ['A data de nascimento deve ser anterior a hoje.'] }),
    );
    await renderApp('/events/3/register', { signedIn: true });

    await fireEvent.press(screen.getByTestId('register-next'));
    await settle();
    await fillData();
    await fireEvent.press(screen.getByTestId('register-next'));
    await settle();
    await fireEvent.press(screen.getByTestId('terms-checkbox'));
    await fireEvent.press(screen.getByTestId('register-submit'));
    await settle();

    expect(screen.getByTestId('field-birth_date')).toBeOnTheScreen();
    expect(screen.getByText('A data de nascimento deve ser anterior a hoje.')).toBeOnTheScreen();
  });
});

describe('Perfil', () => {
  it('excluir a conta pede confirmação, explica as inscrições e sai', async () => {
    auth.deleteAccount.mockResolvedValue();
    await renderApp('/account', { signedIn: true });

    expect(screen.getByTestId('account-email')).toHaveTextContent('maria@velunora.example');
    await fireEvent.press(screen.getByTestId('delete-account'));
    expect(alert).toHaveBeenCalledWith('Excluir minha conta', expect.stringContaining('Suas inscrições continuam com a equipe'), expect.any(Array));

    await pressAlertButton(alert, 'Excluir');
    await settle();

    expect(auth.deleteAccount).toHaveBeenCalled();
    expect(screen.getByText('Sua área no De Casa em Casa')).toBeOnTheScreen();
  });

  it('sem conta mostra o convite e os links', async () => {
    await renderApp('/account');

    expect(screen.getByText('Sua área no De Casa em Casa')).toBeOnTheScreen();
    expect(screen.getByText('Política de privacidade')).toBeOnTheScreen();
    expect(screen.getByText('Falar com a equipe')).toBeOnTheScreen();
    expect(screen.queryByTestId('delete-account')).not.toBeOnTheScreen();
  });

  it('o crédito "Desenvolvido em casa por HelpFlux" abre o site da HelpFlux', async () => {
    const openURL = jest.spyOn(Linking, 'openURL').mockResolvedValue(true);
    await renderApp('/account');

    expect(screen.getByText(/^Desenvolvido em casa por HelpFlux$/)).toBeOnTheScreen();
    await fireEvent.press(screen.getByTestId('credit-link'));

    expect(openURL).toHaveBeenCalledWith('https://helpflux.com.br/');
    openURL.mockRestore();
  });
});
