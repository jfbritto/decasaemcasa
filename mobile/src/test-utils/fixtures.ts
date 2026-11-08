import type { AppConfig, EventDetail, EventSummary, Inscription, Participant } from '@/types/api';

// Dados de teste, sempre fictícios (encontros da "Velunora").

export const participantFixture: Participant = { id: 7, email: 'maria@velunora.example', name: 'Maria Velunora' };

export const eventFixture: EventSummary = {
  id: 3,
  title: 'Encontro na Serra',
  city: 'Serra',
  description: 'Uma noite de música numa casa no alto da Serra.',
  date: '2026-12-12T20:00:00-03:00',
  image_url: null,
  capacity: 20,
  available_spots: 4,
  is_full: false,
};

export const eventDetailFixture: EventDetail = { ...eventFixture, my_inscription: null };

export function inscriptionFixture(overrides: Partial<Inscription> = {}): Inscription {
  return {
    id: 41,
    state: 'pending',
    status: 'pendente',
    full_name: 'Maria Velunora',
    created_at: '2026-11-01T10:00:00-03:00',
    upcoming: true,
    can: { upload_proof: false, request_social: false, cancel: true },
    event: {
      id: 3,
      title: 'Encontro na Serra',
      city: 'Serra',
      description: null,
      date: '2026-12-12T20:00:00-03:00',
      image_url: null,
      address: null,
      arrival_time: null,
    },
    payment: null,
    social_request: null,
    status_url: 'https://decasaemcasa.example/inscricao/abc',
    ...overrides,
  };
}

export function approvedFixture(overrides: Partial<Inscription> = {}): Inscription {
  return inscriptionFixture({
    state: 'approved',
    status: 'aprovado',
    can: { upload_proof: true, request_social: true, cancel: true },
    payment: {
      deadline_at: new Date(Date.now() + 2 * 60 * 60 * 1000).toISOString(),
      deadline_label: '24 horas',
      paused: false,
      proof_sent: false,
      pix: { key: 'pix@velunora.example', holder: 'Turnê Velunora' },
    },
    ...overrides,
  });
}

export const configFixture: AppConfig = {
  manifesto: 'Esse encontro é sobre valorizar cada pessoa individualmente.',
  terms: [
    { title: 'Conduta', text: 'Declaro que as informações são verdadeiras.' },
    { title: 'Contribuição', text: 'O valor mínimo é de R$ 100,00 por participante.' },
  ],
  terms_acceptance: 'Declaro que as informações são verdadeiras e aceito os termos.',
  reference_amount: 100,
  contribution_note: 'Contribua conforme mandar seu coração.',
  support: { whatsapp_number: '+55 27 90000-0000', whatsapp_link: 'https://wa.me/552790000000' },
  links: {
    website: 'https://decasaemcasa.example/inscricao',
    privacy: 'https://decasaemcasa.example/privacidade',
    account_deletion: 'https://decasaemcasa.example/app/excluir-conta',
  },
};
