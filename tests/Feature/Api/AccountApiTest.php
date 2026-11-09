<?php

namespace Tests\Feature\Api;

use App\Models\Inscription;
use App\Models\Participant;
use App\Models\ParticipantLoginCode;
use App\Models\ParticipantPushToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesInscriptions;
use Tests\TestCase;

/**
 * Tokens de push e exclusão de conta (spec 2026-11-08-app-mobile-design, AM8
 * e AM20).
 */
class AccountApiTest extends TestCase
{
    use MakesInscriptions;
    use RefreshDatabase;

    public function test_registra_o_token_de_push_do_aparelho(): void
    {
        $participant = $this->makeParticipant();

        $this->withToken($participant->createToken('app')->plainTextToken)
            ->postJson('/api/v1/push-tokens', [
                'token' => 'ExponentPushToken[abc123]',
                'platform' => 'ios',
                'device_name' => 'iPhone',
            ])->assertNoContent();

        $token = ParticipantPushToken::firstOrFail();
        $this->assertSame($participant->id, $token->participant_id);
        $this->assertNotNull($token->last_seen_at);
    }

    public function test_token_de_push_muda_de_dono(): void
    {
        $first = $this->makeParticipant('primeira@example.com');
        $second = $this->makeParticipant('segunda@example.com');
        $first->pushTokens()->create(['token' => 'ExponentPushToken[mesmo]', 'platform' => 'android']);

        $this->withToken($second->createToken('app')->plainTextToken)
            ->postJson('/api/v1/push-tokens', ['token' => 'ExponentPushToken[mesmo]', 'platform' => 'android'])
            ->assertNoContent();

        $this->assertSame(1, ParticipantPushToken::count());
        $this->assertSame($second->id, ParticipantPushToken::first()->participant_id);
    }

    public function test_token_fora_do_formato_do_expo_e_recusado(): void
    {
        $participant = $this->makeParticipant();

        $this->withToken($participant->createToken('app')->plainTextToken)
            ->postJson('/api/v1/push-tokens', ['token' => 'qualquer-coisa', 'platform' => 'ios'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('token');
    }

    public function test_apaga_so_o_proprio_token(): void
    {
        $mine = $this->makeParticipant('maria@example.com');
        $other = $this->makeParticipant('outra@example.com');
        $mine->pushTokens()->create(['token' => 'ExponentPushToken[meu]', 'platform' => 'ios']);
        $other->pushTokens()->create(['token' => 'ExponentPushToken[dela]', 'platform' => 'ios']);

        $api = $this->withToken($mine->createToken('app')->plainTextToken);
        $api->deleteJson('/api/v1/push-tokens/'.rawurlencode('ExponentPushToken[dela]'))->assertNoContent();
        $api->deleteJson('/api/v1/push-tokens/'.rawurlencode('ExponentPushToken[meu]'))->assertNoContent();

        $this->assertSame(['ExponentPushToken[dela]'], ParticipantPushToken::pluck('token')->all());
    }

    public function test_excluir_conta_apaga_tudo_do_app_e_mantem_as_inscricoes(): void
    {
        $participant = $this->makeParticipant('maria@example.com');
        $inscription = $this->makeInscription($this->makeEvent(), ['email' => 'maria@example.com']);
        $participant->pushTokens()->create(['token' => 'ExponentPushToken[abc]', 'platform' => 'ios']);
        ParticipantLoginCode::create(['email' => 'maria@example.com', 'code_hash' => 'x', 'expires_at' => now()->addMinutes(5)]);
        $token = $participant->createToken('app')->plainTextToken;
        $participant->createToken('outro aparelho');

        $this->withToken($token)->deleteJson('/api/v1/account')->assertNoContent();

        $this->assertSame(0, Participant::count());
        $this->assertSame(0, ParticipantPushToken::count());
        $this->assertSame(0, ParticipantLoginCode::count());
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertTrue(Inscription::whereKey($inscription->id)->exists());
    }

    public function test_token_de_conta_excluida_nao_entra_mais(): void
    {
        $participant = $this->makeParticipant();
        $token = $participant->createToken('app')->plainTextToken;

        $this->withToken($token)->deleteJson('/api/v1/account')->assertNoContent();
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/v1/me')->assertStatus(401);
    }
}
