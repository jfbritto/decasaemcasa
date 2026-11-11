# O app no painel e na página de status: plano de implementação

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** mostrar no painel quem entrou pelo app (selo, filtro, quadro e contador) e convidar para o app na página de status da inscrição.

**Architecture:** uma classe de leitura, `App\Participants\AppPresence`, responde "quem usa o app" por e-mail, com um número fixo de consultas. O filtro da lista usa dois escopos novos em `Inscription`, com a mesma regra de e-mail do `ownedBy` (AM2). As telas só leem esses dados. A página de status ganha um bloco Blade com os links de `config('services.app_stores')`.

**Tech Stack:** Laravel 10, Blade, Tailwind 3 (com `public/build/` versionado), PHPUnit 10 no MySQL do Docker.

**Spec:** `docs/superpowers/specs/2026-11-11-app-no-painel-design.md` (AP1 a AP9).

**Branch:** `feat/emails-links-do-app` (PR #10). Testes: `docker-compose exec -T app php artisan test --filter=<Classe>`. Docker CLI quebrado na máquina: usar `docker-compose`.

---

## Arquivos

| Arquivo | Papel |
|---|---|
| Criar `app/Participants/AppPresenceEntry.php` | Valor: última entrada, último uso, plataformas do push |
| Criar `app/Participants/AppPresence.php` | Leitura por e-mail e totais |
| Mudar `app/Models/Inscription.php` | Escopos `usesApp()` e `doesNotUseApp()` |
| Mudar `app/Http/Controllers/Admin/InscriptionController.php` | Filtro `?app=`, presença na lista e na página |
| Mudar `resources/views/admin/inscriptions/index.blade.php` | Selo, filtro, "Limpar" |
| Mudar `resources/views/admin/inscriptions/show.blade.php` | Quadro "App" |
| Mudar `app/Http/Controllers/Admin/DashboardController.php` e `resources/views/admin/dashboard.blade.php` | Faixa "No app" |
| Mudar `resources/views/inscriptions/status.blade.php` | Convite e frase do suporte |
| Testes `tests/Feature/AppPresenceTest.php`, `tests/Feature/Admin/AppPresenceAdminTest.php`, `tests/Feature/StatusPageAppLinksTest.php` | |

Atenção: não use `$app` como nome de variável de view. O Laravel compartilha `$app` (o container) com todas as views.

---

### Task 1: AppPresence e escopos

**Files:**
- Create: `app/Participants/AppPresenceEntry.php`, `app/Participants/AppPresence.php`
- Modify: `app/Models/Inscription.php` (perto de `scopeOwnedBy`, linha ~79)
- Test: `tests/Feature/AppPresenceTest.php`

- [ ] **Step 1: teste que falha**

```php
<?php

namespace Tests\Feature;

use App\Models\Inscription;
use App\Participants\AppPresence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesInscriptions;
use Tests\TestCase;

/**
 * Quem usa o app (spec 2026-11-11-app-no-painel-design, AP2 a AP5).
 */
class AppPresenceTest extends TestCase
{
    use MakesInscriptions;
    use RefreshDatabase;

    public function test_acha_o_participante_pelo_e_mail_normalizado(): void
    {
        $participant = $this->makeParticipant('maria@example.com');
        $participant->forceFill(['last_login_at' => now()->subDay()])->save();
        $participant->pushTokens()->create(['token' => 'ExponentPushToken[velunora-1]', 'platform' => 'ios']);
        $participant->pushTokens()->create(['token' => 'ExponentPushToken[velunora-2]', 'platform' => 'ios']);
        $token = $participant->createToken('app');
        $token->accessToken->forceFill(['last_used_at' => now()->subHour()])->save();

        $found = app(AppPresence::class)->forEmails([' Maria@Example.com ', 'outra@example.com']);

        $this->assertSame(['maria@example.com'], array_keys($found));
        $entry = $found['maria@example.com'];
        $this->assertTrue($entry->pushEnabled());
        $this->assertSame(['ios'], $entry->platforms);
        $this->assertSame('iPhone', $entry->platformLabel());
        $this->assertNotNull($entry->lastLoginAt);
        $this->assertNotNull($entry->lastUsedAt);
    }

    public function test_e_mail_com_acento_nao_e_o_mesmo(): void
    {
        $this->makeParticipant('maria@gmail.com');

        $this->assertNull(app(AppPresence::class)->forEmail('maria@gmáil.com'));
        $this->assertNotNull(app(AppPresence::class)->forEmail('MARIA@gmail.com'));
    }

    public function test_sem_push_e_sem_token_usado(): void
    {
        $this->makeParticipant('joao@example.com');

        $entry = app(AppPresence::class)->forEmail('joao@example.com');

        $this->assertFalse($entry->pushEnabled());
        $this->assertNull($entry->lastUsedAt);
    }

    public function test_totais(): void
    {
        $this->makeParticipant('a@example.com')->pushTokens()->create(['token' => 'ExponentPushToken[a]', 'platform' => 'android']);
        $b = $this->makeParticipant('b@example.com');
        $b->pushTokens()->create(['token' => 'ExponentPushToken[b1]', 'platform' => 'ios']);
        $b->pushTokens()->create(['token' => 'ExponentPushToken[b2]', 'platform' => 'android']);
        $this->makeParticipant('c@example.com');

        $this->assertSame(['participants' => 3, 'with_push' => 2], app(AppPresence::class)->totals());
    }

    public function test_escopos_usa_e_nao_usa_o_app(): void
    {
        $event = $this->makeEvent();
        $this->makeParticipant('maria@example.com');
        $usa = $this->makeInscription($event, ['email' => ' Maria@Example.com ']);
        $naoUsa = $this->makeInscription($event, ['email' => 'maria@gmáil.com', 'cpf' => '111.444.777-35']);

        $this->assertSame([$usa->id], Inscription::usesApp()->pluck('id')->all());
        $this->assertSame([$naoUsa->id], Inscription::doesNotUseApp()->pluck('id')->all());
    }
}
```

- [ ] **Step 2: rodar e ver falhar**

Run: `docker-compose exec -T app php artisan test --filter=AppPresenceTest`
Expected: FAIL (classe `App\Participants\AppPresence` não existe).

- [ ] **Step 3: implementar**

`app/Participants/AppPresenceEntry.php`:

```php
<?php

namespace App\Participants;

use Carbon\CarbonInterface;

/**
 * O que o painel mostra de quem usa o app (spec 2026-11-11-app-no-painel-design, AP4).
 */
final class AppPresenceEntry
{
    /**
     * @param  list<string>  $platforms  "android" e/ou "ios", sem repetição, em ordem
     */
    public function __construct(
        public readonly ?CarbonInterface $lastLoginAt,
        public readonly ?CarbonInterface $lastUsedAt,
        public readonly array $platforms,
    ) {
    }

    /** O app registrou pelo menos um aparelho para push. */
    public function pushEnabled(): bool
    {
        return $this->platforms !== [];
    }

    /** "iPhone", "Android" ou "Android e iPhone". */
    public function platformLabel(): string
    {
        return implode(' e ', array_map(fn (string $p) => $p === 'ios' ? 'iPhone' : 'Android', $this->platforms));
    }
}
```

`app/Participants/AppPresence.php`:

```php
<?php

namespace App\Participants;

use App\Models\Participant;
use App\Models\ParticipantPushToken;
use Illuminate\Support\Carbon;

/**
 * Quem usa o app, para o painel (spec 2026-11-11-app-no-painel-design, AP2 a AP5).
 * Só leitura. A chave é o e-mail normalizado (Participant::normalizeEmail), e
 * participants.email é binário: "maria@gmáil.com" não é "maria@gmail.com" (AM2).
 */
class AppPresence
{
    /**
     * @param  iterable<string|null>  $emails
     * @return array<string, AppPresenceEntry> e-mail normalizado => presença
     */
    public function forEmails(iterable $emails): array
    {
        $keys = [];
        foreach ($emails as $email) {
            if (is_string($email) && trim($email) !== '') {
                $keys[Participant::normalizeEmail($email)] = true;
            }
        }
        if ($keys === []) {
            return [];
        }

        return Participant::query()
            ->whereIn('email', array_keys($keys))
            ->withMax('tokens', 'last_used_at')
            ->with('pushTokens:id,participant_id,platform')
            ->get()
            ->mapWithKeys(fn (Participant $participant) => [
                $participant->email => new AppPresenceEntry(
                    lastLoginAt: $participant->last_login_at,
                    lastUsedAt: $participant->tokens_max_last_used_at ? Carbon::parse($participant->tokens_max_last_used_at) : null,
                    platforms: $participant->pushTokens->pluck('platform')->unique()->sort()->values()->all(),
                ),
            ])
            ->all();
    }

    public function forEmail(?string $email): ?AppPresenceEntry
    {
        if (! is_string($email) || trim($email) === '') {
            return null;
        }

        return $this->forEmails([$email])[Participant::normalizeEmail($email)] ?? null;
    }

    /**
     * Total geral, sem período (AP8).
     *
     * @return array{participants: int, with_push: int}
     */
    public function totals(): array
    {
        return [
            'participants' => Participant::count(),
            'with_push' => ParticipantPushToken::query()->distinct()->count('participant_id'),
        ];
    }
}
```

Em `app/Models/Inscription.php`, logo depois de `scopeOwnedBy`:

```php
    /**
     * Inscrições de quem já entrou no app (spec 2026-11-11-app-no-painel-design,
     * AP3): a regra do ownedBy, vista do lado da inscrição.
     */
    public function scopeUsesApp(Builder $query): Builder
    {
        return $query->whereExists(self::participantWithSameEmail());
    }

    public function scopeDoesNotUseApp(Builder $query): Builder
    {
        return $query->whereNotExists(self::participantWithSameEmail());
    }

    private static function participantWithSameEmail(): \Closure
    {
        return fn ($sub) => $sub->selectRaw('1')
            ->from('participants')
            ->whereRaw('participants.email = LOWER(TRIM(inscriptions.email)) COLLATE utf8mb4_bin');
    }
```

- [ ] **Step 4: rodar e ver passar**

Run: `docker-compose exec -T app php artisan test --filter=AppPresenceTest`
Expected: 5 passed.

- [ ] **Step 5: commit**

```bash
git add app/Participants/AppPresence.php app/Participants/AppPresenceEntry.php app/Models/Inscription.php tests/Feature/AppPresenceTest.php
git commit -m "feat(painel): AppPresence diz quem usa o app, por e-mail"
```

### Task 2: lista de inscrições (selo e filtro)

**Files:**
- Modify: `app/Http/Controllers/Admin/InscriptionController.php` (`index`, linhas ~26 a 180)
- Modify: `resources/views/admin/inscriptions/index.blade.php` (filtros ~linha 100 a 115; célula do nome ~linha 303)
- Test: `tests/Feature/Admin/AppPresenceAdminTest.php`

- [ ] **Step 1: teste que falha**

```php
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
}
```

- [ ] **Step 2: rodar e ver falhar**

Run: `docker-compose exec -T app php artisan test --filter=AppPresenceAdminTest`
Expected: FAIL (`data-app-badge` não aparece; o filtro não filtra).

- [ ] **Step 3: implementar**

No controller, `use App\Participants\AppPresence;` e assinatura
`public function index(Request $request, AdminPeriodFilter $periodFilter, AppPresence $appPresence)`.
Depois do bloco "Filtro por solicitação social":

```php
        // Filtro por uso do app (spec 2026-11-11-app-no-painel-design, AP6)
        if ($request->input('app') === 'sim') {
            $query->usesApp();
        } elseif ($request->input('app') === 'nao') {
            $query->doesNotUseApp();
        }
```

Depois do `paginate`:

```php
        $appPresenceByEmail = $appPresence->forEmails($inscriptions->pluck('email'));
```

e `compact('inscriptions', 'events', 'counts', 'appPresenceByEmail')` no `return view`.

Na view, depois do `<div class="sm:w-44">` do `social_request`:

```blade
                <div class="sm:w-36">
                    <select name="app" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">App: todos</option>
                        <option value="sim" {{ request('app') === 'sim' ? 'selected' : '' }}>Usa o app</option>
                        <option value="nao" {{ request('app') === 'nao' ? 'selected' : '' }}>Não usa o app</option>
                    </select>
                </div>
```

No `@if` do "Limpar", acrescentar `'app'` às duas listas de `hasAny`.

Na célula do nome, depois do `@endif` dos selos "Social":

```blade
                                                @php
                                                    $presence = $appPresenceByEmail[\App\Models\Participant::normalizeEmail($inscription->email ?? '')] ?? null;
                                                @endphp
                                                @if($presence)
                                                    <span data-app-badge class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-xs font-medium bg-sky-100 text-sky-700"
                                                          title="{{ $presence->pushEnabled() ? 'Usa o app, avisos ligados ('.$presence->platformLabel().')' : 'Usa o app, avisos desligados' }}">
                                                        App
                                                        @if($presence->pushEnabled())
                                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 2a6 6 0 0 0-6 6v3.59l-1.7 1.7A1 1 0 0 0 3 15h14a1 1 0 0 0 .7-1.71L16 11.59V8a6 6 0 0 0-6-6Zm0 16a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 10 18Z"/></svg>
                                                        @endif
                                                    </span>
                                                @endif
```

- [ ] **Step 4: rodar e ver passar**

Run: `docker-compose exec -T app php artisan test --filter=AppPresenceAdminTest`
Expected: 2 passed.

- [ ] **Step 5: commit**

```bash
git add app/Http/Controllers/Admin/InscriptionController.php resources/views/admin/inscriptions/index.blade.php tests/Feature/Admin/AppPresenceAdminTest.php
git commit -m "feat(painel): selo e filtro de quem usa o app na lista de inscrições"
```

### Task 3: página da inscrição e dashboard

**Files:**
- Modify: `app/Http/Controllers/Admin/InscriptionController.php` (`show`, linha ~186)
- Modify: `resources/views/admin/inscriptions/show.blade.php` (depois do quadro "Encontro", linha ~370)
- Modify: `app/Http/Controllers/Admin/DashboardController.php`, `resources/views/admin/dashboard.blade.php` (linha 11 e depois do grid do topo)
- Test: `tests/Feature/Admin/AppPresenceAdminTest.php` (acrescentar)

- [ ] **Step 1: testes que falham** (acrescentar à classe da Task 2)

```php
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
```

- [ ] **Step 2: rodar e ver falhar**

Run: `docker-compose exec -T app php artisan test --filter=AppPresenceAdminTest`
Expected: os dois novos falham.

- [ ] **Step 3: implementar**

`show`: assinatura `public function show(Inscription $inscription, AppPresence $appPresence)`; antes do `return`:
`$appPresenceEntry = $appPresence->forEmail($inscription->email);` e acrescentar `'appPresenceEntry'` ao `compact`.

Na view, depois do `</div>` do quadro "Encontro" e antes de `{{-- Migrar de Evento --}}`:

```blade
                {{-- App (spec 2026-11-11-app-no-painel-design, AP7) --}}
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">App</h2>
                    @if($appPresenceEntry)
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500">Última entrada</dt>
                                <dd class="text-gray-900 text-right">{{ $appPresenceEntry->lastLoginAt?->format('d/m/Y H:i') ?? 'sem registro' }}</dd>
                            </div>
                            @if($appPresenceEntry->lastUsedAt)
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500">Último uso</dt>
                                <dd class="text-gray-900 text-right">{{ $appPresenceEntry->lastUsedAt->format('d/m/Y H:i') }}</dd>
                            </div>
                            @endif
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500">Avisos no celular</dt>
                                <dd class="text-gray-900 text-right">{{ $appPresenceEntry->pushEnabled() ? 'ligados ('.$appPresenceEntry->platformLabel().')' : 'desligados' }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="text-sm text-gray-500">Ainda não entrou no app.</p>
                    @endif
                </div>
```

Dashboard: `index(AdminPeriodFilter $periodFilter, AppPresence $appPresence)`, `$app_totals = $appPresence->totals();` e `'app_totals'` no `compact`. Na view, trocar o `mb-8` do grid do topo (linha 11) por `mb-4` e, logo depois do `</div>` desse grid:

```blade
        {{-- No app (spec 2026-11-11-app-no-painel-design, AP8): total geral, sem o filtro de período --}}
        <div data-app-totals
           class="flex flex-wrap items-center justify-between gap-2 bg-white border border-gray-200 rounded-xl shadow px-4 py-3 sm:px-5 mb-8">
            <p class="text-sm font-medium text-gray-700">No app</p>
            <p class="text-sm text-gray-600">
                <strong class="text-gray-900">{{ $app_totals['participants'] }}</strong> {{ $app_totals['participants'] === 1 ? 'pessoa' : 'pessoas' }},
                <strong class="text-gray-900">{{ $app_totals['with_push'] }}</strong> com avisos ligados
            </p>
        </div>
```

(A faixa não é link: a lista de inscrições aplica o filtro de período e o de encontros ativos, e mostraria menos gente que o total.)

- [ ] **Step 4: rodar e ver passar**

Run: `docker-compose exec -T app php artisan test --filter=AppPresenceAdminTest`
Expected: 4 passed.

- [ ] **Step 5: commit**

```bash
git add app/Http/Controllers/Admin resources/views/admin tests/Feature/Admin/AppPresenceAdminTest.php
git commit -m "feat(painel): quadro do app na inscrição e total no dashboard"
```

### Task 4: página de status

**Files:**
- Modify: `resources/views/inscriptions/status.blade.php` (depois do quadro de status, linha ~478; frase do suporte, linha ~534)
- Test: `tests/Feature/StatusPageAppLinksTest.php`
- Build: `public/build/` (versionado)

- [ ] **Step 1: teste que falha**

```php
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

        $this->statusPage()->assertOk()->assertDontSee('Acompanhe tudo pelo app');
    }
}
```

- [ ] **Step 2: rodar e ver falhar**

Run: `docker-compose exec -T app php artisan test --filter=StatusPageAppLinksTest`
Expected: FAIL.

- [ ] **Step 3: implementar**

Depois do `</div>` que fecha o quadro de status e antes de `{{-- Botão de Cancelamento --}}`:

```blade
        {{-- Acompanhe pelo app (spec 2026-11-11-app-no-painel-design, AP1) --}}
        @php
            $appStoreUrl = config('services.app_stores.app_store');
            $googlePlayUrl = config('services.app_stores.google_play');
        @endphp
        @if($appStoreUrl || $googlePlayUrl)
        <div class="rounded-2xl shadow-lg p-6 mb-6 text-center" style="background-color:#1a2e6e;">
            <h3 class="text-lg font-bold mb-2" style="color:#e88a2d;">Acompanhe tudo pelo app</h3>
            <p class="text-sm mb-4" style="color:#dfe4f2;">
                Baixe o app De Casa em Casa e entre com o e-mail que você usou na inscrição: você acompanha suas inscrições, vê cada etapa e recebe os avisos da equipe na hora.
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                @if($appStoreUrl)
                <a href="{{ $appStoreUrl }}" target="_blank" rel="noopener" class="px-5 py-3 bg-white font-semibold rounded-xl hover:bg-gray-100 transition-colors text-sm" style="color:#1a2e6e;">Baixar na App Store</a>
                @endif
                @if($googlePlayUrl)
                <a href="{{ $googlePlayUrl }}" target="_blank" rel="noopener" class="px-5 py-3 bg-white font-semibold rounded-xl hover:bg-gray-100 transition-colors text-sm" style="color:#1a2e6e;">Baixar no Google Play</a>
                @endif
            </div>
        </div>
        @endif
```

Na frase do suporte: `chegam por e-mail e nesta página.` vira `chegam por e-mail, no app e nesta página.`

O e-mail não aparece na página: ela abre por link e hoje não mostra o e-mail; o texto pede o "e-mail que você usou na inscrição".

- [ ] **Step 4: rodar e ver passar; recompilar o CSS**

Run: `docker-compose exec -T app php artisan test --filter=StatusPageAppLinksTest` (3 passed)
Run: `docker-compose exec -T app npm run build` e conferir `git status public/build`.

- [ ] **Step 5: commit**

```bash
git add resources/views/inscriptions/status.blade.php tests/Feature/StatusPageAppLinksTest.php public/build
git commit -m "feat(site): convite para o app na página de status da inscrição"
```

### Task 5: fechar

- [ ] Suíte inteira: `docker-compose exec -T app php artisan test` (tudo passando).
- [ ] Conferir as telas com dados fictícios (lista, inscrição, dashboard, status) por captura.
- [ ] `git push` e atualizar a descrição do PR #10 (o que muda no painel e na página de status; deploy: `git pull`, `config:cache && route:cache`).
