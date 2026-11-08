# App De Casa em Casa (iOS e Android)

> Spec de 08/11/2026. Pedido do João: um app do participante, no mesmo molde
> do HelpCheck (Laravel com API v1 + Expo SDK 57 em `mobile/`), para o
> portfólio e para "surpreender o pessoal". Contas da Apple e do Google já
> existem. Decisões numeradas AM1 a AM40.

## Objetivo

O participante tem a própria área no celular: vê os encontros abertos, se
inscreve, acompanha o status de todas as inscrições num lugar só, envia o
comprovante, pede a contribuição social, cancela e, confirmado, vê endereço
e horário. Recebe push a cada mudança feita pela equipe.

Nada muda para quem usa a web: o formulário, a página de status por token e
os e-mails continuam iguais.

## Identidade e login

- **AM1. Participante é uma entidade nova** (`participants`), separada dos
  `users` (que são só a equipe). Campos: `email` (único, minúsculo),
  `name` (o da inscrição mais recente, opcional), `last_login_at`. Usa o
  `HasApiTokens` do Sanctum.
- **AM2. A chave é o e-mail.** As inscrições do participante são as que têm
  o mesmo e-mail (`Inscription::ownedBy($participant)`), sem caixa e sem
  espaços, mas **com acento**: a comparação é binária
  (`LOWER(TRIM(email)) COLLATE utf8mb4_bin`), e `participants.email` e
  `participant_login_codes.email` são `utf8mb4_bin`. A collation padrão
  (`utf8mb4_unicode_ci`) ignora acentos e deixaria "maria@gmáil.com", um
  domínio que qualquer um registra, entrar na conta de "maria@gmail.com"
  (achado da revisão de segurança, corrigido antes do merge). O login só
  aceita e-mail ASCII. Assim, quem se inscreveu pela web antes de baixar o
  app encontra tudo ao entrar. CPF não serve de chave: qualquer um pode
  digitar o CPF de outra pessoa num formulário, mas só o dono recebe o código
  no e-mail.
- **AM3. Login sem senha, por código de 6 dígitos no e-mail.**
  `POST /api/v1/auth/code {email}` gera o código (guardado só como hash, vale
  10 minutos, um código vivo por e-mail: pedir de novo invalida o anterior) e
  envia o e-mail `emails.app-login-code`. A resposta é sempre a mesma (202),
  exista ou não inscrição com o e-mail, para não revelar quem está inscrito.
- **AM4. `POST /api/v1/auth/verify {email, code, device_name}`** confere o
  código (no máximo 5 tentativas por código; na quinta errada ele morre),
  cria o participante se ainda não existe e devolve `{token, participant}`.
  Código usado não vale de novo.
- **AM5. Limites:** pedir código, 5 por minuto por IP e 3 a cada 10 minutos
  por e-mail; conferir, 10 por minuto por IP. Demais rotas, o `throttle:api`
  do grupo (60 por minuto por participante ou IP; a chave leva o tipo, para
  o id de um participante não dividir cota com o mesmo id da equipe).
- **AM6. Conta de revisão das lojas:** `APP_REVIEW_EMAIL` e `APP_REVIEW_CODE`
  no `.env`. Para esse e-mail nenhum e-mail sai e o código fixo vale. Sem as
  duas variáveis, ou com código de menos de 12 caracteres (ele não tem
  contador nem validade), não existe conta de revisão. O campo do app é
  numérico e aceita até 32 números: o código de revisão é só de números.
- **AM7. Só o participante usa a API do app.** Middleware `participant` nas
  rotas autenticadas: um token de outro tipo dá 403.
- **AM8. Sair** revoga o token do aparelho e apaga o token de push que o app
  mandar. **Excluir conta** (`DELETE /api/v1/account`, exigência da Apple e
  do Google) apaga o participante, os tokens de acesso, os de push e os
  códigos. As inscrições ficam com a equipe (são o registro do encontro e
  existem também pela web); o app explica isso e oferece o WhatsApp da equipe
  para pedir a remoção. Página pública `/app/excluir-conta` para o link que a
  Play pede.

## API (`/api/v1`)

- **AM9. Formato:** JSON sem `data` para recurso único; listas como
  `{data: [...]}`. Datas em ISO 8601 com fuso (`toIso8601String`). Erros de
  validação no padrão do Laravel (422 com `errors`); recusa de regra com
  `{message, code}`.
- **AM10. Rotas públicas:** `GET /config` (textos do manifesto e dos termos,
  valor de referência, suporte, links de privacidade e exclusão),
  `GET /events` (publicados, de hoje em diante, por data) e
  `GET /events/{id}`. Endereço do encontro **nunca** sai nessas rotas.
- **AM11. Rotas do participante:** `GET /me`, `POST /auth/logout`,
  `GET /inscriptions`, `GET /inscriptions/{id}`, `POST /inscriptions`,
  `POST /inscriptions/{id}/payment-proof`,
  `POST /inscriptions/{id}/social-request`,
  `POST /inscriptions/{id}/cancel`, `POST /push-tokens`,
  `DELETE /push-tokens/{token}`, `DELETE /account`.
- **AM12. Inscrição de outro e-mail dá 404**, nunca 403 (não revela que
  existe). Todo endpoint novo nasce com esse teste.
- **AM13. O estado da tela é decidido no servidor** (`InscriptionView`): o
  app recebe `state` (um de `pending`, `pending_full`, `approved`,
  `approved_full`, `confirmed`, `waitlisted`, `expired`, `rejected`,
  `cancelled_by_admin`, `cancelled_by_participant`), `can` (`upload_proof`,
  `request_social`, `cancel`) e só desenha. É a mesma árvore de `if` da
  `status.blade.php`.
- **AM14. Pix só para quem pode pagar:** chave e titular saem apenas quando
  `can.upload_proof` é verdadeiro.
- **AM15. Endereço e horário só para confirmado**, como na web.
- **AM16. Inscrever pelo app usa o e-mail da conta.** O campo não existe no
  formulário do app; o resto (nome, CPF, nascimento, bairro e cidade,
  WhatsApp, Instagram, história, termos) segue as mesmas regras da web.
  Duplicada (mesmo CPF ou e-mail no mesmo encontro) responde 409
  `already_registered` com o `inscription_id` existente quando ela é da
  conta (o app a abre); quando é de outro e-mail (o CPF de outra pessoa),
  409 `already_registered_other_email`, sem o id.
- **AM17. Uma regra, um lugar:** as regras que web e API dividem saem do
  `InscriptionController` para `app/Inscriptions/`:
  - `InscriptionRules` (validação dos dados, mensagens, CPF, e-mail);
  - `RegisterInscription` (publicado, lotado, duplicada, criar, notificar);
  - `SubmitPaymentProof` (só aprovado, gravação condicional contra o cron,
    apaga o comprovante anterior);
  - `SubmitSocialRequest` e `CancelInscription`.
  O controller web passa a só traduzir o resultado em redirect, sem mudar
  nenhum texto nem comportamento.
- **AM18. Termos e manifesto num lugar só:** `config/inscriptions.php`
  (`manifesto`, `terms`, `terms_acceptance`, `reference_amount`). A
  `create.blade.php` lê de lá e o `GET /config` também.
- **AM19. Comprovante pelo app:** multipart `payment_proof`, JPG, PNG ou PDF
  até 5 MB (o mesmo da web). O app reduz a foto para no máximo 1600 px em
  JPEG antes de enviar.

## Push

- **AM20. Tokens em `participant_push_tokens`** (token Expo único no sistema:
  registrado em outra conta, muda de dono), `platform`, `device_name`,
  `last_seen_at`.
- **AM21. O push nasce no `NotificationService`**, ao lado do e-mail, e vai
  pela fila (job `SendParticipantPush`): na produção, o `queue:work` do cron
  de cada minuto. Push é conveniência: falha de push nunca impede o e-mail.
- **AM22. Recebem push** as mudanças feitas pela equipe: aprovação, fila de
  espera, prazo vencido, não aprovação, cancelamento pela equipe,
  confirmação, contribuição social aprovada ou não, transferência de
  encontro, vagas esgotadas e mensagem personalizada. O que o próprio
  participante fez (inscrição recebida, pedido social, cancelamento) não
  gera push.
- **AM23. Cliente do Expo** copiado do HelpCheck (`ExpoPushClient`: lotes de
  100, `EXPO_ACCESS_TOKEN` opcional, nunca chama a rede em teste). Ticket com
  `DeviceNotRegistered` apaga o token na hora.
- **AM24. O push leva `{inscription_id, kind}`**; o toque abre a inscrição.
- **AM25. Textos de push sem travessão, com "seu lugar na sala"** e sem
  prometer nada por WhatsApp (memória de copy do projeto).

## App (`mobile/`)

- **AM26. Mesma base do HelpCheck:** Expo SDK 57, Expo Router em
  `mobile/src/app`, TypeScript estrito, zustand para a sessão,
  TanStack Query para os dados, `expo-secure-store` para o token, Jest com
  `jest-expo` e Testing Library. Sem banco local: o app é online (não há
  trabalho de campo sem sinal).
- **AM27. Navegação:** abas "Encontros", "Minhas inscrições" e "Perfil". Os
  encontros abrem sem login (a Apple pede que o app mostre algo antes da
  conta); "Quero participar" e as outras abas pedem o login.
- **AM28. Telas:** boas-vindas e e-mail (`(auth)/login`), código
  (`(auth)/code`), lista de encontros, detalhe do encontro, inscrição em
  etapas (manifesto, dados, termos), lista de inscrições, detalhe da
  inscrição (status, prazo com contagem regressiva, Pix com copiar,
  comprovante por câmera, galeria ou arquivo, contribuição social, endereço
  com "Abrir no mapa", cancelar), perfil (avisos no celular, ajuda pelo
  WhatsApp, privacidade, sair, excluir conta).
- **AM29. Identidade visual:** a do violão do favicon. Terracota
  (`#C2621B`) como cor da marca, fundo creme, texto marrom escuro. Títulos
  em Fraunces, interface em Figtree (a fonte do site). **Cor de marca não
  representa estado:** verde confirmado, âmbar atenção, vermelho recusa,
  cinza neutro.
- **AM30. Regra duplicada de propósito:** a validação do CPF (o app avisa
  antes de enviar). Os dois lados leem os mesmos casos em
  `tests/fixtures/cpf-cases.json` (PHPUnit e Jest), como no HelpCheck.
- **AM31. Contagem regressiva** a partir de `payment.deadline_at`; ao zerar,
  o app busca a inscrição de novo (o cron decide, não o app).
- **AM32. Push:** permissão pedida só com um toque do participante (cartão
  "Ativar avisos" na lista de inscrições e no perfil); nunca em Expo Go nem
  simulador.
- **AM33. Textos visíveis sem travessão** e com "seu lugar na sala"; teste
  de arquitetura no Jest recusa travessão em `src/`.
- **AM34. Bundle `br.com.helpflux.decasaemcasa`**, scheme `decasaemcasa`,
  dono no Expo `jfbritto0` (o mesmo do HelpCheck). O `projectId` do EAS sai
  do `eas init`.
- **AM35. Ícones e splash** gerados de `resources/brand/app-icon.svg` (o
  violão em creme sobre terracota) por `mobile/scripts/generate-icons.mjs`.

## Site

- **AM36. `/privacidade`:** política de privacidade (dados coletados, para
  quê, por quanto tempo, push, exclusão, contato). As duas lojas exigem a
  URL.
- **AM37. `/app/excluir-conta`:** como excluir a conta no app e como pedir a
  remoção das inscrições.

## Produção (hospedagem compartilhada)

- **AM38. Migrations novas:** `participants`, `participant_login_codes`,
  `participant_push_tokens`. A `personal_access_tokens` vem da migration do
  próprio Sanctum 3 (carregada pelo pacote). Nenhuma tabela existente muda.
  O `bootstrap/app.php` passa a ligar o `App\Exceptions\Handler` (o do
  framework estava ligado), para a API responder JSON no 401 e no 404.
- **AM39. `.env` novo (todos opcionais):** `APP_REVIEW_EMAIL`,
  `APP_REVIEW_CODE`, `EXPO_ACCESS_TOKEN`. Push depende do cron
  `schedule:run` (já existe).
- **AM40. Testes:** PHPUnit para cada rota (acesso de outro e-mail = 404,
  sem token = 401), login (código errado, expirado, tentativas, reuso,
  conta de revisão), push (fila, token morto) e as regras extraídas (a
  suíte atual continua passando); Jest para cliente HTTP, sessão, regras e
  telas principais; `tsc` e lint.
