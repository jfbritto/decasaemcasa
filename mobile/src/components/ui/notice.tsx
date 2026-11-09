import { Ionicons } from '@react-native-vector-icons/ionicons';
import type { ComponentProps, ReactNode } from 'react';
import { StyleSheet, View } from 'react-native';

import { AppText } from '@/components/ui/app-text';
import { tones, type Tone } from '@/theme/colors';
import { radius, spacing } from '@/theme/spacing';

type Props = {
  tone: Tone;
  title?: string;
  text?: string | string[];
  icon?: ComponentProps<typeof Ionicons>['name'];
  children?: ReactNode;
  testID?: string;
};

/** Um aviso no tom de um estado (prazo, pedido em análise, endereço secreto). */
export function Notice({ tone, title, text, icon, children, testID }: Props) {
  const palette = tones[tone];
  const paragraphs = text === undefined ? [] : Array.isArray(text) ? text : [text];

  return (
    <View
      testID={testID}
      accessibilityRole={title ? 'summary' : undefined}
      style={[styles.box, { backgroundColor: palette.soft, borderColor: palette.border }]}
    >
      {icon ? <Ionicons name={icon} size={22} color={palette.solid} style={styles.icon} /> : null}
      <View style={styles.body}>
        {title ? (
          <AppText variant="bodyStrong" color={palette.text}>
            {title}
          </AppText>
        ) : null}
        {paragraphs.map((paragraph) => (
          <AppText key={paragraph} variant="body" color={palette.text}>
            {paragraph}
          </AppText>
        ))}
        {children}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  box: {
    flexDirection: 'row',
    gap: spacing.md,
    padding: spacing.lg,
    borderRadius: radius.lg,
    borderWidth: 1,
  },
  icon: {
    marginTop: 1,
  },
  body: {
    flex: 1,
    gap: spacing.sm,
  },
});
