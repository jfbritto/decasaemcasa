import type { Ionicons } from '@react-native-vector-icons/ionicons';
import type { ComponentProps } from 'react';

import type { Tone } from '@/theme/colors';

// O ícone e o tom de cada tipo de aviso (App\Notifications\Push\ParticipantPush),
// nas cores de estado da página de status (a cor da marca nunca é estado).

type Look = { icon: ComponentProps<typeof Ionicons>['name']; tone: Tone };

const LOOKS: Record<string, Look> = {
  received: { icon: 'mail-open-outline', tone: 'curation' },
  approved: { icon: 'checkmark-circle-outline', tone: 'payment' },
  payment_reminder: { icon: 'time-outline', tone: 'waiting' },
  waitlisted: { icon: 'hourglass-outline', tone: 'waiting' },
  expired: { icon: 'hourglass-outline', tone: 'waiting' },
  event_full: { icon: 'people-outline', tone: 'waiting' },
  rejected: { icon: 'close-circle-outline', tone: 'danger' },
  cancelled_by_admin: { icon: 'remove-circle-outline', tone: 'neutral' },
  cancelled_by_participant: { icon: 'remove-circle-outline', tone: 'neutral' },
  confirmed: { icon: 'home', tone: 'success' },
  social_submitted: { icon: 'heart-half-outline', tone: 'curation' },
  social_approved: { icon: 'heart', tone: 'success' },
  social_rejected: { icon: 'heart-dislike-outline', tone: 'neutral' },
  migrated: { icon: 'swap-horizontal', tone: 'payment' },
  custom_message: { icon: 'chatbubble-ellipses-outline', tone: 'neutral' },
};

const FALLBACK: Look = { icon: 'notifications-outline', tone: 'neutral' };

export function notificationLook(kind: string): Look {
  return LOOKS[kind] ?? FALLBACK;
}

export const NOTIFICATION_KINDS = Object.keys(LOOKS);

/** O selo da aba "Avisos": nada com zero, "9+" a partir de 10 (não cresce). */
export function badgeLabel(unread: number): string | undefined {
  if (unread <= 0) return undefined;
  return unread > 9 ? '9+' : String(unread);
}
