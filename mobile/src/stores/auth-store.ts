import { create } from 'zustand';

import { queryClient } from '@/lib/query-client';
import { clearSession, readSession, saveParticipant, saveSession } from '@/lib/secure-session';
import { onUnauthorized, setCurrentToken } from '@/lib/session-token';
import { ApiError } from '@/services/api';
import { deleteAccount as deleteAccountRequest, fetchMe, logout } from '@/services/auth';
import { forgetPushToken, storedPushToken } from '@/services/push';
import type { Participant } from '@/types/api';

// A sessão do app (spec 2026-11-08-app-mobile-design, AM4 e AM8). O token
// mora no chaveiro (secure-session) e em memória (session-token, para o
// cliente HTTP); os dados da conta nunca passam para a próxima pessoa no
// mesmo celular (o cache do React Query é limpo ao sair).

export type AuthStatus = 'restoring' | 'signedOut' | 'signedIn';

type AuthState = {
  status: AuthStatus;
  participant: Participant | null;
  /** Abre o app com a sessão guardada; confere a conta em segundo plano. */
  restore: () => Promise<void>;
  signIn: (token: string, participant: Participant) => Promise<void>;
  /** "Sair": revoga o token no servidor (se der) e limpa o aparelho. */
  signOut: () => Promise<void>;
  /** "Excluir minha conta": lança se o servidor recusar (a conta fica). */
  deleteAccount: () => Promise<void>;
  /** Encerra só no aparelho (token revogado, conta excluída em outro lugar). */
  endSessionLocally: () => Promise<void>;
};

export const useAuthStore = create<AuthState>((set, get) => ({
  status: 'restoring',
  participant: null,

  restore: async () => {
    const session = await readSession();
    if (!session) {
      set({ status: 'signedOut', participant: null });
      return;
    }
    setCurrentToken(session.token);
    set({ status: 'signedIn', participant: session.participant });

    // Sem rede a sessão fica; um 401 encerra (ouvinte do onUnauthorized).
    try {
      const participant = await fetchMe();
      if (get().status === 'signedIn') {
        set({ participant });
        await saveParticipant(participant);
      }
    } catch (error) {
      if (!(error instanceof ApiError)) return;
    }
  },

  signIn: async (token, participant) => {
    queryClient.clear();
    setCurrentToken(token);
    await saveSession({ token, participant });
    set({ status: 'signedIn', participant });
  },

  signOut: async () => {
    const pushToken = await storedPushToken();
    try {
      await logout(pushToken);
    } catch {
      // Sem rede, o token fica no servidor até expirar ou a conta sair; o
      // aparelho sai do mesmo jeito.
    }
    await get().endSessionLocally();
  },

  deleteAccount: async () => {
    await deleteAccountRequest();
    await get().endSessionLocally();
  },

  endSessionLocally: async () => {
    setCurrentToken(null);
    set({ status: 'signedOut', participant: null });
    queryClient.clear();
    await clearSession();
    await forgetPushToken();
  },
}));

onUnauthorized(() => {
  if (useAuthStore.getState().status === 'signedIn') {
    void useAuthStore.getState().endSessionLocally();
  }
});

export const isSignedIn = (state: AuthState) => state.status === 'signedIn';

/** Para os testes: a store como na abertura do app. */
export function resetAuthStore(): void {
  setCurrentToken(null);
  useAuthStore.setState({ status: 'restoring', participant: null });
}
