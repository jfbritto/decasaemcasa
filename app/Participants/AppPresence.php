<?php

namespace App\Participants;

use App\Models\Participant;
use App\Models\ParticipantPushToken;
use Illuminate\Support\Carbon;

/**
 * Quem usa o app, para o painel (spec 2026-11-11-app-no-painel-design, AP2 a AP5).
 * Só leitura. A chave é o e-mail normalizado (Participant::normalizeEmail), e
 * participants.email é binário: "maria@gmáil.com" não é "maria@gmail.com" (AM2).
 */
class AppPresence
{
    /**
     * @param  iterable<string|null>  $emails
     * @return array<string, AppPresenceEntry> e-mail normalizado => presença
     */
    public function forEmails(iterable $emails): array
    {
        $keys = [];
        foreach ($emails as $email) {
            if (is_string($email) && trim($email) !== '') {
                $keys[Participant::normalizeEmail($email)] = true;
            }
        }
        if ($keys === []) {
            return [];
        }

        return Participant::query()
            ->whereIn('email', array_keys($keys))
            ->withMax('tokens', 'last_used_at')
            ->with('pushTokens:id,participant_id,platform')
            ->get()
            ->mapWithKeys(fn (Participant $participant) => [
                $participant->email => new AppPresenceEntry(
                    lastLoginAt: $participant->last_login_at,
                    lastUsedAt: $participant->tokens_max_last_used_at ? Carbon::parse($participant->tokens_max_last_used_at) : null,
                    platforms: $participant->pushTokens->pluck('platform')->unique()->sort()->values()->all(),
                ),
            ])
            ->all();
    }

    public function forEmail(?string $email): ?AppPresenceEntry
    {
        if (! is_string($email) || trim($email) === '') {
            return null;
        }

        return $this->forEmails([$email])[Participant::normalizeEmail($email)] ?? null;
    }

    /**
     * Total geral, sem período (AP8).
     *
     * @return array{participants: int, with_push: int}
     */
    public function totals(): array
    {
        return [
            'participants' => Participant::count(),
            'with_push' => ParticipantPushToken::query()->distinct()->count('participant_id'),
        ];
    }
}
