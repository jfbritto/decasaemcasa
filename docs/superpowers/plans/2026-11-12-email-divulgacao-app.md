# E-mail de divulgação do app: plano de implementação

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** comando `php artisan inscriptions:announce-app` que manda, uma vez por pessoa, o e-mail "O De Casa em Casa agora tem app" para quem tem inscrição viva num encontro que ainda vai acontecer.

**Architecture:** a classe `App\Participants\AppAnnouncementAudience` só lê e escolhe quem recebe. O `NotificationService::notifyAppAnnouncement` envia pelo `sendEmail` de sempre, no canal `app_announcement`. O comando exibe os números e envia. O registro em `notifications` evita o envio duplicado. O painel reenvia com o modelo novo, e o "Reenviar pendentes" ignora esse canal.

**Tech Stack:** Laravel 10.50, PHP 8.2, Blade, PHPUnit 10 no Docker (banco `testing`), `Illuminate\Support\Sleep`.

**Spec:** `docs/superpowers/specs/2026-11-12-email-divulgacao-app-design.md` (DA1 a DA11, com DA4b).

**Branch:** `feat/email-divulgacao-app`, que já tem os commits da spec.

**Rodar testes:** `docker-compose exec -T app php artisan test --filter=<Classe>`. Se o Docker estiver parado, usar `open -a Docker` e esperar. A CLI `docker` está quebrada nesta máquina, então use sempre `docker-compose`.

**Regras de texto:** sem travessão; "seu lugar na sala", nunca "cadeira"; dados de teste fictícios (Velunora, example.com).

---

## Mapa de arquivos

| Arquivo | O quê |
|---|---|
| Criar `app/Participants/AppAnnouncementRecipient.php` | Destinatário: e-mail, nome, id da inscrição mais recente e encontros |
| Criar `app/Participants/AppAnnouncementAudience.php` | Seleção (DA1 a DA5) e constante `CHANNEL` |
| Criar `resources/views/emails/app-announcement.blade.php` | Modelo do e-mail (DA6) |
| Modificar `app/Services/NotificationService.php` | Método `notifyAppAnnouncement` (DA7) |
| Criar `app/Console/Commands/AnnounceApp.php` | O comando (DA8 a DA10). O `Kernel` já carrega a pasta |
| Modificar `app/Services/NotificationResendService.php` | Ramo do reenvio com o modelo (DA11) |
| Modificar `app/Http/Controllers/Admin/NotificationController.php` | `rateLimitPendingQuery` ignora o canal (DA11) |
| Modificar `resources/views/admin/notifications/index.blade.php` | Opção do filtro e rótulo "Divulgação do app" (DA11) |
| Testes `tests/Feature/AppAnnouncementAudienceTest.php`, `tests/Feature/AppAnnouncementEmailTest.php`, `tests/Feature/AnnounceAppCommandTest.php`, `tests/Feature/Admin/AppAnnouncementPanelTest.php` | |

---

### Task 1: Seleção de quem recebe

**Files:**
- Create: `app/Participants/AppAnnouncementRecipient.php`
- Create: `app/Participants/AppAnnouncementAudience.php`
- Test: `tests/Feature/AppAnnouncementAudienceTest.php`

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Participants\AppAnnouncementAudience;
use App\Participants\AppAnnouncementRecipient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesInscriptions;
use Tests\TestCase;

/**
 * Quem recebe a divulgação do app (spec 2026-11-12-email-divulgacao-app-design, DA1 a DA5).
 */
class AppAnnouncementAudienceTest extends TestCase
{
    use MakesInscriptions;
    use RefreshDatabase;

    private function build(): array
    {
        return app(AppAnnouncementAudience::class)->build();
    }

    /**
     * @return list<string>
     */
    private function emails(): array
    {
        $emails = array_map(fn (AppAnnouncementRecipient $r) => $r->email, $this->build()['recipients']);
        sort($emails);

        return $emails;
    }

    private function logAnnouncement(string $recipient, string $status, array $metadata = [], string $channel = AppAnnouncementAudience::CHANNEL): void
    {
        Notification::create([
            'type' => 'email',
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => 'O De Casa em Casa agora tem app',
            'message' => 'Aviso do app',
            'status' => $status,
            'metadata' => $metadata,
        ]);
    }

    public function test_entram_os_quatro_status_e_saem_rejeitado_e_cancelado(): void
    {
        $event = $this->makeEvent();
        foreach (['pendente', 'aprovado', 'fila_de_espera', 'confirmado', 'rejeitado', 'cancelado'] as $status) {
            $this->makeInscription($event, ['status' => $status, 'email' => "{$status}@example.com"]);
        }

        $this->assertSame(
            ['aprovado@example.com', 'confirmado@example.com', 'fila_de_espera@example.com', 'pendente@example.com'],
            $this->emails()
        );
    }

    public function test_so_encontros_publicados_de_hoje_em_diante(): void
    {
        $this->makeInscription($this->makeEvent(['date' => today()->setTime(1, 0)]), ['email' => 'hoje@example.com']);
        $this->makeInscription($this->makeEvent(['date' => now()->subDay()]), ['email' => 'passado@example.com']);
        $this->makeInscription($this->makeEvent(['status' => 'cancelled']), ['email' => 'cancelado@example.com']);
        $this->makeInscription($this->makeEvent(['status' => 'draft']), ['email' => 'rascunho@example.com']);
        $this->makeInscription($this->makeEvent(['status' => 'finished']), ['email' => 'finalizado@example.com']);
        $excluido = $this->makeEvent();
        $this->makeInscription($excluido, ['email' => 'excluido@example.com']);
        $excluido->delete();

        $this->assertSame(['hoje@example.com'], $this->emails());
    }

    public function test_um_por_pessoa_com_o_nome_da_inscricao_mais_recente(): void
    {
        $vitoria = $this->makeEvent(['city' => 'Vitória']);
        $serra = $this->makeEvent(['city' => 'Serra']);
        $antiga = $this->makeInscription($vitoria, ['full_name' => 'Maria Antiga', 'email' => ' Maria@Example.com ']);
        $antiga->forceFill(['created_at' => now()->subDays(2)])->save();
        $recente = $this->makeInscription($serra, ['full_name' => 'Maria Velunora', 'email' => 'maria@example.com']);

        $recipients = $this->build()['recipients'];

        $this->assertCount(1, $recipients);
        $this->assertSame('maria@example.com', $recipients[0]->email);
        $this->assertSame('Maria Velunora', $recipients[0]->name);
        $this->assertSame($recente->id, $recipients[0]->inscriptionId);
        $this->assertEqualsCanonicalizing(['Vitória', 'Serra'], array_map(fn ($e) => $e->city, $recipients[0]->events));
    }

    public function test_no_empate_de_data_vale_o_maior_id(): void
    {
        $event = $this->makeEvent();
        $primeira = $this->makeInscription($event, ['full_name' => 'Primeira Velunora']);
        $segunda = $this->makeInscription($event, ['full_name' => 'Segunda Velunora']);
        $mesmoMomento = now()->subHour()->startOfSecond();
        $primeira->forceFill(['created_at' => $mesmoMomento])->save();
        $segunda->forceFill(['created_at' => $mesmoMomento])->save();

        $this->assertSame('Segunda Velunora', $this->build()['recipients'][0]->name);
    }

    public function test_quem_usa_o_app_ou_tem_e_mail_com_acento_fica_de_fora(): void
    {
        $event = $this->makeEvent();
        $this->makeParticipant('joao@example.com');
        $this->makeInscription($event, ['email' => 'Joao@Example.com']);
        $this->makeInscription($event, ['email' => 'ana@exámple.com']);
        $this->makeInscription($event, ['email' => 'maria@example.com']);

        $result = $this->build();

        $this->assertSame(['maria@example.com'], array_map(fn ($r) => $r->email, $result['recipients']));
        $this->assertSame(['uses_app' => 1, 'non_ascii' => 1, 'already_received' => 0], $result['excluded']);
    }

    public function test_quem_ja_recebeu_fica_de_fora_mas_falha_e_exemplo_nao_contam(): void
    {
        $event = $this->makeEvent();
        foreach (['recebeu', 'reenvio', 'falhou', 'exemplo', 'outro'] as $nome) {
            $this->makeInscription($event, ['email' => "{$nome}@example.com"]);
        }
        $this->logAnnouncement(' Recebeu@Example.com', 'sent', ['inscription_id' => 1]);
        $this->logAnnouncement('reenvio@example.com', 'sent', ['inscription_id' => 2, 'resent_from' => 99]);
        $this->logAnnouncement('falhou@example.com', 'failed');
        $this->logAnnouncement('exemplo@example.com', 'sent', ['sample' => true]);
        $this->logAnnouncement('outro@example.com', 'sent', [], 'custom_message');

        $result = $this->build();

        $this->assertSame(['exemplo@example.com', 'falhou@example.com', 'outro@example.com'], $this->emails());
        $this->assertSame(2, $result['excluded']['already_received']);
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `docker-compose exec -T app php artisan test --filter=AppAnnouncementAudienceTest`
Expected: FAIL, porque a classe `App\Participants\AppAnnouncementAudience` não existe.

- [ ] **Step 3: Implementar**

`app/Participants/AppAnnouncementRecipient.php`:

```php
<?php

namespace App\Participants;

use App\Models\Event;

/**
 * Uma pessoa que recebe a divulgação do app (spec 2026-11-12-email-divulgacao-app-design, DA3).
 */
final class AppAnnouncementRecipient
{
    /**
     * @param  list<Event>  $events  os encontros futuros das inscrições que entraram
     */
    public function __construct(
        public readonly string $email,
        public readonly string $name,
        public readonly int $inscriptionId,
        public readonly array $events,
    ) {}
}
```

`app/Participants/AppAnnouncementAudience.php`:

```php
<?php

namespace App\Participants;

use App\Models\Inscription;
use App\Models\Notification;
use App\Models\Participant;

/**
 * Quem recebe o e-mail de divulgação do app (spec
 * 2026-11-12-email-divulgacao-app-design, DA1 a DA5). Só leitura.
 */
class AppAnnouncementAudience
{
    public const CHANNEL = 'app_announcement';

    public const STATUSES = ['pendente', 'aprovado', 'fila_de_espera', 'confirmado'];

    public function __construct(private readonly AppPresence $appPresence) {}

    /**
     * @return array{
     *     recipients: list<AppAnnouncementRecipient>,
     *     excluded: array{uses_app: int, non_ascii: int, already_received: int}
     * }
     */
    public function build(): array
    {
        // Os mesmos encontros abertos do formulário e do app (DA2)
        $byEmail = Inscription::query()
            ->whereIn('status', self::STATUSES)
            ->whereHas('event', fn ($query) => $query->where('status', 'published')->whereDate('date', '>=', now()))
            ->with('event')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Inscription $inscription) => is_string($inscription->email) && trim($inscription->email) !== '')
            ->groupBy(fn (Inscription $inscription) => Participant::normalizeEmail($inscription->email));

        $usingApp = $this->appPresence->forEmails($byEmail->keys());
        $alreadyReceived = $this->alreadyReceived();

        $recipients = [];
        $excluded = ['uses_app' => 0, 'non_ascii' => 0, 'already_received' => 0];

        foreach ($byEmail as $email => $inscriptions) {
            $email = (string) $email;

            if (isset($usingApp[$email])) {
                $excluded['uses_app']++;

                continue;
            }
            // O login do app só aceita e-mail ASCII (DA4b)
            if (! Participant::isAsciiEmail($email)) {
                $excluded['non_ascii']++;

                continue;
            }
            if (isset($alreadyReceived[$email])) {
                $excluded['already_received']++;

                continue;
            }

            $latest = $inscriptions->first();
            $recipients[] = new AppAnnouncementRecipient(
                email: $email,
                name: $latest->full_name,
                inscriptionId: $latest->id,
                events: $inscriptions->pluck('event')->unique('id')->values()->all(),
            );
        }

        return ['recipients' => $recipients, 'excluded' => $excluded];
    }

    /**
     * E-mails que já receberam o aviso de verdade (DA5). A comparação é no
     * PHP: a collation de notifications.recipient ignora acento.
     *
     * @return array<string, true>
     */
    private function alreadyReceived(): array
    {
        return Notification::query()
            ->where('type', 'email')
            ->where('channel', self::CHANNEL)
            ->where('status', 'sent')
            ->get(['recipient', 'metadata'])
            ->reject(fn (Notification $notification) => ! empty($notification->metadata['sample']))
            ->mapWithKeys(fn (Notification $notification) => [Participant::normalizeEmail($notification->recipient) => true])
            ->all();
    }
}
```

- [ ] **Step 4: Rodar e ver passar**

Run: `docker-compose exec -T app php artisan test --filter=AppAnnouncementAudienceTest`
Expected: PASS (6 testes).

- [ ] **Step 5: Commit**

```bash
git add app/Participants/AppAnnouncementRecipient.php app/Participants/AppAnnouncementAudience.php tests/Feature/AppAnnouncementAudienceTest.php
git commit -m "feat: quem recebe a divulgação do app"
```

---

### Task 2: O e-mail e o envio pelo NotificationService

**Files:**
- Create: `resources/views/emails/app-announcement.blade.php`
- Modify: `app/Services/NotificationService.php` (novo método logo depois de `notifyCustomMessage`)
- Test: `tests/Feature/AppAnnouncementEmailTest.php`

- [ ] **Step 1: Escrever o teste que falha**

```php
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

    protected function setUp(): void
    {
        parent::setUp();
        // Independe do .env da máquina.
        config(['services.app_stores' => [
            'app_store' => 'https://apps.apple.com/br/app/de-casa-em-casa/id6833039609',
            'google_play' => 'https://play.google.com/store/apps/details?id=br.com.helpflux.decasaemcasa',
        ]]);
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
            'Baixar na App Store',
            'Baixar no Google Play',
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
        $this->assertStringContainsString('Olá <strong>Maria Velunora</strong>,', $message->getHtmlBody());
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `docker-compose exec -T app php artisan test --filter=AppAnnouncementEmailTest`
Expected: FAIL, com "View [emails.app-announcement] not found" e "Call to undefined method notifyAppAnnouncement".

- [ ] **Step 3: Implementar**

`resources/views/emails/app-announcement.blade.php`:

```blade
@extends('emails.layout')

@section('subject', 'O De Casa em Casa agora tem app')

@section('badge')
<span style="display:inline-block; background-color:#e0e7ff; color:#1a2e6e; padding:6px 20px; border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
    Novidade
</span>
@endsection

@section('content')
<p style="margin:0 0 16px; color:#1a2e6e; font-size:16px; line-height:1.6;">
@if (filled($name))
    Olá <strong>{{ $name }}</strong>,
@else
    Olá!
@endif
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Temos uma novidade: o De Casa em Casa agora tem app, para iPhone e Android.
</p>
<p style="margin:0 0 8px; color:#4a4639; font-size:15px; line-height:1.7;">
    Pelo app você acompanha suas inscrições de perto:
</p>
<ul style="margin:0 0 16px; padding-left:20px; color:#4a4639; font-size:15px; line-height:1.7;">
    <li>vê em que etapa está cada inscrição;</li>
    <li>quando for aprovada, vê o prazo e o Pix e envia o comprovante pelo celular;</li>
    <li>com seu lugar na sala garantido, encontra o endereço e o horário do encontro;</li>
    <li>se ativar as notificações, recebe os avisos da equipe na hora.</li>
</ul>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Para entrar, use este mesmo e-mail (<strong>{{ $email }}</strong>): você recebe um código de 6 números, sem senha.
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Os botões para baixar estão logo abaixo.
</p>
<p style="margin:0; color:#4a4639; font-size:15px; line-height:1.7;">
    Um abraço da<br>
    <strong>Equipe De Casa em Casa</strong>
</p>
@endsection
```

`app/Services/NotificationService.php`: adicionar `use App\Participants\AppAnnouncementAudience;` aos imports e, depois de `notifyCustomMessage`, o método:

```php
    /**
     * O e-mail de divulgação do app (spec 2026-11-12-email-divulgacao-app-design,
     * DA6 e DA7). Só e-mail: quem recebe ainda não tem o app.
     */
    public function notifyAppAnnouncement(string $email, ?string $name, array $metadata = []): bool
    {
        return $this->sendEmail(
            $email,
            'O De Casa em Casa agora tem app',
            'Aviso do app para '.(filled($name) ? $name : $email),
            null,
            AppAnnouncementAudience::CHANNEL,
            $metadata,
            'emails.app-announcement',
            ['name' => $name, 'email' => $email]
        );
    }
```

- [ ] **Step 4: Rodar e ver passar**

Run: `docker-compose exec -T app php artisan test --filter=AppAnnouncementEmailTest`
Expected: PASS (3 testes).

- [ ] **Step 5: Commit**

```bash
git add resources/views/emails/app-announcement.blade.php app/Services/NotificationService.php tests/Feature/AppAnnouncementEmailTest.php
git commit -m "feat: e-mail de divulgação do app"
```

---

### Task 3: O comando `inscriptions:announce-app`

**Files:**
- Create: `app/Console/Commands/AnnounceApp.php`
- Test: `tests/Feature/AnnounceAppCommandTest.php`

Observações para os testes:
- O `expectsOutputToContain` não liga para a ordem, mas cada `doWrite` satisfaz uma expectativa só. Por isso não espere dois trechos da mesma linha.
- O `expectsTable` confere as linhas exatas e na ordem.
- A falha de envio é simulada com `config(['mail.default' => 'inexistente'])`, sem `Mail::fake()`. O `Mail::mailer()` lança "Mailer [inexistente] is not defined", e o `sendEmail` registra `failed` e devolve `false`.

- [ ] **Step 1: Escrever o teste que falha**

```php
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
        $this->makeParticipant('ana@example.com');
        $this->makeInscription($this->makeEvent(['city' => 'Serra']), ['email' => 'ana@example.com']);

        $this->artisan('inscriptions:announce-app --dry-run')
            ->expectsTable(['Encontro', 'Data', 'Pessoas que recebem'], [['Vitória', now()->addWeek()->format('d/m/Y'), 2]])
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
        Sleep::assertSleptTimes(1);

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

    public function test_to_invalido_ou_junto_com_dry_run_nao_envia(): void
    {
        $this->artisan('inscriptions:announce-app --to=nao-e-email')
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

    public function test_para_depois_de_tres_falhas_seguidas(): void
    {
        $event = $this->makeEvent();
        foreach (['a', 'b', 'c', 'd', 'e'] as $letra) {
            $this->makeInscription($event, ['email' => "{$letra}@example.com"]);
        }
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
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `docker-compose exec -T app php artisan test --filter=AnnounceAppCommandTest`
Expected: FAIL, com "The command "inscriptions:announce-app" does not exist."

- [ ] **Step 3: Implementar**

`app/Console/Commands/AnnounceApp.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\Participant;
use App\Participants\AppAnnouncementAudience;
use App\Participants\AppAnnouncementRecipient;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Sleep;

/**
 * Divulgação do app por e-mail (spec 2026-11-12-email-divulgacao-app-design,
 * DA8 a DA10). Disparo único, rodado à mão no servidor. Rodar de novo só
 * manda para quem ainda não recebeu.
 */
class AnnounceApp extends Command
{
    protected $signature = 'inscriptions:announce-app
        {--to= : Manda um exemplo só para este e-mail}
        {--dry-run : Mostra quem recebe, sem enviar nada}';

    protected $description = 'Manda o e-mail de divulgação do app para quem tem inscrição viva num encontro que ainda vai acontecer';

    private const MAX_CONSECUTIVE_FAILURES = 3;

    public function handle(AppAnnouncementAudience $audience, NotificationService $notifications): int
    {
        $to = $this->option('to');
        $dryRun = (bool) $this->option('dry-run');

        if ($to !== null && $dryRun) {
            $this->error('Use uma opção de cada vez: --to ou --dry-run. Nada foi enviado.');

            return self::FAILURE;
        }

        $problem = $this->sendingProblem();

        if ($to !== null) {
            return $this->sendSample((string) $to, $problem, $notifications);
        }

        $result = $audience->build();
        $this->report($result);

        if ($dryRun) {
            if ($problem !== null) {
                $this->warn($problem.' Assim o envio de verdade não roda.');
            }

            return self::SUCCESS;
        }

        if ($problem !== null) {
            $this->error($problem.' Nada foi enviado.');

            return self::FAILURE;
        }

        $recipients = $result['recipients'];

        if ($recipients === []) {
            $this->info('Ninguém para receber.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Enviar o aviso do app para '.count($recipients).' pessoa(s)?', false)) {
            $this->info('Nada foi enviado.');

            return self::SUCCESS;
        }

        return $this->sendAll($recipients, $notifications);
    }

    /**
     * Sem mailer o sendEmail só registra "skipped"; sem os links o e-mail
     * sairia sem os botões (DA10).
     */
    private function sendingProblem(): ?string
    {
        if (blank(config('mail.default'))) {
            return 'O envio de e-mail não está configurado (MAIL_MAILER vazio).';
        }

        if (blank(config('services.app_stores.app_store')) && blank(config('services.app_stores.google_play'))) {
            return 'Os links das lojas estão vazios (APP_STORE_URL e GOOGLE_PLAY_URL).';
        }

        return null;
    }

    private function sendSample(string $to, ?string $problem, NotificationService $notifications): int
    {
        if (filter_var(trim($to), FILTER_VALIDATE_EMAIL) === false) {
            $this->error("E-mail inválido: {$to}. Nada foi enviado.");

            return self::FAILURE;
        }

        if ($problem !== null) {
            $this->error($problem.' Nada foi enviado.');

            return self::FAILURE;
        }

        $email = Participant::normalizeEmail($to);

        if (! $notifications->notifyAppAnnouncement($email, null, ['sample' => true])) {
            $this->error("Falha ao enviar o exemplo para {$email}. O erro está em Notificações, no painel.");

            return self::FAILURE;
        }

        $this->info("Exemplo enviado para {$email}.");

        return self::SUCCESS;
    }

    /**
     * @param  array{recipients: list<AppAnnouncementRecipient>, excluded: array{uses_app: int, non_ascii: int, already_received: int}}  $result
     */
    private function report(array $result): void
    {
        $rows = [];
        foreach ($result['recipients'] as $recipient) {
            foreach ($recipient->events as $event) {
                $rows[$event->id] ??= ['sort' => $event->date->getTimestamp(), 'city' => $event->city, 'date' => $event->date->format('d/m/Y'), 'people' => 0];
                $rows[$event->id]['people']++;
            }
        }
        uasort($rows, fn (array $a, array $b) => $a['sort'] <=> $b['sort']);

        if ($rows !== []) {
            $this->table(
                ['Encontro', 'Data', 'Pessoas que recebem'],
                array_map(fn (array $row) => [$row['city'], $row['date'], $row['people']], array_values($rows))
            );
        }

        $excluded = $result['excluded'];
        $this->line('Recebem: '.count($result['recipients']).' pessoa(s).');
        $this->line("Ficam de fora: {$excluded['uses_app']} já usam o app, {$excluded['non_ascii']} com e-mail que o app não aceita, {$excluded['already_received']} já receberam.");
    }

    /**
     * @param  list<AppAnnouncementRecipient>  $recipients
     */
    private function sendAll(array $recipients, NotificationService $notifications): int
    {
        $sent = 0;
        $failed = 0;
        $consecutiveFailures = 0;
        $stopped = false;

        $bar = $this->output->createProgressBar(count($recipients));
        $bar->start();

        foreach ($recipients as $index => $recipient) {
            // Folga para o limite por segundo do provedor de e-mail (DA9)
            if ($index > 0) {
                Sleep::for(1)->second();
            }

            $ok = $notifications->notifyAppAnnouncement($recipient->email, $recipient->name, ['inscription_id' => $recipient->inscriptionId]);

            if ($ok) {
                $sent++;
                $consecutiveFailures = 0;
            } else {
                $failed++;
                $consecutiveFailures++;
            }

            $bar->advance();

            if ($consecutiveFailures >= self::MAX_CONSECUTIVE_FAILURES) {
                $stopped = true;

                break;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Enviados: {$sent}. Falhas: {$failed}.");

        if ($stopped) {
            $this->warn('Parei depois de '.self::MAX_CONSECUTIVE_FAILURES.' falhas seguidas, o que costuma ser cota ou limite do provedor de e-mail. Rode de novo mais tarde (depois de 1 hora, se o limite for por hora): só quem não recebeu volta para a lista.');
        } elseif ($failed > 0) {
            $this->warn('Rode de novo para tentar quem falhou: só quem não recebeu volta para a lista.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
```

- [ ] **Step 4: Rodar e ver passar**

Run: `docker-compose exec -T app php artisan test --filter=AnnounceAppCommandTest`
Expected: PASS (7 testes).

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/AnnounceApp.php tests/Feature/AnnounceAppCommandTest.php
git commit -m "feat: comando inscriptions:announce-app"
```

---

### Task 4: O painel (reenvio, reenvio em lote e rótulo)

**Files:**
- Modify: `app/Services/NotificationResendService.php` (o `if` que escolhe `$view`, por volta da linha 75)
- Modify: `app/Http/Controllers/Admin/NotificationController.php` (`rateLimitPendingQuery`, por volta da linha 265)
- Modify: `resources/views/admin/notifications/index.blade.php` (o select `channel`, por volta da linha 138, e o `$channelLabels`, por volta da linha 261)
- Test: `tests/Feature/Admin/AppAnnouncementPanelTest.php`

- [ ] **Step 1: Escrever o teste que falha**

```php
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
        $this->assertStringContainsString('Olá <strong>Maria Velunora</strong>,', $this->sentHtml());
        $this->assertStringContainsString('código de 6 números', $this->sentHtml());
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
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `docker-compose exec -T app php artisan test --filter=AppAnnouncementPanelTest`
Expected: FAIL. O reenvio sai como texto puro, sem o "Olá <strong>", e o lote enfileira 2 jobs em vez de 1. Também falta a opção `app_announcement`.

- [ ] **Step 3: Implementar**

Em `app/Services/NotificationResendService.php`, adicionar `use App\Participants\AppAnnouncementAudience;` e trocar o começo do `if` que escolhe o modelo:

```php
        if ($notification->channel === AppAnnouncementAudience::CHANNEL) {
            // A divulgação do app não depende de encontro (DA11)
            $view = 'emails.app-announcement';
            $viewData = ['name' => $inscription?->full_name, 'email' => $notification->recipient];
        } elseif ($inscription && $inscription->event && isset($viewMap[$notification->channel])) {
```

O resto do `if` e a chamada a `sendEmail` continuam iguais, para que o `resent_from` e o `template_used` sejam juntados como hoje.

Em `app/Http/Controllers/Admin/NotificationController.php`, adicionar `use App\Participants\AppAnnouncementAudience;` e, em `rateLimitPendingQuery`, logo depois de `Notification::where('status', 'failed')`:

```php
            // Para a divulgação do app, rodar o comando de novo já é o reenvio (DA11)
            ->where('channel', '!=', AppAnnouncementAudience::CHANNEL)
```

Em `resources/views/admin/notifications/index.blade.php`, depois da opção `custom_message` do select:

```blade
                            <option value="app_announcement" {{ request('channel') === 'app_announcement' ? 'selected' : '' }}>Divulgação do app</option>
```

E no `$channelLabels`, depois de `'custom_message' => 'Mensagem Personalizada',`:

```php
                                                'app_announcement' => 'Divulgação do app',
```

- [ ] **Step 4: Rodar e ver passar**

Run: `docker-compose exec -T app php artisan test --filter=AppAnnouncementPanelTest`
Expected: PASS (4 testes).

- [ ] **Step 5: Commit**

```bash
git add app/Services/NotificationResendService.php app/Http/Controllers/Admin/NotificationController.php resources/views/admin/notifications/index.blade.php tests/Feature/Admin/AppAnnouncementPanelTest.php
git commit -m "feat(painel): divulgação do app no reenvio e nas notificações"
```

---

### Task 5: Suíte inteira, conferência manual e PR

- [ ] **Step 1: Suíte inteira**

Run: `docker-compose exec -T app php artisan test`
Expected: tudo passa (eram 145 testes antes; agora são 145 + 20).

- [ ] **Step 2: Conferir o comando de verdade no ambiente local**

Rodar estes comandos no banco de desenvolvimento local (Docker), nunca em produção:
- `docker-compose exec -T app php artisan inscriptions:announce-app --dry-run`: mostra a tabela e os totais.
- `docker-compose exec -T app php artisan inscriptions:announce-app --to=teste@example.com`: o e-mail chega ao MailHog (http://localhost:8028). Abrir e conferir a aparência.
- Apagar em seguida a linha de exemplo criada em `notifications`: só esta, pelo id.

- [ ] **Step 3: CLAUDE.md do projeto**

O `CLAUDE.md` da raiz do projeto está fora do git (`?? CLAUDE.md`), então não entra no PR. Atualizá-lo localmente com uma linha sobre o comando `inscriptions:announce-app`.

- [ ] **Step 4: Revisão de código e PR**

Revisão pelo subagente `superpowers:code-reviewer`. Depois, `git push -u origin feat/email-divulgacao-app` e `gh pr create`. O PR leva o roteiro de produção da spec (seção "Como o João roda em produção").
