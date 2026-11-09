import * as FileSystem from 'expo-file-system';

import { onUnauthorized, setCurrentToken } from '@/lib/session-token';
import { ApiError, NetworkError } from '@/services/api';
import { uploadPaymentProof } from '@/services/inscriptions';

// O comprovante vai pelo upload nativo do expo-file-system, nunca pelo fetch
// com { uri } no FormData (o fetch do Expo SDK 57 recusa: "Unsupported
// FormDataPart implementation", visto no emulador em 09/11/2026).
const uploadMock = (FileSystem as unknown as { uploadMock: jest.Mock }).uploadMock;

beforeEach(() => {
  process.env.EXPO_PUBLIC_API_URL = 'https://decasaemcasa.example';
  uploadMock.mockReset();
  setCurrentToken('tok-1');
});

describe('uploadPaymentProof', () => {
  it('manda o arquivo como multipart no campo payment_proof, com o token', async () => {
    uploadMock.mockResolvedValue({ status: 200, body: JSON.stringify({ id: 41, state: 'approved' }), headers: {} });

    const answer = await uploadPaymentProof(41, { uri: 'file:///cache/c.jpg', name: 'comprovante.jpg', mimeType: 'image/jpeg' });

    expect(answer).toEqual({ id: 41, state: 'approved' });
    const [url, options] = uploadMock.mock.calls[0];
    expect(url).toBe('https://decasaemcasa.example/api/v1/inscriptions/41/payment-proof');
    expect(options).toMatchObject({
      httpMethod: 'POST',
      uploadType: FileSystem.UploadType.MULTIPART,
      fieldName: 'payment_proof',
      mimeType: 'image/jpeg',
      headers: { Accept: 'application/json', Authorization: 'Bearer tok-1' },
    });
    expect(uploadMock.mock.instances[0]).toMatchObject({ uri: 'file:///cache/c.jpg' });
  });

  it('o 422 da API volta como ApiError com o code', async () => {
    uploadMock.mockResolvedValue({
      status: 422,
      body: JSON.stringify({ message: 'O prazo terminou.', code: 'deadline_passed' }),
      headers: {},
    });

    const error = (await uploadPaymentProof(41, { uri: 'file:///c.pdf', name: 'c.pdf', mimeType: 'application/pdf' }).catch((e) => e)) as ApiError;

    expect(error).toBeInstanceOf(ApiError);
    expect(error.code).toBe('deadline_passed');
  });

  it('falha do envio é NetworkError; 401 encerra a sessão', async () => {
    uploadMock.mockRejectedValueOnce(new Error('timeout'));
    await expect(uploadPaymentProof(41, { uri: 'file:///c.jpg', name: 'c.jpg', mimeType: 'image/jpeg' })).rejects.toBeInstanceOf(NetworkError);

    const listener = jest.fn();
    const stop = onUnauthorized(listener);
    uploadMock.mockResolvedValueOnce({ status: 401, body: '{"message":"Unauthenticated."}', headers: {} });
    await uploadPaymentProof(41, { uri: 'file:///c.jpg', name: 'c.jpg', mimeType: 'image/jpeg' }).catch(() => undefined);
    expect(listener).toHaveBeenCalledTimes(1);
    stop();
  });
});
