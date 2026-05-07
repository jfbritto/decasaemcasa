<?php

namespace App\Services;

use App\Models\Inscription;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class NotificationResendService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Reenvia uma notificação. Retorna true em caso de sucesso.
     */
    public function resend(Notification $notification): bool
    {
        if ($notification->type === 'email') {
            return $this->resendEmail($notification);
        }

        if ($notification->type === 'whatsapp') {
            return $this->resendWhatsApp($notification);
        }

        return false;
    }

    private function resendEmail(Notification $notification): bool
    {
        $inscriptionId = $notification->metadata['inscription_id'] ?? null;
        $inscription = $inscriptionId
            ? Inscription::with(['event' => fn ($q) => $q->withTrashed()])->find($inscriptionId)
            : null;

        $viewMap = [
            'inscription_received' => 'emails.inscription-received',
            'inscription_approved' => 'emails.inscription-approved',
            'inscription_waitlisted' => 'emails.inscription-waitlisted',
            'inscription_confirmed' => 'emails.inscription-confirmed',
            'inscription_rejected' => 'emails.inscription-rejected',
            'inscription_cancelled' => 'emails.inscription-cancelled',
            'payment_reminder' => 'emails.payment-reminder',
            'social_request_submitted' => 'emails.social-request-submitted',
            'social_request_approved' => 'emails.social-request-approved',
            'social_request_rejected' => 'emails.social-request-rejected',
        ];

        $view = null;
        $viewData = [];

        if ($inscription && $inscription->event && isset($viewMap[$notification->channel])) {
            $view = $viewMap[$notification->channel];
            $viewData = [
                'inscription' => $inscription,
                'event' => $inscription->event,
                'statusUrl' => route('inscricao.status', $inscription->token),
            ];
            if ($notification->channel === 'social_request_approved') {
                $viewData['amountFormatted'] = 'R$ '.number_format((float) $inscription->social_request_amount, 2, ',', '.');
            }
        } elseif ($notification->channel !== 'general') {
            Log::warning('Reenvio sem template HTML', [
                'notification_id' => $notification->id,
                'channel' => $notification->channel,
                'inscription_exists' => (bool) $inscription,
                'event_exists' => $inscription ? (bool) $inscription->event : false,
            ]);
        }

        $resendMetadata = array_merge($notification->metadata ?? [], [
            'resent_from' => $notification->id,
            'template_used' => $view !== null,
        ]);

        return $this->notificationService->sendEmail(
            $notification->recipient,
            $notification->subject ?? 'De Casa em Casa',
            $notification->message,
            null,
            $notification->channel,
            $resendMetadata,
            $view,
            $viewData
        );
    }

    private function resendWhatsApp(Notification $notification): bool
    {
        return $this->notificationService->sendWhatsApp(
            $notification->recipient,
            $notification->message,
            null,
            $notification->channel,
            $notification->metadata ?? []
        );
    }
}
