import { apiRequest } from '@/services/api';
import { apiUpload } from '@/services/api-upload';
import type { Inscription, InscriptionPayload } from '@/types/api';

// As inscrições do participante (spec 2026-11-08-app-mobile-design, AM11 a
// AM19).

export async function fetchInscriptions(): Promise<Inscription[]> {
  const answer = await apiRequest<{ data: Inscription[] }>('/inscriptions');
  return answer.data;
}

export async function fetchInscription(id: number): Promise<Inscription> {
  return apiRequest<Inscription>(`/inscriptions/${id}`);
}

/** 201 com a inscrição; 409 `already_registered` traz o inscription_id da existente. */
export async function createInscription(payload: InscriptionPayload): Promise<Inscription> {
  return apiRequest<Inscription>('/inscriptions', { method: 'POST', body: payload });
}

export type ProofFile = {
  uri: string;
  name: string;
  mimeType: 'image/jpeg' | 'image/png' | 'application/pdf';
};

/** O comprovante como multipart, pelo upload nativo (services/api-upload). */
export async function uploadPaymentProof(id: number, file: ProofFile): Promise<Inscription> {
  return apiUpload<Inscription>(`/inscriptions/${id}/payment-proof`, {
    uri: file.uri,
    field: 'payment_proof',
    mimeType: file.mimeType,
  });
}

export async function submitSocialRequest(id: number, reason: string, amount: number): Promise<Inscription> {
  return apiRequest<Inscription>(`/inscriptions/${id}/social-request`, {
    method: 'POST',
    body: { reason: reason.trim(), amount },
  });
}

export async function cancelInscription(id: number): Promise<Inscription> {
  return apiRequest<Inscription>(`/inscriptions/${id}/cancel`, { method: 'POST' });
}
