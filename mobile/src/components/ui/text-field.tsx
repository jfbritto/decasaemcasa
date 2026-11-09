import { forwardRef, useState } from 'react';
import { StyleSheet, TextInput, View, type TextInputProps } from 'react-native';

import { AppText } from '@/components/ui/app-text';
import { colors } from '@/theme/colors';
import { controlHeight, radius, spacing } from '@/theme/spacing';
import { textVariants } from '@/theme/typography';

type Props = TextInputProps & {
  label: string;
  error?: string | null;
  hint?: string;
  /** Mostra "12/1000" embaixo, com o maxLength. */
  counter?: boolean;
  testID?: string;
};

export const TextField = forwardRef<TextInput, Props>(function TextField(
  { label, error, hint, counter, multiline, value, maxLength, style, onFocus, onBlur, ...rest },
  ref,
) {
  const [focused, setFocused] = useState(false);
  const borderColor = error ? colors.danger : focused ? colors.primary : colors.control;

  return (
    <View style={styles.wrapper}>
      <AppText variant="label">{label}</AppText>
      <TextInput
        ref={ref}
        accessibilityLabel={label}
        accessibilityHint={error ?? hint}
        placeholderTextColor={colors.textFaint}
        value={value}
        maxLength={maxLength}
        multiline={multiline}
        textAlignVertical={multiline ? 'top' : 'center'}
        onFocus={(event) => {
          setFocused(true);
          onFocus?.(event);
        }}
        onBlur={(event) => {
          setFocused(false);
          onBlur?.(event);
        }}
        style={[styles.input, multiline ? styles.multiline : null, { borderColor }, style]}
        {...rest}
      />
      {error ? (
        <AppText variant="caption" color={colors.danger}>
          {error}
        </AppText>
      ) : hint ? (
        <AppText variant="caption">{hint}</AppText>
      ) : null}
      {counter && maxLength ? (
        <AppText variant="caption" style={styles.counter}>
          {`${value?.length ?? 0}/${maxLength}`}
        </AppText>
      ) : null}
    </View>
  );
});

const styles = StyleSheet.create({
  wrapper: {
    gap: spacing.xs,
  },
  input: {
    ...textVariants.input,
    minHeight: controlHeight,
    borderWidth: 1.5,
    borderRadius: radius.md,
    paddingHorizontal: spacing.lg,
    backgroundColor: colors.surface,
  },
  multiline: {
    minHeight: 132,
    paddingTop: spacing.md,
    paddingBottom: spacing.md,
  },
  counter: {
    alignSelf: 'flex-end',
  },
});
