<?php

namespace App\Services;

use App\Models\Inscription;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService
    ) {}

    public function sendEmail(string $to, string $subject, string $message, ?User $user = null, ?string $channel = null, array $metadata = [], ?string $view = null, array $viewData = []): bool
    {
        try {
            $mailer = config('mail.default');
            if (empty($mailer)) {
                Log::warning('Mailer não configurado. Email não será enviado.', [
                    'to' => $to,
                    'subject' => $subject,
                ]);
                $this->logNotification('email', $channel ?? 'general', $to, $subject, $message, $user, $metadata, 'skipped', 'Mailer não configurado');

                return true;
            }

            if ($view) {
                Mail::mailer($mailer)->send($view, $viewData, function ($mail) use ($to, $subject) {
                    $mail->to($to)->subject($subject);
                });
            } else {
                Mail::mailer($mailer)->raw($message, function ($mail) use ($to, $subject) {
                    $mail->to($to)->subject($subject);
                });
            }

            $this->logNotification('email', $channel ?? 'general', $to, $subject, $message, $user, $metadata, 'sent');

            return true;
        } catch (\Exception $e) {
            $this->logNotification('email', $channel ?? 'general', $to, $subject, $message, $user, $metadata, 'failed', $e->getMessage());
            Log::error('Erro ao enviar email: '.$e->getMessage(), [
                'to' => $to,
                'subject' => $subject,
                'exception' => $e,
            ]);
            if (config('app.env') === 'local') {
                return true;
            }

            return false;
        }
    }

    public function sendWhatsApp(string $to, string $message, ?User $user = null, ?string $channel = null, array $metadata = []): bool
    {
        if (! $this->whatsAppService->isEnabled()) {
            $this->logNotification('whatsapp', $channel ?? 'general', $to, null, $message, $user, $metadata, 'skipped', 'WhatsApp desativado');

            return true;
        }

        try {
            $this->whatsAppService->send($to, $message);

            $this->logNotification('whatsapp', $channel ?? 'general', $to, null, $message, $user, $metadata, 'sent');

            return true;
        } catch (\Exception $e) {
            $this->logNotification('whatsapp', $channel ?? 'general', $to, null, $message, $user, $metadata, 'failed', $e->getMessage());
            Log::error('Erro ao enviar WhatsApp: '.$e->getMessage(), [
                'to' => $to,
                'exception' => $e,
            ]);
            if (config('app.env') === 'local') {
                return true;
            }

            return false;
        }
    }

    // =====================
    // INSCRIÇÕES - De Casa em Casa
    // =====================

    /**
     * Pós-Inscrição (status: pendente)
     */
    public function notifyInscriptionReceived(Inscription $inscription): void
    {
        $event = $inscription->event;
        $statusUrl = route('inscricao.status', $inscription->token);

        // Email
        $subject = 'Inscrição recebida - De Casa em Casa';
        $message = "Inscrição recebida para {$inscription->full_name} - {$event->city}";

        $this->sendEmail(
            $inscription->email,
            $subject,
            $message,
            null,
            'inscription_received',
            ['inscription_id' => $inscription->id],
            'emails.inscription-received',
            ['inscription' => $inscription, 'event' => $event, 'statusUrl' => $statusUrl]
        );

        // WhatsApp
        $wa = "Olá {$inscription->full_name}! Recebemos sua inscrição para o encontro *De Casa em Casa* em *{$event->city}* ({$event->date->format('d/m/Y')}). ";
        $wa .= "Estamos em fase de curadoria e retornaremos em breve.\n\n";
        $wa .= "Lembrete: cada pessoa deve fazer sua própria inscrição, incluindo crianças e acompanhantes.\n\n";
        $wa .= "Acompanhe aqui: {$statusUrl}";

        $this->sendWhatsApp(
            $inscription->whatsapp,
            $wa,
            null,
            'inscription_received',
            ['inscription_id' => $inscription->id]
        );
    }

    /**
     * Aprovação (status: aprovado)
     */
    public function notifyInscriptionApproved(Inscription $inscription): void
    {
        $event = $inscription->event;
        $statusUrl = route('inscricao.status', $inscription->token);

        // Email
        $subject = 'Sua participação foi aprovada! - De Casa em Casa';
        $message = "Participação aprovada para {$inscription->full_name} - {$event->city}";

        $this->sendEmail(
            $inscription->email,
            $subject,
            $message,
            null,
            'inscription_approved',
            ['inscription_id' => $inscription->id],
            'emails.inscription-approved',
            ['inscription' => $inscription, 'event' => $event, 'statusUrl' => $statusUrl]
        );

        // WhatsApp
        $wa = "Olá {$inscription->full_name}! Sua participação no encontro *De Casa em Casa* em *{$event->city}* ({$event->date->format('d/m/Y')}) foi *aprovada*!\n\n";

        $pixKey = config('services.pix.key');
        if ($pixKey) {
            $pixHolder = config('services.pix.holder', 'Marcos Almeida');
            $wa .= "Chave Pix: *{$pixKey}*\nTitular: {$pixHolder}\nVocê define o valor que faz sentido pra você.\n\n";
        }

        $wa .= "Envie seu comprovante de pagamento pelo link para garantir sua vaga: {$statusUrl}";

        $this->sendWhatsApp(
            $inscription->whatsapp,
            $wa,
            null,
            'inscription_approved',
            ['inscription_id' => $inscription->id]
        );
    }

    /**
     * Fila de Espera (status: fila_de_espera)
     */
    public function notifyInscriptionWaitlisted(Inscription $inscription): void
    {
        $event = $inscription->event;
        $statusUrl = route('inscricao.status', $inscription->token);

        // Email
        $subject = 'Fila de Espera - De Casa em Casa';
        $message = "Fila de espera para {$inscription->full_name} - {$event->city}";

        $this->sendEmail(
            $inscription->email,
            $subject,
            $message,
            null,
            'inscription_waitlisted',
            ['inscription_id' => $inscription->id],
            'emails.inscription-waitlisted',
            ['inscription' => $inscription, 'event' => $event, 'statusUrl' => $statusUrl]
        );

        // WhatsApp
        $wa = "Olá {$inscription->full_name}! As vagas para o encontro *De Casa em Casa* em *{$event->city}* ({$event->date->format('d/m/Y')}) já foram preenchidas. ";
        $wa .= "Você está na nossa *Fila de Espera* e avisaremos caso surja uma vaga. ";
        $wa .= "Acompanhe aqui: {$statusUrl}";

        $this->sendWhatsApp(
            $inscription->whatsapp,
            $wa,
            null,
            'inscription_waitlisted',
            ['inscription_id' => $inscription->id]
        );
    }

    /**
     * Confirmação (status: confirmado) — inclui endereço!
     */
    public function notifyInscriptionConfirmed(Inscription $inscription): void
    {
        $inscription->load('event');
        $event = $inscription->event;
        $statusUrl = route('inscricao.status', $inscription->token);

        // Email
        $subject = 'Confirmado! Prepare o coração - De Casa em Casa';
        $message = "Confirmação para {$inscription->full_name} - {$event->city}";

        $this->sendEmail(
            $inscription->email,
            $subject,
            $message,
            null,
            'inscription_confirmed',
            ['inscription_id' => $inscription->id],
            'emails.inscription-confirmed',
            ['inscription' => $inscription, 'event' => $event, 'statusUrl' => $statusUrl]
        );

        // WhatsApp
        $wa = "Olá {$inscription->full_name}! Você está *confirmado(a)* no encontro *De Casa em Casa*!\n\n";
        if ($event->full_address) {
            $wa .= "Endereço: {$event->full_address}\n";
        }
        if ($event->arrival_time) {
            $wa .= "Horário: {$event->arrival_time}\n";
        }
        $wa .= "Data: {$event->date->format('d/m/Y')}\n\n";
        if ($event->full_address) {
            $wa .= "⚠️ *IMPORTANTE:* Não divulgue este endereço. Não compartilhe em grupos. Este encontro é secreto. Somente pessoas com nome na lista poderão entrar.\n\n";
        }
        $wa .= "Prepare o coração! Detalhes: {$statusUrl}";

        $this->sendWhatsApp(
            $inscription->whatsapp,
            $wa,
            null,
            'inscription_confirmed',
            ['inscription_id' => $inscription->id]
        );
    }

    private function logNotification(string $type, string $channel, string $recipient, ?string $subject, string $message, ?User $user, array $metadata, string $status, ?string $errorMessage = null): void
    {
        Notification::create([
            'user_id' => $user?->id,
            'type' => $type,
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => $subject,
            'message' => $message,
            'status' => $status,
            'error_message' => $errorMessage,
            'metadata' => $metadata,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }
}
