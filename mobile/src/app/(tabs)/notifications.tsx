import { Ionicons } from '@react-native-vector-icons/ionicons';
import { useMutation, useQuery } from '@tanstack/react-query';
import { useFocusEffect } from 'expo-router';
import { useCallback } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, View } from 'react-native';

import { LoadError } from '@/components/load-error';
import { PushCard } from '@/components/push-card';
import { SignInPrompt } from '@/components/sign-in-prompt';
import { AppText } from '@/components/ui/app-text';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/ui/empty-state';
import { Screen } from '@/components/ui/screen';
import { notificationLook } from '@/domain/notification-kinds';
import { formatRelative } from '@/lib/format';
import { openNotification } from '@/lib/open-notification';
import { queryClient, queryKeys } from '@/lib/query-client';
import { markAllNotificationsRead, fetchNotifications } from '@/services/notifications';
import { isSignedIn, useAuthStore } from '@/stores/auth-store';
import { brand, colors, tones } from '@/theme/colors';
import { radius, spacing } from '@/theme/spacing';
import type { AppNotification, NotificationList } from '@/types/api';

/**
 * "Avisos" (spec 2026-11-08-app-mobile-design, AM41 a AM43): cada e-mail que
 * a pessoa recebeu, também aqui, do mais novo ao mais antigo. O toque abre a
 * inscrição; o push chega no celular de quem ativou os avisos.
 */
export default function NotificationsScreen() {
  const signedIn = useAuthStore(isSignedIn);

  if (!signedIn) {
    return (
      <Screen>
        <AppText variant="title" accessibilityRole="header">
          Avisos
        </AppText>
        <SignInPrompt
          title="Seus avisos num lugar só"
          text="Entre com seu e-mail para ver aqui cada novidade da sua inscrição e receber os avisos no celular."
          next="/notifications"
        />
      </Screen>
    );
  }

  return <SignedInNotifications />;
}

function SignedInNotifications() {
  const query = useQuery({ queryKey: queryKeys.notifications, queryFn: fetchNotifications });
  // As abas ficam montadas: cada volta para "Avisos" busca de novo (visto no
  // emulador: sem isso, um aviso novo só aparecia puxando a tela).
  const { refetch } = query;
  useFocusEffect(
    useCallback(() => {
      void refetch();
    }, [refetch]),
  );
  const readAll = useMutation({
    mutationFn: markAllNotificationsRead,
    onMutate: () => {
      queryClient.setQueryData<NotificationList>(queryKeys.notifications, (old) =>
        old ? { data: old.data.map((item) => ({ ...item, read: true })), unread_count: 0 } : old,
      );
    },
    onError: () => void query.refetch(),
  });
  const unread = query.data?.unread_count ?? 0;

  return (
    <Screen onRefresh={() => void query.refetch()} refreshing={query.isRefetching} testID="notifications-screen">
      <View style={styles.header}>
        <AppText variant="title" accessibilityRole="header">
          Avisos
        </AppText>
        {unread > 0 ? (
          <Button label="Marcar todos como lidos" variant="ghost" onPress={() => readAll.mutate()} style={styles.readAll} testID="read-all" />
        ) : null}
      </View>

      <PushCard />

      {query.isPending ? (
        <ActivityIndicator color={colors.primary} style={styles.loading} />
      ) : query.isError ? (
        <LoadError error={query.error} onRetry={() => void query.refetch()} />
      ) : query.data.data.length === 0 ? (
        <EmptyState
          icon="notifications-outline"
          title="Nenhum aviso ainda"
          text="Cada novidade da sua inscrição aparece aqui e no seu e-mail: curadoria, aprovação, confirmação."
        />
      ) : (
        <View style={styles.list}>
          {query.data.data.map((notification) => (
            <NotificationRow key={notification.id} notification={notification} />
          ))}
        </View>
      )}
    </Screen>
  );
}

function NotificationRow({ notification }: { notification: AppNotification }) {
  const look = notificationLook(notification.kind);
  const tone = tones[look.tone];

  return (
    <Pressable
      testID={`notification-${notification.id}`}
      accessibilityRole="button"
      accessibilityLabel={`${notification.read ? '' : 'Não lido. '}${notification.title}. ${notification.body}`}
      onPress={() => openNotification(notification)}
      style={({ pressed }) => [
        styles.row,
        { backgroundColor: notification.read ? colors.surface : brand[50], borderColor: notification.read ? colors.border : brand[200] },
        pressed ? styles.pressed : null,
      ]}
    >
      <View style={[styles.icon, { backgroundColor: tone.soft }]}>
        <Ionicons name={look.icon} size={22} color={tone.solid} />
      </View>
      <View style={styles.body}>
        <View style={styles.titleLine}>
          <AppText variant={notification.read ? 'label' : 'bodyStrong'} style={styles.flex} numberOfLines={2}>
            {notification.title}
          </AppText>
          {notification.read ? null : <View style={styles.dot} testID={`unread-${notification.id}`} />}
        </View>
        <AppText variant="body" color={colors.textMuted}>
          {notification.body}
        </AppText>
        <AppText variant="caption">{formatRelative(notification.created_at)}</AppText>
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
  },
  readAll: {
    paddingHorizontal: spacing.sm,
    minHeight: 40,
  },
  loading: {
    marginTop: spacing.xxl,
  },
  list: {
    gap: spacing.sm,
  },
  row: {
    flexDirection: 'row',
    gap: spacing.md,
    padding: spacing.lg,
    borderRadius: radius.lg,
    borderWidth: 1,
  },
  pressed: {
    opacity: 0.85,
  },
  icon: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  body: {
    flex: 1,
    gap: spacing.xxs,
  },
  titleLine: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  flex: {
    flex: 1,
  },
  dot: {
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: brand[600],
  },
});
