import type { ReactNode } from 'react';
import { KeyboardAvoidingView, Platform, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { colors } from '@/theme/colors';
import { spacing } from '@/theme/spacing';

type Props = {
  children: ReactNode;
  /** Puxar para atualizar. */
  onRefresh?: () => void;
  refreshing?: boolean;
  /** A tela tem o cabeçalho nativo em cima (o topo seguro já está nele). */
  underHeader?: boolean;
  /** Rodapé fixo (o botão de enviar de um formulário). */
  footer?: ReactNode;
  testID?: string;
};

/** A tela rolável padrão: fundo creme, margens, área segura e teclado. */
export function Screen({ children, onRefresh, refreshing = false, underHeader, footer, testID }: Props) {
  const insets = useSafeAreaInsets();

  return (
    // No Android do SDK 57 o app é edge-to-edge: a janela não encolhe com o
    // teclado, então o 'height' sobe o rodapé (visto no emulador: sem ele, o
    // teclado cobria o botão "Receber código").
    <KeyboardAvoidingView style={styles.root} behavior={Platform.OS === 'ios' ? 'padding' : 'height'}>
      <ScrollView
        testID={testID}
        style={styles.root}
        contentContainerStyle={[
          styles.content,
          { paddingTop: underHeader ? spacing.lg : insets.top + spacing.lg, paddingBottom: (footer ? spacing.lg : insets.bottom) + spacing.xxl },
        ]}
        keyboardShouldPersistTaps="handled"
        refreshControl={
          onRefresh ? <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} colors={[colors.primary]} /> : undefined
        }
      >
        {children}
      </ScrollView>
      {/* Sem cabeçalho, o conteúdo rola por baixo da barra de status: um
          fundo creme do tamanho dela deixa o relógio legível. */}
      {underHeader ? null : <View pointerEvents="none" style={[styles.statusBar, { height: insets.top }]} />}
      {footer ? <View style={[styles.footer, { paddingBottom: insets.bottom + spacing.md }]}>{footer}</View> : null}
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: colors.bg,
  },
  content: {
    paddingHorizontal: spacing.lg,
    gap: spacing.lg,
  },
  statusBar: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    backgroundColor: colors.bg,
  },
  footer: {
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.md,
    backgroundColor: colors.bg,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: colors.border,
    gap: spacing.sm,
  },
});
