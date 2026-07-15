<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Inscription;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireUnpaidInscriptions extends Command
{
    protected $signature = 'inscriptions:expire-unpaid {--dry-run : Apenas lista quem seria expirado, sem alterar nada}';

    protected $description = 'Move para a fila de espera inscrições aprovadas sem comprovante com prazo de pagamento vencido';

    public function handle(NotificationService $notificationService): int
    {
        $query = Inscription::with(['event' => fn ($query) => $query->withTrashed()])
            ->where('status', 'aprovado')
            ->whereNull('payment_proof')
            ->whereNotNull('payment_deadline_at')
            ->where('payment_deadline_at', '<=', now())
            ->where(function ($query) {
                // Prazo pausado enquanto a solicitação de contribuição social está em análise
                $query->whereNull('social_request_status')
                    ->orWhere('social_request_status', '!=', 'pendente');
            });

        $total = (clone $query)->count();

        // Notificações são síncronas: limita o lote para caber na janela de 1 minuto
        // do scheduler e respeitar o rate limit do Resend; o restante sai no próximo minuto
        $candidates = $query->orderBy('payment_deadline_at')->limit(25)->get();

        if ($candidates->isEmpty()) {
            $this->info('Nenhuma inscrição com prazo de pagamento vencido.');

            return self::SUCCESS;
        }

        if ($total > $candidates->count()) {
            $this->info("Processando {$candidates->count()} de {$total} candidatos; o restante será processado nas próximas execuções.");
        }

        $expired = 0;

        foreach ($candidates as $inscription) {
            // Não expira quem nunca recebeu a notificação de aprovação (ex.: falha do Resend).
            // Sem filtro de type: e-mail OU WhatsApp enviado conta como avisado, pois ambos carregam o prazo
            $wasNotified = Notification::where('channel', 'inscription_approved')
                ->where('status', 'sent')
                ->where('metadata->inscription_id', $inscription->id)
                ->where('created_at', '>=', $inscription->approved_at)
                ->exists();

            if (! $wasNotified) {
                $this->warn("  #{$inscription->id} {$inscription->full_name}: sem notificação de aprovação enviada — expiração adiada.");

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  [dry-run] Expiraria #{$inscription->id} {$inscription->full_name} (prazo: {$inscription->payment_deadline_at})");

                continue;
            }

            if (! $inscription->expireToWaitlist()) {
                continue;
            }

            $expired++;

            try {
                ActivityLog::log('expirar_inscricao', "Prazo de pagamento vencido: {$inscription->full_name} movido(a) para a fila de espera", $inscription);
            } catch (\Throwable $e) {
                Log::warning('Falha ao registrar log de atividade: '.$e->getMessage());
            }

            // Evento lotado: a pessoa não tinha como enviar comprovante (formulário oculto),
            // então o aviso de "prazo vencido" seria incoerente — expira em silêncio.
            // Evento soft-deletado: o refresh() dentro de expireToWaitlist() recarrega a
            // relação `event` SEM withTrashed, então aqui `$inscription->event` fica null
            // e a notificação é pulada de propósito (evento excluído → aviso de "vaga
            // liberada" não faz sentido)
            if ($inscription->event && ! $inscription->event->isFull()) {
                try {
                    $notificationService->notifyInscriptionExpired($inscription);
                } catch (\Throwable $e) {
                    Log::error("Falha ao notificar expiração da inscrição #{$inscription->id}: ".$e->getMessage());
                }
            }

            $this->line("  Expirada: #{$inscription->id} {$inscription->full_name}");
        }

        $this->info("Inscrições expiradas: {$expired}");

        return self::SUCCESS;
    }
}
