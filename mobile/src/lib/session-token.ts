// O token da sessão em memória, fora da store, para o cliente HTTP ler sem
// importar a store (que importa os serviços): sem ciclo de imports.

let token: string | null = null;
const unauthorizedListeners = new Set<() => void>();

export function currentToken(): string | null {
  return token;
}

export function setCurrentToken(value: string | null): void {
  token = value;
}

/** A sessão escuta: um 401 com token encerra a sessão no aparelho. */
export function onUnauthorized(listener: () => void): () => void {
  unauthorizedListeners.add(listener);
  return () => {
    unauthorizedListeners.delete(listener);
  };
}

export function notifyUnauthorized(): void {
  for (const listener of unauthorizedListeners) {
    try {
      listener();
    } catch (error) {
      console.warn('[sessão] um ouvinte do 401 falhou', error);
    }
  }
}
