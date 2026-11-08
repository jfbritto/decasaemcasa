import { useQuery } from '@tanstack/react-query';
import { Linking } from 'react-native';

import { AppText } from '@/components/ui/app-text';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { queryKeys } from '@/lib/query-client';
import { fetchConfig } from '@/services/events';
import { colors } from '@/theme/colors';

/**
 * "Precisa de ajuda?" com o WhatsApp da equipe. O WhatsApp é só canal de
 * contato: as atualizações chegam por e-mail e aqui no app (memória de copy
 * do projeto).
 */
export function SupportCard() {
  const config = useQuery({ queryKey: queryKeys.config, queryFn: fetchConfig, staleTime: Infinity });
  const link = config.data?.support.whatsapp_link;
  if (!link) return null;

  return (
    <Card>
      <AppText variant="heading">Precisa de ajuda?</AppText>
      <AppText variant="body" color={colors.textMuted}>
        Fale direto com a equipe pelo WhatsApp. As atualizações da sua inscrição chegam por e-mail e aqui no app.
      </AppText>
      <Button label="Falar no WhatsApp" variant="whatsapp" icon="logo-whatsapp" onPress={() => Linking.openURL(link)} />
      {config.data?.support.whatsapp_number ? (
        <AppText variant="caption" center>
          {`Atendimento: ${config.data.support.whatsapp_number}`}
        </AppText>
      ) : null}
    </Card>
  );
}
