<?php

namespace App\Console\Commands;

use App\Models\Inscription;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendPaymentReminders extends Command
{
    protected $signature = 'inscriptions:send-payment-reminders {--days=2 : Dias desde a aprovação sem comprovante}';

    protected $description = 'Envia lembrete para inscritos aprovados que não enviaram comprovante';

    public function handle(NotificationService $notificationService): int
    {
        $days = (int) $this->option('days');

        $inscriptions = Inscription::with('event')
            ->where('status', 'aprovado')
            ->whereNull('payment_proof')
            ->where('approved_at', '<=', now()->subDays($days))
            ->get();

        if ($inscriptions->isEmpty()) {
            $this->info('Nenhum inscrito pendente de comprovante encontrado.');

            return self::SUCCESS;
        }

        $this->info("Encontrados {$inscriptions->count()} inscritos sem comprovante há mais de {$days} dias.");

        $sent = 0;
        foreach ($inscriptions as $inscription) {
            $statusUrl = route('inscricao.status', $inscription->token);
            $event = $inscription->event;

            // Email
            $notificationService->sendEmail(
                $inscription->email,
                'Lembrete: Envie seu comprovante - De Casa em Casa',
                "Lembrete de comprovante para {$inscription->full_name}",
                null,
                'payment_reminder',
                ['inscription_id' => $inscription->id],
                'emails.payment-reminder',
                ['inscription' => $inscription, 'event' => $event, 'statusUrl' => $statusUrl]
            );

            // WhatsApp
            $wa = "Olá {$inscription->full_name}! Lembramos que sua participação no encontro *De Casa em Casa* em *{$event->city}* foi aprovada. ";
            $wa .= "Para garantir sua vaga, envie o comprovante de pagamento pelo link: {$statusUrl}";

            $notificationService->sendWhatsApp(
                $inscription->whatsapp,
                $wa,
                null,
                'payment_reminder',
                ['inscription_id' => $inscription->id]
            );

            $sent++;
            $this->line("  Lembrete enviado para: {$inscription->full_name} ({$inscription->email})");
        }

        $this->info("Lembretes enviados: {$sent}");

        return self::SUCCESS;
    }
}
