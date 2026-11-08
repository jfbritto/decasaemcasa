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
- Ícone, ícone adaptativo (com monocromático), splash, ícone 512 e gráfico de recursos
  1024 x 500 da Play em `mobile/assets/images/` e `mobile/store/`.
- Política de privacidade em `/privacidade` e página de exclusão de conta em
  `/app/excluir-conta` (vão para o ar com o deploy do servidor).
- Textos das lojas, respostas de privacidade e notas para o revisor em
  `mobile/store/textos.md`.

## 1. Antes de tudo: o servidor em produção

O app de loja fala com a API de produção. **Sem o deploy, o app abre sem encontros e
ninguém entra.** A hospedagem é a compartilhada da HostGator (sem CI):

1. Backup do banco pelo cPanel/phpMyAdmin.
2. Subir o código da `main` (depois do merge do PR) do jeito habitual.
3. `php artisan migrate` cria `participants`, `participant_login_codes` e
   `participant_push_tokens`. A `personal_access_tokens` é do próprio Sanctum: se o
   `migrate` já rodou antes com ele instalado, ela já existe; senão, entra agora.
   Nenhuma tabela existente muda.
4. No `.env` de produção (nenhuma é obrigatória para o site seguir igual):
   - `APP_REVIEW_EMAIL` e `APP_REVIEW_CODE`: a conta de revisão das lojas (seção 6);
   - `EXPO_ACCESS_TOKEN`: só se ligar a "enhanced push security" no Expo.
5. `php artisan config:clear` e `php artisan view:clear` (ou `config:cache`, se for o
   costume).
6. Conferir no cPanel o cron `schedule:run` a cada minuto: é ele que roda a fila, e o
   push vai pela fila.
7. Conferir no navegador: `/api/v1/events` responde JSON, `/privacidade` e
   `/app/excluir-conta` abrem, e o formulário `/inscricao` segue igual.

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

1. Escolher um e-mail fictício só para isso (ex.: `revisao.app@decasaemcasa.example`),
   sem nenhuma inscrição real, e um código aleatório de **pelo menos 12 números** (só
   números: o campo do app é numérico). Com menos, a conta de revisão fica desligada de
   propósito: o código fixo não tem contador de tentativas nem validade, e o limite por
   e-mail do login é a proteção dele.
2. Pôr os dois no `.env` de produção: `APP_REVIEW_EMAIL` e `APP_REVIEW_CODE`. Para esse
   e-mail nenhum e-mail sai e o código fixo vale.
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

1. Merge do PR e deploy do servidor (seção 1), com `APP_REVIEW_EMAIL` e
   `APP_REVIEW_CODE`.
2. Autorização da marca (seção 7).
3. Firebase e a chave FCM V1 (seção 2.1).
4. `eas build` e `eas submit` (seções 3 a 5).
5. Capturas de tela (roteiro em `mobile/store/textos.md`) no seu iPhone e no Android.
6. Ficha, privacidade e conta de revisão nas duas lojas.
