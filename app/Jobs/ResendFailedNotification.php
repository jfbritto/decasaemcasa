<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\NotificationResendService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ResendFailedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Notification $notification
    ) {
        $this->onConnection('database');
    }

    public function handle(NotificationResendService $resender): void
    {
        try {
            $resender->resend($this->notification);
        } catch (\Throwable $e) {
            Log::error("Falha ao reenviar notificação #{$this->notification->id}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Chamado pelo Laravel quando o job esgota todas as tentativas.
     * Libera a trava `resend_queued_at` para que a notificação possa ser
     * reenfileirada manualmente em uma nova tentativa.
     */
    public function failed(\Throwable $exception): void
    {
        try {
            $this->notification->refresh();
            $metadata = $this->notification->metadata ?? [];
            unset($metadata['resend_queued_at']);
            $this->notification->metadata = $metadata;
            $this->notification->save();
        } catch (\Throwable $e) {
            Log::warning('Falha ao liberar trava de reenvio: '.$e->getMessage());
        }
    }
}
