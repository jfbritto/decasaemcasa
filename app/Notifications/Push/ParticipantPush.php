<?php

namespace App\Notifications\Push;

use App\Jobs\SendParticipantPush;
use App\Models\Event;
use App\Models\Inscription;
use App\Models\ParticipantNotification;
use App\Models\ParticipantPushToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * O aviso do app que acompanha cada e-mail ao participante (spec
 * 2026-11-08-app-mobile-design, AM21, AM22, AM25 e AM41 a AM46): fica na aba
 * "Avisos" e vira push nos aparelhos de quem ativou os avisos. Chamado pelo
 * NotificationService ao lado do e-mail (e pelos lembretes de comprovante).
 * Sem travessão, com "seu lugar na sala" e sem prometer nada por WhatsApp.
 *
 * O aviso é gravado mesmo sem aparelho (quem instalar depois encontra o
 * histórico); o job do push só entra na fila quando o dono tem aparelho.
 * Nunca lança: o aviso é conveniência e nunca impede o e-mail.
 */
final class ParticipantPush
{
    public function received(Inscription $inscription): void
    {
        $this->send($inscription, 'received', 'Recebemos sua inscrição',
            "Sua história para o encontro em {$this->city($inscription)} chegou. A equipe vai ler com carinho e avisamos assim que tiver novidade.");
    }

    public function approved(Inscription $inscription): void
    {
        $this->send($inscription, 'approved', 'Sua participação foi aprovada!',
            "Envie o comprovante em até {$inscription->payment_deadline_label} para garantir seu lugar na sala em {$this->city($inscription)}.");
    }

    public function paymentReminder(Inscription $inscription): void
    {
        $this->send($inscription, 'payment_reminder', 'Falta o seu comprovante',
            "Sua participação em {$this->city($inscription)} está aprovada. Envie o comprovante do Pix para garantir seu lugar na sala.");
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

    public function cancelledByParticipant(Inscription $inscription): void
    {
        $this->send($inscription, 'cancelled_by_participant', 'Inscrição cancelada',
            "Sua inscrição no encontro em {$this->city($inscription)} foi cancelada, como você pediu. Esperamos te ver em outra casa!");
    }

    public function confirmed(Inscription $inscription): void
    {
        $this->send($inscription, 'confirmed', 'Confirmado! Prepare o coração',
            "Seu lugar na sala em {$this->city($inscription)} está garantido. Veja o endereço e o horário no app.");
    }

    public function socialSubmitted(Inscription $inscription): void
    {
        $this->send($inscription, 'social_submitted', 'Recebemos seu pedido de contribuição social',
            'A equipe vai analisar e logo responde. Enquanto isso, o prazo do comprovante fica pausado.');
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
            $email = mb_strtolower(trim((string) $inscription->email));

            $notification = ParticipantNotification::create([
                'email' => $email,
                'inscription_id' => $inscription->id,
                'kind' => $kind,
                'title' => $title,
                'body' => $body,
            ]);

            $hasDevice = ParticipantPushToken::query()
                ->whereHas('participant', fn ($query) => $query->where('email', $email))
                ->exists();

            if ($hasDevice) {
                SendParticipantPush::dispatch($notification->id);
            }
        } catch (\Throwable $e) {
            Log::warning("Falha ao gravar ou enfileirar o aviso do app ({$kind}): ".$e->getMessage());
        }
    }

    private function city(Inscription $inscription): string
    {
        $event = $inscription->event;

        return $event ? ($event->city ?: $event->title) : 'De Casa em Casa';
    }
}
