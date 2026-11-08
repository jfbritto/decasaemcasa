import { Ionicons } from '@react-native-vector-icons/ionicons';
import * as Clipboard from 'expo-clipboard';
import { useState } from 'react';
import { Alert, Pressable, StyleSheet, View } from 'react-native';

import { Countdown } from '@/components/countdown';
import { AppText } from '@/components/ui/app-text';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Notice } from '@/components/ui/notice';
import { formatDeadline } from '@/lib/format';
import { pickProofDocument, pickProofPhoto, ProofPickError } from '@/lib/proof-picker';
import { errorMessage } from '@/services/api';
import type { ProofFile } from '@/services/inscriptions';
import { brand, colors } from '@/theme/colors';
import { radius, spacing } from '@/theme/spacing';
import type { Inscription } from '@/types/api';

type Props = {
  inscription: Inscription;
  contributionNote: string | null;
  onUpload: (file: ProofFile) => Promise<void>;
  uploading: boolean;
  /** O prazo zerou: a tela busca a inscrição de novo daqui a pouco. */
  onDeadline: () => void;
};

/**
 * O pagamento da inscrição aprovada (a parte "Enviar comprovante" da página
 * de status da web): prazo com contagem regressiva ou pausado, Pix com
 * "Copiar", e o comprovante pela câmera, galeria ou arquivo.
 */
export function PaymentSection({ inscription, contributionNote, onUpload, uploading, onDeadline }: Props) {
  const payment = inscription.payment;
  const [copied, setCopied] = useState(false);
  if (!payment) return null;

  const copyPix = async () => {
    if (!payment.pix) return;
    await Clipboard.setStringAsync(payment.pix.key);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const pick = async (source: 'camera' | 'library' | 'file') => {
    try {
      const file = source === 'file' ? await pickProofDocument() : await pickProofPhoto(source);
      if (file) await onUpload(file);
    } catch (error) {
      Alert.alert('Comprovante', error instanceof ProofPickError ? error.message : errorMessage(error));
    }
  };

  const showDeadline = payment.deadline_at && !payment.proof_sent;

  return (
    <View style={styles.wrapper}>
      {showDeadline && !payment.paused ? (
        <Notice tone="waiting" icon="hourglass-outline" title={`Prazo para envio do comprovante: ${formatDeadline(payment.deadline_at)}`} testID="deadline-notice">
          <Countdown deadline={payment.deadline_at!} onExpire={onDeadline} />
          <AppText variant="caption" color={colors.textMuted}>
            Após o prazo, sua inscrição volta automaticamente para a fila de espera e seu lugar na sala é liberado para a próxima pessoa.
          </AppText>
        </Notice>
      ) : null}
      {showDeadline && payment.paused ? (
        <Notice
          tone="waiting"
          icon="pause-circle-outline"
          title="Prazo pausado"
          text="Enquanto sua solicitação de contribuição social estiver em análise, o prazo para envio do comprovante fica suspenso. Quando a equipe responder, ele será reiniciado e avisaremos você."
          testID="deadline-paused"
        />
      ) : null}

      <Card testID="payment-card">
        <AppText variant="heading">Enviar comprovante de pagamento</AppText>

        {payment.pix ? (
          <View style={styles.pix}>
            <AppText variant="label">Chave Pix para contribuição</AppText>
            <AppText variant="bodyStrong" color={brand[800]} selectable testID="pix-key">
              {payment.pix.key}
            </AppText>
            {payment.pix.holder ? <AppText variant="caption">{payment.pix.holder}</AppText> : null}
            <Button
              label={copied ? 'Chave copiada!' : 'Copiar chave Pix'}
              icon={copied ? 'checkmark' : 'copy-outline'}
              variant="secondary"
              onPress={() => void copyPix()}
              testID="copy-pix"
            />
          </View>
        ) : null}
        {contributionNote ? <AppText variant="caption">{contributionNote}</AppText> : null}

        {payment.proof_sent ? (
          <Notice tone="success" icon="checkmark-circle" text="Comprovante já enviado. Aguardando confirmação da equipe." testID="proof-sent" />
        ) : null}

        <AppText variant="label">
          {payment.proof_sent ? 'Enviar novo comprovante (substitui o anterior):' : 'Envie a foto ou o PDF do comprovante (até 5MB):'}
        </AppText>
        {uploading ? (
          <View style={styles.uploading}>
            <Button label="Enviando... Aguarde" loading />
          </View>
        ) : (
          <View style={styles.sources}>
            <SourceButton icon="camera-outline" label="Câmera" onPress={() => void pick('camera')} testID="proof-camera" />
            <SourceButton icon="images-outline" label="Galeria" onPress={() => void pick('library')} testID="proof-library" />
            {/* "Arquivo": PDF do banco ou um print guardado. */}
            <SourceButton icon="document-outline" label="Arquivo" onPress={() => void pick('file')} testID="proof-file" />
          </View>
        )}
      </Card>
    </View>
  );
}

function SourceButton({ icon, label, onPress, testID }: { icon: 'camera-outline' | 'images-outline' | 'document-outline'; label: string; onPress: () => void; testID: string }) {
  return (
    <Pressable
      testID={testID}
      accessibilityRole="button"
      accessibilityLabel={`Enviar comprovante: ${label}`}
      onPress={onPress}
      style={({ pressed }) => [styles.source, { backgroundColor: pressed ? colors.surfaceSoft : colors.surface }]}
    >
      <Ionicons name={icon} size={24} color={brand[700]} />
      <AppText variant="label">{label}</AppText>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  wrapper: {
    gap: spacing.lg,
  },
  pix: {
    backgroundColor: brand[50],
    borderRadius: radius.lg,
    padding: spacing.lg,
    gap: spacing.xs,
  },
  uploading: {
    gap: spacing.sm,
  },
  sources: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  source: {
    flex: 1,
    minHeight: 80,
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.xs,
    borderRadius: radius.lg,
    borderWidth: 1.5,
    borderColor: colors.control,
  },
});
