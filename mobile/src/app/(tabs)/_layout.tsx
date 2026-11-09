import { Ionicons } from '@react-native-vector-icons/ionicons';
import { useQuery } from '@tanstack/react-query';
import { Tabs } from 'expo-router/js-tabs';
import type { ComponentProps } from 'react';
import type { ColorValue } from 'react-native';

import { badgeLabel } from '@/domain/notification-kinds';
import { queryKeys } from '@/lib/query-client';
import { fetchNotifications } from '@/services/notifications';
import { isSignedIn, useAuthStore } from '@/stores/auth-store';
import { brand, colors } from '@/theme/colors';
import { fonts } from '@/theme/typography';

type IconName = ComponentProps<typeof Ionicons>['name'];

function icon(active: IconName, inactive: IconName) {
  function TabIcon({ color, focused, size }: { color: ColorValue; focused: boolean; size: number }) {
    return <Ionicons name={focused ? active : inactive} size={size} color={color} />;
  }
  return TabIcon;
}

// As abas (spec 2026-11-08-app-mobile-design, AM27 e AM41). "Encontros" abre
// sem conta; as outras convidam a entrar. "Avisos" mostra quantos não foram
// lidos (a lista é a mesma da tela, buscada de novo a cada volta ao app).
export default function TabsLayout() {
  const signedIn = useAuthStore(isSignedIn);
  const notifications = useQuery({ queryKey: queryKeys.notifications, queryFn: fetchNotifications, enabled: signedIn });
  const unread = signedIn ? (notifications.data?.unread_count ?? 0) : 0;

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: brand[600],
        tabBarInactiveTintColor: colors.textMuted,
        tabBarLabelStyle: { fontFamily: fonts.semibold, fontSize: 12 },
        tabBarStyle: { backgroundColor: colors.surface, borderTopColor: colors.border },
        tabBarBadgeStyle: { backgroundColor: brand[600], color: colors.onPrimary, fontFamily: fonts.bold, fontSize: 11 },
        sceneStyle: { backgroundColor: colors.bg },
      }}
    >
      <Tabs.Screen name="index" options={{ title: 'Encontros', tabBarIcon: icon('home', 'home-outline') }} />
      <Tabs.Screen name="inscriptions" options={{ title: 'Inscrições', tabBarIcon: icon('ticket', 'ticket-outline') }} />
      <Tabs.Screen
        name="notifications"
        options={{
          title: 'Avisos',
          tabBarIcon: icon('notifications', 'notifications-outline'),
          tabBarBadge: badgeLabel(unread),
          tabBarAccessibilityLabel: unread > 0 ? `Avisos, ${unread} não lidos` : 'Avisos',
        }}
      />
      <Tabs.Screen name="account" options={{ title: 'Perfil', tabBarIcon: icon('person-circle', 'person-circle-outline') }} />
    </Tabs>
  );
}
