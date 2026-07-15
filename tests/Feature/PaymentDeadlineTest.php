<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Inscription;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentDeadlineTest extends TestCase
{
    use RefreshDatabase;

    private function makeEvent(array $overrides = []): Event
    {
        // ATENÇÃO: conferir campos NOT NULL em
        // database/migrations/*create_events_table* e *add_new_fields_to_events*
        // e ajustar este helper se necessário.
        return Event::create(array_merge([
            'title' => 'Encontro Teste',
            'slug' => 'encontro-teste-'.uniqid(), // events.slug é NOT NULL UNIQUE, sem auto-geração no model
            'city' => 'Vitória',
            'date' => now()->addMonth(),
            'status' => 'published',
            'capacity' => 10,
            'confirmed_count' => 0,
        ], $overrides));
    }

    private function makeInscription(Event $event, array $overrides = []): Inscription
    {
        return Inscription::create(array_merge([
            'event_id' => $event->id,
            'full_name' => 'Maria da Silva',
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

    private function logApprovalNotificationSent(Inscription $inscription): void
    {
        Notification::create([
            'type' => 'email',
            'channel' => 'inscription_approved',
            'recipient' => $inscription->email,
            'subject' => 'Aprovado',
            'message' => 'Aprovado',
            'status' => 'sent',
            'metadata' => ['inscription_id' => $inscription->id],
            'sent_at' => $inscription->approved_at,
        ]);
    }

    public function test_aprovar_define_prazo_de_pagamento(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), [
            'status' => 'pendente',
            'approved_at' => null,
            'payment_deadline_at' => null,
        ]);

        $inscription->approve();

        $this->assertNotNull($inscription->payment_deadline_at);
        $this->assertTrue(
            $inscription->payment_deadline_at->between(now()->addMinutes(59), now()->addMinutes(61))
        );
        $this->assertNull($inscription->payment_expired_at);
    }

    public function test_decisao_da_solicitacao_social_reinicia_prazo(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), [
            'social_request_status' => 'pendente',
            'social_request_reason' => 'Situação financeira difícil no momento.',
            'social_request_amount' => 30.00,
        ]);

        $inscription->approveSocialRequest(30.00, 'Combinado!', null);

        $this->assertTrue($inscription->payment_deadline_at->isFuture());
    }

    public function test_expire_to_waitlist_expira_inscricao_elegivel(): void
    {
        $inscription = $this->makeInscription($this->makeEvent());

        $this->assertTrue($inscription->expireToWaitlist());
        $this->assertSame('fila_de_espera', $inscription->status);
        $this->assertNotNull($inscription->payment_expired_at);
    }

    public function test_expire_to_waitlist_nao_expira_com_comprovante(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), [
            'payment_proof' => 'payment_proofs/teste.jpg',
        ]);

        $this->assertFalse($inscription->expireToWaitlist());
        $this->assertSame('aprovado', $inscription->fresh()->status);
    }

    public function test_expire_to_waitlist_nao_expira_com_solicitacao_social_pendente(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), [
            'social_request_status' => 'pendente',
            'social_request_reason' => 'Situação financeira difícil no momento.',
            'social_request_amount' => 30.00,
        ]);

        $this->assertFalse($inscription->expireToWaitlist());
        $this->assertSame('aprovado', $inscription->fresh()->status);
    }

    public function test_expire_to_waitlist_e_idempotente(): void
    {
        $inscription = $this->makeInscription($this->makeEvent());

        $this->assertTrue($inscription->expireToWaitlist());
        $this->assertFalse($inscription->expireToWaitlist());
    }

    public function test_decisao_social_nao_define_prazo_para_nao_aprovado(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), [
            'status' => 'fila_de_espera',
            'payment_deadline_at' => null,
            'social_request_status' => 'pendente',
            'social_request_reason' => 'Situação financeira difícil no momento.',
            'social_request_amount' => 30.00,
        ]);

        $inscription->rejectSocialRequest('Não foi possível desta vez.', null);

        $this->assertNull($inscription->payment_deadline_at);
    }

    public function test_comando_expira_aprovado_sem_comprovante_com_prazo_vencido(): void
    {
        $inscription = $this->makeInscription($this->makeEvent());
        $this->logApprovalNotificationSent($inscription);

        $this->artisan('inscriptions:expire-unpaid')->assertSuccessful();

        $inscription->refresh();
        $this->assertSame('fila_de_espera', $inscription->status);
        $this->assertNotNull($inscription->payment_expired_at);
        $this->assertTrue(
            Notification::where('channel', 'inscription_expired')
                ->where('metadata->inscription_id', $inscription->id)
                ->exists()
        );
    }

    public function test_comando_nao_expira_quem_enviou_comprovante(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), [
            'payment_proof' => 'payment_proofs/teste.jpg',
        ]);
        $this->logApprovalNotificationSent($inscription);

        $this->artisan('inscriptions:expire-unpaid')->assertSuccessful();

        $this->assertSame('aprovado', $inscription->fresh()->status);
    }

    public function test_comando_nao_expira_antes_do_prazo(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), [
            'payment_deadline_at' => now()->addMinutes(30),
        ]);
        $this->logApprovalNotificationSent($inscription);

        $this->artisan('inscriptions:expire-unpaid')->assertSuccessful();

        $this->assertSame('aprovado', $inscription->fresh()->status);
    }

    public function test_comando_nao_expira_inscricao_legada_sem_prazo(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), [
            'payment_deadline_at' => null,
        ]);
        $this->logApprovalNotificationSent($inscription);

        $this->artisan('inscriptions:expire-unpaid')->assertSuccessful();

        $this->assertSame('aprovado', $inscription->fresh()->status);
    }

    public function test_comando_nao_expira_com_solicitacao_social_pendente(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), [
            'social_request_status' => 'pendente',
            'social_request_reason' => 'Situação financeira difícil no momento.',
            'social_request_amount' => 30.00,
        ]);
        $this->logApprovalNotificationSent($inscription);

        $this->artisan('inscriptions:expire-unpaid')->assertSuccessful();

        $this->assertSame('aprovado', $inscription->fresh()->status);
    }

    public function test_comando_nao_expira_quem_nao_recebeu_notificacao_de_aprovacao(): void
    {
        $inscription = $this->makeInscription($this->makeEvent());
        // nenhuma Notification 'sent' registrada (ex.: falha de rate limit do Resend)

        $this->artisan('inscriptions:expire-unpaid')->assertSuccessful();

        $this->assertSame('aprovado', $inscription->fresh()->status);
    }

    public function test_dry_run_nao_altera_nada(): void
    {
        $inscription = $this->makeInscription($this->makeEvent());
        $this->logApprovalNotificationSent($inscription);

        $this->artisan('inscriptions:expire-unpaid --dry-run')->assertSuccessful();

        $this->assertSame('aprovado', $inscription->fresh()->status);
        $this->assertFalse(
            Notification::where('channel', 'inscription_expired')->exists()
        );
        $this->assertNull($inscription->fresh()->payment_expired_at);
    }

    public function test_comando_expira_sem_notificar_quando_evento_lotado(): void
    {
        $event = $this->makeEvent(['capacity' => 10, 'confirmed_count' => 10]);
        $inscription = $this->makeInscription($event);
        $this->logApprovalNotificationSent($inscription);

        $this->artisan('inscriptions:expire-unpaid')->assertSuccessful();

        $this->assertSame('fila_de_espera', $inscription->fresh()->status);
        $this->assertFalse(
            Notification::where('channel', 'inscription_expired')
                ->where('metadata->inscription_id', $inscription->id)
                ->exists()
        );
    }

    public function test_comando_expira_sem_notificar_quando_evento_excluido(): void
    {
        $event = $this->makeEvent();
        $inscription = $this->makeInscription($event);
        $this->logApprovalNotificationSent($inscription);
        $event->delete(); // soft delete

        $this->artisan('inscriptions:expire-unpaid')->assertSuccessful();

        $this->assertSame('fila_de_espera', $inscription->fresh()->status);
        $this->assertFalse(
            Notification::where('channel', 'inscription_expired')
                ->where('metadata->inscription_id', $inscription->id)
                ->exists()
        );
    }

    public function test_comando_nao_expira_com_notificacao_anterior_a_reaprovacao(): void
    {
        $inscription = $this->makeInscription($this->makeEvent());
        $this->logApprovalNotificationSent($inscription);
        // Notificação ficou mais antiga que a (re)aprovação: guard deve exigir aviso novo
        Notification::query()->update(['created_at' => $inscription->approved_at->copy()->subHour()]);

        $this->artisan('inscriptions:expire-unpaid')->assertSuccessful();

        $this->assertSame('aprovado', $inscription->fresh()->status);
    }

    public function test_reenvio_de_aprovacao_reinicia_prazo_vencido(): void
    {
        $inscription = $this->makeInscription($this->makeEvent());
        $notification = Notification::create([
            'type' => 'email',
            'channel' => 'inscription_approved',
            'recipient' => $inscription->email,
            'subject' => 'Aprovado',
            'message' => 'Aprovado',
            'status' => 'failed',
            'metadata' => ['inscription_id' => $inscription->id],
        ]);

        $result = app(\App\Services\NotificationResendService::class)->resend($notification);

        $this->assertTrue($result);
        $this->assertTrue($inscription->fresh()->payment_deadline_at->isFuture());
    }

    public function test_reenvio_de_aprovacao_e_ignorado_para_quem_nao_aguarda_pagamento(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), ['status' => 'fila_de_espera']);
        $notification = Notification::create([
            'type' => 'email',
            'channel' => 'inscription_approved',
            'recipient' => $inscription->email,
            'subject' => 'Aprovado',
            'message' => 'Aprovado',
            'status' => 'failed',
            'metadata' => ['inscription_id' => $inscription->id],
        ]);

        $result = app(\App\Services\NotificationResendService::class)->resend($notification);

        $this->assertFalse($result);
        $this->assertTrue($inscription->fresh()->payment_deadline_at->isPast());
    }
}
