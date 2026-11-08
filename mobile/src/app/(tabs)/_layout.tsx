import { Ionicons } from '@react-native-vector-icons/ionicons';
import { Tabs } from 'expo-router/js-tabs';
import type { ComponentProps } from 'react';
import type { ColorValue } from 'react-native';

import { brand, colors } from '@/theme/colors';
import { fonts } from '@/theme/typography';

type IconName = ComponentProps<typeof Ionicons>['name'];

function icon(active: IconName, inactive: IconName) {
  function TabIcon({ color, focused, size }: { color: ColorValue; focused: boolean; size: number }) {
    return <Ionicons name={focused ? active : inactive} size={size} color={color} />;
  }
  return TabIcon;
}

// As três abas (spec 2026-11-08-app-mobile-design, AM27). "Encontros" abre
// sem conta; as outras convidam a entrar.
export default function TabsLayout() {
  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: brand[600],
        tabBarInactiveTintColor: colors.textMuted,
        tabBarLabelStyle: { fontFamily: fonts.semibold, fontSize: 12 },
        tabBarStyle: { backgroundColor: colors.surface, borderTopColor: colors.border },
        sceneStyle: { backgroundColor: colors.bg },
      }}
    >
      <Tabs.Screen name="index" options={{ title: 'Encontros', tabBarIcon: icon('home', 'home-outline') }} />
      <Tabs.Screen name="inscriptions" options={{ title: 'Minhas inscrições', tabBarIcon: icon('ticket', 'ticket-outline') }} />
      <Tabs.Screen name="account" options={{ title: 'Perfil', tabBarIcon: icon('person-circle', 'person-circle-outline') }} />
    </Tabs>
  );
}
