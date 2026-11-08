<?php

namespace App\Inscriptions;

use App\Models\Inscription;
use App\Services\NotificationService;

/**
 * O cancelamento pelo próprio participante, na web e no app (spec
 * 2026-11-08-app-mobile-design, AM17). Recusa o que já está cancelado ou
 * rejeitado; a API ainda recusa o confirmado (o botão não existe para ele na
 * web nem no app: confirmado fala com a equipe).
 */
final class CancelInscription
{
    public const ALREADY_CLOSED = 'already_closed';

    public const MESSAGES = [
        self::ALREADY_CLOSED => 'Esta inscrição já está cancelada.',
    ];

    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function refusal(Inscription $inscription): ?string
    {
        return $inscription->isCancelled() || $inscription->isRejected() ? self::ALREADY_CLOSED : null;
    }

    /**
     * Null quando cancelada; senão, o motivo da recusa.
     */
    public function handle(Inscription $inscription): ?string
    {
        if ($refusal = $this->refusal($inscription)) {
            return $refusal;
        }

        $inscription->cancel();

        $this->notifications->notifyInscriptionCancelled($inscription);

        return null;
    }
}
