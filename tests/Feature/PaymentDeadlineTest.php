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
}
