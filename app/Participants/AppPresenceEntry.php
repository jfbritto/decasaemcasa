<?php

namespace App\Participants;

use Carbon\CarbonInterface;

/**
 * O que o painel mostra de quem usa o app (spec 2026-11-11-app-no-painel-design, AP4).
 */
final class AppPresenceEntry
{
    /**
     * @param  list<string>  $platforms  "android" e/ou "ios", sem repetição, em ordem
     */
    public function __construct(
        public readonly ?CarbonInterface $lastLoginAt,
        public readonly ?CarbonInterface $lastUsedAt,
        public readonly array $platforms,
    ) {
    }

    /** O app registrou pelo menos um aparelho para push. */
    public function pushEnabled(): bool
    {
        return $this->platforms !== [];
    }

    /** "iPhone", "Android" ou "Android e iPhone". */
    public function platformLabel(): string
    {
        return implode(' e ', array_map(fn (string $p) => $p === 'ios' ? 'iPhone' : 'Android', $this->platforms));
    }
}
