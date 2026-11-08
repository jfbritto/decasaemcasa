<?php

namespace App\Inscriptions;

use App\Models\Inscription;

/**
 * O que a tela de uma inscrição mostra, decidido no servidor (spec
 * 2026-11-08-app-mobile-design, AM13 a AM15): a mesma árvore de `if` da
 * resources/views/inscriptions/status.blade.php. O app só desenha.
 */
final class InscriptionView
{
    public const PENDING = 'pending';

    public const PENDING_FULL = 'pending_full';

    public const APPROVED = 'approved';

    public const APPROVED_FULL = 'approved_full';

    public const CONFIRMED = 'confirmed';

    public const WAITLISTED = 'waitlisted';

    public const EXPIRED = 'expired';

    public const REJECTED = 'rejected';

    public const CANCELLED_BY_ADMIN = 'cancelled_by_admin';

    public const CANCELLED_BY_PARTICIPANT = 'cancelled_by_participant';

    public static function state(Inscription $inscription): string
    {
        $full = self::eventFull($inscription);

        return match (true) {
            $inscription->isPending() && $full => self::PENDING_FULL,
            $inscription->isPending() => self::PENDING,
            $inscription->isApproved() && $full => self::APPROVED_FULL,
            $inscription->isApproved() => self::APPROVED,
            $inscription->isConfirmed() => self::CONFIRMED,
            $inscription->isWaitlisted() && $inscription->payment_expired_at !== null => self::EXPIRED,
            $inscription->isWaitlisted() => self::WAITLISTED,
            $inscription->isRejected() => self::REJECTED,
            $inscription->isCancelledByAdmin() => self::CANCELLED_BY_ADMIN,
            default => self::CANCELLED_BY_PARTICIPANT,
        };
    }

    /**
     * O comprovante e o Pix aparecem só para aprovado com vaga (a web mostra
     * o formulário nessa condição).
     */
    public static function canUploadProof(Inscription $inscription): bool
    {
        return $inscription->isApproved() && ! self::eventFull($inscription);
    }

    /**
     * O pedido de contribuição social: aprovado com vaga, sem pedido ou com o
     * anterior recusado.
     */
    public static function canRequestSocial(Inscription $inscription): bool
    {
        return self::canUploadProof($inscription)
            && (! $inscription->hasSocialRequest() || $inscription->isSocialRequestRejected());
    }

    /**
     * O botão "Cancelar minha inscrição": nem cancelada, nem rejeitada, nem
     * confirmada (confirmado fala com a equipe).
     */
    public static function canCancel(Inscription $inscription): bool
    {
        return ! $inscription->isCancelled() && ! $inscription->isRejected() && ! $inscription->isConfirmed();
    }

    /**
     * O prazo do comprovante está pausado pelo pedido social pendente.
     */
    public static function deadlinePaused(Inscription $inscription): bool
    {
        return $inscription->payment_deadline_at !== null
            && ! $inscription->payment_proof
            && $inscription->isSocialRequestPending();
    }

    private static function eventFull(Inscription $inscription): bool
    {
        return $inscription->event !== null && $inscription->event->isFull();
    }
}
