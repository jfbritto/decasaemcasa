<?php

namespace App\Jobs;

use App\Models\ParticipantNotification;
use App\Models\ParticipantPushToken;
use App\Notifications\Push\ExpoPushClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * O push de um aviso do app, para os aparelhos do dono do e-mail (spec
 * 2026-11-08-app-mobile-design, AM21 a AM24 e AM44). Vai pela fila: na
 * produção, o queue:work do cron de cada minuto.
 *
 * Uma tentativa só: o cliente do Expo já tenta de novo o que é passageiro, e
 * repetir o job poderia entregar o mesmo push duas vezes.
 */
class SendParticipantPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly int $notificationId,
    ) {}

    public function handle(ExpoPushClient $client): void
    {
        $notification = ParticipantNotification::find($this->notificationId);

        if (! $notification) {
            return;
        }

        $tokens = ParticipantPushToken::query()
            ->whereHas('participant', fn ($query) => $query->where('email', $notification->email))
            ->get();

        if ($tokens->isEmpty()) {
            return;
        }

        $messages = $tokens->map(fn (ParticipantPushToken $token) => [
            'to' => $token->token,
            'title' => $notification->title,
            'body' => $notification->body,
            'sound' => 'default',
            'channelId' => 'default',
            'data' => [
                'notification_id' => $notification->id,
                'inscription_id' => $notification->inscription_id,
                'kind' => $notification->kind,
            ],
        ])->values()->all();

        $tickets = $client->send($messages);

        // Aparelho que desinstalou o app ou trocou de conta no Expo: o token
        // morreu e sai na hora.
        foreach ($tokens->values() as $index => $token) {
            if (($tickets[$index]['details']['error'] ?? null) === 'DeviceNotRegistered') {
                $token->delete();
            }
        }
    }
}
