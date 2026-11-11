# O app no painel e na página de status

> Spec de 11/11/2026, aprovada pelo João na conversa. O app está nas duas lojas
> desde 11/11/2026 e os e-mails já convidam para ele (PR #10). Este trabalho
> entra no mesmo PR. Decisões numeradas AP1 a AP9. Base:
> `2026-11-08-app-mobile-design.md` (AM1 a AM46).

## Objetivo

1. Quem abre a página de status da inscrição na web vê o mesmo convite dos
   e-mails para baixar o app.
2. A equipe vê no painel quem entrou pelo app e se os avisos no celular estão
   ligados. Isso ajuda na curadoria: ao aprovar, a equipe sabe se o aviso chega
   na hora no celular ou só por e-mail.

O app em si não muda, e a API também não.

## Página de status (web)

- **AP1. Convite na página de status.** Em `inscriptions/status.blade.php`,
  logo depois do quadro de status, aparece o bloco "Acompanhe tudo pelo app",
  com os botões "Baixar na App Store" e "Baixar no Google Play", feitos em
  Tailwind e nas cores da página.
  - O texto é adaptado para a web: "Baixe o app De Casa em Casa e entre com o
    e-mail da inscrição (**{e-mail}**): você acompanha suas inscrições, vê cada
    etapa e recebe os avisos da equipe na hora."
  - Aparece em todos os status da inscrição.
  - Os links vêm de `config('services.app_stores')`, a mesma configuração dos
    e-mails. Um link vazio esconde o botão daquela loja; os dois vazios
    escondem o bloco.
  - No quadro de suporte da mesma página, a frase "as atualizações da sua
    inscrição chegam por e-mail e nesta página" passa a dizer "por e-mail, no
    app e nesta página".
  - Como `public/build/` é versionado, o CSS é recompilado e entra no commit
    junto (ver o PR #7).

## O que é "usa o app"

- **AP2. Usa o app** quer dizer que existe um `participants` com o e-mail da
  inscrição.
  - O participante só é criado quando o código é verificado
    (`Api\V1\AuthController::verify`), então existir já significa que a pessoa
    entrou pelo menos uma vez.
  - Quem sai do app continua contando como "usa o app". A saída apaga o
    token de acesso e o de push daquele aparelho; sem outro aparelho, a
    pessoa fica sem o sino e sem o "último uso".
  - Quem exclui a conta (AM8) deixa de existir e volta a aparecer como "não usa".
  - A conta de revisão das lojas conta como uma pessoa no app.
- **AP3. Ligação com a inscrição** pelo e-mail, com a mesma regra de posse do
  app (AM2): `LOWER(TRIM(inscriptions.email)) COLLATE utf8mb4_bin =
  participants.email`. Uma inscrição com "Maria@Example.com " pertence a
  "maria@example.com"; uma com "maria@gmáil.com" não pertence a ela.
  - Nas telas, cada linha é procurada por `Participant::normalizeEmail($inscription->email)`,
    a mesma normalização que o envio de push usa. Ela difere do `TRIM` do SQL
    só em e-mails com tabulação ou quebra de linha, o que é aceito.
- **AP4. Os dados mostrados:**
  - **Última entrada:** `participants.last_login_at`.
  - **Último uso:** o maior `personal_access_tokens.last_used_at` do
    participante, que o Sanctum atualiza a cada chamada à API. Fica vazio se
    não houver token usado.
  - **Avisos ligados:** a pessoa tem pelo menos um `participant_push_tokens`.
    O `platform` (`ios` ou `android`) aparece como "iPhone" ou "Android". Se
    a pessoa desligar as notificações nos ajustes do celular, o token
    continua registrado: o sino quer dizer "o app registrou o aparelho".
  - Datas no fuso de `America/Sao_Paulo`, que é o `app.timezone`.
- **AP5. Uma classe só para a leitura:** `App\Participants\AppPresence`, ao
  lado do `LoginCodes`.
  - `forEmails(iterable $emails): array` devolve, para cada e-mail
    normalizado, um objeto com `lastLoginAt`, `lastUsedAt` e `platforms`
    (lista sem repetição). Usa um número fixo de consultas (participantes com
    `withMax` dos tokens e `with` dos tokens de push), sem N+1.
  - `forEmail(string $email)` devolve um objeto ou null.
  - `totals(): array` devolve `participants` e `with_push` (participantes
    distintos com token de push).
  - O filtro fica fora da classe, nos escopos `usesApp()` e `doesNotUseApp()`
    de `Inscription`: um `whereExists` / `whereNotExists` em `participants`
    com a regra do AP3, ao lado do `ownedBy` que já existe.

## Painel

- **AP6. Lista de inscrições** (`admin/inscriptions/index.blade.php`):
  - selo "App" ao lado do nome de quem usa o app, com um sino quando os avisos
    estão ligados e um `title` com a plataforma;
  - filtro novo "Usa o app" (Todos, Sim, Não), no padrão dos filtros de
    comprovante e de contribuição social (`?app=sim|nao`). Um valor desconhecido
    é ignorado. O `app` entra na lista de filtros do botão "Limpar". A
    exportação CSV ignora o `app`, como já ignora os outros dois;
  - a presença é carregada uma vez por página, com `AppPresence::forEmails`
    usando os e-mails da página.
- **AP7. Página da inscrição** (`admin/inscriptions/show.blade.php`): um
  quadro "App" na coluna da direita.
  - Para quem não usa o app: "Ainda não entrou no app".
  - Para quem usa: a última entrada, o último uso (se houver) e os avisos,
    "ligados (iPhone)" ou "desligados".
- **AP8. Dashboard:** uma faixa "No app" logo abaixo dos 4 cartões do topo,
  com o total de participantes e quantos estão com os avisos ligados.
  - É o total geral, sem o filtro de período, porque o app é novo e o filtro
    vale para as datas dos encontros.

## Testes

- **AP9.** PHPUnit no banco `testing`, com dados fictícios ("Velunora"):
  - a página de status mostra os dois links das lojas, e um link vazio
    esconde o botão daquela loja;
  - na lista, o selo aparece para uma inscrição cujo e-mail tem maiúscula e
    espaço, e não aparece para um e-mail com acento parecido;
  - o filtro Sim e o filtro Não;
  - o sino aparece só com um token de push;
  - na página da inscrição, os dois estados;
  - os números do dashboard.

## Fora do escopo

- Uma página própria no painel com todas as pessoas do app.
- O selo na lista de inscrições da página do encontro
  (`admin/events/show.blade.php`).
- O filtro "Usa o app" na exportação CSV.
- Qualquer mudança no app ou na API.
- Avisar a equipe quando alguém entra no app.
