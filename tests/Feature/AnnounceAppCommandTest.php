<?php

namespace Tests\Feature;

use App\Models\Inscription;
use App\Models\Notification;
use App\Participants\AppAnnouncementAudience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Sleep;
use Tests\Concerns\MakesInscriptions;
use Tests\TestCase;

/**
 * O comando de divulgação do app (spec 2026-11-12-email-divulgacao-app-design, DA8 a DA10).
 */
class AnnounceAppCommandTest extends TestCase
{
    use MakesInscriptions;
    use RefreshDatabase;

    private const APP_STORE = 'https://apps.apple.com/br/app/de-casa-em-casa/id6833039609';

    protected function setUp(): void
    {
        parent::setUp();
        // Independe do .env da máquina.
        config(['services.app_stores' => [
            'app_store' => self::APP_STORE,
            'google_play' => 'https://play.google.com/store/apps/details?id=br.com.helpflux.decasaemcasa',
        ]]);
        Sleep::fake();
    }

    protected function tearDown(): void
    {
        Sleep::fake(false);
        parent::tearDown();
    }

    private function twoPeople(): void
    {
        $event = $this->makeEvent(['city' => 'Vitória', 'date' => now()->addWeek()->setTime(19, 0)]);
        $this->makeInscription($event, ['full_name' => 'Maria Velunora', 'email' => 'maria@example.com']);
        $this->makeInscription($event, ['full_name' => 'João Velunora', 'email' => 'joao@example.com', 'cpf' => '111.444.777-35', 'status' => 'pendente']);
    }

    private function sentCount(): int
    {
        return Notification::where('channel', AppAnnouncementAudience::CHANNEL)->where('status', 'sent')->count();
    }

    public function test_dry_run_nao_envia_e_mostra_os_numeros(): void
    {
        $this->twoPeople();
        // Maria também está num segundo encontro: duas linhas na tabela, uma pessoa no total
        $serra = $this->makeEvent(['city' => 'Serra', 'date' => now()->addWeeks(2)->setTime(19, 0)]);
        $this->makeInscription($serra, ['full_name' => 'Maria Velunora', 'email' => 'maria@example.com', 'status' => 'pendente']);
        // Quem já usa o app fica de fora
        $this->makeParticipant('ana@example.com');
        $this->makeInscription($serra, ['email' => 'ana@example.com']);

        $this->artisan('inscriptions:announce-app --dry-run')
            ->expectsTable(['Encontro', 'Data', 'Pessoas que recebem'], [
                ['Vitória', now()->addWeek()->format('d/m/Y'), 2],
                ['Serra', now()->addWeeks(2)->format('d/m/Y'), 1],
            ])
            ->expectsOutputToContain('Recebem: 2 pessoa(s).')
            ->expectsOutputToContain('Ficam de fora: 1 já usam o app, 0 com e-mail que o app não aceita, 0 já receberam.')
            ->assertSuccessful();

        $this->assertSame(0, Notification::count());
        Sleep::assertNeverSlept();
    }

    public function test_envio_confirmado_manda_um_por_pessoa_e_nao_repete(): void
    {
        $this->twoPeople();

        $this->artisan('inscriptions:announce-app')
            ->expectsConfirmation('Enviar o aviso do app para 2 pessoa(s)?', 'yes')
            ->expectsOutputToContain('Enviados: 2. Falhas: 0.')
            ->assertSuccessful();

        $this->assertSame(2, $this->sentCount());
        $maria = Notification::where('recipient', 'maria@example.com')->sole();
        $this->assertSame(Inscription::where('email', 'maria@example.com')->value('id'), $maria->metadata['inscription_id']);
        // Uma pausa de 1 segundo entre os dois envios (DA9)
        Sleep::assertSequence([Sleep::for(1)->second()]);

        $this->artisan('inscriptions:announce-app')
            ->expectsOutputToContain('Ninguém para receber.')
            ->assertSuccessful();

        $this->assertSame(2, $this->sentCount());
    }

    public function test_responder_nao_nao_envia(): void
    {
        $this->twoPeople();

        $this->artisan('inscriptions:announce-app')
            ->expectsConfirmation('Enviar o aviso do app para 2 pessoa(s)?', 'no')
            ->expectsOutputToContain('Nada foi enviado.')
            ->assertSuccessful();

        $this->assertSame(0, Notification::count());
    }

    public function test_to_manda_um_exemplo_que_nao_tira_ninguem_da_lista(): void
    {
        $this->twoPeople();

        $this->artisan('inscriptions:announce-app --to=Maria@Example.com')
            ->expectsOutputToContain('Exemplo enviado para maria@example.com.')
            ->assertSuccessful();

        $sample = Notification::sole();
        $this->assertSame('maria@example.com', $sample->recipient);
        $this->assertSame(['sample' => true], $sample->metadata);
        $this->assertCount(2, app(AppAnnouncementAudience::class)->build()['recipients']);
    }

    public function test_to_invalido_vazio_ou_junto_com_dry_run_nao_envia(): void
    {
        $this->twoPeople();

        $this->artisan('inscriptions:announce-app --to=nao-e-email')
            ->expectsOutputToContain('E-mail inválido')
            ->assertFailed();

        // --to sem valor não pode cair no envio para todos
        $this->artisan('inscriptions:announce-app --to')
            ->expectsOutputToContain('E-mail inválido')
            ->assertFailed();

        $this->artisan('inscriptions:announce-app --to=maria@example.com --dry-run')
            ->expectsOutputToContain('Use uma opção de cada vez')
            ->assertFailed();

        $this->assertSame(0, Notification::count());
    }

    public function test_sem_links_das_lojas_ou_sem_mailer_nao_envia(): void
    {
        $this->twoPeople();
        config(['services.app_stores.app_store' => '', 'services.app_stores.google_play' => '']);

        $this->artisan('inscriptions:announce-app')
            ->expectsOutputToContain('Os links das lojas estão vazios')
            ->assertFailed();
        $this->artisan('inscriptions:announce-app --to=maria@example.com')
            ->expectsOutputToContain('Os links das lojas estão vazios')
            ->assertFailed();
        $this->artisan('inscriptions:announce-app --dry-run')
            ->expectsOutputToContain('Os links das lojas estão vazios')
            ->assertSuccessful();

        config(['services.app_stores.app_store' => self::APP_STORE, 'mail.default' => null]);

        $this->artisan('inscriptions:announce-app')
            ->expectsOutputToContain('O envio de e-mail não está configurado')
            ->assertFailed();

        $this->assertSame(0, Notification::count());
    }

    public function test_em_producao_recusa_mailer_que_nao_entrega(): void
    {
        $this->twoPeople();
        $this->app['env'] = 'production';
        config(['mail.default' => 'log']);

        $this->artisan('inscriptions:announce-app --to=maria@example.com')
            ->expectsOutputToContain('não entrega e-mail de verdade')
            ->assertFailed();
        $this->artisan('inscriptions:announce-app')
            ->expectsOutputToContain('não entrega e-mail de verdade')
            ->assertFailed();

        $this->assertSame(0, Notification::count());
    }

    public function test_para_depois_de_tres_falhas_seguidas(): void
    {
        $event = $this->makeEvent();
        foreach (['a', 'b', 'c', 'd', 'e'] as $letra) {
            $this->makeInscription($event, ['email' => "{$letra}@example.com"]);
        }
        // Mailer inexistente: o Mail::mailer() lança e o sendEmail registra "failed"
        config(['mail.default' => 'inexistente']);

        $this->artisan('inscriptions:announce-app')
            ->expectsConfirmation('Enviar o aviso do app para 5 pessoa(s)?', 'yes')
            ->expectsOutputToContain('Enviados: 0. Falhas: 3.')
            ->expectsOutputToContain('Parei depois de 3 falhas seguidas')
            ->assertFailed();

        $this->assertSame(3, Notification::where('status', 'failed')->count());
        $this->assertCount(5, app(AppAnnouncementAudience::class)->build()['recipients']);
        Sleep::assertSleptTimes(2);
    }
}
