<?php

namespace Tests\Feature;

use App\Models\Inscription;
use App\Participants\AppPresence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesInscriptions;
use Tests\TestCase;

/**
 * Quem usa o app (spec 2026-11-11-app-no-painel-design, AP2 a AP5).
 */
class AppPresenceTest extends TestCase
{
    use MakesInscriptions;
    use RefreshDatabase;

    public function test_acha_o_participante_pelo_e_mail_normalizado(): void
    {
        $participant = $this->makeParticipant('maria@example.com');
        $participant->forceFill(['last_login_at' => now()->subDay()])->save();
        $participant->pushTokens()->create(['token' => 'ExponentPushToken[velunora-1]', 'platform' => 'ios']);
        $participant->pushTokens()->create(['token' => 'ExponentPushToken[velunora-2]', 'platform' => 'ios']);
        $token = $participant->createToken('app');
        $token->accessToken->forceFill(['last_used_at' => now()->subHour()])->save();

        $found = app(AppPresence::class)->forEmails([' Maria@Example.com ', 'outra@example.com']);

        $this->assertSame(['maria@example.com'], array_keys($found));
        $entry = $found['maria@example.com'];
        $this->assertTrue($entry->pushEnabled());
        $this->assertSame(['ios'], $entry->platforms);
        $this->assertSame('iPhone', $entry->platformLabel());
        $this->assertNotNull($entry->lastLoginAt);
        $this->assertNotNull($entry->lastUsedAt);
    }

    public function test_e_mail_com_acento_nao_e_o_mesmo(): void
    {
        $this->makeParticipant('maria@gmail.com');

        $this->assertNull(app(AppPresence::class)->forEmail('maria@gmáil.com'));
        $this->assertNotNull(app(AppPresence::class)->forEmail('MARIA@gmail.com'));
    }

    public function test_sem_push_e_sem_token_usado(): void
    {
        $this->makeParticipant('joao@example.com');

        $entry = app(AppPresence::class)->forEmail('joao@example.com');

        $this->assertFalse($entry->pushEnabled());
        $this->assertNull($entry->lastUsedAt);
    }

    public function test_totais(): void
    {
        $this->makeParticipant('a@example.com')->pushTokens()->create(['token' => 'ExponentPushToken[a]', 'platform' => 'android']);
        $b = $this->makeParticipant('b@example.com');
        $b->pushTokens()->create(['token' => 'ExponentPushToken[b1]', 'platform' => 'ios']);
        $b->pushTokens()->create(['token' => 'ExponentPushToken[b2]', 'platform' => 'android']);
        $this->makeParticipant('c@example.com');

        $this->assertSame(['participants' => 3, 'with_push' => 2], app(AppPresence::class)->totals());
    }

    public function test_escopos_usa_e_nao_usa_o_app(): void
    {
        $event = $this->makeEvent();
        $this->makeParticipant('maria@example.com');
        $usa = $this->makeInscription($event, ['email' => ' Maria@Example.com ']);
        $naoUsa = $this->makeInscription($event, ['email' => 'maria@gmáil.com', 'cpf' => '111.444.777-35']);

        $this->assertSame([$usa->id], Inscription::usesApp()->pluck('id')->all());
        $this->assertSame([$naoUsa->id], Inscription::doesNotUseApp()->pluck('id')->all());
    }
}
