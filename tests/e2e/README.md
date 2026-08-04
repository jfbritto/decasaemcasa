# Testes E2E (Playwright)

Valida o fluxo completo do prazo de pagamento contra o ambiente local
(Docker + MailHog): inscrição pública, aprovação, contador, upload,
expiração pelo cron, faixas de prazo por proximidade do evento (1h/6h/24h),
pausa por solicitação social e auto-reload da página.

Os testes criam os próprios eventos e inscrições e removem tudo ao final
(incluindo e-mails do MailHog). Não use contra produção.

## Pré-requisitos

- Containers de dev no ar: `docker-compose up -d` (app em :8585, MailHog em :8028)
- Node 18+

## Rodando

```bash
cd tests/e2e
npm install
npx playwright install chromium   # primeira vez
npx playwright test               # headless
HEADED=1 SLOWMO=400 npx playwright test   # com navegador visível
```

O teste 7 (auto-reload) dura ~1,5 minuto por design: espera o contador zerar
e o reload automático de 75s acontecer de verdade.
