import { apiRequest, UPLOAD_TIMEOUT_MS } from '@/services/api';
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

/**
 * O comprovante como multipart. No React Native, um arquivo do aparelho vai
 * no FormData como { uri, name, type }.
 */
export async function uploadPaymentProof(id: number, file: ProofFile): Promise<Inscription> {
  const form = new FormData();
  form.append('payment_proof', { uri: file.uri, name: file.name, type: file.mimeType } as unknown as Blob);
  return apiRequest<Inscription>(`/inscriptions/${id}/payment-proof`, {
    method: 'POST',
    form,
    timeoutMs: UPLOAD_TIMEOUT_MS,
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
