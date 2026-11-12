<?php

namespace App\Participants;

use App\Models\Inscription;
use App\Models\Notification;
use App\Models\Participant;

/**
 * Quem recebe o e-mail de divulgação do app (spec
 * 2026-11-12-email-divulgacao-app-design, DA1 a DA5). Só leitura.
 */
class AppAnnouncementAudience
{
    public const CHANNEL = 'app_announcement';

    public const STATUSES = ['pendente', 'aprovado', 'fila_de_espera', 'confirmado'];

    public function __construct(private readonly AppPresence $appPresence) {}

    /**
     * @return array{
     *     recipients: list<AppAnnouncementRecipient>,
     *     excluded: array{uses_app: int, non_ascii: int, already_received: int}
     * }
     */
    public function build(): array
    {
        // Os mesmos encontros abertos do formulário e do app (DA2)
        $byEmail = Inscription::query()
            ->whereIn('status', self::STATUSES)
            ->whereHas('event', fn ($query) => $query->where('status', 'published')->whereDate('date', '>=', now()))
            ->with('event')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Inscription $inscription) => is_string($inscription->email) && trim($inscription->email) !== '')
            ->groupBy(fn (Inscription $inscription) => Participant::normalizeEmail($inscription->email));

        $usingApp = $this->appPresence->forEmails($byEmail->keys());
        $log = $this->announcementLog();

        $recipients = [];
        $excluded = ['uses_app' => 0, 'non_ascii' => 0, 'already_received' => 0];

        foreach ($byEmail as $email => $inscriptions) {
            $email = (string) $email;

            if (isset($usingApp[$email])) {
                $excluded['uses_app']++;

                continue;
            }
            // O login do app só aceita e-mail ASCII (DA4b)
            if (! Participant::isAsciiEmail($email)) {
                $excluded['non_ascii']++;

                continue;
            }
            if (isset($log['sent'][$email])) {
                $excluded['already_received']++;

                continue;
            }

            $latest = $inscriptions->first();
            $recipients[] = new AppAnnouncementRecipient(
                email: $email,
                name: $latest->full_name,
                inscriptionId: $latest->id,
                events: $inscriptions->pluck('event')->unique('id')->values()->all(),
            );
        }

        // Quem já falhou vai para o fim: falhas permanentes no começo da fila
        // não podem travar todas as rodadas (DA10). O usort é estável.
        usort($recipients, fn (AppAnnouncementRecipient $a, AppAnnouncementRecipient $b) => isset($log['failed'][$a->email]) <=> isset($log['failed'][$b->email]));

        return ['recipients' => $recipients, 'excluded' => $excluded];
    }

    /**
     * Os e-mails que já receberam o aviso de verdade (DA5) e os que já
     * falharam. A comparação é no PHP: a collation de
     * notifications.recipient ignora acento. O exemplo do --to não conta.
     *
     * @return array{sent: array<string, true>, failed: array<string, true>}
     */
    private function announcementLog(): array
    {
        $log = ['sent' => [], 'failed' => []];

        Notification::query()
            ->where('type', 'email')
            ->where('channel', self::CHANNEL)
            ->whereIn('status', ['sent', 'failed'])
            ->get(['recipient', 'status', 'metadata'])
            ->reject(fn (Notification $notification) => ! empty($notification->metadata['sample']))
            ->each(function (Notification $notification) use (&$log) {
                $log[$notification->status][Participant::normalizeEmail($notification->recipient)] = true;
            });

        return $log;
    }
}
