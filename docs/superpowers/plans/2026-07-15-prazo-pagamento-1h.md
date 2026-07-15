# Prazo de 1 hora para pagamento pós-aprovação — Plano de Implementação

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Após a aprovação, o inscrito tem 1 hora para enviar o comprovante Pix; se não enviar, a inscrição volta automaticamente para a fila de espera, a vaga é liberada e a pessoa é notificada.

**Architecture:** Nova coluna `payment_deadline_at` preenchida em `Inscription::approve()` (cobre aprovação individual e em massa); comando artisan `inscriptions:expire-unpaid` rodando a cada minuto via scheduler já existente (cron do cPanel HostGator); expiração via UPDATE condicional (atômico) para evitar corrida com o upload de comprovante; nova notificação `inscription_expired` seguindo o padrão `NotificationService` + template Blade.

**Tech Stack:** Laravel 10, MySQL (HostGator plano M — sem worker persistente; scheduler via cron `schedule:run` a cada minuto, que já roda `queue:work database --max-time=55`), e-mails síncronos via Resend (produção) / MailHog (local), Alpine.js no front público, PHPUnit 10.

---

## Contexto e requisitos (spec)

Pedido do cliente (Marcos Almeida, repassado em 14/07/2026):

1. Ajustar o e-mail de aprovação informando prazo de **1 hora** para concluir o pagamento (o cliente enviou mockup com o copy desejado: "AÇÃO NECESSÁRIA: SUA VAGA EXPIRA EM 1 HORA", passos 1-2, botão "JÁ FIZ O PIX: QUERO ENVIAR O COMPROVANTE"). **Esse texto não existe hoje no código** — o template atual (`resources/views/emails/inscription-approved.blade.php`) não menciona prazo.
2. Se o pagamento não for identificado no prazo, a inscrição sai de `aprovado`, vai para `fila_de_espera` e a vaga é liberada.
3. A pessoa recebe notificação (e-mail + WhatsApp) avisando da mudança de status.
4. Dúvidas do cliente a resolver sistemicamente: confirmação do pagamento, contagem do prazo, validade do link.

### Como o sistema funciona hoje (verificado no código)

- Inscrição = model `Inscription` (`app/Models/Inscription.php`), status ENUM MySQL: `pendente, aprovado, fila_de_espera, confirmado, rejeitado, cancelado`. Fila de espera é só um status (sem tabela/posição).
- Aprovação individual: `Admin/InscriptionController@approve` (linha 207); em massa: `@bulkAction` (linha 502). **Ambas chamam `Inscription::approve()`** (model, linha 109) e depois `NotificationService::notifyInscriptionApproved()` — e-mail enviado **sincronamente** no momento da aprovação (não vai para a queue).
- "Pagamento identificado" = participante envia comprovante (`InscriptionController@uploadPaymentProof`, linha 158) → preenche `payment_proof`, **status continua `aprovado`**. Depois o admin confirma manualmente (`Admin/InscriptionController@confirm`, linha 279) → status `confirmado`. O painel deriva "aguardando_pix" como `aprovado + payment_proof NULL`.
- Link do e-mail = `route('inscricao.status', $inscription->token)` — token aleatório de 64 chars, **sem expiração** (não é URL assinada).
- Scheduler (`app/Console/Kernel.php`) roda só `queue:work database --max-time=55` a cada minuto — ou seja, **já existe cron `schedule:run` funcionando em produção**.
- Existe fluxo de **contribuição social**: pessoa aprovada pode pedir análise (`social_request_status = pendente`) antes de pagar; equipe aprova/rejeita e só então a pessoa paga.
- Houve falhas de envio por rate limit do Resend (existe infra de reenvio: `NotificationResendService`, `ResendFailedNotification`). Logo, **pode existir aprovado que nunca recebeu o e-mail** — não pode ser expirado às cegas.

## Decisões de design

| Questão do cliente | Solução |
|---|---|
| Contagem do prazo | Coluna `payment_deadline_at` preenchida no momento da aprovação (`now() + 60min`, configurável via `INSCRIPTION_PAYMENT_DEADLINE_MINUTES`). E-mail/WhatsApp/página de status mostram o horário absoluto ("até às 15h32 de 15/07"), não só "1 hora". |
| Confirmação do pagamento | O que "para o relógio" é o **envio do comprovante** (preenche `payment_proof`) — a confirmação manual do admin continua como está. Corrida cron × upload resolvida com UPDATEs condicionais dos dois lados. |
| Validade do link | O link por token **continua válido para sempre como página de status** (a pessoa precisa ver que voltou para a fila). O que expira é a permissão de upload, controlada pelo status. Não é preciso URL assinada. |
| Quem expira | `status=aprovado` + `payment_proof NULL` + `payment_deadline_at` vencido + **sem solicitação social pendente** + **com notificação de aprovação efetivamente enviada** (guarda contra falha do Resend). |
| Legado | Aprovados de antes do deploy têm `payment_deadline_at NULL` → nunca expiram automaticamente (tratamento manual). |
| Solicitação social | Enquanto `social_request_status=pendente` o prazo fica **pausado** (cron pula). Quando a equipe aprova/rejeita a solicitação, o prazo **reinicia** (novo `now()+60min`), pois só então a pessoa pode pagar. |
| "Segunda chance" | Admin re-aprova alguém da fila (fluxo já existente) → `approve()` gera **novo prazo** e reenvia o e-mail de aprovação. É o mecanismo natural de extensão de prazo. |
| Marcador de expiração | Coluna `payment_expired_at` preenchida só pelo cron — distingue "voltou pra fila por prazo vencido" de "foi pra fila manualmente/evento lotado" (muda o texto da página de status e do guard de upload). |

### Fora do escopo (decidido, documentar para o cliente)

- **Auto-promoção do próximo da fila:** perigoso com janela de 1h — uma expiração às 3h da manhã aprovaria o próximo automaticamente, cujo prazo venceria dormindo, gerando cascata que drena a fila inteira de madrugada. A aprovação do próximo continua manual (e o admin já vê o contador "aguardando_pix" no painel). Se o cliente insistir, tratar em plano separado.
- **Notificação ao admin quando alguém expira** (futuro; hoje o painel + ActivityLog cobrem).
- **Alterar `SendPaymentReminders`** (`inscriptions:send-payment-reminders`): não é agendado e fica obsoleto para o novo fluxo; deixar como está.

## Estrutura de arquivos

| Arquivo | Ação | Responsabilidade |
|---|---|---|
| `config/inscriptions.php` | Criar | Minutos do prazo (env `INSCRIPTION_PAYMENT_DEADLINE_MINUTES`, default 60) |
| `database/migrations/2026_07_15_000001_add_payment_deadline_to_inscriptions_table.php` | Criar | Colunas `payment_deadline_at`, `payment_expired_at` + índice |
| `app/Models/Inscription.php` | Modificar | Prazo em `approve()`, `startPaymentDeadline()`, `expireToWaitlist()`, accessor `payment_deadline_label`, reinício de prazo nas decisões de solicitação social |
| `app/Console/Commands/ExpireUnpaidInscriptions.php` | Criar | Comando `inscriptions:expire-unpaid` (com `--dry-run`) |
| `app/Console/Kernel.php` | Modificar | Agendar o comando a cada minuto ANTES do `queue:work` |
| `app/Services/NotificationService.php` | Modificar | `notifyInscriptionExpired()` + prazo no copy de `notifyInscriptionApproved()` |
| `resources/views/emails/inscription-expired.blade.php` | Criar | E-mail "voltou para a fila de espera" |
| `resources/views/emails/inscription-approved.blade.php` | Modificar | Copy de urgência com prazo (mockup do cliente) |
| `app/Services/NotificationResendService.php` | Modificar | Mapear `inscription_expired` no `$viewMap` |
| `app/Http/Controllers/InscriptionController.php` | Modificar | Guard atômico no `uploadPaymentProof` + mensagens |
| `resources/views/inscriptions/status.blade.php` | Modificar | Contador regressivo (aprovado) + mensagem de expirado (fila) |
| `routes/web.php` | Modificar | `inscription-expired` no preview de e-mails (local) |
| `.env.example` | Modificar | Documentar `INSCRIPTION_PAYMENT_DEADLINE_MINUTES` |
| `phpunit.xml`, `tests/TestCase.php`, `tests/CreatesApplication.php`, `tests/Feature/PaymentDeadlineTest.php` | Criar | Infra de testes (inexistente hoje) + testes da feature |

**Importante para quem executa:** os templates de e-mail só podem depender de `$inscription`, `$event` e `$statusUrl` — o `NotificationResendService` (linha 56) reenvia com exatamente essas 3 variáveis. Todo dado novo deve ser derivado de `$inscription` dentro do Blade.

---

### Task 1: Infra de testes

O diretório `tests/` está **vazio** (sem TestCase). As migrations usam `ALTER TABLE ... ENUM` cru de MySQL — **SQLite não funciona**; os testes precisam de um banco MySQL `testing`.

**Files:**
- Create: `tests/TestCase.php`, `tests/CreatesApplication.php`
- Modify: `phpunit.xml`

- [ ] **Step 1: Verificar pré-requisitos**

Run: `grep -A4 '"autoload-dev"' composer.json` — deve mapear `"Tests\\": "tests/"`. Se não mapear, adicionar e rodar `composer dump-autoload`.

Run: `php artisan tinker --execute="echo DB::connection()->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);"` — confirma MySQL local acessível.

**Fallback:** se não houver MySQL local utilizável, PULAR as Tasks de teste (steps marcados "teste") e validar tudo pelo **checklist manual da Task 8** — registrar isso no commit final.

- [ ] **Step 2: Criar banco de testes**

Run (usar usuário/senha do `.env`): `mysql -u USUARIO -p -e "CREATE DATABASE IF NOT EXISTS testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"`

- [ ] **Step 3: Criar `tests/CreatesApplication.php`**

```php
<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
```

- [ ] **Step 4: Criar `tests/TestCase.php`**

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
}
```

- [ ] **Step 5: Fixar conexão no `phpunit.xml`**

Dentro de `<php>`, adicionar antes de `DB_DATABASE`:

```xml
<env name="DB_CONNECTION" value="mysql"/>
```

- [ ] **Step 6: Smoke test**

Run: `php artisan test` — Expected: "No tests found" (ou 0 tests), sem erro de bootstrap.

- [ ] **Step 7: Commit**

```bash
git add tests/ phpunit.xml composer.json
git commit -m "test: adiciona infraestrutura básica de testes (PHPUnit + MySQL testing)

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 2: Config, migration e prazo no model

**Files:**
- Create: `config/inscriptions.php`
- Create: `database/migrations/2026_07_15_000001_add_payment_deadline_to_inscriptions_table.php`
- Modify: `app/Models/Inscription.php`
- Modify: `.env.example`
- Test: `tests/Feature/PaymentDeadlineTest.php`

- [ ] **Step 1: Escrever os testes que devem falhar**

Criar `tests/Feature/PaymentDeadlineTest.php`:

```php
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
        // ATENÇÃO executor: conferir campos NOT NULL em
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
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --filter PaymentDeadlineTest`
Expected: FAIL (coluna `payment_deadline_at` não existe / prazo não definido).

- [ ] **Step 3: Criar `config/inscriptions.php`**

```php
<?php

return [

    /*
     * Minutos que o inscrito aprovado tem para enviar o comprovante
     * antes de voltar automaticamente para a fila de espera.
     */
    'payment_deadline_minutes' => (int) env('INSCRIPTION_PAYMENT_DEADLINE_MINUTES', 60),

];
```

Adicionar ao `.env.example` (perto das outras configs de app):

```
INSCRIPTION_PAYMENT_DEADLINE_MINUTES=60
```

- [ ] **Step 4: Criar a migration**

`database/migrations/2026_07_15_000001_add_payment_deadline_to_inscriptions_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            // NULL para inscrições aprovadas antes desta feature: nunca expiram automaticamente
            $table->timestamp('payment_deadline_at')->nullable()->after('approved_at');
            // Preenchido apenas pelo cron de expiração
            $table->timestamp('payment_expired_at')->nullable()->after('payment_deadline_at');
            $table->index(['status', 'payment_deadline_at'], 'inscriptions_status_payment_deadline_idx');
        });
    }

    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->dropIndex('inscriptions_status_payment_deadline_idx');
            $table->dropColumn(['payment_deadline_at', 'payment_expired_at']);
        });
    }
};
```

Run: `php artisan migrate`

- [ ] **Step 5: Atualizar `app/Models/Inscription.php`**

Adicionar ao `$fillable` (após `'approved_at'`): `'payment_deadline_at'`, `'payment_expired_at'`.

Adicionar aos `$casts`: `'payment_deadline_at' => 'datetime'`, `'payment_expired_at' => 'datetime'`.

Substituir `approve()` (linha 109) por:

```php
    public function approve(): void
    {
        $this->status = 'aprovado';
        $this->approved_at = now();
        $this->startPaymentDeadline();
        $this->payment_expired_at = null;
        $this->save();
    }

    public function startPaymentDeadline(): void
    {
        $this->payment_deadline_at = now()->addMinutes(
            (int) config('inscriptions.payment_deadline_minutes', 60)
        );
    }
```

Adicionar após `revertRejectionToPending()`:

```php
    /**
     * Expira a inscrição para a fila de espera de forma atômica.
     * Retorna false se o comprovante chegou (ou o status mudou) entre a busca e o update.
     */
    public function expireToWaitlist(): bool
    {
        $updated = static::whereKey($this->id)
            ->where('status', 'aprovado')
            ->whereNull('payment_proof')
            ->where(function ($query) {
                // Pausa da solicitação social também no UPDATE: fecha a janela entre a
                // busca de candidatos do cron e este update
                $query->whereNull('social_request_status')
                    ->orWhere('social_request_status', '!=', 'pendente');
            })
            ->update([
                'status' => 'fila_de_espera',
                'payment_expired_at' => now(),
                'updated_at' => now(),
            ]);

        if ($updated) {
            $this->refresh();
        }

        return (bool) $updated;
    }
```

Nos métodos `approveSocialRequest()` e `rejectSocialRequest()`, adicionar **antes do `$this->save()`** (a pessoa só pode pagar depois da decisão, então o prazo reinicia):

```php
        if ($this->isApproved()) {
            $this->startPaymentDeadline();
        }
```

Adicionar junto aos accessors (perto de `getStatusLabelAttribute`):

```php
    public function getPaymentDeadlineLabelAttribute(): string
    {
        $minutes = (int) config('inscriptions.payment_deadline_minutes', 60);

        if ($minutes % 60 === 0) {
            $hours = intdiv($minutes, 60);

            return $hours === 1 ? '1 hora' : "{$hours} horas";
        }

        return "{$minutes} minutos";
    }
```

- [ ] **Step 6: Rodar e ver passar**

Run: `php artisan test --filter PaymentDeadlineTest`
Expected: PASS (2 testes).

- [ ] **Step 7: Commit**

```bash
git add config/inscriptions.php database/migrations/2026_07_15_000001_add_payment_deadline_to_inscriptions_table.php app/Models/Inscription.php .env.example tests/Feature/PaymentDeadlineTest.php
git commit -m "feat(inscriptions): prazo de pagamento definido na aprovação

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 3: Notificação de expiração (e-mail + WhatsApp)

**Files:**
- Modify: `app/Services/NotificationService.php` (novo método, colocar após `notifyInscriptionWaitlisted`, ~linha 216)
- Create: `resources/views/emails/inscription-expired.blade.php`
- Modify: `app/Services/NotificationResendService.php:49` (viewMap)
- Modify: `routes/web.php` (~linha 32, array `$allowed` do preview)

- [ ] **Step 1: Criar o template `resources/views/emails/inscription-expired.blade.php`**

```blade
@extends('emails.layout')

@section('subject', 'Sua inscrição voltou para a fila de espera - De Casa em Casa')

@section('badge')
<span style="display:inline-block; background-color:#ffedd5; color:#9a3412; padding:6px 20px; border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
    Fila de Espera
</span>
@endsection

@section('content')
<p style="margin:0 0 16px; color:#1a2e6e; font-size:16px; line-height:1.6;">
    Olá <strong>{{ $inscription->full_name }}</strong>,
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Não identificamos o envio do seu comprovante dentro do prazo de <strong>{{ $inscription->payment_deadline_label }}</strong> e, conforme avisamos no e-mail de aprovação, sua vaga foi liberada para a próxima pessoa da fila.
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Sua inscrição <strong>não foi cancelada</strong>: ela voltou para a nossa <strong>fila de espera</strong>. Se um lugar na sala se abrir, sua participação poderá ser aprovada novamente — você receberá um novo aviso, com um novo prazo.
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Já fez o Pix e não conseguiu anexar o comprovante a tempo? Fique tranquilo(a): nossa equipe pode reativar sua aprovação. Acompanhe sua inscrição pelo link abaixo.
</p>
@endsection

@section('event_info')
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td width="40" valign="top" style="padding-right:14px;">
            <div style="width:36px; height:36px; background-color:#1a2e6e; border-radius:8px; text-align:center; line-height:36px; color:#e88a2d; font-size:16px;">
                &#127968;
            </div>
        </td>
        <td valign="top">
            <p style="margin:0 0 4px; color:#1a2e6e; font-size:15px; font-weight:700;">{{ $event->city }}</p>
            <p style="margin:0; color:#9a9384; font-size:13px;">{{ $event->date->format('d/m/Y') }}</p>
        </td>
    </tr>
</table>
@endsection

@section('cta_url', $statusUrl)
@section('cta_text', 'Acompanhar Inscrição')
```

- [ ] **Step 2: Adicionar `notifyInscriptionExpired()` no `NotificationService`**

Inserir após `notifyInscriptionWaitlisted()` (~linha 216), seguindo o padrão dos vizinhos:

```php
    /**
     * Prazo de pagamento vencido (aprovado -> fila_de_espera)
     */
    public function notifyInscriptionExpired(Inscription $inscription): void
    {
        $event = $inscription->event;
        $statusUrl = route('inscricao.status', $inscription->token);

        // Email
        $subject = 'Sua inscrição voltou para a fila de espera - De Casa em Casa';
        $message = "Prazo de pagamento vencido para {$inscription->full_name} - {$event->city}";

        $this->sendEmail(
            $inscription->email,
            $subject,
            $message,
            null,
            'inscription_expired',
            ['inscription_id' => $inscription->id],
            'emails.inscription-expired',
            ['inscription' => $inscription, 'event' => $event, 'statusUrl' => $statusUrl]
        );

        // WhatsApp
        $wa = "Olá {$inscription->full_name}! O prazo de {$inscription->payment_deadline_label} para envio do comprovante do encontro *De Casa em Casa* em *{$event->city}* terminou e sua vaga foi liberada para a próxima pessoa da fila. ";
        $wa .= "Sua inscrição voltou para a *fila de espera* — se um lugar na sala se abrir, sua participação poderá ser aprovada novamente.\n\n";
        $wa .= "Já fez o Pix e não conseguiu enviar o comprovante a tempo? Nossa equipe pode reativar sua aprovação. Acompanhe aqui: {$statusUrl}";

        $this->sendWhatsApp(
            $inscription->whatsapp,
            $wa,
            null,
            'inscription_expired',
            ['inscription_id' => $inscription->id]
        );
    }
```

- [ ] **Step 3: Mapear no reenvio e no preview**

Em `app/Services/NotificationResendService.php`, no `$viewMap` (após `'inscription_waitlisted'`):

```php
            'inscription_expired' => 'emails.inscription-expired',
```

Em `routes/web.php`, adicionar `'inscription-expired'` ao array `$allowed` do preview de e-mails e, logo após a criação da inscrição de preview, garantir dados de prazo:

```php
        if (in_array($template, ['inscription-approved', 'inscription-expired']) && ! $inscription->payment_deadline_at) {
            $inscription->payment_deadline_at = $template === 'inscription-approved' ? now()->addHour() : now()->subMinutes(5);
        }
```

- [ ] **Step 4: Verificar visualmente**

Run: `php artisan serve` (ou ambiente local equivalente) e abrir `http://localhost:8000/email-preview/inscription-expired`.
Expected: e-mail renderiza com badge laranja "Fila de Espera", texto de prazo e CTA "Acompanhar Inscrição".

- [ ] **Step 5: Commit**

```bash
git add app/Services/NotificationService.php app/Services/NotificationResendService.php resources/views/emails/inscription-expired.blade.php routes/web.php
git commit -m "feat(notifications): notificação de prazo de pagamento vencido

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 4: Comando de expiração + agendamento

**Files:**
- Create: `app/Console/Commands/ExpireUnpaidInscriptions.php`
- Modify: `app/Console/Kernel.php:15`
- Test: `tests/Feature/PaymentDeadlineTest.php`

- [ ] **Step 1: Escrever os testes que devem falhar**

Adicionar à `PaymentDeadlineTest`:

```php
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
    }
```

Run: `php artisan test --filter PaymentDeadlineTest`
Expected: novos testes FALHAM ("command inscriptions:expire-unpaid not found").

- [ ] **Step 2: Criar `app/Console/Commands/ExpireUnpaidInscriptions.php`**

```php
<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Inscription;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireUnpaidInscriptions extends Command
{
    protected $signature = 'inscriptions:expire-unpaid {--dry-run : Apenas lista quem seria expirado, sem alterar nada}';

    protected $description = 'Move para a fila de espera inscrições aprovadas sem comprovante com prazo de pagamento vencido';

    public function handle(NotificationService $notificationService): int
    {
        $candidates = Inscription::with('event')
            ->where('status', 'aprovado')
            ->whereNull('payment_proof')
            ->whereNotNull('payment_deadline_at')
            ->where('payment_deadline_at', '<=', now())
            ->where(function ($query) {
                // Prazo pausado enquanto a solicitação de contribuição social está em análise
                $query->whereNull('social_request_status')
                    ->orWhere('social_request_status', '!=', 'pendente');
            })
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('Nenhuma inscrição com prazo de pagamento vencido.');

            return self::SUCCESS;
        }

        $expired = 0;

        foreach ($candidates as $inscription) {
            // Não expira quem nunca recebeu a notificação de aprovação (ex.: falha do Resend).
            // Sem filtro de type: e-mail OU WhatsApp enviado conta como avisado, pois ambos carregam o prazo
            $wasNotified = Notification::where('channel', 'inscription_approved')
                ->where('status', 'sent')
                ->where('metadata->inscription_id', $inscription->id)
                ->where('created_at', '>=', $inscription->approved_at)
                ->exists();

            if (! $wasNotified) {
                $this->warn("  #{$inscription->id} {$inscription->full_name}: sem notificação de aprovação enviada — expiração adiada.");

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  [dry-run] Expiraria #{$inscription->id} {$inscription->full_name} (prazo: {$inscription->payment_deadline_at})");

                continue;
            }

            if (! $inscription->expireToWaitlist()) {
                continue;
            }

            $expired++;

            try {
                ActivityLog::log('expirar_inscricao', "Prazo de pagamento vencido: {$inscription->full_name} movido(a) para a fila de espera", $inscription);
            } catch (\Throwable $e) {
                Log::warning('Falha ao registrar log de atividade: '.$e->getMessage());
            }

            // Evento lotado: a pessoa não tinha como enviar comprovante (formulário oculto),
            // então o aviso de "prazo vencido" seria incoerente — expira em silêncio
            if (! ($inscription->event && $inscription->event->isFull())) {
                try {
                    $notificationService->notifyInscriptionExpired($inscription);
                } catch (\Throwable $e) {
                    Log::error("Falha ao notificar expiração da inscrição #{$inscription->id}: ".$e->getMessage());
                }
            }

            $this->line("  Expirada: #{$inscription->id} {$inscription->full_name}");
        }

        $this->info("Inscrições expiradas: {$expired}");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 3: Rodar e ver passar**

Run: `php artisan test --filter PaymentDeadlineTest`
Expected: PASS (9 testes).

- [ ] **Step 4: Agendar no `app/Console/Kernel.php`**

O `schedule:run` executa os comandos em sequência e o `queue:work --max-time=55` bloqueia ~55s; o novo comando deve vir **ANTES** para rodar no início de cada minuto:

```php
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('inscriptions:expire-unpaid')->everyMinute()->withoutOverlapping();
        $schedule->command('queue:work database --max-time=55 --sleep=1 --tries=3')->everyMinute()->withoutOverlapping();
    }
```

Run: `php artisan schedule:list`
Expected: os dois comandos listados, `inscriptions:expire-unpaid` primeiro.

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/ExpireUnpaidInscriptions.php app/Console/Kernel.php tests/Feature/PaymentDeadlineTest.php
git commit -m "feat(inscriptions): expira aprovados sem comprovante após o prazo

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 5: Copy de urgência no e-mail e WhatsApp de aprovação

**Files:**
- Modify: `resources/views/emails/inscription-approved.blade.php`
- Modify: `app/Services/NotificationService.php:139-179` (`notifyInscriptionApproved`)

Copy baseado no mockup aprovado pelo cliente. Regra de copy do projeto: usar "seu lugar na sala", evitar "cadeira". Nunca escrever "1 hora" fixo — sempre `$inscription->payment_deadline_label` e horário absoluto do prazo.

- [ ] **Step 1: Reescrever o bloco `content` do template**

Em `inscription-approved.blade.php`, substituir a seção `@section('content')` inteira (linhas 11-43) por:

```blade
@section('content')
@php
    $deadlineLabel = $inscription->payment_deadline_label;
    $deadlineTime = $inscription->payment_deadline_at?->format('H\hi');
    $deadlineDate = $inscription->payment_deadline_at?->format('d/m');
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fef3c7; border:1px solid #f59e0b; border-radius:8px; margin-bottom:20px;">
    <tr>
        <td style="padding:12px 16px; text-align:center;">
            <p style="margin:0; color:#92400e; font-size:14px; font-weight:800; letter-spacing:0.5px;">
                &#9203; AÇÃO NECESSÁRIA: SUA VAGA EXPIRA EM {{ mb_strtoupper($deadlineLabel) }}
            </p>
        </td>
    </tr>
</table>
<p style="margin:0 0 16px; color:#1a2e6e; font-size:16px; line-height:1.6;">
    Olá <strong>{{ $inscription->full_name }}</strong>,
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    A boa notícia é que sua participação foi oficialmente <strong style="color:#1a2e6e;">aprovada</strong>. A notícia urgente é que você precisa agir agora.
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Como o nosso espaço é muito intimista e temos uma lista de espera enorme de pessoas querendo participar, <strong>sua vaga ficará reservada no seu nome apenas por {{ $deadlineLabel }}@if($deadlineTime) (até às {{ $deadlineTime }} de {{ $deadlineDate }})@endif</strong>. Se não recebermos o seu comprovante dentro desse prazo, sua inscrição voltará automaticamente para a fila de espera e seu lugar na sala será liberado para a próxima pessoa.
</p>
<p style="margin:0 0 8px; color:#1a2e6e; font-size:15px; line-height:1.7; font-weight:700;">
    Siga os 2 passos abaixo para não perder seu lugar na sala:
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    <strong>1.</strong> Faça sua contribuição usando a Chave Pix informada no quadro abaixo.<br>
    <strong>2.</strong> Com o comprovante salvo no celular, clique no botão para anexá-lo e finalizar.
</p>
@if(config('services.pix.key'))
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef2ff; border:1px solid #c7d2fe; border-radius:8px;">
    <tr>
        <td style="padding:16px;">
            <p style="margin:0 0 6px; color:#1a2e6e; font-size:13px; font-weight:600;">Chave Pix para contribuição:</p>
            <p style="margin:0 0 4px; color:#4f46e5; font-size:16px; font-weight:700; font-family:monospace;">{{ config('services.pix.key') }}</p>
            <p style="margin:0 0 8px; color:#9a9384; font-size:12px;">{{ config('services.pix.holder') }}</p>
            <p style="margin:0; color:#6b7280; font-size:12px; font-style:italic;">Meu sonho era fazer uma bilheteria que subvertesse a lógica do entretenimento e nos colocasse no campo da arte, no fluxo da dádiva. Assim, essa é uma oportunidade para você deixar fluir a generosidade! Esse encontro tem valor inestimável e o preço é sugerido: R$ 100,00 (cem reais). Contribua conforme mandar seu coração, segundo as suas condições e recursos, <strong>mas finalize agora para assegurar seu lugar.</strong></p>
        </td>
    </tr>
</table>
@endif
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f3ed; border-left:3px solid #e88a2d; border-radius:6px; margin-top:16px;">
    <tr>
        <td style="padding:14px 16px;">
            <p style="margin:0 0 6px; color:#1a2e6e; font-size:13px; font-weight:700;">Não consegue contribuir com o valor de referência neste momento?</p>
            <p style="margin:0; color:#4a4639; font-size:13px; line-height:1.6;">
                Antes de fazer o Pix, você pode solicitar uma <strong>contribuição social</strong> no link de status. Conte brevemente sua situação e o valor que consegue contribuir — nossa equipe analisa e retorna com a resposta. <em>Enquanto sua solicitação estiver em análise, o prazo fica pausado. A vaga só é confirmada após aprovação da solicitação e envio do comprovante.</em>
            </p>
        </td>
    </tr>
</table>
<p style="margin:16px 0 0; color:#92400e; font-size:14px; line-height:1.6;">
    &#9200; O cronômetro já está rodando. Nos vemos lá?
</p>
@endsection
```

E trocar a linha do CTA (última linha do arquivo):

```blade
@section('cta_text', 'Já fiz o Pix: enviar comprovante')
```

(O `@if($deadlineTime)` protege reenvios de notificações antigas em que `payment_deadline_at` é NULL.)

- [ ] **Step 2: Atualizar subject e WhatsApp em `notifyInscriptionApproved()`**

Trocar a linha do subject:

```php
        $subject = "Aprovado(a)! Você tem {$inscription->payment_deadline_label} para garantir seu lugar - De Casa em Casa";
```

E logo após a linha `$wa = "Olá {$inscription->full_name}! Sua participação..."` adicionar:

```php
        $deadlineTime = $inscription->payment_deadline_at?->format('H\hi');
        $wa .= "⏳ *Atenção:* sua vaga fica reservada por *{$inscription->payment_deadline_label}*".($deadlineTime ? " (até às {$deadlineTime})" : '').". Se não recebermos seu comprovante nesse prazo, sua inscrição volta automaticamente para a fila de espera e seu lugar é liberado para a próxima pessoa.\n\n";
```

- [ ] **Step 3: Verificar visualmente**

Abrir `http://localhost:8000/email-preview/inscription-approved`.
Expected: banner âmbar com "AÇÃO NECESSÁRIA", prazo com horário absoluto, passos 1-2, CTA "Já fiz o Pix: enviar comprovante".

Fluxo real: aprovar uma inscrição de teste no admin e conferir o e-mail no MailHog.

- [ ] **Step 4: Rodar testes (regressão)**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/emails/inscription-approved.blade.php app/Services/NotificationService.php
git commit -m "feat(notifications): copy de urgência com prazo no e-mail de aprovação

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 6: Guard atômico no upload de comprovante

**Files:**
- Modify: `app/Http/Controllers/InscriptionController.php:158-182`
- Test: `tests/Feature/PaymentDeadlineTest.php`

- [ ] **Step 1: Escrever o teste que deve falhar**

```php
    public function test_upload_apos_expiracao_e_bloqueado(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), [
            'status' => 'fila_de_espera',
            'payment_expired_at' => now(),
        ]);

        $response = $this->post(route('inscricao.upload-comprovante', $inscription->token), [
            'payment_proof' => \Illuminate\Http\UploadedFile::fake()->create('comprovante.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('inscricao.status', $inscription->token));
        $response->assertSessionHas('error');
        $this->assertNull($inscription->fresh()->payment_proof);
    }
```

Run: `php artisan test --filter test_upload_apos_expiracao_e_bloqueado`
Expected: FAIL apenas se a mensagem específica for verificada — o bloqueio genérico já existe. Ajuste a asserção para exigir a mensagem nova: `$response->assertSessionHas('error', 'O prazo para envio do comprovante terminou e sua inscrição voltou para a fila de espera.');` → FAIL.

- [ ] **Step 2: Atualizar `uploadPaymentProof()`**

Adicionar `use Illuminate\Support\Facades\Storage;` nos imports e substituir o método por:

```php
    public function uploadPaymentProof(Request $request, string $token)
    {
        $inscription = Inscription::where('token', $token)->firstOrFail();

        if (! $inscription->isApproved()) {
            $message = $inscription->isWaitlisted() && $inscription->payment_expired_at
                ? 'O prazo para envio do comprovante terminou e sua inscrição voltou para a fila de espera.'
                : 'Sua inscrição não está em status de aprovação para envio de comprovante.';

            return redirect()
                ->route('inscricao.status', $token)
                ->with('error', $message);
        }

        $request->validate([
            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'payment_proof.required' => 'Selecione o arquivo do comprovante.',
            'payment_proof.mimes' => 'O comprovante deve ser uma imagem (JPG, PNG) ou PDF.',
            'payment_proof.max' => 'O arquivo deve ter no máximo 5MB.',
        ]);

        $path = $request->file('payment_proof')->store('payment_proofs', 'public');

        // Update condicional: aceita o comprovante apenas enquanto o status ainda é
        // "aprovado" (evita corrida com o cron de expiração)
        $accepted = Inscription::whereKey($inscription->id)
            ->where('status', 'aprovado')
            ->update(['payment_proof' => $path, 'updated_at' => now()]);

        if (! $accepted) {
            Storage::disk('public')->delete($path);

            return redirect()
                ->route('inscricao.status', $token)
                ->with('error', 'O prazo para envio do comprovante terminou e sua inscrição voltou para a fila de espera.');
        }

        return redirect()->route('inscricao.upload-sucesso', $token);
    }
```

- [ ] **Step 3: Rodar e ver passar**

Run: `php artisan test --filter PaymentDeadlineTest`
Expected: PASS (10 testes).

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/InscriptionController.php tests/Feature/PaymentDeadlineTest.php
git commit -m "fix(inscriptions): upload de comprovante atômico e bloqueado após expiração

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 7: Página de status — prazo, contador e mensagem de expirado

**Files:**
- Modify: `resources/views/inscriptions/status.blade.php`

- [ ] **Step 1: Contador regressivo no bloco de aprovado**

Dentro do bloco de upload (`@if($inscription->isApproved() && !($inscription->event && $inscription->event->isFull()))`, linha ~186), logo após a abertura da `<div class="border-2 border-dashed...">` e antes do `<h3>`, inserir:

```blade
                    @if($inscription->payment_deadline_at && !$inscription->payment_proof)
                    <div class="bg-amber-50 border border-amber-300 rounded-lg p-4 mb-4"
                         x-data="{
                            deadline: {{ $inscription->payment_deadline_at->getTimestamp() * 1000 }},
                            remaining: 1,
                            display: '',
                            tick() {
                                this.remaining = this.deadline - Date.now();
                                if (this.remaining <= 0) return;
                                const totalSec = Math.floor(this.remaining / 1000);
                                const m = Math.floor(totalSec / 60);
                                const s = totalSec % 60;
                                this.display = m + 'min ' + String(s).padStart(2, '0') + 's';
                            }
                         }"
                         x-init="tick(); setInterval(() => tick(), 1000)">
                        <p class="text-sm font-bold text-amber-900">
                            ⏳ Prazo para envio do comprovante: até às {{ $inscription->payment_deadline_at->format('H\hi \d\e d/m') }}
                        </p>
                        <p class="text-sm text-amber-800 mt-1" x-show="remaining > 0" x-cloak>
                            Tempo restante: <strong x-text="display"></strong>
                        </p>
                        <p class="text-sm text-amber-800 mt-1" x-show="remaining <= 0" x-cloak>
                            O prazo terminou. Se você não enviou o comprovante, sua inscrição voltará para a fila de espera em instantes.
                        </p>
                        <p class="text-xs text-amber-700 mt-2">
                            Após o prazo, sua inscrição volta automaticamente para a fila de espera e seu lugar na sala é liberado para a próxima pessoa.
                        </p>
                    </div>
                    @endif
```

- [ ] **Step 2: Mensagem específica para quem expirou**

No bloco "Mensagem por Status", inserir ANTES do `@elseif($inscription->isWaitlisted())` (linha ~138):

```blade
                @elseif($inscription->isWaitlisted() && $inscription->payment_expired_at)
                    <p class="text-gray-700 leading-relaxed">
                        Não recebemos seu comprovante dentro do prazo e sua inscrição voltou para a <strong>fila de espera</strong> — seu lugar na sala foi liberado para a próxima pessoa.
                    </p>
                    <p class="text-gray-700 leading-relaxed mt-3">
                        Se um lugar se abrir, sua participação poderá ser aprovada novamente e você receberá um novo aviso por e-mail e WhatsApp, com um novo prazo.
                    </p>
                    <p class="text-gray-700 leading-relaxed mt-3">
                        Já fez o Pix e não conseguiu enviar o comprovante a tempo? Fique tranquilo(a): nossa equipe pode reativar sua aprovação.
                    </p>
```

E no bloco do badge, inserir ANTES do `@elseif($inscription->isWaitlisted())` (linha ~56):

```blade
                @elseif($inscription->isWaitlisted() && $inscription->payment_expired_at)
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-orange-100 text-orange-800">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                        Fila de Espera
                    </span>
```

Atenção: o CSS condicional da `<div>` da mensagem (linha ~101) usa `isWaitlisted()` sem distinção — a cor laranja já serve para os dois casos, não precisa mudar.

- [ ] **Step 3: Verificação manual**

1. Aprovar inscrição de teste → abrir link de status → ver contador regressivo com horário correto.
2. No banco: `UPDATE inscriptions SET payment_deadline_at = NOW() - INTERVAL 5 MINUTE WHERE id = X;` → rodar `php artisan inscriptions:expire-unpaid` → recarregar página → badge "Fila de Espera" + mensagem de prazo vencido, formulário de upload sumiu.

- [ ] **Step 4: Commit**

```bash
git add resources/views/inscriptions/status.blade.php
git commit -m "feat(inscriptions): contador de prazo e mensagem de expiração na página de status

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 8: Verificação ponta-a-ponta e checklist de deploy (HostGator M)

- [ ] **Step 1: Fluxo completo local (com MailHog)**

1. Criar evento publicado com vagas; criar inscrição pelo formulário público.
2. Aprovar no admin → conferir no MailHog: e-mail com banner de urgência e horário do prazo; conferir `payment_deadline_at` no banco.
3. Página de status: contador rodando.
4. `UPDATE inscriptions SET payment_deadline_at = NOW() - INTERVAL 5 MINUTE ...` e rodar `php artisan inscriptions:expire-unpaid --dry-run` (lista, não altera) e depois sem `--dry-run`.
5. Conferir: status `fila_de_espera`, e-mail de expiração no MailHog, ActivityLog registrado no admin (`expirar_inscricao`), painel com contadores corretos.
6. Re-aprovar a mesma inscrição no admin → novo prazo + novo e-mail (fluxo de segunda chance).
7. Testar caminho feliz: aprovar outra inscrição, enviar comprovante dentro do prazo, rodar o comando → nada muda; admin confirma pagamento normalmente.
8. Run: `php artisan test` → Expected: PASS.

- [ ] **Step 2: Deploy (produção HostGator plano M)**

1. Backup do banco (cPanel > phpMyAdmin ou `mysqldump`).
2. Subir o código (git pull / deploy habitual).
3. `php artisan migrate --force`
4. `php artisan config:clear && php artisan view:clear` (e `config:cache` se o projeto usa cache de config em produção).
5. Confirmar no cPanel > Cron Jobs que existe `* * * * * cd /home/USUARIO/CAMINHO && php artisan schedule:run >> /dev/null 2>&1` (deve existir — o `queue:work` já roda por ele; se não existir, criar).
6. `php artisan schedule:list` via SSH para confirmar os dois comandos.
7. `php artisan inscriptions:expire-unpaid --dry-run` — Expected: "Nenhuma inscrição..." (legados têm prazo NULL).
8. Aprovar uma inscrição real de teste e acompanhar o ciclo completo em produção.

- [ ] **Step 3: Comunicar ao cliente/admin**

- Aprovados **antes** do deploy não expiram automaticamente (sem prazo registrado) — resolver manualmente os pendentes antigos.
- **Evitar aprovações de madrugada/em massa fora de horário**: o prazo corre enquanto a pessoa dorme. Sugerir aprovar em horário comercial.
- O prazo é configurável sem código: `INSCRIPTION_PAYMENT_DEADLINE_MINUTES` no `.env` (+ `config:clear`).
- Solicitação de contribuição social pausa o prazo; a decisão da equipe reinicia a contagem.
- Quem expirou pode ser re-aprovado normalmente pelo admin (ganha novo prazo e novo e-mail).

- [ ] **Step 4: Commit final / merge**

Usar superpowers:finishing-a-development-branch.

---

## As built — desvios deliberados do texto original (pós-review)

Este plano foi executado integralmente; as revisões de código introduziram melhorias que tornam alguns trechos acima desatualizados. O código é a fonte da verdade. Desvios principais:

- `payment_expired_at` **não** está em `$fillable` (campo é escrito só pelo cron/atribuição direta; nos testes use `forceFill`). `startPaymentDeadline()` centraliza o reinício do prazo e a limpeza de `payment_expired_at`.
- `expireToWaitlist()` também re-checa `payment_deadline_at <= now()` no UPDATE (o reenvio de aprovação pode reiniciar o prazo no meio do loop do cron).
- O comando eager-loada `event` com `withTrashed()`, processa no máximo **25 candidatos por execução** (logando o excedente) e é agendado com `withoutOverlapping(5)`.
- `NotificationResendService`: reenvio de aprovação por e-mail **reinicia prazo vencido** e é ignorado para quem não aguarda mais pagamento; reenvio por WhatsApp com prazo obsoleto é **bloqueado** (texto verbatim não pode ser re-renderizado).
- Índice composto `(channel, status)` em `notifications` (migration `2026_07_15_000002`).
- Página de status: contador **não aparece** com solicitação social pendente (mostra "⏸ Prazo pausado") e agenda um `location.reload()` único ~75s após o prazo zerar.
- Upload: guard para `store() === false` e deleção do comprovante antigo ao substituir.
- Infra dev: `pcre.jit=0` em `docker/php/local.ini` (segfault do PHPUnit em ARM64); guard fail-fast em `tests/CreatesApplication.php` aborta se o banco resolvido não for `testing` (proteção contra `config:cache`).

## Apêndice: respostas às dúvidas do cliente

- **"Confirmação do pagamento"** — o relógio para quando o comprovante é enviado (não exige confirmação do admin dentro da 1h; a conferência manual continua depois, como hoje).
- **"Contagem do prazo"** — começa na aprovação (o e-mail é disparado no mesmo instante, sincronamente) e o e-mail mostra o horário exato do vencimento; verificação a cada minuto pelo cron já existente no HostGator.
- **"Validade do link"** — o link continua funcionando para sempre como página de acompanhamento; o que expira é a possibilidade de enviar comprovante, que segue o status. Quem clicar depois do prazo vê a explicação de que voltou para a fila.
