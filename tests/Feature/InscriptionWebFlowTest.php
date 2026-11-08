<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Inscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesInscriptions;
use Tests\TestCase;

/**
 * O fluxo público da web (formulário, solicitação social, cancelamento),
 * preso antes de as regras saírem do InscriptionController para
 * app/Inscriptions (spec 2026-11-08-app-mobile-design, AM17): nenhum texto
 * nem comportamento da web muda.
 */
class InscriptionWebFlowTest extends TestCase
{
    use MakesInscriptions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->acceptAnyMailDomain();
    }

    private function validPayload(Event $event, array $overrides = []): array
    {
        return array_merge([
            'event_id' => $event->id,
            'full_name' => 'Joana Velunora',
            'cpf' => '529.982.247-25',
            'birth_date' => '1992-03-04',
            'city_neighborhood' => 'Jardim da Penha, Vitória',
            'whatsapp' => '(27) 98888-0000',
            'email' => 'joana@gmail.com',
            'instagram' => '@joana',
            'motivation' => 'Quero viver esse encontro com o coração aberto.',
            'terms_accepted' => '1',
        ], $overrides);
    }

    public function test_inscricao_valida_cria_pendente_e_leva_ao_status(): void
    {
        $event = $this->makeEvent();

        $response = $this->post(route('inscricao.store'), $this->validPayload($event));

        $inscription = Inscription::firstOrFail();
        $response->assertRedirect(route('inscricao.status', $inscription->token));
        $response->assertSessionHas('success', 'Inscrição realizada com sucesso!');
        $this->assertSame('pendente', $inscription->status);
        $this->assertSame('joana@gmail.com', $inscription->email);
        $this->assertTrue($inscription->terms_accepted);
    }

    public function test_inscricao_duplicada_por_cpf_leva_a_inscricao_existente(): void
    {
        $event = $this->makeEvent();
        $existing = $this->makeInscription($event, ['status' => 'pendente', 'email' => 'outra@gmail.com']);

        $response = $this->post(route('inscricao.store'), $this->validPayload($event));

        $response->assertRedirect(route('inscricao.status', $existing->token));
        $response->assertSessionHas('info', 'Você já possui uma inscrição para este encontro. Aqui está o status dela.');
        $this->assertSame(1, Inscription::count());
    }

    public function test_inscricao_duplicada_por_email_leva_a_inscricao_existente(): void
    {
        $event = $this->makeEvent();
        $existing = $this->makeInscription($event, ['status' => 'pendente', 'cpf' => '111.444.777-35', 'email' => 'joana@gmail.com']);

        $response = $this->post(route('inscricao.store'), $this->validPayload($event));

        $response->assertRedirect(route('inscricao.status', $existing->token));
        $this->assertSame(1, Inscription::count());
    }

    public function test_encontro_lotado_recusa_inscricao(): void
    {
        $event = $this->makeEvent(['capacity' => 1, 'confirmed_count' => 1]);

        $response = $this->from(route('inscricao.create'))->post(route('inscricao.store'), $this->validPayload($event));

        $response->assertRedirect(route('inscricao.create'));
        $response->assertSessionHas('error', 'As vagas para este encontro estão esgotadas.');
        $this->assertSame(0, Inscription::count());
    }

    public function test_encontro_nao_publicado_recusa_inscricao(): void
    {
        $event = $this->makeEvent(['status' => 'draft']);

        $response = $this->from(route('inscricao.create'))->post(route('inscricao.store'), $this->validPayload($event));

        $response->assertSessionHas('error', 'Este encontro não está disponível para inscrições.');
        $this->assertSame(0, Inscription::count());
    }

    public function test_cpf_invalido_e_erro_de_validacao(): void
    {
        $event = $this->makeEvent();

        $response = $this->from(route('inscricao.create'))->post(route('inscricao.store'), $this->validPayload($event, ['cpf' => '123.456.789-00']));

        $response->assertSessionHasErrors(['cpf' => 'O CPF informado é inválido.']);
        $this->assertSame(0, Inscription::count());
    }

    public function test_email_com_erro_de_digitacao_sugere_correcao(): void
    {
        $event = $this->makeEvent();

        $response = $this->from(route('inscricao.create'))->post(route('inscricao.store'), $this->validPayload($event, ['email' => 'joana@gmail.con']));

        $response->assertSessionHasErrors(['email' => 'Verifique seu e-mail. Você quis dizer joana@gmail.com?']);
    }

    public function test_solicitacao_social_de_aprovado_e_registrada(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), [
            'payment_deadline_at' => now()->addHours(5),
        ]);

        $response = $this->post(route('inscricao.solicitacao-social', $inscription->token), [
            'reason' => 'Estou desempregada neste mês e consigo contribuir com menos.',
            'amount' => '40',
        ]);

        $response->assertRedirect(route('inscricao.status', $inscription->token));
        $response->assertSessionHas('success', 'Solicitação enviada! A equipe vai analisar e logo retornaremos.');
        $inscription->refresh();
        $this->assertSame('pendente', $inscription->social_request_status);
        $this->assertSame('40.00', $inscription->social_request_amount);
    }

    public function test_solicitacao_social_repetida_e_recusada(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), [
            'payment_deadline_at' => now()->addHours(5),
            'social_request_status' => 'pendente',
            'social_request_reason' => 'Motivo anterior bem explicado aqui.',
            'social_request_amount' => 30,
        ]);

        $response = $this->post(route('inscricao.solicitacao-social', $inscription->token), [
            'reason' => 'Estou desempregada neste mês e consigo contribuir com menos.',
            'amount' => '40',
        ]);

        $response->assertSessionHas('error', 'Você já possui uma solicitação de contribuição social registrada.');
        $this->assertSame('30.00', $inscription->refresh()->social_request_amount);
    }

    public function test_solicitacao_social_de_quem_nao_esta_aprovado_e_recusada(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), ['status' => 'pendente']);

        $response = $this->post(route('inscricao.solicitacao-social', $inscription->token), [
            'reason' => 'Estou desempregada neste mês e consigo contribuir com menos.',
            'amount' => '40',
        ]);

        $response->assertSessionHas('error', 'Solicitação disponível apenas para inscrições aprovadas.');
        $this->assertNull($inscription->refresh()->social_request_status);
    }

    public function test_cancelamento_pelo_participante(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), ['status' => 'pendente']);

        $response = $this->post(route('inscricao.cancel', $inscription->token));

        $response->assertRedirect(route('inscricao.status', $inscription->token));
        $response->assertSessionHas('success', 'Sua inscrição foi cancelada com sucesso.');
        $inscription->refresh();
        $this->assertSame('cancelado', $inscription->status);
        $this->assertSame('participant', $inscription->cancelled_by);
    }

    public function test_cancelar_inscricao_ja_cancelada_e_recusado(): void
    {
        $inscription = $this->makeInscription($this->makeEvent(), ['status' => 'cancelado', 'cancelled_by' => 'admin']);

        $response = $this->post(route('inscricao.cancel', $inscription->token));

        $response->assertSessionHas('error', 'Esta inscrição já está cancelada.');
        $this->assertSame('admin', $inscription->refresh()->cancelled_by);
    }

    public function test_formulario_mostra_os_termos(): void
    {
        $this->makeEvent();

        $response = $this->get(route('inscricao.create'));

        $response->assertOk();
        $response->assertSee('Conduta:', false);
        $response->assertSee('O valor mínimo é de R$ 100,00 por participante.', false);
        $response->assertSee('Esse encontro é sobre valorizar cada pessoa individualmente. Só é possível fazer uma inscrição de cada vez.', false);
    }
}
