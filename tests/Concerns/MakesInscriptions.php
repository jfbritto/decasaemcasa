<?php

namespace Tests\Concerns;

use App\Inscriptions\MailDomainChecker;
use App\Models\Event;
use App\Models\Inscription;
use App\Models\Participant;

/**
 * Encontros, inscrições e participantes de teste. Dados sempre fictícios.
 */
trait MakesInscriptions
{
    protected function makeEvent(array $overrides = []): Event
    {
        // events.slug é NOT NULL UNIQUE, sem auto-geração no model
        return Event::create(array_merge([
            'title' => 'Encontro Velunora',
            'slug' => 'encontro-velunora-'.uniqid(),
            'city' => 'Vitória',
            'date' => now()->addMonth(),
            'status' => 'published',
            'capacity' => 10,
            'confirmed_count' => 0,
        ], $overrides));
    }

    protected function makeInscription(Event $event, array $overrides = []): Inscription
    {
        return Inscription::create(array_merge([
            'event_id' => $event->id,
            'full_name' => 'Maria Velunora',
            'cpf' => '529.982.247-25',
            'birth_date' => '1990-01-01',
            'city_neighborhood' => 'Centro, Vitória',
            'whatsapp' => '(27) 99999-0000',
            'email' => 'maria@example.com',
            'motivation' => 'Quero muito participar deste encontro especial.',
            'terms_accepted' => true,
            'status' => 'aprovado',
            'approved_at' => now()->subHours(2),
            'payment_deadline_at' => now()->subHour(),
        ], $overrides));
    }

    protected function makeParticipant(string $email = 'maria@example.com', array $overrides = []): Participant
    {
        return Participant::create(array_merge(['email' => $email, 'name' => 'Maria Velunora'], $overrides));
    }

    /**
     * Sem DNS nos testes: todo domínio de e-mail recebe mensagens.
     */
    protected function acceptAnyMailDomain(): void
    {
        $this->app->instance(MailDomainChecker::class, new class extends MailDomainChecker
        {
            public function acceptsMail(?string $email): bool
            {
                return true;
            }
        });
    }
}
