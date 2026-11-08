<?php

namespace Tests\Feature\Api;

use App\Models\Inscription;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesInscriptions;
use Tests\TestCase;

/**
 * As inscrições do participante pelo app (spec 2026-11-08-app-mobile-design,
 * AM11 a AM19).
 */
class InscriptionsApiTest extends TestCase
{
    use MakesInscriptions;
    use RefreshDatabase;

    private Participant $participant;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->acceptAnyMailDomain();
        $this->participant = $this->makeParticipant('maria@example.com');
        $this->token = $this->participant->createToken('app')->plainTextToken;
    }

    private function api(): static
    {
        return $this->withToken($this->token);
    }

    private function payload(int $eventId, array $overrides = []): array
    {
        return array_merge([
            'event_id' => $eventId,
            'full_name' => 'Maria Velunora',
            'cpf' => '529.982.247-25',
            'birth_date' => '1990-01-01',
            'city_neighborhood' => 'Centro, Vitória',
            'whatsapp' => '(27) 99999-0000',
            'instagram' => '@mariavelunora',
            'motivation' => 'Quero muito participar deste encontro especial.',
            'terms_accepted' => true,
        ], $overrides);
    }

    public function test_lista_so_as_inscricoes_do_email_da_conta_proximas_primeiro(): void
    {
        $far = $this->makeInscription($this->makeEvent(['date' => now()->addDays(30)]), ['email' => 'MARIA@example.com', 'status' => 'pendente']);
        $near = $this->makeInscription($this->makeEvent(['date' => now()->addDays(2)]), ['email' => 'maria@example.com', 'status' => 'pendente']);
        $past = $this->makeInscription($this->makeEvent(['date' => now()->subDays(10)]), ['email' => 'maria@example.com', 'status' => 'confirmado']);
        $this->makeInscription($this->makeEvent(), ['email' => 'outra@example.com', 'status' => 'pendente']);

        $response = $this->api()->getJson('/api/v1/inscriptions')->assertOk();

        $this->assertSame([$near->id, $far->id, $past->id], array_column($response->json('data'), 'id'));
        $this->assertSame([true, true, false], array_column($response->json('data'), 'upcoming'));
    }

    public function test_inscricao_de_outro_email_da_404_em_todas_as_rotas(): void
    {
        Storage::fake('public');
        $other = $this->makeInscription($this->makeEvent(), ['email' => 'outra@example.com', 'payment_deadline_at' => now()->addHour()]);

        $this->api()->getJson("/api/v1/inscriptions/{$other->id}")->assertNotFound();
        $this->api()->postJson("/api/v1/inscriptions/{$other->id}/cancel")->assertNotFound();
        $this->api()->postJson("/api/v1/inscriptions/{$other->id}/social-request", [
            'reason' => 'Uma justificativa com mais de vinte caracteres.',
            'amount' => 10,
        ])->assertNotFound();
        $this->api()->post("/api/v1/inscriptions/{$other->id}/payment-proof", [
            'payment_proof' => UploadedFile::fake()->image('pix.jpg'),
        ])->assertNotFound();

        $this->assertSame('aprovado', $other->refresh()->status);
        $this->assertNull($other->payment_proof);
        $this->assertNull($other->social_request_status);
    }

    public function test_estado_e_acoes_de_cada_situacao(): void
    {
        $event = $this->makeEvent();
        $full = $this->makeEvent(['capacity' => 1, 'confirmed_count' => 1]);
        $cases = [
            'pending' => [$event, ['status' => 'pendente'], ['upload_proof' => false, 'request_social' => false, 'cancel' => true]],
            'pending_full' => [$full, ['status' => 'pendente'], ['upload_proof' => false, 'request_social' => false, 'cancel' => true]],
            'approved' => [$event, ['status' => 'aprovado', 'payment_deadline_at' => now()->addHour()], ['upload_proof' => true, 'request_social' => true, 'cancel' => true]],
            'approved_full' => [$full, ['status' => 'aprovado'], ['upload_proof' => false, 'request_social' => false, 'cancel' => true]],
            'confirmed' => [$event, ['status' => 'confirmado'], ['upload_proof' => false, 'request_social' => false, 'cancel' => false]],
            'waitlisted' => [$event, ['status' => 'fila_de_espera'], ['upload_proof' => false, 'request_social' => false, 'cancel' => true]],
            'expired' => [$event, ['status' => 'fila_de_espera', 'payment_expired_at' => now()], ['upload_proof' => false, 'request_social' => false, 'cancel' => true]],
            'rejected' => [$event, ['status' => 'rejeitado'], ['upload_proof' => false, 'request_social' => false, 'cancel' => false]],
            'cancelled_by_admin' => [$event, ['status' => 'cancelado', 'cancelled_by' => 'admin'], ['upload_proof' => false, 'request_social' => false, 'cancel' => false]],
            'cancelled_by_participant' => [$event, ['status' => 'cancelado', 'cancelled_by' => 'participant'], ['upload_proof' => false, 'request_social' => false, 'cancel' => false]],
        ];

        foreach ($cases as $state => [$caseEvent, $attributes, $can]) {
            $inscription = $this->makeInscription($caseEvent, [...$attributes, 'email' => 'maria@example.com', 'cpf' => uniqid()]);
            if (isset($attributes['payment_expired_at'])) {
                $inscription->forceFill(['payment_expired_at' => $attributes['payment_expired_at']])->save();
            }

            $this->api()->getJson("/api/v1/inscriptions/{$inscription->id}")
                ->assertOk()
                ->assertJsonPath('state', $state)
                ->assertJsonPath('can', $can);
        }
    }

    public function test_pix_so_sai_para_quem_pode_enviar_comprovante(): void
    {
        config(['services.pix.key' => 'pix@velunora.example', 'services.pix.holder' => 'Turnê Velunora']);
        $event = $this->makeEvent();
        $approved = $this->makeInscription($event, ['email' => 'maria@example.com', 'payment_deadline_at' => now()->addHours(6)]);
        $pending = $this->makeInscription($event, ['email' => 'maria@example.com', 'status' => 'pendente', 'cpf' => '111.444.777-35']);

        $this->api()->getJson("/api/v1/inscriptions/{$approved->id}")
            ->assertJsonPath('payment.pix.key', 'pix@velunora.example')
            ->assertJsonPath('payment.pix.holder', 'Turnê Velunora')
            ->assertJsonPath('payment.proof_sent', false)
            ->assertJsonPath('payment.paused', false)
            ->assertJsonPath('payment.deadline_label', '1 hora');

        $pendingBody = $this->api()->getJson("/api/v1/inscriptions/{$pending->id}")->assertJsonPath('payment', null);
        $this->assertStringNotContainsString('pix@velunora.example', $pendingBody->getContent());
    }

    public function test_endereco_e_horario_so_para_confirmado(): void
    {
        $event = $this->makeEvent(['address' => 'Rua Velunora, 10', 'arrival_time' => '19h']);
        $confirmed = $this->makeInscription($event, ['email' => 'maria@example.com', 'status' => 'confirmado']);
        $approved = $this->makeInscription($event, ['email' => 'maria@example.com', 'cpf' => '111.444.777-35', 'payment_deadline_at' => now()->addHour()]);

        $this->api()->getJson("/api/v1/inscriptions/{$confirmed->id}")
            ->assertJsonPath('event.address', 'Rua Velunora, 10')
            ->assertJsonPath('event.arrival_time', '19h');

        $body = $this->api()->getJson("/api/v1/inscriptions/{$approved->id}")
            ->assertJsonPath('event.address', null)
            ->assertJsonPath('event.arrival_time', null)
            ->getContent();
        $this->assertStringNotContainsString('Rua Velunora', $body);
    }

    public function test_inscrever_pelo_app_usa_o_email_da_conta(): void
    {
        $event = $this->makeEvent();

        $response = $this->api()->postJson('/api/v1/inscriptions', $this->payload($event->id, ['email' => 'tentativa@example.com']));

        $response->assertCreated()->assertJsonPath('state', 'pending');
        $inscription = Inscription::findOrFail($response->json('id'));
        $this->assertSame('maria@example.com', $inscription->email);
        $this->assertSame('pendente', $inscription->status);
    }

    public function test_inscricao_duplicada_responde_409_com_a_existente(): void
    {
        $event = $this->makeEvent();
        $existing = $this->makeInscription($event, ['email' => 'maria@example.com', 'status' => 'pendente', 'cpf' => '111.444.777-35']);

        $this->api()->postJson('/api/v1/inscriptions', $this->payload($event->id))
            ->assertStatus(409)
            ->assertJsonPath('code', 'already_registered')
            ->assertJsonPath('inscription_id', $existing->id);
    }

    public function test_encontro_lotado_ou_fechado_recusa(): void
    {
        $full = $this->makeEvent(['capacity' => 2, 'confirmed_count' => 2]);
        $draft = $this->makeEvent(['status' => 'draft']);

        $this->api()->postJson('/api/v1/inscriptions', $this->payload($full->id))
            ->assertStatus(422)
            ->assertJsonPath('code', 'full')
            ->assertJsonPath('message', 'As vagas para este encontro estão esgotadas.');
        $this->api()->postJson('/api/v1/inscriptions', $this->payload($draft->id))
            ->assertStatus(422)
            ->assertJsonPath('code', 'unavailable');
        $this->assertSame(0, Inscription::count());
    }

    public function test_dados_invalidos_voltam_com_as_mensagens_da_web(): void
    {
        $event = $this->makeEvent();

        $this->api()->postJson('/api/v1/inscriptions', $this->payload($event->id, [
            'cpf' => '123.456.789-00',
            'motivation' => 'curta',
            'terms_accepted' => false,
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.cpf.0', 'O CPF informado é inválido.')
            ->assertJsonPath('errors.motivation.0', 'Sua história deve ter pelo menos 10 caracteres.')
            ->assertJsonPath('errors.terms_accepted.0', 'Você deve aceitar os termos.');
    }

    public function test_comprovante_de_aprovado_e_aceito_e_substitui_o_anterior(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('payment_proofs/antigo.jpg', 'x');
        $inscription = $this->makeInscription($this->makeEvent(), [
            'email' => 'maria@example.com',
            'payment_deadline_at' => now()->addHour(),
            'payment_proof' => 'payment_proofs/antigo.jpg',
        ]);

        $this->api()->post("/api/v1/inscriptions/{$inscription->id}/payment-proof", [
            'payment_proof' => UploadedFile::fake()->image('pix.jpg'),
        ])->assertOk()->assertJsonPath('payment.proof_sent', true);

        $inscription->refresh();
        $this->assertNotSame('payment_proofs/antigo.jpg', $inscription->payment_proof);
        Storage::disk('public')->assertExists($inscription->payment_proof);
        Storage::disk('public')->assertMissing('payment_proofs/antigo.jpg');
    }

    public function test_comprovante_depois_do_prazo_e_recusado(): void
    {
        Storage::fake('public');
        $inscription = $this->makeInscription($this->makeEvent(), ['email' => 'maria@example.com', 'status' => 'fila_de_espera']);
        $inscription->forceFill(['payment_expired_at' => now()])->save();

        $this->api()->post("/api/v1/inscriptions/{$inscription->id}/payment-proof", [
            'payment_proof' => UploadedFile::fake()->image('pix.jpg'),
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'deadline_passed');
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_comprovante_com_tipo_errado_e_recusado(): void
    {
        Storage::fake('public');
        $inscription = $this->makeInscription($this->makeEvent(), ['email' => 'maria@example.com', 'payment_deadline_at' => now()->addHour()]);

        $this->api()->post("/api/v1/inscriptions/{$inscription->id}/payment-proof", [
            'payment_proof' => UploadedFile::fake()->create('pix.docx', 10),
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.payment_proof.0', 'O comprovante deve ser uma imagem (JPG, PNG) ou PDF.');
    }

    public function test_pedido_social_pausa_o_prazo(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), ['email' => 'maria@example.com', 'payment_deadline_at' => now()->addHour()]);

        $this->api()->postJson("/api/v1/inscriptions/{$inscription->id}/social-request", [
            'reason' => 'Estou desempregada neste mês e consigo contribuir com menos.',
            'amount' => 35.5,
        ])
            ->assertOk()
            ->assertJsonPath('social_request.status', 'pendente')
            ->assertJsonPath('social_request.amount', '35.50')
            ->assertJsonPath('payment.paused', true)
            ->assertJsonPath('can.request_social', false);

        $this->api()->postJson("/api/v1/inscriptions/{$inscription->id}/social-request", [
            'reason' => 'Estou desempregada neste mês e consigo contribuir com menos.',
            'amount' => 20,
        ])->assertStatus(422)->assertJsonPath('code', 'already_requested');
    }

    public function test_cancelar_pelo_app(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), ['email' => 'maria@example.com', 'status' => 'pendente']);

        $this->api()->postJson("/api/v1/inscriptions/{$inscription->id}/cancel")
            ->assertOk()
            ->assertJsonPath('state', 'cancelled_by_participant')
            ->assertJsonPath('can.cancel', false);
        $this->assertSame('participant', $inscription->refresh()->cancelled_by);
    }

    public function test_confirmado_nao_cancela_pelo_app(): void
    {
        $event = $this->makeEvent(['confirmed_count' => 1]);
        $inscription = $this->makeInscription($event, ['email' => 'maria@example.com', 'status' => 'confirmado']);

        $this->api()->postJson("/api/v1/inscriptions/{$inscription->id}/cancel")
            ->assertStatus(422)
            ->assertJsonPath('code', 'confirmed');
        $this->assertSame('confirmado', $inscription->refresh()->status);
        $this->assertSame(1, $event->refresh()->confirmed_count);
    }
}
