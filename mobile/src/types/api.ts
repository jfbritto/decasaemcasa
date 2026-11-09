// O formato da API v1 (spec 2026-11-08-app-mobile-design, AM9 a AM16): os
// Resources do Laravel em app/Http/Resources/Api/V1.

export type Participant = {
  id: number;
  email: string;
  name: string | null;
};

export type EventSummary = {
  id: number;
  title: string;
  city: string | null;
  description: string | null;
  /** ISO 8601 com fuso. */
  date: string | null;
  image_url: string | null;
  /** Nulo quando o encontro não tem limite de vagas. */
  capacity: number | null;
  available_spots: number | null;
  is_full: boolean;
};

export type EventDetail = EventSummary & {
  my_inscription: { id: number; state: InscriptionState } | null;
};

export type InscriptionState =
  | 'pending'
  | 'pending_full'
  | 'approved'
  | 'approved_full'
  | 'confirmed'
  | 'waitlisted'
  | 'expired'
  | 'rejected'
  | 'cancelled_by_admin'
  | 'cancelled_by_participant';

export type SocialRequest = {
  status: 'pendente' | 'aprovado' | 'rejeitado';
  reason: string | null;
  /** Decimal em texto ("35.50"). */
  amount: string | null;
  admin_message: string | null;
  submitted_at: string | null;
};

export type Inscription = {
  id: number;
  state: InscriptionState;
  status: string;
  full_name: string;
  created_at: string | null;
  upcoming: boolean;
  can: {
    upload_proof: boolean;
    request_social: boolean;
    cancel: boolean;
  };
  event: {
    id: number;
    title: string;
    city: string | null;
    description: string | null;
    date: string | null;
    image_url: string | null;
    /** Só para confirmado. */
    address: string | null;
    arrival_time: string | null;
  } | null;
  /** Só quando o comprovante pode ser enviado. */
  payment: {
    deadline_at: string | null;
    deadline_label: string | null;
    paused: boolean;
    proof_sent: boolean;
    pix: { key: string; holder: string | null } | null;
  } | null;
  social_request: SocialRequest | null;
  status_url: string;
};

/** Um aviso da aba "Avisos" (o par de um e-mail recebido; AM41 a AM43). */
export type AppNotification = {
  id: number;
  kind: string;
  title: string;
  body: string;
  inscription_id: number | null;
  read: boolean;
  created_at: string | null;
};

export type NotificationList = {
  data: AppNotification[];
  unread_count: number;
};

export type AppConfig = {
  manifesto: string;
  terms: { title: string; text: string }[];
  terms_acceptance: string;
  reference_amount: number;
  contribution_note: string;
  support: { whatsapp_number: string | null; whatsapp_link: string | null };
  links: { website: string; privacy: string; account_deletion: string };
};

export type InscriptionPayload = {
  event_id: number;
  full_name: string;
  cpf: string;
  /** AAAA-MM-DD */
  birth_date: string;
  city_neighborhood: string;
  whatsapp: string;
  instagram: string;
  motivation: string;
  terms_accepted: boolean;
};
