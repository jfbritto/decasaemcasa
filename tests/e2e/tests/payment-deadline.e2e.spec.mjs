import { test, expect } from '@playwright/test';
import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const REPO = path.resolve(__dirname, '..', '..', '..');
const APP_URL = 'http://localhost:8585';
const MAILHOG_URL = 'http://localhost:8028';
const PDF_FIXTURE = path.join(__dirname, '..', 'fixtures', 'comprovante.pdf');
const TMP_DIR = path.join(__dirname, '..', 'tinker-tmp');
const SCREENSHOTS = path.join(__dirname, '..', 'screenshots');

fs.mkdirSync(TMP_DIR, { recursive: true });
fs.mkdirSync(SCREENSHOTS, { recursive: true });

// ---------------------------------------------------------------------------
// Helpers: tinker / artisan via docker-compose
// ---------------------------------------------------------------------------

function tinkerRaw(phpCode) {
  const file = path.join(TMP_DIR, `t-${Date.now()}-${Math.random().toString(36).slice(2)}.php`);
  fs.writeFileSync(file, phpCode);
  try {
    const out = execSync(
      `docker-compose exec -T app php artisan tinker --execute="$(cat '${file}')"`,
      { cwd: REPO, encoding: 'utf8', maxBuffer: 20 * 1024 * 1024 }
    );
    return out;
  } finally {
    fs.unlinkSync(file);
  }
}

function tinkerJson(phpCode) {
  const out = tinkerRaw(phpCode);
  const m = out.match(/___JSON___(.*?)___END___/s);
  if (!m) {
    throw new Error('Marcador JSON não encontrado na saída do tinker:\n' + out);
  }
  return JSON.parse(m[1]);
}

function artisan(command) {
  return execSync(`docker-compose exec -T app php artisan ${command}`, {
    cwd: REPO,
    encoding: 'utf8',
    maxBuffer: 20 * 1024 * 1024,
  });
}

// ---------------------------------------------------------------------------
// Helpers: MailHog
// ---------------------------------------------------------------------------

async function mailhogSearchTo(email) {
  const res = await fetch(`${MAILHOG_URL}/api/v2/search?kind=to&query=${encodeURIComponent(email)}`);
  const data = await res.json();
  return data.items || [];
}

function decodeQuotedPrintable(str) {
  str = str.replace(/=\r\n/g, '').replace(/=\n/g, '');
  const bytes = [];
  for (let i = 0; i < str.length; i++) {
    if (str[i] === '=' && /^[0-9A-Fa-f]{2}$/.test(str.substr(i + 1, 2))) {
      bytes.push(parseInt(str.substr(i + 1, 2), 16));
      i += 2;
    } else {
      bytes.push(str.charCodeAt(i));
    }
  }
  return Buffer.from(bytes).toString('utf8');
}

function mailBodyText(message) {
  const headers = message.Content.Headers || {};
  const cteHeader = headers['Content-Transfer-Encoding'] || [''];
  const cte = cteHeader[0].toLowerCase();
  let body = message.Content.Body || '';
  if (cte.includes('base64')) {
    body = Buffer.from(body.replace(/\r?\n/g, ''), 'base64').toString('utf8');
  } else if (cte.includes('quoted-printable')) {
    body = decodeQuotedPrintable(body);
  }
  return body;
}

function decodeMimeHeader(str) {
  return str.replace(/=\?([^?]+)\?([bBqQ])\?([^?]*)\?=/g, (_, charset, enc, text) => {
    if (enc.toLowerCase() === 'b') {
      return Buffer.from(text, 'base64').toString('utf8');
    }
    return decodeQuotedPrintable(text.replace(/_/g, ' '));
  });
}

function mailSubject(message) {
  const headers = message.Content.Headers || {};
  return decodeMimeHeader((headers['Subject'] || [''])[0]);
}

async function waitForMailTo(email, { timeout = 20000, subjectContains } = {}) {
  const start = Date.now();
  while (Date.now() - start < timeout) {
    const items = await mailhogSearchTo(email);
    const match = subjectContains
      ? items.find((i) => mailSubject(i).includes(subjectContains))
      : items[0];
    if (match) return match;
    await new Promise((r) => setTimeout(r, 500));
  }
  throw new Error(`Nenhum e-mail encontrado para ${email}${subjectContains ? ` com assunto contendo "${subjectContains}"` : ''}`);
}

// ---------------------------------------------------------------------------
// Dados de teste: eventos criados e removidos pela própria suíte
// ---------------------------------------------------------------------------

function cleanupTestData() {
  tinkerRaw(`
    \$eventIds = App\\Models\\Event::withTrashed()->where('slug', 'like', 'e2e-prazo-%')->pluck('id');
    \$inscIds = App\\Models\\Inscription::whereIn('event_id', \$eventIds)->pluck('id');
    \$proofs = App\\Models\\Inscription::whereIn('event_id', \$eventIds)->whereNotNull('payment_proof')->pluck('payment_proof');
    foreach (\$proofs as \$p) { Illuminate\\Support\\Facades\\Storage::disk('public')->delete(\$p); }
    if (\$inscIds->isNotEmpty()) {
        App\\Models\\Notification::whereIn('metadata->inscription_id', \$inscIds)->delete();
        App\\Models\\ActivityLog::where('subject_type', App\\Models\\Inscription::class)->whereIn('subject_id', \$inscIds)->delete();
    }
    App\\Models\\Inscription::whereIn('event_id', \$eventIds)->delete();
    App\\Models\\Event::withTrashed()->whereIn('id', \$eventIds)->forceDelete();
    echo 'CLEANED';
  `);
}

test.describe.serial('Prazo de pagamento pós-aprovação', () => {
  let eventNearId; // 1 dia até o evento: faixa base de 1h
  let eventMidId; // 60h: faixa de 6h
  let eventFarId; // 2 meses: faixa de 24h

  /** @type {{id:number, token:string, email:string}} */
  let insc1; // inscrição feliz: form público + aprovação + upload
  /** @type {{id:number, token:string, email:string}} */
  let insc2; // inscrição expiração
  /** @type {{id:number, token:string, email:string}} */
  let insc3; // inscrição auto-reload JS
  /** @type {{id:number, token:string, email:string}} */
  let insc4; // inscrição prazo pausado

  test.beforeAll(() => {
    // A suíte apaga dados (eventos e2e-prazo-%, inscrições, e-mails do MailHog):
    // só roda contra ambiente local
    const envOut = tinkerRaw(`echo 'ENV='.app()->environment().'=FIM';`);
    const env = (envOut.match(/ENV=(.+?)=FIM/) || [])[1];
    if (env !== 'local') {
      throw new Error(`Recusando rodar o E2E: APP_ENV é "${env}", esperado "local".`);
    }

    cleanupTestData();

    const events = tinkerJson(`
      \$near = App\\Models\\Event::create(['title' => 'E2E Teste', 'slug' => 'e2e-prazo-near-'.time(), 'description' => 'Evento dedicado para teste E2E de prazo de pagamento', 'city' => 'E2E Teste', 'date' => now()->addDay(), 'status' => 'published', 'capacity' => 10, 'confirmed_count' => 0]);
      \$mid = App\\Models\\Event::create(['title' => 'E2E Teste Meio', 'slug' => 'e2e-prazo-mid-'.time(), 'description' => 'Evento na faixa intermediaria para teste do prazo de 6h', 'city' => 'E2E Meio', 'date' => now()->addHours(60), 'status' => 'published', 'capacity' => 10, 'confirmed_count' => 0]);
      \$far = App\\Models\\Event::create(['title' => 'E2E Teste Distante', 'slug' => 'e2e-prazo-far-'.time(), 'description' => 'Evento distante para teste do prazo de 24h', 'city' => 'E2E Distante', 'date' => now()->addMonths(2), 'status' => 'published', 'capacity' => 10, 'confirmed_count' => 0]);
      echo '___JSON___'.json_encode(['near' => \$near->id, 'mid' => \$mid->id, 'far' => \$far->id]).'___END___';
    `);
    eventNearId = events.near;
    eventMidId = events.mid;
    eventFarId = events.far;
  });

  test.afterAll(async () => {
    cleanupTestData();
    await fetch(`${MAILHOG_URL}/api/v1/messages`, { method: 'DELETE' }).catch(() => {});
  });

  test('1. Inscrição pública em /inscricao com dados válidos', async ({ page }) => {
    const email = `e2e.decasaemcasa+teste${Date.now()}@gmail.com`;

    await page.goto(`${APP_URL}/inscricao`);

    // Etapa 1: seleção do evento E2E próximo
    await page.locator(`input[type="radio"][name="event_id"][value="${eventNearId}"]`).check();
    await page.locator('div[x-show="step === 1"] button:has-text("Continuar")').click();

    // Etapa 2: manifesto
    await page.locator('div[x-show="step === 2"] button:has-text("Continuar")').click();

    // Etapa 3: dados do participante
    await page.fill('#full_name', 'Fulano E2E da Silva');
    await page.fill('#cpf', '52998224725');
    await page.fill('#birth_date', '1990-05-20');
    await page.fill('#city_neighborhood', 'Bairro Teste, Cidade Teste');
    await page.fill('#whatsapp', '31999998888');
    await page.fill('#email', email);
    await page.fill('#motivation', 'Quero muito participar deste encontro especial de teste E2E.');
    await page.locator('div[x-show="step === 3"] button:has-text("Continuar")').click();

    // Etapa 4: termos
    await page.locator('input[name="terms_accepted"]').check();
    await page.screenshot({ path: path.join(SCREENSHOTS, '01-form.png'), fullPage: true });
    await page.locator('div[x-show="step === 4"] button:has-text("Enviar Inscrição")').click();

    // Redireciona para página de status com token na URL
    await page.waitForURL(/\/inscricao\/[A-Za-z0-9]+$/, { timeout: 15000 });
    const url = page.url();
    const token = url.split('/inscricao/')[1];
    expect(token).toBeTruthy();

    const dbRow = tinkerJson(`
      \$i = App\\Models\\Inscription::where('token', '${token}')->first();
      echo '___JSON___'.json_encode(['id' => \$i->id, 'status' => \$i->status]).'___END___';
    `);
    expect(dbRow.status).toBe('pendente');
    insc1 = { id: dbRow.id, token, email };

    const mail = await waitForMailTo(email, { subjectContains: 'Inscrição recebida' });
    expect(mailSubject(mail)).toContain('Inscrição recebida');
  });

  test('2. Aprovação de evento próximo dispara e-mail com prazo de 1h', async () => {
    expect(insc1).toBeTruthy();

    const result = tinkerJson(`
      \$i = App\\Models\\Inscription::find(${insc1.id});
      \$i->approve();
      app(App\\Services\\NotificationService::class)->notifyInscriptionApproved(\$i);
      echo '___JSON___'.json_encode([
        'payment_deadline_at' => \$i->payment_deadline_at->toDateTimeString(),
        'minutes' => \$i->payment_deadline_minutes,
        'now' => now()->toDateTimeString(),
      ]).'___END___';
    `);

    expect(result.minutes).toBe(60);
    const deadline = new Date(result.payment_deadline_at.replace(' ', 'T'));
    const now = new Date(result.now.replace(' ', 'T'));
    const diffMinutes = (deadline - now) / 60000;
    expect(diffMinutes).toBeGreaterThan(58);
    expect(diffMinutes).toBeLessThan(62);

    const approvedMail = await waitForMailTo(insc1.email, { subjectContains: 'garantir seu lugar' });
    const body = mailBodyText(approvedMail);
    expect(body).toContain('AÇÃO NECESSÁRIA');
    expect(body).toContain('até às');
    expect(body).toContain('Já fiz o Pix: enviar comprovante');
  });

  test('3. Página de status mostra contador ticando', async ({ page }) => {
    await page.goto(`${APP_URL}/inscricao/${insc1.token}`);

    await expect(page.getByText('Prazo para envio do comprovante: até às', { exact: false })).toBeVisible();

    const countdown = page.locator('p:has-text("Tempo restante")');
    const first = (await countdown.textContent())?.trim();
    await page.waitForTimeout(2500);
    const second = (await countdown.textContent())?.trim();

    await page.screenshot({ path: path.join(SCREENSHOTS, '02-countdown.png'), fullPage: true });

    expect(first).toBeTruthy();
    expect(second).toBeTruthy();
    expect(second).not.toBe(first);
  });

  test('4. Upload feliz de comprovante PDF', async ({ page }) => {
    await page.goto(`${APP_URL}/inscricao/${insc1.token}`);

    await page.setInputFiles('#payment_proof', PDF_FIXTURE);
    await page.locator('button:visible:has-text("Enviar Comprovante")').click();

    await page.waitForURL(/comprovante-enviado$/, { timeout: 15000 });
    await expect(page.getByText('Deu tudo certo!')).toBeVisible();
    await page.screenshot({ path: path.join(SCREENSHOTS, '02b-upload-sucesso.png'), fullPage: true });

    await page.goto(`${APP_URL}/inscricao/${insc1.token}`);
    await expect(page.getByText('Comprovante já enviado')).toBeVisible();
  });

  test('5. Expiração de segunda inscrição por prazo vencido', async () => {
    const created = tinkerJson(`
      \$i = App\\Models\\Inscription::create([
        'event_id' => ${eventNearId},
        'full_name' => 'Ciclana E2E Expirada',
        'cpf' => '11111111111',
        'birth_date' => '1992-03-10',
        'city_neighborhood' => 'Bairro Teste 2',
        'whatsapp' => '31999997777',
        'email' => 'e2e.decasaemcasa+expira${Date.now()}@gmail.com',
        'motivation' => 'Motivo de teste com mais de dez caracteres para expiracao.',
        'terms_accepted' => true,
        'status' => 'pendente',
      ]);
      echo '___JSON___'.json_encode(['id' => \$i->id, 'token' => \$i->token, 'email' => \$i->email]).'___END___';
    `);
    insc2 = created;

    tinkerJson(`
      \$i = App\\Models\\Inscription::find(${insc2.id});
      \$i->approve();
      app(App\\Services\\NotificationService::class)->notifyInscriptionApproved(\$i);
      echo '___JSON___'.json_encode(['ok' => true]).'___END___';
    `);

    tinkerJson(`
      \$i = App\\Models\\Inscription::find(${insc2.id});
      \$i->payment_deadline_at = now()->subMinutes(5);
      \$i->save();
      echo '___JSON___'.json_encode(['payment_deadline_at' => \$i->payment_deadline_at->toDateTimeString()]).'___END___';
    `);

    const out = artisan('inscriptions:expire-unpaid');
    expect(out).toContain(`Expirada: #${insc2.id}`);

    const dbState = tinkerJson(`
      \$i = App\\Models\\Inscription::find(${insc2.id});
      echo '___JSON___'.json_encode([
        'status' => \$i->status,
        'payment_expired_at' => optional(\$i->payment_expired_at)->toDateTimeString(),
      ]).'___END___';
    `);
    expect(dbState.status).toBe('fila_de_espera');
    expect(dbState.payment_expired_at).toBeTruthy();

    const mail = await waitForMailTo(insc2.email, { subjectContains: 'fila de espera' });
    const body = mailBodyText(mail);
    expect(body).toContain('não foi cancelada');
  });

  test('6. Página de status da inscrição expirada', async ({ page }) => {
    await page.goto(`${APP_URL}/inscricao/${insc2.token}`);
    await expect(page.locator('span.bg-orange-100.text-orange-800:has-text("Fila de Espera")')).toBeVisible();
    await expect(page.getByText('Não recebemos seu comprovante dentro do prazo', { exact: false })).toBeVisible();
    await expect(page.locator('#payment_proof')).toHaveCount(0);
    await page.screenshot({ path: path.join(SCREENSHOTS, '03-expirado.png'), fullPage: true });
  });

  test('7. Auto-reload da página após o prazo terminar (JS)', async ({ page }) => {
    test.setTimeout(180_000);

    const created = tinkerJson(`
      \$i = App\\Models\\Inscription::create([
        'event_id' => ${eventNearId},
        'full_name' => 'Beltrano E2E Autoreload',
        'cpf' => '22222222222',
        'birth_date' => '1988-07-01',
        'city_neighborhood' => 'Bairro Teste 3',
        'whatsapp' => '31999996666',
        'email' => 'e2e.decasaemcasa+autoreload${Date.now()}@gmail.com',
        'motivation' => 'Motivo de teste com mais de dez caracteres para autoreload.',
        'terms_accepted' => true,
        'status' => 'pendente',
      ]);
      echo '___JSON___'.json_encode(['id' => \$i->id, 'token' => \$i->token, 'email' => \$i->email]).'___END___';
    `);
    insc3 = created;

    tinkerJson(`
      \$i = App\\Models\\Inscription::find(${insc3.id});
      \$i->approve();
      app(App\\Services\\NotificationService::class)->notifyInscriptionApproved(\$i);
      \$i->payment_deadline_at = now()->addSeconds(8);
      \$i->save();
      echo '___JSON___'.json_encode(['payment_deadline_at' => \$i->payment_deadline_at->toDateTimeString()]).'___END___';
    `);

    await page.goto(`${APP_URL}/inscricao/${insc3.token}`);
    await expect(page.getByText('O prazo terminou', { exact: false })).toBeVisible({ timeout: 15000 });

    // Backdateia e roda o comando de expiração enquanto a página já mostra "prazo terminou"
    tinkerJson(`
      \$i = App\\Models\\Inscription::find(${insc3.id});
      \$i->payment_deadline_at = now()->subMinutes(1);
      \$i->save();
      echo '___JSON___'.json_encode(['ok' => true]).'___END___';
    `);
    const out = artisan('inscriptions:expire-unpaid');
    expect(out).toContain(`Expirada: #${insc3.id}`);

    // Aguarda o reload automático agendado pelo JS (75s após zerar o contador)
    await page.waitForEvent('load', { timeout: 110_000 });

    await expect(page.locator('span.bg-orange-100.text-orange-800:has-text("Fila de Espera")')).toBeVisible();
  });

  test('8. Prazo pausado por solicitação de contribuição social pendente', async ({ page }) => {
    const created = tinkerJson(`
      \$i = App\\Models\\Inscription::create([
        'event_id' => ${eventNearId},
        'full_name' => 'Sicrana E2E Pausada',
        'cpf' => '33333333333',
        'birth_date' => '1995-11-11',
        'city_neighborhood' => 'Bairro Teste 4',
        'whatsapp' => '31999995555',
        'email' => 'e2e.decasaemcasa+pausada${Date.now()}@gmail.com',
        'motivation' => 'Motivo de teste com mais de dez caracteres para pausa.',
        'terms_accepted' => true,
        'status' => 'pendente',
      ]);
      echo '___JSON___'.json_encode(['id' => \$i->id, 'token' => \$i->token, 'email' => \$i->email]).'___END___';
    `);
    insc4 = created;

    tinkerJson(`
      \$i = App\\Models\\Inscription::find(${insc4.id});
      \$i->approve();
      app(App\\Services\\NotificationService::class)->notifyInscriptionApproved(\$i);
      \$i->social_request_status = 'pendente';
      \$i->social_request_reason = 'Teste E2E';
      \$i->social_request_amount = 30;
      \$i->save();
      echo '___JSON___'.json_encode(['ok' => true]).'___END___';
    `);

    await page.goto(`${APP_URL}/inscricao/${insc4.token}`);
    await expect(page.getByText('Prazo pausado', { exact: false })).toBeVisible();
    await expect(page.getByText('Tempo restante')).toHaveCount(0);
    await page.screenshot({ path: path.join(SCREENSHOTS, '04-pausado.png'), fullPage: true });

    // Backdateia o prazo com solicitação social pendente: NÃO deve expirar
    tinkerJson(`
      \$i = App\\Models\\Inscription::find(${insc4.id});
      \$i->payment_deadline_at = now()->subMinutes(5);
      \$i->save();
      echo '___JSON___'.json_encode(['ok' => true]).'___END___';
    `);
    const out = artisan('inscriptions:expire-unpaid');
    expect(out).not.toContain(`Expirada: #${insc4.id}`);

    const dbState = tinkerJson(`
      \$i = App\\Models\\Inscription::find(${insc4.id});
      echo '___JSON___'.json_encode(['status' => \$i->status]).'___END___';
    `);
    expect(dbState.status).toBe('aprovado');
  });

  test('9. Evento a mais de 72h usa prazo de 24 horas', async ({ page }) => {
    const created = tinkerJson(`
      \$i = App\\Models\\Inscription::create([
        'event_id' => ${eventFarId},
        'full_name' => 'Distante E2E Estendida',
        'cpf' => '44444444444',
        'birth_date' => '1991-02-02',
        'city_neighborhood' => 'Bairro Teste 5',
        'whatsapp' => '31999994444',
        'email' => 'e2e.decasaemcasa+distante${Date.now()}@gmail.com',
        'motivation' => 'Motivo de teste com mais de dez caracteres para prazo estendido.',
        'terms_accepted' => true,
        'status' => 'pendente',
      ]);
      echo '___JSON___'.json_encode(['id' => \$i->id, 'token' => \$i->token, 'email' => \$i->email]).'___END___';
    `);

    const result = tinkerJson(`
      \$i = App\\Models\\Inscription::find(${created.id});
      \$i->approve();
      app(App\\Services\\NotificationService::class)->notifyInscriptionApproved(\$i);
      echo '___JSON___'.json_encode([
        'minutes' => \$i->payment_deadline_minutes,
        'deadline' => \$i->payment_deadline_at->toDateTimeString(),
        'now' => now()->toDateTimeString(),
      ]).'___END___';
    `);

    expect(result.minutes).toBe(1440);
    const diffMinutes =
      (new Date(result.deadline.replace(' ', 'T')) - new Date(result.now.replace(' ', 'T'))) / 60000;
    expect(diffMinutes).toBeGreaterThan(1438);
    expect(diffMinutes).toBeLessThan(1442);

    const mail = await waitForMailTo(created.email, { subjectContains: 'garantir seu lugar' });
    expect(mailSubject(mail)).toContain('24 horas');
    const body = mailBodyText(mail);
    expect(body).toContain('24 HORAS');
    expect(body).toContain('por 24 horas');

    await page.goto(`${APP_URL}/inscricao/${created.token}`);
    await expect(page.getByText('Prazo para envio do comprovante: até às', { exact: false })).toBeVisible();
    const countdown = page.locator('p:has-text("Tempo restante")');
    await expect(countdown).toContainText(/\d{1,2}h \d{1,2}min/);
    await page.screenshot({ path: path.join(SCREENSHOTS, '05-prazo-24h.png'), fullPage: true });
  });

  test('10. Evento entre 48h e 72h usa prazo de 6 horas', async () => {
    const created = tinkerJson(`
      \$i = App\\Models\\Inscription::create([
        'event_id' => ${eventMidId},
        'full_name' => 'Meio E2E Intermediaria',
        'cpf' => '55555555555',
        'birth_date' => '1993-09-09',
        'city_neighborhood' => 'Bairro Teste 6',
        'whatsapp' => '31999993333',
        'email' => 'e2e.decasaemcasa+meio${Date.now()}@gmail.com',
        'motivation' => 'Motivo de teste com mais de dez caracteres para faixa intermediaria.',
        'terms_accepted' => true,
        'status' => 'pendente',
      ]);
      echo '___JSON___'.json_encode(['id' => \$i->id, 'token' => \$i->token, 'email' => \$i->email]).'___END___';
    `);

    const result = tinkerJson(`
      \$i = App\\Models\\Inscription::find(${created.id});
      \$i->approve();
      app(App\\Services\\NotificationService::class)->notifyInscriptionApproved(\$i);
      echo '___JSON___'.json_encode(['minutes' => \$i->payment_deadline_minutes]).'___END___';
    `);

    expect(result.minutes).toBe(360);

    const mail = await waitForMailTo(created.email, { subjectContains: 'garantir seu lugar' });
    expect(mailSubject(mail)).toContain('6 horas');
    expect(mailBodyText(mail)).toContain('6 HORAS');
  });
});
