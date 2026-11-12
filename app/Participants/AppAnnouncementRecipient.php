<?php

namespace App\Participants;

use App\Models\Event;

/**
 * Uma pessoa que recebe a divulgação do app (spec 2026-11-12-email-divulgacao-app-design, DA3).
 */
final class AppAnnouncementRecipient
{
    /**
     * @param  list<Event>  $events  os encontros futuros das inscrições que entraram
     */
    public function __construct(
        public readonly string $email,
        public readonly string $name,
        public readonly int $inscriptionId,
        public readonly array $events,
    ) {}
}
