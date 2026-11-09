<?php

namespace Tests\Feature\Api;

use App\Jobs\SendParticipantPush;
use App\Models\Notification;
use App\Models\ParticipantNotification;
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
 * Cada e-mail ao participante vira um aviso do app (aba "Avisos") e, para
 * quem ativou os avisos, um push (spec 2026-11-08-app-mobile-design, AM21 a
 * AM25 e AM41 a AM46).
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

    private function withDevice(string $email = 'maria@example.com'): void
    {
        $this->makeParticipant($email)->pushTokens()->create(['token' => 'ExponentPushToken[m]', 'platform' => 'ios']);
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

        $notice = ParticipantNotification::firstOrFail();
        Http::assertSent(function (Request $request) use ($inscription, $notice) {
            $message = $request->data()[0];

            return $request->url() === ExpoPushClient::SEND_URL
                && $message['to'] === 'ExponentPushToken[maria]'
                && $message['title'] === 'Sua participação foi aprovada!'
                && $message['body'] === 'Envie o comprovante em até 24 horas para garantir seu lugar na sala em Serra.'
                && $message['data'] === ['notification_id' => $notice->id, 'inscription_id' => $inscription->id, 'kind' => 'approved'];
        });
        // O e-mail sai como sempre.
        $this->assertTrue(Notification::where('channel', 'inscription_approved')->where('type', 'email')->exists());
    }

    public function test_todo_email_ao_participante_vira_aviso_do_app(): void
    {
        Queue::fake();
        $event = $this->makeEvent(['city' => 'Serra']);
        $other = $this->makeEvent(['city' => 'Guarapari']);
        $inscription = $this->makeInscription($event, ['payment_deadline_at' => now()->addHour(), 'social_request_amount' => 40]);
        $service = $this->notifications();

        $service->notifyInscriptionReceived($inscription);
        $service->notifyInscriptionApproved($inscription);
        $service->pushPaymentReminder($inscription);
        $service->notifyInscriptionWaitlisted($inscription);
        $service->notifyInscriptionExpired($inscription);
        $service->notifyInscriptionRejected($inscription);
        $service->notifyInscriptionCancelledByAdmin($inscription);
        $service->notifyInscriptionCancelled($inscription);
        $service->notifyInscriptionConfirmed($inscription);
        $service->notifySocialRequestSubmitted($inscription);
        $service->notifySocialRequestApproved($inscription);
        $service->notifySocialRequestRejected($inscription);
        $service->notifyInscriptionMigrated($inscription, $other, 'Serra');
        $service->notifyEventFull($inscription);
        $service->notifyCustomMessage($inscription, 'Lembrete', 'Tragam almofada.');

        $this->assertSame([
            'received', 'approved', 'payment_reminder', 'waitlisted', 'expired', 'rejected', 'cancelled_by_admin',
            'cancelled_by_participant', 'confirmed', 'social_submitted', 'social_approved', 'social_rejected',
            'migrated', 'event_full', 'custom_message',
        ], ParticipantNotification::orderBy('id')->pluck('kind')->all());
        $this->assertSame(15, ParticipantNotification::where('email', 'maria@example.com')->where('inscription_id', $inscription->id)->count());
        // Sem aparelho, nada vai para a fila: o aviso fica guardado para quando instalar.
        Queue::assertNothingPushed();
    }

    public function test_lembrete_de_comprovante_do_painel_tambem_avisa(): void
    {
        Queue::fake();
        $this->withDevice();
        $admin = \App\Models\User::create(['name' => 'Equipe', 'email' => 'equipe@example.com', 'password' => bcrypt('x'), 'role' => 'admin']);
        $inscription = $this->makeInscription($this->makeEvent(), ['payment_deadline_at' => now()->addHours(3)]);

        $this->actingAs($admin)->post(route('admin.inscricoes.send-reminder', $inscription));

        $this->assertSame('payment_reminder', ParticipantNotification::firstOrFail()->kind);
        Queue::assertPushed(SendParticipantPush::class, 1);
    }

    public function test_push_vai_pela_fila_com_o_id_do_aviso(): void
    {
        Queue::fake();
        $this->withDevice();
        $inscription = $this->makeInscription($this->makeEvent(), ['status' => 'confirmado']);

        $this->notifications()->notifyInscriptionConfirmed($inscription);

        $notice = ParticipantNotification::firstOrFail();
        $this->assertSame('Confirmado! Prepare o coração', $notice->title);
        Queue::assertPushed(SendParticipantPush::class, fn (SendParticipantPush $job) => $job->notificationId === $notice->id);
    }

    public function test_acao_do_proprio_participante_tambem_avisa(): void
    {
        Queue::fake();
        $participant = $this->makeParticipant('maria@example.com');
        $participant->pushTokens()->create(['token' => 'ExponentPushToken[m]', 'platform' => 'ios']);
        $inscription = $this->makeInscription($this->makeEvent(), ['status' => 'pendente']);

        $this->withToken($participant->createToken('app')->plainTextToken)
            ->postJson("/api/v1/inscriptions/{$inscription->id}/cancel")
            ->assertOk();

        $this->assertSame('cancelled_by_participant', ParticipantNotification::firstOrFail()->kind);
        Queue::assertPushed(SendParticipantPush::class, 1);
    }

    public function test_aviso_de_inscricao_com_email_acentuado_nao_vai_para_a_conta_sem_acento(): void
    {
        Queue::fake();
        $this->withDevice('maria@example.com');
        $inscription = $this->makeInscription($this->makeEvent(), ['status' => 'confirmado', 'email' => 'marïa@example.com']);

        $this->notifications()->notifyInscriptionConfirmed($inscription);

        Queue::assertNothingPushed();
        $this->assertSame(0, ParticipantNotification::where('email', 'maria@example.com')->count());
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

    public function test_falha_do_expo_nao_impede_o_email_nem_o_aviso(): void
    {
        Http::fake([ExpoPushClient::SEND_URL => Http::response(['errors' => [['code' => 'INTERNAL']]], 500)]);
        $this->withDevice();
        $inscription = $this->makeInscription($this->makeEvent(), ['status' => 'fila_de_espera']);

        $this->notifications()->notifyInscriptionWaitlisted($inscription);

        $this->assertTrue(Notification::where('channel', 'inscription_waitlisted')->where('status', 'sent')->exists());
        $this->assertSame('waitlisted', ParticipantNotification::firstOrFail()->kind);
        $this->assertSame(1, ParticipantPushToken::count());
    }

    public function test_mensagem_personalizada_vira_aviso_curto_sem_html(): void
    {
        Queue::fake();
        $inscription = $this->makeInscription($this->makeEvent(), ['status' => 'confirmado']);

        $this->notifications()->notifyCustomMessage($inscription, 'Lembrete do encontro', "<p>Tragam\n  uma almofada!</p>");

        $notice = ParticipantNotification::firstOrFail();
        $this->assertSame('Lembrete do encontro', $notice->title);
        $this->assertSame('Tragam uma almofada!', $notice->body);
    }

    public function test_textos_dos_avisos_sem_travessao(): void
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
