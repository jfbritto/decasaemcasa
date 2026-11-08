<?php

namespace Tests\Feature\Api;

use App\Jobs\SendParticipantPush;
use App\Models\Notification;
use App\Models\ParticipantPushToken;
use App\Notifications\Push\ExpoPushClient;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\MakesInscriptions;
use Tests\TestCase;

/**
 * Push do app ao lado do e-mail (spec 2026-11-08-app-mobile-design, AM21 a
 * AM25).
 */
class PushNotificationTest extends TestCase
{
    use MakesInscriptions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    private function notifications(): NotificationService
    {
        return app(NotificationService::class);
    }

    private function fakeExpo(array $tickets = [['status' => 'ok', 'id' => 'ticket-1']]): void
    {
        Http::fake([ExpoPushClient::SEND_URL => Http::response(['data' => $tickets])]);
    }

    public function test_aprovacao_manda_push_para_o_aparelho_do_dono(): void
    {
        $this->fakeExpo();
        $participant = $this->makeParticipant('maria@example.com');
        $participant->pushTokens()->create(['token' => 'ExponentPushToken[maria]', 'platform' => 'ios']);
        $inscription = $this->makeInscription($this->makeEvent(['city' => 'Serra']), [
            'email' => 'Maria@Example.com',
            'payment_deadline_at' => now()->addDay(),
        ]);
        $inscription->forceFill(['payment_deadline_minutes' => 1440])->save();

        $this->notifications()->notifyInscriptionApproved($inscription);

        Http::assertSent(function (Request $request) use ($inscription) {
            $message = $request->data()[0];

            return $request->url() === ExpoPushClient::SEND_URL
                && $message['to'] === 'ExponentPushToken[maria]'
                && $message['title'] === 'Sua participação foi aprovada!'
                && $message['body'] === 'Envie o comprovante em até 24 horas para garantir seu lugar na sala em Serra.'
                && $message['data'] === ['inscription_id' => $inscription->id, 'kind' => 'approved'];
        });
        // O e-mail sai como sempre.
        $this->assertTrue(Notification::where('channel', 'inscription_approved')->where('type', 'email')->exists());
    }

    public function test_sem_aparelho_nada_vai_para_a_fila(): void
    {
        Queue::fake();
        $inscription = $this->makeInscription($this->makeEvent(), ['status' => 'confirmado']);

        $this->notifications()->notifyInscriptionConfirmed($inscription);

        Queue::assertNothingPushed();
    }

    public function test_push_vai_pela_fila(): void
    {
        Queue::fake();
        $this->makeParticipant('maria@example.com')->pushTokens()->create(['token' => 'ExponentPushToken[m]', 'platform' => 'android']);
        $inscription = $this->makeInscription($this->makeEvent(), ['status' => 'confirmado']);

        $this->notifications()->notifyInscriptionConfirmed($inscription);

        Queue::assertPushed(SendParticipantPush::class, fn (SendParticipantPush $job) => $job->kind === 'confirmed'
            && $job->inscriptionId === $inscription->id
            && $job->title === 'Confirmado! Prepare o coração');
    }

    public function test_token_morto_sai_na_hora(): void
    {
        $this->fakeExpo([
            ['status' => 'error', 'message' => 'not registered', 'details' => ['error' => 'DeviceNotRegistered']],
            ['status' => 'ok', 'id' => 'ticket-2'],
        ]);
        $participant = $this->makeParticipant('maria@example.com');
        $participant->pushTokens()->create(['token' => 'ExponentPushToken[morto]', 'platform' => 'ios']);
        $participant->pushTokens()->create(['token' => 'ExponentPushToken[vivo]', 'platform' => 'android']);
        $inscription = $this->makeInscription($this->makeEvent(), ['status' => 'rejeitado']);

        $this->notifications()->notifyInscriptionRejected($inscription);

        $this->assertSame(['ExponentPushToken[vivo]'], ParticipantPushToken::pluck('token')->all());
    }

    public function test_falha_do_expo_nao_impede_o_email(): void
    {
        Http::fake([ExpoPushClient::SEND_URL => Http::response(['errors' => [['code' => 'INTERNAL']]], 500)]);
        $this->makeParticipant('maria@example.com')->pushTokens()->create(['token' => 'ExponentPushToken[m]', 'platform' => 'ios']);
        $inscription = $this->makeInscription($this->makeEvent(), ['status' => 'fila_de_espera']);

        $this->notifications()->notifyInscriptionWaitlisted($inscription);

        $this->assertTrue(Notification::where('channel', 'inscription_waitlisted')->where('status', 'sent')->exists());
        $this->assertSame(1, ParticipantPushToken::count());
    }

    public function test_acao_do_proprio_participante_nao_gera_push(): void
    {
        Queue::fake();
        $participant = $this->makeParticipant('maria@example.com');
        $participant->pushTokens()->create(['token' => 'ExponentPushToken[m]', 'platform' => 'ios']);
        $inscription = $this->makeInscription($this->makeEvent(), ['status' => 'pendente']);

        $this->withToken($participant->createToken('app')->plainTextToken)
            ->postJson("/api/v1/inscriptions/{$inscription->id}/cancel")
            ->assertOk();

        Queue::assertNotPushed(SendParticipantPush::class);
    }

    public function test_mensagem_personalizada_vira_push_curto_sem_html(): void
    {
        Queue::fake();
        $this->makeParticipant('maria@example.com')->pushTokens()->create(['token' => 'ExponentPushToken[m]', 'platform' => 'ios']);
        $inscription = $this->makeInscription($this->makeEvent(), ['status' => 'confirmado']);

        $this->notifications()->notifyCustomMessage($inscription, 'Lembrete do encontro', "<p>Tragam\n  uma almofada!</p>");

        Queue::assertPushed(SendParticipantPush::class, fn (SendParticipantPush $job) => $job->kind === 'custom_message'
            && $job->title === 'Lembrete do encontro'
            && $job->body === 'Tragam uma almofada!');
    }

    public function test_textos_de_push_sem_travessao(): void
    {
        $source = file_get_contents(app_path('Notifications/Push/ParticipantPush.php'));

        // Comentários podem ter; os textos (entre aspas) não.
        preg_match_all("/(['\"])(?:(?!\\1).)*\\1/u", $source, $strings);
        foreach ($strings[0] as $literal) {
            $this->assertStringNotContainsString('—', $literal);
            $this->assertStringNotContainsString('–', $literal);
            $this->assertStringNotContainsStringIgnoringCase('cadeira', $literal);
            $this->assertStringNotContainsStringIgnoringCase('whatsapp', $literal);
        }
    }
}
