# Textos das lojas

App **De Casa em Casa**. Sem travessão e com "seu lugar na sala", como no resto do
projeto. Conferir os limites de caracteres na hora de colar (os consoles contam).

## 1. Identidade

| Campo | Texto |
|---|---|
| Nome (App Store, até 30) | De Casa em Casa |
| Subtítulo (App Store, até 30) | Encontros em casas reais |
| Título (Play, até 30) | De Casa em Casa |
| Descrição curta (Play, até 80) | Inscreva-se nos encontros da turnê e acompanhe tudo até a confirmação. |
| Categoria App Store | Estilo de vida (principal), Música (secundária) |
| Categoria Play | Eventos |
| Classificação | Livre / 4+ (sem violência, sem conteúdo adulto, sem jogos, sem conteúdo de usuários visível a outros) |
| Preço | Gratuito, sem compras no app, sem anúncios |
| URL de suporte | https://decasaemcasa.omarcosalmeida.com.br/inscricao |
| URL da política de privacidade | https://decasaemcasa.omarcosalmeida.com.br/privacidade |
| URL de exclusão de conta (Play) | https://decasaemcasa.omarcosalmeida.com.br/app/excluir-conta |

**Palavras-chave (App Store, até 100):**
`turnê,encontro,música,sarau,show em casa,acústico,inscrição,casa,experiência,curadoria`

Não use o nome do artista nas palavras-chave sem a autorização da seção 7 do roteiro
(`docs/infra/publicacao-lojas.md`).

**Texto promocional (App Store, até 170):**
Uma turnê que acontece onde a vida acontece. Escolha a cidade, conte sua história e
acompanhe sua inscrição até o seu lugar na sala.

## 2. Descrição completa (as duas lojas)

De Casa em Casa é uma turnê que acontece dentro de casas reais, com pessoas reais.
Cada encontro reúne um grupo pequeno ao redor da música e de boas conversas, num
endereço que só os confirmados conhecem.

Com o app você:

- vê os próximos encontros, com a cidade, a data e as vagas;
- faz sua inscrição contando a sua história;
- acompanha cada etapa: curadoria, aprovação, contribuição e confirmação;
- recebe um aviso no celular assim que a equipe aprovar ou confirmar sua participação;
- copia a chave Pix e envia o comprovante pela câmera, pela galeria ou em PDF, dentro do
  prazo, com o tempo restante na tela;
- pede uma contribuição social, se não puder contribuir com o valor de referência;
- vê o endereço e o horário do encontro quando seu lugar na sala estiver garantido.

Sem senha: você entra com o seu e-mail e um código de 6 números. As inscrições feitas
pelo site com o mesmo e-mail aparecem no app.

A inscrição é individual: cada pessoa faz a sua, inclusive crianças e acompanhantes.

## 3. Imagens das lojas (prontas)

Mesmo padrão do HelpCheck e do TreinaEdu: a tela REAL do app sobre o terracota da marca,
com uma frase curta em cima. As telas foram gravadas contra o banco fictício "Velunora"
(Maria Velunora, e-mails `.example`, endereço fictício): nada de participante real.

| Arquivo | Onde vai |
|---|---|
| `store/app-store/capturas-1206x2622/` | App Store Connect, "iPhone com Dynamic Island (tela média)", o quadro que ele pede primeiro |
| `store/app-store/capturas-6.9/` (1320 x 2868) | App Store Connect, iPhone 6,9" |
| `store/app-store/cabecalho-5244x2950.png` | App Store Connect, "Cabeçalho e resultados de busca" (16:9) |
| `store/app-store/icone-1024.png` | Só se pedirem: o ícone da App Store vem do build |
| `store/google-play/capturas/` (1080 x 1920) | Play Console, Capturas de tela do telefone |
| `store/google-play/grafico-recursos-1024x500.png` | Play Console, Recurso gráfico |
| `store/google-play/icone-512.png` | Play Console, Ícone do app |

As 7 telas, na mesma ordem nas duas lojas, e a frase de cada uma:

1. **Encontros** (`01-encontros`): "Os encontros da turnê no seu celular".
2. **O encontro** (`02-encontro`): "Saiba como funciona cada encontro".
3. **Inscrição, manifesto** (`03-inscricao`): "Conte sua história na inscrição".
4. **Aprovada, prazo e Pix** (`04-aprovada`): "O prazo e o Pix na mesma tela".
5. **Confirmada, endereço** (`05-confirmada`): "Seu lugar na sala, com endereço e horário".
6. **Avisos** (`06-avisos`): "Os avisos da equipe chegam na hora".
7. **Minhas inscrições** (`07-inscricoes`): "O status de cada inscrição à mão".

Na App Store as capturas ficam presas à versão: trocar exige uma versão nova em revisão.
No Google Play dá para trocar a qualquer hora.

### Capturas novas

As telas cruas ficam em `store/originais/ios` (simulador do iPhone 17 Pro Max, 1320 x 2868)
e `store/originais/android` (emulador, 1080 x 2340). A App Store usa as do iPhone e o
Google Play, as do Android: a Apple recusa imagem com interface de outra plataforma.

1. Um banco só com dados fictícios e a API local apontada para ele (servir de dentro de
   `public/` com `php -S`, porque o `artisan serve` não repassa o `APP_ENV`).
2. O app no Expo Go (`npx expo start --lan`) com o "Tools button" desligado no menu do
   Expo Go (no iOS, a chave só vira arrastando, não com toque).
3. Barra de status limpa:
   - iOS: `xcrun simctl status_bar booted override --time 9:41 --batteryState discharging --batteryLevel 100 --wifiBars 3 --cellularBars 4 --dataNetwork wifi`
     (com `charged` a bateria aparece verde, carregando).
   - Android: modo de demonstração (`sysui_demo_allowed` e o broadcast
     `com.android.systemui.demo` com relógio 0941, bateria 100, wifi cheio e sem
     notificações).
4. Gravar cada tela com o nome da lista acima: `xcrun simctl io booted screenshot` no iOS
   e `adb exec-out screencap -p` no Android. Espere a barra de rolagem sumir antes.
5. Da pasta `mobile/`: `python3 scripts/compor-capturas.py` (precisa do Pillow; as frases
   estão no script) e conferir todas antes de subir.

## 4. Privacidade

### Apple (Privacidade do app)

Coleta de dados: **sim**. Nada é usado para rastreamento. Tudo ligado à identidade da
pessoa, com a finalidade **Funcionalidade do app**:

| Tipo de dado | Itens |
|---|---|
| Informações de contato | Nome, e-mail, telefone (WhatsApp) |
| Conteúdo do usuário | Fotos (só o comprovante que a pessoa escolhe), outro conteúdo (a história da inscrição e a justificativa da contribuição social) |
| Informações financeiras | Outras informações financeiras (o comprovante do Pix e o valor proposto) |
| Outros dados | CPF, data de nascimento, bairro e cidade, Instagram (opcional) |

Identificadores: o token de push do aparelho não é usado para rastreamento nem para
publicidade.

### Google (Segurança dos dados)

- Coleta dados: **sim**. Compartilha com terceiros: **não** (os provedores do serviço,
  como hospedagem, e-mail e push, não contam como compartilhamento).
- Criptografia em trânsito: **sim** (HTTPS).
- A pessoa pode pedir a exclusão: **sim** (no app, em Perfil, e pela URL de exclusão).
- Dados coletados, todos **obrigatórios** para a funcionalidade, salvo onde indicado:
  - Informações pessoais: nome, e-mail, telefone, outras informações (CPF, data de
    nascimento, cidade; Instagram opcional);
  - Informações financeiras: outras informações financeiras (comprovante e valor);
  - Fotos e vídeos: fotos (opcional, só o comprovante escolhido);
  - Arquivos e documentos: o comprovante em PDF (opcional);
  - Mensagens e conteúdo: outro conteúdo gerado pelo usuário (a história);
  - IDs do dispositivo ou outros IDs: o token de push (opcional, só com os avisos
    ativados).

## 5. Notas para o revisor (em inglês, as duas lojas)

```text
De Casa em Casa is a music tour that happens inside real homes. The app lets
participants browse upcoming gatherings, apply with a short personal story, follow
the manual curation, send the payment receipt (Brazilian Pix) within the deadline
and, once confirmed, see the secret address.

Login has no password: the user types an e-mail and receives a 6-digit code.
For review, use:
  E-mail: <APP_REVIEW_EMAIL>
  Code:   <APP_REVIEW_CODE>   (12+ digits: type all of them, then tap "Entrar")
(the code above always works for this e-mail; no e-mail is sent)

How to test:
1. "Encontros" tab: open a gathering and tap "Quero participar" (apply).
2. Fill the form (any valid Brazilian CPF, e.g. 529.982.247-25) and accept the terms.
3. "Minhas inscrições" shows the application as "Em curadoria" (under manual review).
   Our team approves applications manually; during the review we will approve yours so
   you can see the payment step (Pix key, countdown and receipt upload).
4. "Perfil" has "Excluir minha conta" (account deletion) and the privacy policy.

The app has no purchases: the contribution is paid outside the app by Pix and only the
receipt is uploaded. Notifications are optional and only report status changes.
```

## 6. Novidades da versão 1.0.0

Primeira versão: encontros, inscrição, acompanhamento, comprovante, contribuição social
e avisos no celular.
