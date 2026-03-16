<?php

namespace App\Jobs;

use App\Models\Inscription;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEventFullNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Inscription $inscription
    ) {
        $this->onConnection('database');
    }

    public function handle(NotificationService $notificationService): void
    {
        try {
            $notificationService->notifyEventFull($this->inscription);
        } catch (\Throwable $e) {
            Log::error("Falha ao enviar notificação de evento esgotado para inscrição #{$this->inscription->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
