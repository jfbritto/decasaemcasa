/**
 * Endereço do servidor do De Casa em Casa (site e API no mesmo host), sem a
 * barra final.
 *
 * Lido na hora e sempre como `process.env.EXPO_PUBLIC_API_URL` (com ponto):
 * o Expo só embute o valor no build nessa forma exata.
 */
export function serverUrl(): string {
  const url = (process.env.EXPO_PUBLIC_API_URL ?? '').replace(/\/+$/, '');
  if (!url && __DEV__ && !warned) {
    warned = true;
    console.warn('EXPO_PUBLIC_API_URL vazio: copie mobile/.env.example para mobile/.env.');
  }
  return url;
}

let warned = false;
