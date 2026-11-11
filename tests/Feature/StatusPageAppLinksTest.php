<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesInscriptions;
use Tests\TestCase;

/**
 * O convite para o app na página de status (spec 2026-11-11-app-no-painel-design, AP1).
 */
class StatusPageAppLinksTest extends TestCase
{
    use MakesInscriptions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Independe do .env da máquina.
        config(['services.app_stores' => [
            'app_store' => 'https://apps.apple.com/br/app/de-casa-em-casa/id6833039609',
            'google_play' => 'https://play.google.com/store/apps/details?id=br.com.helpflux.decasaemcasa',
        ]]);
    }

    private function statusPage()
    {
        $inscription = $this->makeInscription($this->makeEvent(), ['status' => 'pendente']);

        return $this->get(route('inscricao.status', $inscription->token));
    }

    public function test_mostra_os_links_das_duas_lojas(): void
    {
        $this->statusPage()->assertOk()
            ->assertSee('Acompanhe tudo pelo app')
            ->assertSee('https://apps.apple.com/br/app/de-casa-em-casa/id6833039609', false)
            ->assertSee('https://play.google.com/store/apps/details?id=br.com.helpflux.decasaemcasa', false)
            ->assertSee('por e-mail, no app e nesta página');
    }

    public function test_loja_sem_link_some(): void
    {
        config(['services.app_stores.google_play' => '']);

        $this->statusPage()->assertSee('Baixar na App Store')->assertDontSee('Baixar no Google Play');
    }

    public function test_sem_links_o_bloco_some(): void
    {
        config(['services.app_stores.app_store' => '', 'services.app_stores.google_play' => '']);

        $this->statusPage()->assertOk()
            ->assertDontSee('Acompanhe tudo pelo app')
            ->assertSee('chegam por e-mail e nesta página');
    }
}
