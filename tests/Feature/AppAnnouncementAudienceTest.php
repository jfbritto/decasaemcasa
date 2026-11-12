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

    public function test_quem_ja_falhou_vai_para_o_fim_da_fila(): void
    {
        $event = $this->makeEvent();
        foreach (['a', 'b', 'c'] as $i => $letra) {
            $this->makeInscription($event, ['email' => "{$letra}@example.com"])
                ->forceFill(['created_at' => now()->subMinutes(10 - $i)])->save();
        }
        // A mais recente (c) vem primeiro; com falha anterior, vai para o fim
        $this->logAnnouncement('C@example.com', 'failed');

        $order = array_map(fn ($r) => $r->email, $this->build()['recipients']);

        $this->assertSame(['b@example.com', 'a@example.com', 'c@example.com'], $order);
    }
}
