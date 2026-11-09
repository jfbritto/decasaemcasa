<?php

namespace App\Inscriptions;

/**
 * Se o domínio de um e-mail recebe mensagens. Detecta NULL MX (RFC 7505) e
 * domínios sem registros DNS. Em erro de DNS responde que sim (fail-open)
 * para não barrar gente de verdade.
 *
 * Uma classe à parte para os testes trocarem por uma resposta fixa, sem DNS.
 */
class MailDomainChecker
{
    public function acceptsMail(?string $email): bool
    {
        if (! $email || ! str_contains($email, '@')) {
            return true;
        }

        [, $domain] = explode('@', strtolower(trim($email)), 2);
        if (! $domain) {
            return true;
        }

        try {
            $mxRecords = @dns_get_record($domain, DNS_MX);

            if ($mxRecords === false) {
                return true;
            }

            if (! empty($mxRecords)) {
                // NULL MX (RFC 7505): único registro com target "." e prioridade 0
                if (count($mxRecords) === 1
                    && ($mxRecords[0]['target'] ?? '') === ''
                    && (int) ($mxRecords[0]['pri'] ?? -1) === 0) {
                    return false;
                }

                return true;
            }

            // Sem MX: o e-mail ainda pode ser entregue se houver registro A (fallback)
            $aRecords = @dns_get_record($domain, DNS_A);

            return ! empty($aRecords);
        } catch (\Throwable $e) {
            return true;
        }
    }
}
