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
        // "--to" sem valor chega como null: não pode cair no envio para todos
        $sample = $this->input->hasParameterOption('--to');
        $dryRun = (bool) $this->option('dry-run');

        if ($sample && $dryRun) {
            $this->error('Use uma opção de cada vez: --to ou --dry-run. Nada foi enviado.');

            return self::FAILURE;
        }

        $problem = $this->sendingProblem();

        if ($sample) {
            return $this->sendSample((string) $this->option('to'), $problem, $notifications);
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
     * Sem mailer o sendEmail só registra "skipped"; com "log" ou "array" em
     * produção todos ficariam como recebidos sem receber nada; sem os links
     * o e-mail sairia sem os botões (DA10).
     */
    private function sendingProblem(): ?string
    {
        $mailer = config('mail.default');

        if (blank($mailer)) {
            return 'O envio de e-mail não está configurado (MAIL_MAILER vazio).';
        }

        $transport = config("mail.mailers.{$mailer}.transport");
        if (app()->environment('production') && in_array($transport, ['log', 'array'], true)) {
            return "O mailer \"{$mailer}\" não entrega e-mail de verdade (MAIL_MAILER).";
        }

        if (blank(config('services.app_stores.app_store')) && blank(config('services.app_stores.google_play'))) {
            return 'Os links das lojas estão vazios (APP_STORE_URL e GOOGLE_PLAY_URL).';
        }

        return null;
    }

    private function sendSample(string $to, ?string $problem, NotificationService $notifications): int
    {
        if (filter_var(trim($to), FILTER_VALIDATE_EMAIL) === false) {
            $this->error('E-mail inválido em --to'.($to !== '' ? ": {$to}" : '').'. Nada foi enviado.');

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
