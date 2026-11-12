<?php

namespace Tests\Feature\Admin;

use App\Jobs\ResendFailedNotification;
use App\Models\Notification;
use App\Models\User;
use App\Participants\AppAnnouncementAudience;
use App\Services\NotificationResendService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\MakesInscriptions;
use Tests\TestCase;

/**
 * A divulgação do app no painel de notificações (spec 2026-11-12-email-divulgacao-app-design, DA11).
 */
class AppAnnouncementPanelTest extends TestCase
{
    use MakesInscriptions;
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Equipe Velunora', 'email' => 'equipe@example.com', 'password' => bcrypt('x'), 'role' => 'admin']);
    }

    private function failed(array $metadata, string $channel = AppAnnouncementAudience::CHANNEL): Notification
    {
        return Notification::create([
            'type' => 'email',
            'channel' => $channel,
            'recipient' => 'maria@example.com',
            'subject' => 'O De Casa em Casa agora tem app',
            'message' => 'Aviso do app para Maria Velunora',
            'status' => 'failed',
            'error_message' => '550 5.4.6 Hourly Quota Exceeded',
            'metadata' => $metadata,
        ]);
    }

    private function sentHtml(): string
    {
        return app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->sole()->getOriginalMessage()->getHtmlBody();
    }

    public function test_reenviar_usa_o_modelo_do_aviso(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), ['full_name' => 'Maria Velunora']);
        $failed = $this->failed(['inscription_id' => $inscription->id]);

        $this->assertTrue(app(NotificationResendService::class)->resend($failed));

        $resent = Notification::where('status', 'sent')->sole();
        $this->assertSame(AppAnnouncementAudience::CHANNEL, $resent->channel);
        $this->assertSame($failed->id, $resent->metadata['resent_from']);
        $this->assertTrue($resent->metadata['template_used']);
        $html = $this->sentHtml();
        $this->assertStringContainsString('Olá <strong>Maria Velunora</strong>,', $html);
        $this->assertStringContainsString('código de 6 números', $html);
    }

    public function test_reenviar_um_exemplo_continua_exemplo_e_sem_nome(): void
    {
        $failed = $this->failed(['sample' => true]);

        $this->assertTrue(app(NotificationResendService::class)->resend($failed));

        $this->assertTrue(Notification::where('status', 'sent')->sole()->metadata['sample']);
        $this->assertStringContainsString('Olá!', $this->sentHtml());
    }

    public function test_reenviar_pendentes_ignora_o_aviso_do_app(): void
    {
        Queue::fake();
        $announcement = $this->failed(['inscription_id' => 1]);
        $other = $this->failed(['inscription_id' => 2], 'inscription_received');

        $this->actingAs($this->admin())
            ->post(route('admin.notificacoes.bulk-resend-rate-limit'))
            ->assertRedirect();

        Queue::assertPushed(ResendFailedNotification::class, 1);
        Queue::assertPushed(ResendFailedNotification::class, fn (ResendFailedNotification $job) => $job->notification->is($other));
        $this->assertArrayNotHasKey('resend_queued_at', $announcement->fresh()->metadata);
    }

    public function test_painel_tem_o_filtro_e_o_rotulo(): void
    {
        $this->failed(['sample' => true]);

        $this->actingAs($this->admin())
            ->get(route('admin.notificacoes.index'))
            ->assertOk()
            ->assertSee('<option value="app_announcement"', false)
            ->assertSeeText('Divulgação do app');
    }
}
