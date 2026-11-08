<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesInscriptions;
use Tests\TestCase;

/**
 * Encontros e textos públicos do app (spec 2026-11-08-app-mobile-design, AM10).
 */
class EventsApiTest extends TestCase
{
    use MakesInscriptions;
    use RefreshDatabase;

    public function test_lista_so_publicados_de_hoje_em_diante_por_data(): void
    {
        $later = $this->makeEvent(['city' => 'Serra', 'date' => now()->addDays(20)]);
        $sooner = $this->makeEvent(['city' => 'Vila Velha', 'date' => now()->addDays(3)]);
        $this->makeEvent(['city' => 'Rascunho', 'status' => 'draft']);
        $this->makeEvent(['city' => 'Passado', 'date' => now()->subDays(2)]);

        $response = $this->getJson('/api/v1/events');

        $response->assertOk();
        $this->assertSame([$sooner->id, $later->id], array_column($response->json('data'), 'id'));
    }

    public function test_endereco_e_horario_nunca_saem_nas_rotas_publicas(): void
    {
        $event = $this->makeEvent(['address' => 'Rua Secreta, 123', 'arrival_time' => '19h30']);

        $list = $this->getJson('/api/v1/events')->assertOk();
        $show = $this->getJson("/api/v1/events/{$event->id}")->assertOk();

        foreach ([$list->getContent(), $show->getContent()] as $body) {
            $this->assertStringNotContainsString('Rua Secreta', $body);
            $this->assertStringNotContainsString('19h30', $body);
        }
    }

    public function test_vagas_e_lotacao(): void
    {
        $open = $this->makeEvent(['capacity' => 10, 'confirmed_count' => 7]);
        $full = $this->makeEvent(['capacity' => 5, 'confirmed_count' => 5]);
        $unlimited = $this->makeEvent(['capacity' => 0]);

        $this->getJson("/api/v1/events/{$open->id}")
            ->assertJsonPath('available_spots', 3)
            ->assertJsonPath('is_full', false);
        $this->getJson("/api/v1/events/{$full->id}")
            ->assertJsonPath('available_spots', 0)
            ->assertJsonPath('is_full', true);
        $this->getJson("/api/v1/events/{$unlimited->id}")
            ->assertJsonPath('capacity', null)
            ->assertJsonPath('available_spots', null)
            ->assertJsonPath('is_full', false);
    }

    public function test_encontro_fechado_da_404(): void
    {
        $draft = $this->makeEvent(['status' => 'draft']);

        $this->getJson("/api/v1/events/{$draft->id}")
            ->assertNotFound()
            ->assertJsonPath('message', 'Não encontrado.');
    }

    public function test_detalhe_com_token_diz_se_ja_tem_inscricao(): void
    {
        $event = $this->makeEvent();
        $participant = $this->makeParticipant('maria@example.com');
        $inscription = $this->makeInscription($event, ['email' => 'maria@example.com', 'status' => 'pendente']);
        $token = $participant->createToken('app')->plainTextToken;

        $this->getJson("/api/v1/events/{$event->id}")->assertJsonPath('my_inscription', null);
        $this->withToken($token)->getJson("/api/v1/events/{$event->id}")
            ->assertJsonPath('my_inscription.id', $inscription->id)
            ->assertJsonPath('my_inscription.state', 'pending');
    }

    public function test_config_traz_os_textos_da_web(): void
    {
        $this->getJson('/api/v1/config')
            ->assertOk()
            ->assertJsonPath('manifesto', config('inscriptions.manifesto'))
            ->assertJsonPath('terms.0.title', 'Conduta')
            ->assertJsonPath('reference_amount', 100)
            ->assertJsonPath('links.privacy', route('privacidade'))
            ->assertJsonPath('links.account_deletion', route('app.excluir-conta'));
    }

    public function test_paginas_das_lojas_abrem(): void
    {
        $this->get(route('privacidade'))->assertOk()->assertSee('Política de Privacidade');
        $this->get(route('app.excluir-conta'))->assertOk()->assertSee('Excluir minha conta');
    }
}
