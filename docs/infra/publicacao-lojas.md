# Publicação nas lojas: roteiro do João

App do participante do De Casa em Casa (spec
`docs/superpowers/specs/2026-11-08-app-mobile-design.md`). Mesmo caminho do
HelpCheck (`~/Projetos/helpcheck/docs/infra/publicacao-lojas.md`), com as contas
que você já tem.

## 0. O que já está pronto

- App em `mobile/` (Expo SDK 57), com testes, tipos e lint limpos e `expo export` de
  iOS e Android compilando.
- Projeto no Expo criado e ligado: **`@jfbritto0/decasaemcasa`**, projectId
  `92ba50c0-a54e-4f47-b290-6a857fd89df7` (no `mobile/app.json`; o `app.config.js`
  monta a URL do OTA a partir dele).
- Identificadores: bundle iOS e pacote Android **`br.com.helpflux.decasaemcasa`**,
  scheme `decasaemcasa`. Trocar só antes do primeiro build: depois de publicado, não
  muda mais.
- `eas.json` com os perfis `preview` (APK interno) e `production` (lojas), os dois
  apontando para `https://decasaemcasa.omarcosalmeida.com.br`; submit Android para a
  faixa interna como rascunho; iOS em pt-BR.
- Ícone, ícone adaptativo (com monocromático), splash e ícone 512 em
  `mobile/assets/images/` e `mobile/store/`.
- Imagens das lojas prontas, no padrão do HelpCheck e do TreinaEdu (fundo da marca, uma
  frase e a tela real do app): 7 capturas para a App Store (1320 x 2868 e 1206 x 2622), o
  cabeçalho da página do produto, 7 capturas para o Google Play (1080 x 1920) e o recurso
  gráfico 1024 x 500. Onde vai cada uma: `mobile/store/textos.md`, seção 3.
- Política de privacidade em `/privacidade` e página de exclusão de conta em
  `/app/excluir-conta` (vão para o ar com o deploy do servidor).
- Textos das lojas, respostas de privacidade e notas para o revisor em
  `mobile/store/textos.md`.

## 1. Antes de tudo: o servidor em produção

O app de loja fala com a API de produção. **Sem o deploy, o app abre sem encontros e
ninguém entra.** Feito em 09/11/2026 (PRs #5 a #7); o roteiro abaixo vale para os
próximos.

### Como a produção é de fato

Hospedagem compartilhada da HostGator (plano M, cPanel), sem CI. Visto por SSH em
09/11/2026:

- O projeto é um clone da `main` em `~/decasaemcasa.omarcosalmeida.com.br`, e a raiz do
  domínio é a raiz do projeto: o `.htaccess` da raiz repassa para `public/`. Os dois
  `.htaccess` ficam fora do git. O da raiz tem um bloco do cPanel ("do not edit") que
  escolhe a versão do PHP.
- **Configuração e rotas ficam em cache** (`bootstrap/cache/config.php` e
  `routes-v7.php`). Depois de qualquer `git pull` ou mudança no `.env`, refaça os dois
  na hora. No deploy do app, o pull com o cache antigo derrubou o `/inscricao` com 500
  (os textos novos dos termos não estavam no cache).
- `QUEUE_CONNECTION=sync`: e-mail e push saem na hora, dentro da própria requisição. O
  cron roda o `schedule:run` a cada 21 minutos, então só a expiração de prazo vencido
  depende dele (e atrasa até 21 minutos).
- O servidor repassa o cabeçalho `Authorization` ao Laravel sem regra no `.htaccess`.
- **Firewall (mod_security):** recusa com 406 o `POST` sem `Content-Length` e qualquer
  corpo em pedaços (`Transfer-Encoding: chunked`). Por isso o app sempre manda `{}` nos
  `POST` sem dados (`mobile/src/services/api.ts`), e o upload do comprovante usa o
  `expo-file-system`, que informa o tamanho no iOS e no Android. `DELETE` passa.

### Deploy

1. Backup do banco, pelo SSH (não mostra a senha) ou pelo phpMyAdmin:

   ```bash
   E() { grep "^$1=" .env | cut -d= -f2- | sed 's/^"//;s/"$//'; }
   MYSQL_PWD="$(E DB_PASSWORD)" mysqldump --no-tablespaces --single-transaction -h "$(E DB_HOST)" -u "$(E DB_USERNAME)" "$(E DB_DATABASE)" > ~/backup-decasaemcasa-$(date +%F-%H%M).sql && chmod 600 ~/backup-decasaemcasa-*.sql
   ```

2. `git pull origin main` e, **logo em seguida**:

   ```bash
   php artisan migrate --force
   php artisan config:cache && php artisan route:cache && php artisan view:clear
   ```

   Em produção, sem o `--force` o Laravel pede confirmação. O composer só precisa rodar
   se o `composer.lock` mudou; o CSS vai pronto em `public/build`.
3. Conta de revisão das lojas (seção 6), uma vez: `APP_REVIEW_EMAIL` e `APP_REVIEW_CODE`
   no `.env`, depois `php artisan config:cache`. O e-mail é um seu de verdade e sem
   nenhuma inscrição: o revisor vê as inscrições desse e-mail, e o sistema manda e-mail a
   cada etapa. `EXPO_ACCESS_TOKEN` só se ligar a "enhanced push security" no Expo.
4. Do seu computador, na raiz do projeto: `scripts/conferir-api-producao.sh`. Ele abre o
   formulário da web, as rotas públicas do app e as duas páginas das lojas, entra com a
   conta de revisão (pede o código sem mostrar na tela), confere as chamadas com login e
   sai da conta no fim.

Se um dia as chamadas com login voltarem 401 (troca de servidor), falta repassar o
`Authorization`: no `.htaccess` de `public/`, logo depois de `RewriteEngine On`:

```apache
RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

## 2. Push

### 2.1 Android (Firebase)

Sem o `google-services.json` o app compila, mas **não recebe push no Android**.

1. [console.firebase.google.com](https://console.firebase.google.com): no projeto da
   HelpFlux (ou num novo), adicionar o app Android com o pacote
   **`br.com.helpflux.decasaemcasa`** e baixar o `google-services.json`.
2. Entregar ao EAS como variável do tipo arquivo (o `app.config.js` já lê):
   `npx eas-cli@latest env:create --name GOOGLE_SERVICES_JSON --type file --visibility secret --environment production`
   (repetir com `--environment preview` para testar push no APK).
3. Firebase, Configurações do projeto, Contas de serviço, "Gerar nova chave privada".
   Subir no EAS: `npx eas-cli@latest credentials -p android`, "Google Service Account
   Key for FCM V1". Guardar o JSON fora do repositório.

### 2.2 iOS (APNs)

O EAS cria a chave no primeiro build de loja, com o login da Apple. Se falhar:
`npx eas-cli@latest credentials -p ios`, "Set up a new push key". Chave criada à mão no
portal: **Sandbox & Production** e **Team Scoped (All Topics)**. A conta da Apple aceita
só 2 chaves de push: dá para reaproveitar a do HelpCheck (ela vale para todos os apps do
time).

## 3. Build

1. `mobile/app.json`: `version` 1.0.0 (primeira versão).
2. Opcional, para testar antes num Android:
   `cd mobile && npx eas-cli@latest build --platform android --profile preview` (APK).
3. Lojas: `npx eas-cli@latest build --platform all --profile production`. O EAS cuida das
   credenciais e incrementa `buildNumber` e `versionCode` (`appVersionSource: remote`).
4. O EAS envia a raiz do git, não só `mobile/`. O `.easignore` da raiz deixa passar só
   `mobile/` (sem `.env`, `store/` e `node_modules`). Os builds 1.0.0 de 10/11/2026 saíram
   antes dele e levaram junto a pasta `.claude/worktrees/`, com um `.env.demo`.
   Para conferir o que sobe, sem gastar build:
   `npx eas-cli@latest build:inspect --platform android --stage archive --output <pasta vazia> --profile production`.
   O estágio `pre-build` baixa a keystore para a pasta de saída: apague a pasta depois.

## 4. iOS: App Store Connect

1. Apps, "+", Novo app: iOS, nome **De Casa em Casa**, idioma Português (Brasil), bundle
   `br.com.helpflux.decasaemcasa`, SKU `decasaemcasa`.
2. `npx eas-cli@latest submit --platform ios --profile production --latest`.
3. TestFlight: adicionar você como testador interno e instalar.
4. Preencher a ficha (seção 5), a privacidade e a conta de revisão (seção 6). Na versão,
   escolher o build e "Adicionar para revisão".

## 5. Android: Play Console

1. Criar app: nome **De Casa em Casa**, pt-BR, aplicativo, gratuito.
2. **O primeiro `.aab` vai à mão** (regra do Google): baixar do painel do EAS e subir em
   Teste interno, Criar versão.
3. Para os próximos: conta de serviço com acesso à API da Play, cadastrada com
   `npx eas-cli@latest credentials -p android` ("Google Service Account Key for Play Store
   submissions"); depois `npx eas-cli@latest submit --platform android --profile production --latest`
   (vai como rascunho na faixa interna).
4. Antes de sair do teste interno: ficha, classificação de conteúdo, público-alvo,
   anúncios (não tem), Segurança dos dados e a URL de exclusão de conta
   (`https://decasaemcasa.omarcosalmeida.com.br/app/excluir-conta`). Tudo em
   `mobile/store/textos.md`.

## 6. Conta de revisão

Os revisores precisam entrar, e o login é por código no e-mail, que eles não recebem.

1. Escolher um e-mail de verdade, **sem nenhuma inscrição** (o revisor vê todas as
   inscrições desse e-mail; se precisar, um apelido do Gmail como `nome+revisao@gmail.com`
   é outro e-mail para o sistema), e um código aleatório de **pelo menos 12 números** (só
   números: o campo do app é numérico). Com menos, a conta de revisão fica desligada de
   propósito: o código fixo não tem contador de tentativas nem validade, e o limite por
   e-mail do login é a proteção dele. Gerar o código no próprio terminal, sem passar por
   chat nem documento: `LC_ALL=C tr -dc 0-9 < /dev/urandom | head -c 16; echo`.
2. Pôr os dois no `.env` de produção: `APP_REVIEW_EMAIL` e `APP_REVIEW_CODE`, e rodar
   `php artisan config:cache`. Para esse e-mail o código de login não sai por e-mail e
   o código fixo vale. Feito em 09/11/2026, com um e-mail do João; o código está no
   gerenciador de senhas dele.
3. Informar o e-mail e o código como "usuário" e "senha" nos campos de conta de revisão
   do App Store Connect e do Play Console, com as notas em inglês de
   `mobile/store/textos.md`.
4. O revisor pode se inscrever num encontro de verdade: a inscrição aparece na curadoria
   com esse e-mail. Para ele ver o fluxo do Pix, a equipe pode aprovar a inscrição dele;
   depois da revisão, cancelar.
5. Depois da aprovação nas lojas, pode trocar o código (ou apagar as duas variáveis).

## 7. Atenção: a marca não é da HelpFlux

A Apple (diretriz 5.2.1) e o Google pedem que quem publica tenha direito de usar a marca
e o conteúdo. O app sai na conta da HelpFlux com a marca da turnê De Casa em Casa:

- peça ao responsável pela turnê uma **autorização por escrito** para a HelpFlux publicar
  o app (um PDF simples, assinado);
- anexe essa autorização nas notas de revisão da Apple (campo de anexo) e guarde para a
  Play, que pode pedir.

Sem ela, a revisão pode recusar por propriedade intelectual.

## 8. Teste em aparelho real, antes de enviar para revisão

Com o build do TestFlight e o do teste interno, numa conta de teste sua (não a de
revisão):

1. Encontros abrem sem conta; "Quero participar" leva ao login; o código chega no e-mail.
2. Inscrição completa num encontro de teste (crie um encontro rascunho, publique só
   durante o teste e apague depois).
3. Pelo painel, aprovar a inscrição: o push chega e o toque abre a inscrição; o prazo
   conta; copiar o Pix; enviar o comprovante pela câmera e por um PDF.
4. Confirmar pelo painel: o endereço aparece no app, com "Abrir no mapa".
5. Contribuição social: pedir pelo app, aprovar pelo painel, conferir o prazo reiniciado.
6. Sair e excluir a conta (com outra conta de teste).

## 9. Atualização OTA (EAS Update)

Cada build recebe update só do seu canal e da sua `runtimeVersion` (= `version`).

- Vai por OTA: código JS/TS, estilos, textos, imagens importadas.
- Pede build novo e `version` nova: biblioteca nativa nova, plugin, permissão, ícone,
  splash, SDK.
- `cd mobile && npx eas-cli@latest update --channel production --message "o que mudou"`.
- Ordem ao subir `version`: (1) subir no `app.json`, (2) build, (3) submit, (4) só então
  OTA.

## 10. O que é seu

1. ~~Merge do PR e deploy do servidor (seção 1), com `APP_REVIEW_EMAIL` e
   `APP_REVIEW_CODE`.~~ Feito em 09/11/2026.
2. ~~Autorização da marca (seção 7).~~ Feita.
3. Firebase e a chave FCM V1 (seção 2.1).
4. `eas build` e `eas submit` (seções 3 a 5).
5. Subir as imagens prontas de `mobile/store/` (tabela na seção 3 do `textos.md`).
6. Ficha, privacidade e conta de revisão nas duas lojas.
