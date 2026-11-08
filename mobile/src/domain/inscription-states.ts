import type { Tone } from '@/theme/colors';
import type { Inscription, InscriptionState } from '@/types/api';

// O que cada estado mostra (spec 2026-11-08-app-mobile-design, AM13): o
// estado vem decidido do servidor (InscriptionView); aqui só o rótulo, o tom
// e os textos, os mesmos da página de status da web
// (resources/views/inscriptions/status.blade.php), sem travessão e com
// "seu lugar na sala".

type StateCopy = {
  label: string;
  tone: Tone;
  /** Parágrafos da mensagem; `{city}` vira a cidade do encontro. */
  message: string[];
};

const FULL_MESSAGE = [
  'Recebemos sua história e ficamos muito felizes com o seu interesse!',
  'No momento, as vagas para este encontro foram preenchidas. Caso haja alguma desistência, entraremos em contato.',
  'Fique de olho nas próximas edições do De Casa em Casa!',
];

export const STATE_COPY: Readonly<Record<InscriptionState, StateCopy>> = {
  pending: {
    label: 'Em curadoria',
    tone: 'curation',
    message: [
      'Recebemos sua história! Aqui do nosso lado, pessoas reais estão conferindo seus dados e logo logo responderemos. Calma, que esse processo é manual. Assim que sua inscrição for aprovada, você poderá fazer sua contribuição. Obrigado pelo interesse em participar desse encontro especial!',
    ],
  },
  pending_full: { label: 'Vagas esgotadas', tone: 'waiting', message: FULL_MESSAGE },
  approved: {
    label: 'Aguardando pagamento',
    tone: 'payment',
    message: ['Tudo pronto! Sua participação foi aprovada. Para garantir seu lugar na sala, envie o comprovante de pagamento abaixo.'],
  },
  approved_full: { label: 'Vagas esgotadas', tone: 'waiting', message: FULL_MESSAGE },
  confirmed: {
    label: 'Confirmado',
    tone: 'success',
    message: ['Que alegria ter você conosco! Prepare o coração!'],
  },
  waitlisted: { label: 'Fila de espera', tone: 'waiting', message: FULL_MESSAGE },
  expired: {
    label: 'Fila de espera',
    tone: 'waiting',
    message: [
      'Não recebemos seu comprovante dentro do prazo e sua inscrição voltou para a fila de espera. Seu lugar na sala foi liberado para a próxima pessoa.',
      'Se um lugar se abrir, sua participação poderá ser aprovada novamente e você receberá um novo aviso, com um novo prazo.',
      'Já fez o Pix e não conseguiu enviar o comprovante a tempo? Fique tranquilo(a): nossa equipe pode reativar sua aprovação.',
    ],
  },
  rejected: {
    label: 'Não aprovada',
    tone: 'danger',
    message: [
      'Agradecemos muito o interesse em participar do encontro De Casa em Casa. Infelizmente, não conseguimos incluir sua participação nesta edição. Fique de olho nas próximas edições!',
    ],
  },
  cancelled_by_admin: {
    label: 'Cancelada',
    tone: 'neutral',
    message: [
      'Obrigado por se inscrever para o encontro De Casa em Casa.',
      'Como os encontros acontecem dentro de casas e com um número bem pequeno de pessoas, algumas inscrições acabam não seguindo para esta edição. Desta vez, não conseguimos confirmar a sua participação no encontro de {city}.',
      'Mas queremos que você saiba que ficamos muito felizes com o seu interesse em caminhar com a gente. Quem sabe em outra casa, em outra mesa, a gente consiga se encontrar.',
      'Seguimos por aqui, na torcida para que nossos caminhos se cruzem em breve. Um abraço da Equipe De Casa em Casa.',
    ],
  },
  cancelled_by_participant: {
    label: 'Cancelada',
    tone: 'neutral',
    message: [
      'Recebemos sua solicitação de cancelamento da inscrição para o encontro De Casa em Casa em {city}.',
      'Obrigado por nos avisar e por ter caminhado com a gente até aqui no processo de inscrição.',
      'Esperamos que em outra oportunidade, em outra casa e ao redor de outra mesa, a gente consiga se encontrar.',
      'Seguimos por aqui. Um abraço da Equipe De Casa em Casa.',
    ],
  },
};

export function stateMessage(inscription: Inscription): string[] {
  const city = inscription.event?.city || inscription.event?.title || '';
  return STATE_COPY[inscription.state].message.map((paragraph) => paragraph.replace('{city}', city));
}

/**
 * A linha curta do cartão na lista: o que importa agora para esta
 * inscrição.
 */
export function stateHint(inscription: Inscription): string | null {
  switch (inscription.state) {
    case 'approved':
      if (inscription.payment?.proof_sent) return 'Comprovante enviado. Aguardando a confirmação da equipe.';
      if (inscription.payment?.paused) return 'Prazo pausado: sua contribuição social está em análise.';
      return 'Envie o comprovante para garantir seu lugar na sala.';
    case 'confirmed':
      return 'Seu lugar na sala está garantido. Veja o endereço e o horário.';
    case 'pending':
      return 'Sua história está sendo lida com carinho pela equipe.';
    case 'expired':
      return 'O prazo do comprovante terminou.';
    default:
      return null;
  }
}
