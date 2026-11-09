import { router, type Href } from 'expo-router';

import { AppText } from '@/components/ui/app-text';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Notice } from '@/components/ui/notice';
import { formatDateTime, formatMoney } from '@/lib/format';
import { colors } from '@/theme/colors';
import type { Inscription } from '@/types/api';

/**
 * A contribuição social da inscrição aprovada, nos quatro jeitos da página
 * de status da web: ainda não pedida, em análise, aprovada ou não aprovada.
 */
export function SocialRequestSection({ inscription, referenceAmount }: { inscription: Inscription; referenceAmount: number }) {
  const request = inscription.social_request;
  const reference = formatMoney(referenceAmount);

  if (request?.status === 'pendente') {
    return (
      <Notice tone="curation" icon="time-outline" title="Solicitação de contribuição social em análise" testID="social-pending">
        <AppText variant="body" color={colors.text}>
          Recebemos sua solicitação e nossa equipe está analisando. Logo retornaremos com a resposta. A vaga só é confirmada após a aprovação e o envio do comprovante.
        </AppText>
        <AppText variant="caption">{`Valor proposto: ${formatMoney(request.amount)}`}</AppText>
        {request.submitted_at ? <AppText variant="caption">{`Enviado em ${formatDateTime(request.submitted_at)}`}</AppText> : null}
      </Notice>
    );
  }

  if (request?.status === 'aprovado') {
    return (
      <Notice tone="success" icon="checkmark-circle" title="Solicitação aprovada!" testID="social-approved">
        <AppText variant="body" color={colors.text}>
          {`Combinamos o valor de ${formatMoney(request.amount)}. Faça o Pix com esse valor e envie o comprovante acima.`}
        </AppText>
        {request.admin_message ? <AppText variant="body" color={colors.text}>{`Mensagem da equipe: ${request.admin_message}`}</AppText> : null}
      </Notice>
    );
  }

  if (!inscription.can.request_social) return null;

  return (
    <Card testID="social-offer">
      {request?.status === 'rejeitado' ? (
        <Notice tone="neutral" title="Sua solicitação anterior">
          {request.admin_message ? <AppText variant="body" color={colors.text}>{`Mensagem da equipe: ${request.admin_message}`}</AppText> : null}
          <AppText variant="body" color={colors.text}>
            {`Não conseguimos aprovar sua solicitação desta vez. Se ainda quiser participar, contribua com o valor que conseguir dentro da referência de ${reference} e envie o comprovante acima, ou envie uma nova solicitação.`}
          </AppText>
        </Notice>
      ) : null}
      <AppText variant="heading">Não consegue contribuir com o valor de referência neste momento?</AppText>
      <AppText variant="body" color={colors.textMuted}>
        Você pode solicitar uma contribuição social. Sua história será analisada pela equipe.
      </AppText>
      <Button
        label={request?.status === 'rejeitado' ? 'Enviar nova solicitação' : 'Solicitar contribuição social'}
        variant="secondary"
        icon="heart-half-outline"
        onPress={() => router.push(`/inscriptions/${inscription.id}/social` as Href)}
        testID="social-request-button"
      />
    </Card>
  );
}
