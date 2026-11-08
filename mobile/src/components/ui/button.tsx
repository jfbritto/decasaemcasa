import { Ionicons } from '@react-native-vector-icons/ionicons';
import type { ComponentProps } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';

import { AppText } from '@/components/ui/app-text';
import { colors } from '@/theme/colors';
import { controlHeight, radius, spacing } from '@/theme/spacing';

type Variant = 'primary' | 'secondary' | 'danger' | 'ghost' | 'whatsapp';

type Props = {
  label: string;
  onPress?: () => void;
  variant?: Variant;
  icon?: ComponentProps<typeof Ionicons>['name'];
  loading?: boolean;
  disabled?: boolean;
  style?: StyleProp<ViewStyle>;
  testID?: string;
  accessibilityHint?: string;
};

const PALETTE: Record<Variant, { bg: string; pressed: string; text: string; border?: string }> = {
  primary: { bg: colors.primary, pressed: colors.primaryPressed, text: colors.onPrimary },
  secondary: { bg: colors.surface, pressed: colors.surfaceSoft, text: colors.text, border: colors.control },
  danger: { bg: colors.surface, pressed: colors.dangerSoft, text: colors.danger, border: colors.danger },
  ghost: { bg: 'transparent', pressed: colors.surfaceSoft, text: colors.primaryPressed },
  whatsapp: { bg: colors.whatsapp, pressed: '#15803D', text: colors.onPrimary },
};

export function Button({ label, onPress, variant = 'primary', icon, loading, disabled, style, testID, accessibilityHint }: Props) {
  const palette = PALETTE[variant];
  const inactive = disabled || loading;

  return (
    <Pressable
      testID={testID}
      accessibilityRole="button"
      accessibilityLabel={label}
      accessibilityHint={accessibilityHint}
      accessibilityState={{ disabled: Boolean(inactive), busy: Boolean(loading) }}
      disabled={inactive}
      onPress={onPress}
      style={({ pressed }) => [
        styles.base,
        {
          backgroundColor: pressed ? palette.pressed : palette.bg,
          borderColor: palette.border ?? 'transparent',
          borderWidth: palette.border ? 1.5 : 0,
          opacity: disabled && !loading ? 0.5 : 1,
        },
        style,
      ]}
    >
      <View style={styles.content}>
        {loading ? (
          <ActivityIndicator color={palette.text} />
        ) : icon ? (
          <Ionicons name={icon} size={20} color={palette.text} />
        ) : null}
        <AppText variant="button" color={palette.text} numberOfLines={1}>
          {label}
        </AppText>
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  base: {
    minHeight: controlHeight,
    borderRadius: radius.full,
    paddingHorizontal: spacing.xl,
    justifyContent: 'center',
  },
  content: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
  },
});
