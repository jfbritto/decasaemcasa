<?php

namespace Tests\Feature\Api;

use App\Mail\ParticipantLoginCodeMail;
use App\Models\Participant;
use App\Models\ParticipantLoginCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\MakesInscriptions;
use Tests\TestCase;

/**
 * Login do app por código no e-mail (spec 2026-11-08-app-mobile-design, AM3 a
 * AM8).
 */
class AuthApiTest extends TestCase
{
    use MakesInscriptions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->acceptAnyMailDomain();
        Mail::fake();
    }

    /**
     * Pede o código e devolve o que foi enviado por e-mail.
     */
    private function requestCode(string $email = 'maria@example.com'): string
    {
        $this->postJson('/api/v1/auth/code', ['email' => $email])->assertStatus(202);

        $code = null;
        Mail::assertSent(ParticipantLoginCodeMail::class, function (ParticipantLoginCodeMail $mail) use ($email, &$code) {
            $code = $mail->code;

            return $mail->hasTo(mb_strtolower(trim($email)));
        });

        return $code;
    }

    public function test_pedir_codigo_envia_seis_digitos_e_grava_so_o_hash(): void
    {
        $code = $this->requestCode();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $login = ParticipantLoginCode::firstOrFail();
        $this->assertSame('maria@example.com', $login->email);
        $this->assertNotSame($code, $login->code_hash);
        $this->assertStringNotContainsString($code, $login->code_hash);
    }

    public function test_resposta_e_a_mesma_com_ou_sem_inscricao(): void
    {
        $this->makeInscription($this->makeEvent(), ['email' => 'inscrita@example.com']);

        $withInscription = $this->postJson('/api/v1/auth/code', ['email' => 'inscrita@example.com']);
        $without = $this->postJson('/api/v1/auth/code', ['email' => 'ninguem@example.com']);

        $withInscription->assertStatus(202);
        $without->assertStatus(202);
        $this->assertSame($withInscription->json(), $without->json());
    }

    public function test_email_com_erro_de_digitacao_e_recusado_com_sugestao(): void
    {
        $this->postJson('/api/v1/auth/code', ['email' => 'maria@gmail.con'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Verifique seu e-mail. Você quis dizer maria@gmail.com?');

        Mail::assertNothingSent();
    }

    public function test_codigo_certo_cria_participante_e_devolve_token(): void
    {
        $this->makeInscription($this->makeEvent(), ['email' => 'Maria@Example.com', 'full_name' => 'Maria Velunora']);
        $code = $this->requestCode('MARIA@example.com ');

        $response = $this->postJson('/api/v1/auth/verify', [
            'email' => 'maria@example.com',
            'code' => $code,
            'device_name' => 'iPhone da Maria',
        ]);

        $response->assertOk()
            ->assertJsonPath('participant.email', 'maria@example.com')
            ->assertJsonPath('participant.name', 'Maria Velunora');
        $this->assertNotEmpty($response->json('token'));

        $participant = Participant::firstOrFail();
        $this->assertNotNull($participant->last_login_at);
        $this->assertSame('iPhone da Maria', $participant->tokens()->first()->name);

        $this->withToken($response->json('token'))->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('email', 'maria@example.com');
    }

    public function test_codigo_nao_vale_duas_vezes(): void
    {
        $code = $this->requestCode();

        $this->postJson('/api/v1/auth/verify', ['email' => 'maria@example.com', 'code' => $code])->assertOk();
        $this->postJson('/api/v1/auth/verify', ['email' => 'maria@example.com', 'code' => $code])
            ->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'Código inválido ou expirado. Confira os números ou peça um novo código.');
    }

    public function test_codigo_morre_na_quinta_tentativa(): void
    {
        $code = $this->requestCode();
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/verify', ['email' => 'maria@example.com', 'code' => $wrong])->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/verify', ['email' => 'maria@example.com', 'code' => $code])->assertStatus(422);
        $this->assertSame(0, Participant::count());
    }

    public function test_a_tentativa_certa_tambem_conta(): void
    {
        $code = $this->requestCode();
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/v1/auth/verify', ['email' => 'maria@example.com', 'code' => $wrong])->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/verify', ['email' => 'maria@example.com', 'code' => $code])->assertOk();
    }

    public function test_codigo_expirado_e_recusado(): void
    {
        $code = $this->requestCode();

        $this->travel(11)->minutes();

        $this->postJson('/api/v1/auth/verify', ['email' => 'maria@example.com', 'code' => $code])->assertStatus(422);
    }

    public function test_pedir_de_novo_invalida_o_codigo_anterior(): void
    {
        $first = $this->requestCode();
        $this->travel(2)->minutes();
        $this->postJson('/api/v1/auth/code', ['email' => 'maria@example.com'])->assertStatus(202);

        $this->assertSame(1, ParticipantLoginCode::count());
        $second = null;
        Mail::assertSent(ParticipantLoginCodeMail::class, function (ParticipantLoginCodeMail $mail) use (&$second) {
            $second = $mail->code;

            return true;
        });

        if ($first !== $second) {
            $this->postJson('/api/v1/auth/verify', ['email' => 'maria@example.com', 'code' => $first])->assertStatus(422);
        }
        $this->postJson('/api/v1/auth/verify', ['email' => 'maria@example.com', 'code' => $second])->assertOk();
    }

    public function test_codigo_de_outro_email_nao_vale(): void
    {
        $code = $this->requestCode('maria@example.com');

        $this->postJson('/api/v1/auth/verify', ['email' => 'joana@example.com', 'code' => $code])->assertStatus(422);
    }

    public function test_conta_de_revisao_usa_codigo_fixo_sem_enviar_email(): void
    {
        config([
            'participant_app.review_email' => 'revisao@example.com',
            'participant_app.review_code' => '246813579024',
        ]);

        $this->postJson('/api/v1/auth/code', ['email' => 'Revisao@example.com'])->assertStatus(202);
        Mail::assertNothingSent();
        $this->assertSame(0, ParticipantLoginCode::count());

        $this->postJson('/api/v1/auth/verify', ['email' => 'revisao@example.com', 'code' => '000000'])->assertStatus(422);
        $this->postJson('/api/v1/auth/verify', ['email' => 'revisao@example.com', 'code' => '246813579024'])->assertOk();
    }

    public function test_sem_as_duas_variaveis_nao_existe_conta_de_revisao(): void
    {
        config(['participant_app.review_email' => 'revisao@example.com', 'participant_app.review_code' => '']);

        $this->postJson('/api/v1/auth/verify', ['email' => 'revisao@example.com', 'code' => ''])->assertStatus(422);
        $this->requestCode('revisao@example.com');
    }

    public function test_codigo_de_revisao_curto_deixa_a_conta_desligada(): void
    {
        // O código fixo não tem contador nem validade: curto, seria adivinhável.
        config(['participant_app.review_email' => 'revisao@example.com', 'participant_app.review_code' => '246810']);

        $this->postJson('/api/v1/auth/verify', ['email' => 'revisao@example.com', 'code' => '246810'])->assertStatus(422);
        $this->requestCode('revisao@example.com');
    }

    public function test_conferir_tem_limite_por_email_mesmo_de_ips_diferentes(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$i}"])
                ->postJson('/api/v1/auth/verify', ['email' => 'maria@example.com', 'code' => '000000'])
                ->assertStatus(422);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.1'])
            ->postJson('/api/v1/auth/verify', ['email' => 'maria@example.com', 'code' => '000000'])
            ->assertStatus(429);
    }

    public function test_email_com_acento_e_recusado(): void
    {
        foreach (['maria@gmáil.com', 'marïa@gmail.com', 'maria@gmаil.com'] as $email) {
            $this->postJson('/api/v1/auth/code', ['email' => $email])
                ->assertStatus(422)
                ->assertJsonPath('errors.email.0', 'Digite o e-mail sem acentos nem espaços.');
        }

        Mail::assertNothingSent();
        $this->assertSame(0, ParticipantLoginCode::count());
    }

    public function test_dominio_parecido_com_acento_nunca_entra_na_conta_de_outra_pessoa(): void
    {
        // A vítima já tem conta e inscrição; a collation unicode_ci do MySQL
        // trataria "gmáil" como "gmail".
        $victim = $this->makeParticipant('maria@gmail.com');
        $this->makeInscription($this->makeEvent(), ['email' => 'maria@gmail.com']);
        $victim->createToken('app');

        // Mesmo com um código gravado para a variante (como se a validação
        // falhasse), o verify recusa e nenhuma conta é devolvida.
        ParticipantLoginCode::create([
            'email' => 'maria@gmáil.com',
            'code_hash' => hash_hmac('sha256', '123456', (string) config('app.key')),
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->postJson('/api/v1/auth/verify', ['email' => 'maria@gmáil.com', 'code' => '123456'])->assertStatus(422);
        $this->assertFalse(app(\App\Participants\LoginCodes::class)->verify('maria@gmáil.com', '123456'));
        $this->assertSame(1, $victim->tokens()->count());
    }

    public function test_email_em_formato_de_lista_nao_derruba_o_servidor(): void
    {
        $this->postJson('/api/v1/auth/code', ['email' => ['a@example.com']])->assertStatus(422);
        $this->postJson('/api/v1/auth/verify', ['email' => ['a@example.com'], 'code' => '123456'])->assertStatus(422);
    }

    public function test_pedir_codigo_tem_limite_por_email(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/code', ['email' => 'maria@example.com'])->assertStatus(202);
        }

        $this->postJson('/api/v1/auth/code', ['email' => 'maria@example.com'])->assertStatus(429);
    }

    public function test_sair_revoga_o_token_e_apaga_o_push_do_aparelho(): void
    {
        $participant = $this->makeParticipant();
        $token = $participant->createToken('app')->plainTextToken;
        $participant->pushTokens()->create(['token' => 'ExponentPushToken[aaa]', 'platform' => 'ios']);
        $participant->pushTokens()->create(['token' => 'ExponentPushToken[bbb]', 'platform' => 'android']);

        $this->withToken($token)->postJson('/api/v1/auth/logout', ['push_token' => 'ExponentPushToken[aaa]'])->assertNoContent();

        $this->assertSame(0, $participant->tokens()->count());
        $this->assertSame(['ExponentPushToken[bbb]'], $participant->pushTokens()->pluck('token')->all());
    }

    public function test_rotas_do_participante_pedem_token(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401);
        $this->getJson('/api/v1/inscriptions')->assertStatus(401);
        // Sem Accept: application/json a API também responde JSON (AM9).
        $this->get('/api/v1/inscriptions')->assertStatus(401)->assertJsonStructure(['message']);
    }

    public function test_sessao_da_equipe_nao_entra_na_api_do_app(): void
    {
        $admin = \App\Models\User::create([
            'name' => 'Equipe Velunora',
            'email' => 'equipe@example.com',
            'password' => bcrypt('segredo-de-teste'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/inscriptions')
            ->assertStatus(403)
            ->assertJsonPath('code', 'not_participant');
    }
}
