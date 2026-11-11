<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesInscriptions;
use Tests\TestCase;

/**
 * O bloco "Acompanhe tudo pelo app" do layout dos e-mails, com os links das
 * lojas (config services.app_stores).
 */
class EmailAppLinksTest extends TestCase
{
    use MakesInscriptions;
    use RefreshDatabase;

    private const APP_STORE = 'https://apps.apple.com/br/app/de-casa-em-casa/id6833039609';

    private const GOOGLE_PLAY = 'https://play.google.com/store/apps/details?id=br.com.helpflux.decasaemcasa';

    private function renderReceived(): string
    {
        $event = $this->makeEvent();
        $inscription = $this->makeInscription($event, ['status' => 'pendente']);

        return view('emails.inscription-received', [
            'inscription' => $inscription,
            'event' => $event,
            'statusUrl' => route('inscricao.status', $inscription->token),
        ])->render();
    }

    public function test_os_e_mails_da_inscricao_levam_os_links_das_duas_lojas(): void
    {
        $html = $this->renderReceived();

        $this->assertStringContainsString('Acompanhe tudo pelo app', $html);
        $this->assertStringContainsString('href="'.e(self::APP_STORE).'"', $html);
        $this->assertStringContainsString('href="'.e(self::GOOGLE_PLAY).'"', $html);
    }

    public function test_o_e_mail_do_codigo_de_login_nao_leva_o_bloco(): void
    {
        $html = view('emails.app-login-code', ['code' => '123456', 'ttlMinutes' => 10])->render();

        $this->assertStringContainsString('123456', $html);
        $this->assertStringNotContainsString('Acompanhe tudo pelo app', $html);
        $this->assertStringNotContainsString(self::GOOGLE_PLAY, $html);
    }

    public function test_loja_sem_link_some_do_bloco(): void
    {
        config(['services.app_stores.google_play' => null]);

        $html = $this->renderReceived();

        $this->assertStringContainsString('Baixar na App Store', $html);
        $this->assertStringNotContainsString('Baixar no Google Play', $html);
    }

    public function test_sem_nenhum_link_o_bloco_some(): void
    {
        config(['services.app_stores.app_store' => '', 'services.app_stores.google_play' => '']);

        $this->assertStringNotContainsString('Acompanhe tudo pelo app', $this->renderReceived());
    }
}
