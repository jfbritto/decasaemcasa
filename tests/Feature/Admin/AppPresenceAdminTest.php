<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesInscriptions;
use Tests\TestCase;

/**
 * O app no painel (spec 2026-11-11-app-no-painel-design, AP6 a AP8).
 */
class AppPresenceAdminTest extends TestCase
{
    use MakesInscriptions;
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Equipe Velunora', 'email' => 'equipe@example.com', 'password' => bcrypt('x'), 'role' => 'admin']);
    }

    /**
     * O encontro cai dentro do filtro padrão do painel (30 dias à frente),
     * qualquer que seja o mês: makeEvent() usa now()->addMonth().
     */
    private function eventInDefaultPeriod()
    {
        return $this->makeEvent(['date' => now()->addWeek()]);
    }

    public function test_lista_mostra_o_selo_e_o_sino(): void
    {
        $event = $this->eventInDefaultPeriod();
        $maria = $this->makeParticipant('maria@example.com');
        $maria->pushTokens()->create(['token' => 'ExponentPushToken[velunora]', 'platform' => 'android']);
        $this->makeParticipant('joao@example.com');
        $this->makeInscription($event, ['email' => ' Maria@Example.com ', 'status' => 'pendente']);
        $this->makeInscription($event, ['full_name' => 'João Velunora', 'email' => 'joao@example.com', 'status' => 'pendente']);
        $this->makeInscription($event, ['full_name' => 'Ana Velunora', 'email' => 'maria@exámple.com', 'status' => 'pendente']);

        $response = $this->actingAs($this->admin())->get(route('admin.inscricoes.index'));

        $response->assertOk();
        $response->assertSee('Ana Velunora');
        $response->assertSee('title="Usa o app, avisos ligados (Android)"', false);
        $response->assertSee('title="Usa o app, avisos desligados"', false);
        // Maria e João; a Ana, com o e-mail acentuado, não.
        $this->assertSame(2, substr_count($response->getContent(), 'data-app-badge'));
    }

    public function test_filtro_usa_o_app(): void
    {
        $event = $this->eventInDefaultPeriod();
        $this->makeParticipant('maria@example.com');
        $this->makeInscription($event, ['status' => 'pendente']);
        $this->makeInscription($event, ['full_name' => 'Ana Velunora', 'email' => 'ana@example.com', 'cpf' => '111.444.777-35', 'status' => 'pendente']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.inscricoes.index', ['app' => 'sim']))
            ->assertSee('Maria Velunora')->assertDontSee('Ana Velunora');
        $this->actingAs($admin)->get(route('admin.inscricoes.index', ['app' => 'nao']))
            ->assertSee('Ana Velunora')->assertDontSee('Maria Velunora');
        $this->actingAs($admin)->get(route('admin.inscricoes.index', ['app' => 'talvez']))
            ->assertSee('Ana Velunora')->assertSee('Maria Velunora');
    }

    public function test_pagina_da_inscricao_mostra_o_app(): void
    {
        $event = $this->eventInDefaultPeriod();
        $maria = $this->makeParticipant('maria@example.com');
        $maria->forceFill(['last_login_at' => now()->setDate(2026, 11, 10)->setTime(14, 32)])->save();
        $maria->pushTokens()->create(['token' => 'ExponentPushToken[velunora]', 'platform' => 'ios']);
        $usa = $this->makeInscription($event);
        $naoUsa = $this->makeInscription($event, ['full_name' => 'Ana Velunora', 'email' => 'ana@example.com', 'cpf' => '111.444.777-35']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.inscricoes.show', $usa))
            ->assertSee('10/11/2026 14:32')->assertSee('ligados (iPhone)');
        $this->actingAs($admin)->get(route('admin.inscricoes.show', $naoUsa))
            ->assertSee('Ainda não entrou no app.');
    }

    public function test_dashboard_mostra_o_total_no_app(): void
    {
        $this->makeParticipant('a@example.com')->pushTokens()->create(['token' => 'ExponentPushToken[a]', 'platform' => 'android']);
        $this->makeParticipant('b@example.com');

        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertSee('data-app-totals', false)
            ->assertSeeTextInOrder(['No app', '2 pessoas', '1 com avisos ligados']);
    }
}
