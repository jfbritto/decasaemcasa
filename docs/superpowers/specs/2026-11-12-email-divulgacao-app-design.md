# E-mail de divulgação do app

> Spec de 12/11/2026. O desenho foi aprovado pelo João na conversa. O app está
> nas duas lojas desde 11/11/2026, e os e-mails da inscrição já trazem os links
> desde o PR #10. Decisões numeradas DA1 a DA11. Base:
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
  da pessoa (`created_at`) entre as que entraram.
- **DA4. Quem já usa o app fica de fora.** Vale a mesma regra do selo do
  painel (AP3): existe `participants.email` igual ao e-mail normalizado, com
  comparação binária. A consulta é por `AppPresence::forEmails()`.
- **DA5. Quem já recebeu fica de fora.** Entra nessa conta quem tem, na tabela
  `notifications`, uma linha com `type = email`, `channel = app_announcement`
  e `status = sent`, sem `metadata.sample`, para o mesmo e-mail normalizado. Por
  isso a falha (`failed`) e o envio ignorado (`skipped`) voltam para a lista
  na rodada seguinte, e o e-mail de exemplo (DA8) não conta.

A seleção fica numa classe própria, `App\Participants\AppAnnouncementAudience`,
que só lê. Ela devolve os destinatários (e-mail, nome, id da inscrição mais
recente e encontros) e duas contagens dos que ficaram de fora: os que já usam o
app e os que já receberam. O comando só exibe e envia.

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
    ?string $name, array $metadata): bool` monta o envio.

## O comando

- **DA8. `php artisan inscriptions:announce-app`**, rodado pelo João no SSH.
  - `--to=<e-mail>` manda um exemplo só para esse endereço, sem nome na
    saudação e com `metadata = ['sample' => true]`. Não consulta a lista e não
    conta como recebido (DA5). Se o e-mail for inválido, o comando dá erro e não
    envia nada.
  - `--dry-run` não envia nada. Mostra uma tabela por encontro (cidade, data,
    pessoas) e os totais: quantas pessoas recebem e quantas ficam de fora (já
    usam o app, já receberam). Quem tem inscrição em dois encontros aparece nas
    duas linhas da tabela e conta uma vez só no total.
  - Sem opção: mostra os mesmos totais, pede confirmação (o padrão é "não") e
    manda um por um, com barra de progresso. No fim mostra quantos foram
    enviados e quantos falharam. Se houve falha, diz que basta rodar de novo,
    porque só quem não recebeu volta para a lista.
  - Se não houver ninguém para receber, diz isso e termina sem erro.
- **DA9. Ritmo de envio.** O comando faz uma pausa de 1 segundo entre um
  envio e outro, por `Illuminate\Support\Sleep` (`Sleep::fake()` nos testes),
  para não bater no limite de envios por segundo do Resend.
- **DA10. Proteções.**
  - O comando não envia nada quando falta o mailer (`config('mail.default')`
    vazio) ou quando os dois links das lojas estão vazios. Nos dois casos dá
    erro e explica o motivo. Sem os links, o e-mail sairia sem os botões, e sem
    o mailer o `sendEmail` só registra `skipped`.
  - O envio para depois de 3 falhas seguidas, que costumam vir da cota ou do
    limite do provedor, e avisa para tentar mais tarde. As falhas ficam
    registradas como `failed` e voltam na próxima rodada.

## Painel

- **DA11. Notificações reconhecem o canal novo.**
  - Em `admin/notifications/index.blade.php`, "Divulgação do app" entra no
    filtro "Tipo de email" e no mapa de rótulos.
  - `NotificationResendService` reenvia `app_announcement` com o mesmo modelo,
    e não como texto puro. Isso vale para o botão "Reenviar" e para o
    "Reenviar pendentes". O nome sai da inscrição de `metadata.inscription_id`
    e, se não houver inscrição, fica sem nome. O reenvio preserva o
    `metadata.sample`.

## Testes (PHPUnit, banco `testing`)

- **Seleção:**
  - entram os quatro status e saem rejeitado e cancelado;
  - saem os encontros passados, cancelados, em rascunho e excluídos; o
    encontro de hoje entra;
  - e-mails que só diferem por espaço e caixa viram uma pessoa só, com o nome
    da inscrição mais recente;
  - quem já usa o app sai e é contado;
  - quem já recebeu sai e é contado; uma falha ou um exemplo não contam.
- **Comando:**
  - o `--dry-run` não envia nada e mostra os totais;
  - o envio confirmado registra uma notificação `app_announcement` por pessoa;
  - rodar de novo não envia nada;
  - responder "não" não envia nada;
  - o `--to` manda um só, marcado como exemplo, e não tira ninguém da lista;
  - o `--to` com e-mail inválido não envia;
  - sem os links das lojas ou sem mailer, não envia;
  - para depois de 3 falhas seguidas;
  - faz a pausa de 1 segundo entre os envios (`Sleep::assertSlept`).
- **E-mail:** o texto, o nome, o e-mail, os links das lojas e nenhum
  travessão; o "Olá!" quando não há nome.
- **Painel:** o reenvio de `app_announcement` usa o modelo.

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
