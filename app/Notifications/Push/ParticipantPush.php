<?php

namespace App\Notifications\Push;

use App\Jobs\SendParticipantPush;
use App\Models\Event;
use App\Models\Inscription;
use App\Models\ParticipantPushToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Os textos e o disparo do push de cada mudança feita pela equipe (spec
 * 2026-11-08-app-mobile-design, AM22 e AM25). Chamado pelo
 * NotificationService ao lado do e-mail. Sem travessão, com "seu lugar na
 * sala" e sem prometer nada por WhatsApp.
 *
 * Só põe o job na fila quando o dono da inscrição tem aparelho registrado:
 * a maioria das pessoas não tem o app, e a fila não precisa saber delas.
 * Nunca lança: push é conveniência e nunca impede o e-mail.
 */
final class ParticipantPush
{
    public function approved(Inscription $inscription): void
    {
        $this->send($inscription, 'approved', 'Sua participação foi aprovada!',
            "Envie o comprovante em até {$inscription->payment_deadline_label} para garantir seu lugar na sala em {$this->city($inscription)}.");
    }

    public function waitlisted(Inscription $inscription): void
    {
        $this->send($inscription, 'waitlisted', 'Você está na fila de espera',
            "As vagas do encontro em {$this->city($inscription)} foram preenchidas. Se um lugar na sala se abrir, avisamos você.");
    }

    public function expired(Inscription $inscription): void
    {
        $this->send($inscription, 'expired', 'O prazo do comprovante terminou',
            "Sua inscrição em {$this->city($inscription)} voltou para a fila de espera. Já fez o Pix? A equipe pode reativar sua aprovação.");
    }

    public function rejected(Inscription $inscription): void
    {
        $this->send($inscription, 'rejected', 'Sobre sua inscrição',
            "Desta vez não conseguimos incluir sua participação no encontro em {$this->city($inscription)}. Obrigado pelo carinho!");
    }

    public function cancelledByAdmin(Inscription $inscription): void
    {
        $this->send($inscription, 'cancelled_by_admin', 'Inscrição cancelada',
            "Sua inscrição no encontro em {$this->city($inscription)} foi cancelada pela equipe.");
    }

    public function confirmed(Inscription $inscription): void
    {
        $this->send($inscription, 'confirmed', 'Confirmado! Prepare o coração',
            "Seu lugar na sala em {$this->city($inscription)} está garantido. Veja o endereço e o horário no app.");
    }

    public function socialApproved(Inscription $inscription): void
    {
        $amount = 'R$ '.number_format((float) $inscription->social_request_amount, 2, ',', '.');

        $this->send($inscription, 'social_approved', 'Contribuição social aprovada',
            "Combinamos {$amount}. Faça o Pix e envie o comprovante para garantir seu lugar na sala.");
    }

    public function socialRejected(Inscription $inscription): void
    {
        $this->send($inscription, 'social_rejected', 'Sobre sua solicitação',
            'Não conseguimos aprovar a contribuição social desta vez. Veja no app como seguir com a sua inscrição.');
    }

    public function migrated(Inscription $inscription, Event $destination): void
    {
        $city = $destination->city ?: $destination->title;

        $this->send($inscription, 'migrated', 'Sua inscrição foi transferida',
            "Agora você está no encontro em {$city}.");
    }

    public function eventFull(Inscription $inscription): void
    {
        $this->send($inscription, 'event_full', 'Vagas esgotadas',
            "As vagas do encontro em {$this->city($inscription)} foram preenchidas. Se houver desistência, avisamos você.");
    }

    public function customMessage(Inscription $inscription, string $subject, string $body): void
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags($body)));

        $this->send($inscription, 'custom_message', Str::limit($subject, 80), Str::limit($text, 160));
    }

    private function send(Inscription $inscription, string $kind, string $title, string $body): void
    {
        try {
            $hasDevice = ParticipantPushToken::query()
                ->whereHas('participant', fn ($query) => $query->where('email', mb_strtolower(trim($inscription->email))))
                ->exists();

            if ($hasDevice) {
                SendParticipantPush::dispatch($inscription->id, $kind, $title, $body);
            }
        } catch (\Throwable $e) {
            Log::warning("Falha ao enfileirar o push ({$kind}): ".$e->getMessage());
        }
    }

    private function city(Inscription $inscription): string
    {
        $event = $inscription->event;

        return $event ? ($event->city ?: $event->title) : 'De Casa em Casa';
    }
}
