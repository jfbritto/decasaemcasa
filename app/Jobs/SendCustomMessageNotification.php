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

class SendCustomMessageNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Inscription $inscription,
        public string $subject,
        public string $body
    ) {
        $this->onConnection('database');
    }

    public function handle(NotificationService $notificationService): void
    {
        try {
            $notificationService->notifyCustomMessage($this->inscription, $this->subject, $this->body);
        } catch (\Throwable $e) {
            Log::error("Falha ao enviar mensagem customizada para inscrição #{$this->inscription->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
