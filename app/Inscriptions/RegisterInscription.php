<?php

namespace App\Inscriptions;

use App\Models\Event;
use App\Models\Inscription;
use App\Services\NotificationService;

/**
 * Cria uma inscrição já validada (InscriptionRules), na web e no app (spec
 * 2026-11-08-app-mobile-design, AM16 e AM17): encontro publicado, com vaga,
 * sem inscrição do mesmo CPF ou e-mail no mesmo encontro; cria pendente e
 * avisa o participante.
 */
final class RegisterInscription
{
    public const CREATED = 'created';

    public const UNAVAILABLE = 'unavailable';

    public const FULL = 'full';

    public const DUPLICATE = 'duplicate';

    public const MESSAGES = [
        self::UNAVAILABLE => 'Este encontro não está disponível para inscrições.',
        self::FULL => 'As vagas para este encontro estão esgotadas.',
        self::DUPLICATE => 'Você já possui uma inscrição para este encontro. Aqui está o status dela.',
    ];

    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $data  os campos de InscriptionRules (com o e-mail)
     * @return array{0: string, 1: ?Inscription} o resultado e a inscrição criada (CREATED) ou a já existente (DUPLICATE)
     */
    public function handle(Event $event, array $data): array
    {
        if (! $event->isPublished()) {
            return [self::UNAVAILABLE, null];
        }

        if ($event->isFull()) {
            return [self::FULL, null];
        }

        $cpfClean = InscriptionRules::cleanCpf((string) $data['cpf']);
        $existing = Inscription::where('event_id', $event->id)
            ->where(function ($query) use ($cpfClean, $data) {
                $query->whereRaw("REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?", [$cpfClean])
                    ->orWhere('email', $data['email']);
            })
            ->first();

        if ($existing) {
            return [self::DUPLICATE, $existing];
        }

        $inscription = Inscription::create([
            'event_id' => $event->id,
            'full_name' => $data['full_name'],
            'cpf' => $data['cpf'],
            'birth_date' => $data['birth_date'],
            'city_neighborhood' => $data['city_neighborhood'],
            'whatsapp' => $data['whatsapp'],
            'email' => $data['email'],
            'instagram' => $data['instagram'] ?? null,
            'motivation' => $data['motivation'],
            'terms_accepted' => true,
            'status' => 'pendente',
        ]);

        $this->notifications->notifyInscriptionReceived($inscription);

        return [self::CREATED, $inscription];
    }
}
