import { Ionicons } from '@react-native-vector-icons/ionicons';
import type { ComponentProps, ReactNode } from 'react';
import { StyleSheet, View } from 'react-native';

import { AppText } from '@/components/ui/app-text';
import { brand, colors } from '@/theme/colors';
import { spacing } from '@/theme/spacing';

type Props = {
  icon: ComponentProps<typeof Ionicons>['name'];
  title: string;
  text?: string;
  children?: ReactNode;
};

export function EmptyState({ icon, title, text, children }: Props) {
  return (
    <View style={styles.wrapper}>
      <View style={styles.iconCircle}>
        <Ionicons name={icon} size={32} color={brand[600]} />
      </View>
      <AppText variant="heading" center>
        {title}
      </AppText>
      {text ? (
        <AppText variant="body" color={colors.textMuted} center>
          {text}
        </AppText>
      ) : null}
      {children}
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: {
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.xxl,
    paddingHorizontal: spacing.lg,
  },
  iconCircle: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: brand[100],
    alignItems: 'center',
    justifyContent: 'center',
  },
});
