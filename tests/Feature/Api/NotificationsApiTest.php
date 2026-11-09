<?php

namespace Tests\Feature\Api;

use App\Models\ParticipantNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesInscriptions;
use Tests\TestCase;

/**
 * A aba "Avisos" do app (spec 2026-11-08-app-mobile-design, AM41 a AM43).
 */
class NotificationsApiTest extends TestCase
{
    use MakesInscriptions;
    use RefreshDatabase;

    private function notice(string $email, array $overrides = []): ParticipantNotification
    {
        return ParticipantNotification::create(array_merge([
            'email' => $email,
            'kind' => 'approved',
            'title' => 'Sua participação foi aprovada!',
            'body' => 'Envie o comprovante.',
        ], $overrides));
    }

    public function test_lista_os_avisos_do_email_da_conta_do_mais_novo_ao_mais_antigo(): void
    {
        $participant = $this->makeParticipant('maria@example.com');
        $old = $this->notice('maria@example.com', ['kind' => 'received', 'read_at' => now()]);
        $new = $this->notice('maria@example.com', ['kind' => 'approved']);
        $this->notice('outra@example.com');
        $this->notice('marïa@example.com');

        $this->withToken($participant->createToken('app')->plainTextToken)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('data.0.id', $new->id)
            ->assertJsonPath('data.0.read', false)
            ->assertJsonPath('data.1.id', $old->id)
            ->assertJsonPath('data.1.read', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_quem_instala_depois_encontra_o_historico(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), ['email' => 'Maria@Example.com', 'status' => 'pendente']);
        app(NotificationService::class)->notifyInscriptionReceived($inscription);

        $participant = $this->makeParticipant('maria@example.com');

        $this->withToken($participant->createToken('app')->plainTextToken)
            ->getJson('/api/v1/notifications')
            ->assertJsonPath('data.0.kind', 'received')
            ->assertJsonPath('data.0.inscription_id', $inscription->id);
    }

    public function test_marcar_um_e_todos_como_lidos(): void
    {
        $participant = $this->makeParticipant('maria@example.com');
        $first = $this->notice('maria@example.com');
        $second = $this->notice('maria@example.com');
        $api = $this->withToken($participant->createToken('app')->plainTextToken);

        $api->postJson("/api/v1/notifications/{$first->id}/read")->assertNoContent();
        $this->assertNotNull($first->refresh()->read_at);
        $this->assertNull($second->refresh()->read_at);

        $api->postJson('/api/v1/notifications/read-all')->assertNoContent();
        $this->assertSame(0, ParticipantNotification::unread()->count());
    }

    public function test_aviso_de_outro_email_da_404_e_continua_nao_lido(): void
    {
        $participant = $this->makeParticipant('maria@example.com');
        $other = $this->notice('outra@example.com');
        $api = $this->withToken($participant->createToken('app')->plainTextToken);

        $api->postJson("/api/v1/notifications/{$other->id}/read")->assertNotFound();
        $api->postJson('/api/v1/notifications/read-all')->assertNoContent();

        $this->assertNull($other->refresh()->read_at);
    }

    public function test_sem_token_nao_ve_avisos(): void
    {
        $this->getJson('/api/v1/notifications')->assertStatus(401);
    }

    public function test_excluir_a_conta_apaga_os_avisos(): void
    {
        $participant = $this->makeParticipant('maria@example.com');
        $this->notice('maria@example.com');
        $this->notice('outra@example.com');

        $this->withToken($participant->createToken('app')->plainTextToken)->deleteJson('/api/v1/account')->assertNoContent();

        $this->assertSame(['outra@example.com'], ParticipantNotification::pluck('email')->all());
    }
}
