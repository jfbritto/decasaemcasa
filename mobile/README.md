# App De Casa em Casa

O app do participante da turnê **De Casa em Casa**, para iOS e Android: os encontros
abertos, a inscrição, a situação de cada inscrição, o comprovante do Pix, a
contribuição social e, para quem foi confirmado, o endereço e o horário. Avisos no
celular a cada mudança feita pela equipe.

- Spec: [`docs/superpowers/specs/2026-11-08-app-mobile-design.md`](../docs/superpowers/specs/2026-11-08-app-mobile-design.md)
- Publicação nas lojas: [`docs/infra/publicacao-lojas.md`](../docs/infra/publicacao-lojas.md)
- Textos das lojas: [`store/textos.md`](store/textos.md)

## Rodar

```bash
cd mobile
cp .env.example .env        # EXPO_PUBLIC_API_URL: o servidor (Docker local: http://localhost:8585)
npm install
npx expo start --port 8083  # abra no Expo Go
```

No celular de verdade, use o IP da sua máquina na rede no `EXPO_PUBLIC_API_URL`.
Para entrar sem caixa de e-mail, rode o Docker do projeto: o código chega no MailHog
(http://localhost:8028).

O push não funciona no Expo Go: precisa de um build (`eas build --profile preview`).

## Testes

```bash
npm test            # Jest: regras, cliente HTTP, sessão e telas
npx tsc --noEmit
npx expo lint
```

## Estrutura

```text
src/app/          rotas (Expo Router): (tabs) Encontros, Minhas inscrições e Perfil;
                  events/[id], events/[id]/register, inscriptions/[id], inscriptions/[id]/social, login
src/components/   telas em pedaços (cartões, prazo, Pix, endereço) e ui/ (texto, botão, campo)
src/domain/       regras puras: CPF, máscaras, formulário, textos de cada estado
src/lib/          datas, cliente do React Query, sessão no chaveiro, escolha do comprovante
src/services/     a API v1 (/api/v1) e o push do Expo
src/stores/       a sessão (zustand)
src/theme/        cores, fontes e espaçamentos
scripts/          generate-icons.mjs (ícones, splash e artes da Play)
store/            artes e textos das lojas
```
