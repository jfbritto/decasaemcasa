# E-mail de divulgação do app

> Spec de 12/11/2026. O desenho foi aprovado pelo João na conversa. O app está
> nas duas lojas desde 11/11/2026, e os e-mails da inscrição já trazem os links
> desde o PR #10. Decisões numeradas DA1 a DA11 (com DA4b). Base:
> `2026-11-08-app-mobile-design.md` (AM) e `2026-11-11-app-no-painel-design.md` (AP).

## Objetivo

Avisar quem tem inscrição viva num encontro que ainda vai acontecer que o app
existe. Assim a pessoa passa a acompanhar a inscrição e a receber os avisos
pelo celular. O João dispara o envio uma vez, por comando no servidor, e pode
rodar o comando de novo sem que ninguém receba duas vezes.

O app não muda, e a API também não.

## Quem recebe

- **DA1. Inscrições que entram.** Status `pendente`, `aprovado` (com ou sem
  comprovante enviado), `fila_de_espera` e `confirmado`. Ficam de fora
  `rejeitado` e `cancelado`.
- **DA2. Só encontros que ainda vão acontecer.** São os mesmos encontros
  abertos do formulário público e do app (`InscriptionController::create` e
  `Api\V1\EventController::open`): `status = published` e
  `whereDate('date', '>=', now())`. Isso deixa de fora os encontros passados,
  os cancelados, os finalizados, os rascunhos e os excluídos (o
  `SoftDeletes` do `Event` já os esconde do `whereHas`).
- **DA3. Um e-mail por pessoa.** As inscrições são agrupadas pelo e-mail
  normalizado (`Participant::normalizeEmail`, ou seja, `mb_strtolower(trim())`),
  porque é assim que o app junta as inscrições de alguém (AM2). O e-mail vai
  para o endereço normalizado. O nome da saudação vem da inscrição mais recente
  da pessoa entre as que entraram (`created_at` decrescente e, no empate, `id`
  decrescente).
- **DA4. Quem já usa o app fica de fora.** Vale a mesma regra do selo do
  painel (AP3): existe `participants.email` igual ao e-mail normalizado, com
  comparação binária. A consulta é por `AppPresence::forEmails()`.
- **DA4b. Quem não consegue entrar no app fica de fora.** O formulário aceita
  e-mail com acento, mas o login do app só aceita ASCII
  (`Participant::ASCII_EMAIL` em `Api\V1\AuthController::requestCode`). Essas
  pessoas receberiam "use este mesmo e-mail" sem conseguir entrar. Por isso
  ficam de fora quando `Participant::isAsciiEmail()` é falso, e são contadas à
  parte.
- **DA5. Quem já recebeu fica de fora.** Entra nessa conta quem tem, na tabela
  `notifications`, uma linha com `type = email`, `channel = app_announcement`
  e `status = sent`, sem `metadata.sample`, para o mesmo e-mail normalizado.
  - A comparação é feita no PHP, depois de `Participant::normalizeEmail` no
    `recipient`. A collation de `notifications.recipient` é `unicode_ci` e
    ignora acento, por isso não serve.
  - Os reenvios do painel gravam uma linha nova com `metadata.resent_from` e
    o mesmo canal, então também contam.
  - A falha (`failed`) e o envio ignorado (`skipped`) voltam para a lista na
    rodada seguinte, e o e-mail de exemplo (DA8) não conta.

A seleção fica numa classe própria, `App\Participants\AppAnnouncementAudience`,
que só lê. Ela devolve os destinatários (e-mail, nome, id da inscrição mais
recente e encontros) e três contagens dos que ficaram de fora: os que já usam o
app, os que têm e-mail que o app não aceita e os que já receberam. A contagem
é por pessoa, e cada pessoa entra numa só, nesta ordem. O comando só exibe e
envia.

## O e-mail

- **DA6. Modelo `emails/app-announcement.blade.php`**, no layout de sempre.
  - Assunto: "O De Casa em Casa agora tem app". Selo: "Novidade".
  - Saudação: "Olá <nome>," quando há nome. Sem nome (só no exemplo da DA8),
    fica "Olá!".
  - Texto, sem travessão e com "seu lugar na sala" (nunca "cadeira"):
    - "Temos uma novidade: o De Casa em Casa agora tem app, para iPhone e
      Android."
    - "Pelo app você acompanha suas inscrições de perto:"
      - "vê em que etapa está cada inscrição;"
      - "quando for aprovada, vê o prazo e o Pix e envia o comprovante pelo
        celular;"
      - "com seu lugar na sala garantido, encontra o endereço e o horário do
        encontro;"
      - "se ativar as notificações, recebe os avisos da equipe na hora."
    - "Para entrar, use este mesmo e-mail (<e-mail>): você recebe um código de
      6 números, sem senha." O código tem 6 números (`LoginCodes`).
    - "Os botões para baixar estão logo abaixo."
    - Assinatura: "Um abraço da Equipe De Casa em Casa".
  - Os botões das lojas vêm do bloco "Acompanhe tudo pelo app" que o layout já
    tem (`config('services.app_stores')`). O modelo não repete os botões.
  - Não há quadro de encontro (`event_info`), porque a pessoa pode ter mais de
    um.
- **DA7. Só e-mail.** Sem WhatsApp e sem push: quem recebe ainda não tem o app,
  e o WhatsApp é só canal de contato.
  - O envio é por `NotificationService::sendEmail`, com o canal
    `app_announcement` e `metadata = ['inscription_id' => <a mais recente>]`.
    Assim cada envio aparece em Notificações no painel, ligado à inscrição.
  - O método novo `NotificationService::notifyAppAnnouncement(string $email,
    ?string $name, array $metadata): bool` monta o envio. Ele passa ao modelo
    `['name' => ?string, 'email' => string]`. Em `notifications.message` grava
    "Aviso do app para <nome ou e-mail>" (o texto puro só sairia se o modelo
    faltasse).

## O comando

- **DA8. `php artisan inscriptions:announce-app`**, rodado pelo João no SSH.
  - `--to=<e-mail>` manda um exemplo só para esse endereço, sem nome na
    saudação e com `metadata = ['sample' => true]`. Não consulta a lista e não
    conta como recebido (DA5). Se o e-mail for inválido, o comando dá erro e não
    envia nada. O `--to` sem valor também conta como inválido, para nunca cair
    no envio para todos.
  - `--dry-run` não envia nada. Mostra uma tabela por encontro (cidade, data,
    pessoas que recebem) e os totais: quantas pessoas recebem e quantas ficam
    de fora (já usam o app, e-mail que o app não aceita, já receberam). Quem
    tem inscrição em dois encontros aparece nas duas linhas da tabela e conta
    uma vez só no total.
  - `--to` e `--dry-run` juntos dão erro ("use uma opção de cada vez") e não
    enviam nada.
  - Sem opção: mostra os mesmos totais, pede confirmação (o padrão é "não") e
    manda um por um, com barra de progresso. No fim mostra quantos foram
    enviados e quantos falharam. Se houve falha, diz que basta rodar de novo,
    porque só quem não recebeu volta para a lista.
  - Se não houver ninguém para receber, diz isso e termina sem erro.
- **DA9. Ritmo de envio.** Antes de cada envio, menos o primeiro (e também
  depois de uma falha), o comando espera 1 segundo, por
  `Illuminate\Support\Sleep` (`Sleep::fake()` nos testes). São N-1 pausas para
  N envios. O objetivo é não bater no limite de envios por segundo do provedor
  de e-mail.
  - Não sabemos qual provedor a produção usa hoje. O painel fala do Titan (200
    por hora) e também de uma "migração Resend". Se o limite for por hora, a
    DA10 segura o envio e a rodada seguinte continua de onde parou.
- **DA10. Proteções.**
  - No envio de verdade e no `--to`, o comando não envia nada quando falta o
    mailer (`config('mail.default')` vazio) ou quando os dois links das lojas
    estão vazios. Nos dois casos dá erro e explica o motivo. Sem os links, o
    e-mail sairia sem os botões, e sem o mailer o `sendEmail` só registra
    `skipped`. No `--dry-run`, os mesmos casos só geram um aviso.
  - Em produção, o comando também recusa os mailers `log` e `array`, que não
    entregam e-mail de verdade. Com eles, todo mundo ficaria registrado como
    recebido sem ter recebido nada.
  - O envio para depois de 3 falhas seguidas, que costumam vir da cota ou do
    limite do provedor, e diz para rodar de novo mais tarde (depois de 1 hora,
    se o limite for por hora). As falhas ficam registradas como `failed` e
    voltam na próxima rodada.
  - Quem já tem uma falha registrada vai para o fim da fila. Assim, três
    endereços que falham sempre não travam todas as rodadas.

## Painel

- **DA11. Notificações reconhecem o canal novo.**
  - Em `admin/notifications/index.blade.php`, "Divulgação do app" entra no
    filtro "Tipo de email" e no mapa de rótulos.
  - O botão "Reenviar" de uma linha `app_announcement` usa o mesmo modelo, e
    não texto puro. Para isso, `NotificationResendService::resendEmail` ganha
    um ramo próprio antes do `viewMap`, porque o `viewMap` exige inscrição e
    encontro. O modelo recebe `['name' => <full_name da inscrição de
    metadata.inscription_id, ou null>, 'email' => recipient]`.
    - O ramo chama o `sendEmail` como hoje, então o `resent_from` e o
      `template_used` continuam sendo juntados ao metadata, e o
      `metadata.sample` é preservado.
    - Como nos outros canais, o reenvio de uma linha é uma ação manual e
      sempre reenvia.
  - O "Reenviar pendentes" (`NotificationController::rateLimitPendingQuery`)
    passa a ignorar `app_announcement`. Ele pega toda falha sem
    `resent_from`, inclusive a de quem já recebeu numa rodada seguinte do
    comando. Com isso mandaria de novo. Para este canal, rodar o comando de
    novo já é o reenvio.

## Testes (PHPUnit, banco `testing`)

- **Seleção:**
  - entram os quatro status e saem rejeitado e cancelado;
  - saem os encontros passados, cancelados, em rascunho e excluídos; o
    encontro de hoje entra;
  - e-mails que só diferem por espaço e caixa viram uma pessoa só, com o nome
    da inscrição mais recente;
  - quem já usa o app sai e é contado;
  - quem tem e-mail com acento sai e é contado à parte;
  - quem já recebeu sai e é contado, inclusive por reenvio do painel e com o
    `recipient` em outra caixa; uma falha ou um exemplo não contam;
  - quem já falhou vai para o fim da fila.
- **Comando:**
  - o `--dry-run` não envia nada e mostra os totais;
  - o envio confirmado registra uma notificação `app_announcement` por pessoa;
  - rodar de novo não envia nada;
  - responder "não" não envia nada;
  - o `--to` manda um só, marcado como exemplo, e não tira ninguém da lista;
  - o `--to` com e-mail inválido ou sem valor não envia, e `--to` com
    `--dry-run` dá erro;
  - sem os links das lojas ou sem mailer, não envia, e em produção recusa os
    mailers `log` e `array`;
  - no `--dry-run`, quem está em dois encontros aparece nas duas linhas e
    conta uma vez;
  - para depois de 3 falhas seguidas. A falha é simulada com
    `config(['mail.default' => 'inexistente'])` e sem `Mail::fake()`: o
    `Mail::mailer()` lança "Mailer [inexistente] is not defined", o
    `sendEmail` registra `failed` e devolve `false`, e esse nome passa pela
    proteção da DA10, que só barra o mailer vazio;
  - faz N-1 pausas de 1 segundo para N envios (`Sleep::fake()` e
    `Sleep::assertSleptTimes`).
- **E-mail:** o texto, o nome, o e-mail, os links das lojas e nenhum
  travessão; o "Olá!" quando não há nome.
- **Painel:** o reenvio de `app_announcement` usa o modelo, e o "Reenviar
  pendentes" ignora as falhas desse canal.

## Fora do escopo

- Envio pelo painel (o João escolheu o comando).
- Agendar o comando no `Kernel`: é um disparo único.
- Mudanças no app ou na API.

## Como o João roda em produção

Depois do deploy de sempre (`git pull`, `config:cache && route:cache`; esta
mudança não tem migration):

1. `php artisan inscriptions:announce-app --to=<seu e-mail>` e conferir o
   e-mail que chegou.
2. `php artisan inscriptions:announce-app --dry-run` e conferir os números por
   encontro.
3. `php artisan inscriptions:announce-app` e confirmar.
