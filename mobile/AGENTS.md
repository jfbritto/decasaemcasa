App Expo/React Native do participante do De Casa em Casa. Spec:
`docs/superpowers/specs/2026-11-08-app-mobile-design.md` (decisões AM1 a AM40).
Mesma base do app do HelpCheck (`~/Projetos/helpcheck/mobile`).

## Expo mudou: não confie na memória

O Expo quebra APIs a cada SDK. Antes de mexer numa API do Expo, EAS ou React Native:

1. Veja a versão do `expo` no `package.json` (hoje, SDK 57).
2. Leia a documentação da versão: `https://docs.expo.dev/versions/v<major>.0.0/`.
3. Para o resto, `https://docs.expo.dev/llms.txt`.

## Comandos

```bash
npx expo install <pacote>   # SEMPRE no lugar de npm install: resolve a versão do SDK
npx expo start --port 8083  # Metro (Expo Go); EXPO_PUBLIC_API_URL em mobile/.env
npm test                    # Jest
npx tsc --noEmit            # tipos
npx expo lint               # lint
npx expo-doctor             # dependências e config
```

Rode lint, tipos e testes antes de dizer que terminou.

## Regras

- **Nenhuma regra de negócio vive só no app.** O estado da inscrição, o que pode ser
  feito (`can`), o Pix e o endereço vêm decididos da API (`App\Inscriptions\InscriptionView`).
  A única regra duplicada de propósito é a validação do CPF, presa pelos mesmos casos
  dos dois lados em `tests/fixtures/cpf-cases.json` (PHPUnit e Jest).
- **Rotas em `src/app/`** (Expo Router). Componentes, serviços e regras ficam fora dela.
  Telas que pedem conta ficam no `Stack.Protected` do `src/app/_layout.tsx`.
- **Copy:** sem travessão (— ou –), "seu lugar na sala" (nunca "cadeira"), e o WhatsApp
  é só contato: nada de "avisaremos pelo WhatsApp". O `copy-architecture.test.ts` recusa.
- **Cor da marca (terracota) não representa estado:** os tons de estado estão em
  `theme/colors.ts` (`tones`), nas cores da página de status da web.
- **Datas** sempre no horário de Brasília (`lib/format.ts`, UTC-3 fixo).
- **Push** nunca em Expo Go nem simulador (`services/push.ts`); permissão só com toque.
- `ios/` e `android/` são gerados (CNG): nunca edite à mão, configure no `app.json`.
- Dados de teste sempre fictícios ("Velunora").

## Builds e lojas

EAS na conta `jfbritto0`, projeto `@jfbritto0/decasaemcasa`. Passo a passo em
`docs/infra/publicacao-lojas.md`.
