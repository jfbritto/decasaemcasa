import { Text, type TextProps } from 'react-native';

import { textVariants, type TextVariant } from '@/theme/typography';

type Props = TextProps & {
  variant?: TextVariant;
  color?: string;
  center?: boolean;
};

/** Todo texto do app passa por aqui: a fonte e o tamanho vêm da variante. */
export function AppText({ variant = 'body', color, center, style, ...rest }: Props) {
  return (
    <Text
      maxFontSizeMultiplier={1.6}
      {...rest}
      style={[textVariants[variant], color ? { color } : null, center ? { textAlign: 'center' } : null, style]}
    />
  );
}
