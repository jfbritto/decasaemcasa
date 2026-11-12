<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O e-mail de divulgação do app (spec 2026-11-12-email-divulgacao-app-design, DA6 e DA7).
 */
class AppAnnouncementEmailTest extends TestCase
{
    use RefreshDatabase;

    private const APP_STORE = 'https://apps.apple.com/br/app/de-casa-em-casa/id6833039609';

    private const GOOGLE_PLAY = 'https://play.google.com/store/apps/details?id=br.com.helpflux.decasaemcasa';

    protected function setUp(): void
    {
        parent::setUp();
        // Independe do .env da máquina.
        config(['services.app_stores' => ['app_store' => self::APP_STORE, 'google_play' => self::GOOGLE_PLAY]]);
    }

    private function render(?string $name): string
    {
        return view('emails.app-announcement', ['name' => $name, 'email' => 'maria@example.com'])->render();
    }

    public function test_texto_do_e_mail(): void
    {
        $html = $this->render('Maria Velunora');

        foreach ([
            'Novidade',
            'Olá <strong>Maria Velunora</strong>,',
            'o De Casa em Casa agora tem app, para iPhone e Android.',
            'vê em que etapa está cada inscrição;',
            'quando for aprovada, vê o prazo e o Pix e envia o comprovante pelo celular;',
            'com seu lugar na sala garantido, encontra o endereço e o horário do encontro;',
            'se ativar as notificações, recebe os avisos da equipe na hora.',
            'Para entrar, use este mesmo e-mail (<strong>maria@example.com</strong>): você recebe um código de 6 números, sem senha.',
            'href="'.e(self::APP_STORE).'"',
            'href="'.e(self::GOOGLE_PLAY).'"',
        ] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        $this->assertStringNotContainsString('—', $html);
        $this->assertStringNotContainsString('cadeira', $html);
    }

    public function test_sem_nome_a_saudacao_e_ola(): void
    {
        $html = $this->render(null);

        $this->assertStringContainsString('Olá!', $html);
        $this->assertStringNotContainsString('<strong></strong>', $html);
    }

    public function test_o_servico_envia_e_registra_no_canal_do_aviso(): void
    {
        $ok = app(NotificationService::class)->notifyAppAnnouncement('maria@example.com', 'Maria Velunora', ['inscription_id' => 7]);

        $this->assertTrue($ok);
        $log = Notification::sole();
        $this->assertSame('email', $log->type);
        $this->assertSame('app_announcement', $log->channel);
        $this->assertSame('maria@example.com', $log->recipient);
        $this->assertSame('O De Casa em Casa agora tem app', $log->subject);
        $this->assertSame('Aviso do app para Maria Velunora', $log->message);
        $this->assertSame('sent', $log->status);
        $this->assertSame(['inscription_id' => 7], $log->metadata);

        $message = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
        $this->assertSame('maria@example.com', $message->getTo()[0]->getAddress());
        $this->assertSame('O De Casa em Casa agora tem app', $message->getSubject());
        $this->assertStringContainsString('Olá <strong>Maria Velunora</strong>,', $message->getHtmlBody());
    }
}
