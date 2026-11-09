import { STATE_COPY, stateHint, stateMessage } from '@/domain/inscription-states';
import { approvedFixture, inscriptionFixture } from '@/test-utils/fixtures';
import type { InscriptionState } from '@/types/api';

// Os estados que o servidor manda (App\Inscriptions\InscriptionView).
const SERVER_STATES: InscriptionState[] = [
  'pending',
  'pending_full',
  'approved',
  'approved_full',
  'confirmed',
  'waitlisted',
  'expired',
  'rejected',
  'cancelled_by_admin',
  'cancelled_by_participant',
];

describe('STATE_COPY', () => {
  it('cobre cada estado do servidor, e só eles', () => {
    expect(Object.keys(STATE_COPY).sort()).toEqual([...SERVER_STATES].sort());
  });

  it('cor de marca nunca é estado: os tons são os da página de status da web', () => {
    expect(STATE_COPY.pending.tone).toBe('curation');
    expect(STATE_COPY.approved.tone).toBe('payment');
    expect(STATE_COPY.confirmed.tone).toBe('success');
    expect(STATE_COPY.waitlisted.tone).toBe('waiting');
    expect(STATE_COPY.rejected.tone).toBe('danger');
    expect(STATE_COPY.cancelled_by_participant.tone).toBe('neutral');
  });

  it('a mensagem leva a cidade do encontro', () => {
    const message = stateMessage(inscriptionFixture({ state: 'cancelled_by_participant' }));
    expect(message[0]).toContain('em Serra.');
    expect(message.join(' ')).not.toContain('{city}');
  });
});

describe('stateHint', () => {
  it('aprovado: o que falta fazer', () => {
    expect(stateHint(approvedFixture())).toBe('Envie o comprovante para garantir seu lugar na sala.');
    expect(stateHint(approvedFixture({ payment: { ...approvedFixture().payment!, proof_sent: true } }))).toBe(
      'Comprovante enviado. Aguardando a confirmação da equipe.',
    );
    expect(stateHint(approvedFixture({ payment: { ...approvedFixture().payment!, paused: true } }))).toBe(
      'Prazo pausado: sua contribuição social está em análise.',
    );
  });

  it('sem dica para quem não tem o que fazer', () => {
    expect(stateHint(inscriptionFixture({ state: 'rejected' }))).toBeNull();
  });
});
