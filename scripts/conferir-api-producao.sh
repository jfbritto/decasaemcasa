#!/usr/bin/env bash
# Confere a API do app depois do deploy (docs/infra/publicacao-lojas.md, seção 1).
#
#   scripts/conferir-api-producao.sh [URL]
#
# Abre as rotas públicas, entra com a conta de revisão (APP_REVIEW_EMAIL e
# APP_REVIEW_CODE do .env de produção) e confere se o servidor repassa o
# cabeçalho Authorization ao Laravel: em hospedagem com Apache, sem a regra
# do .htaccess, o login funciona mas toda chamada seguinte volta 401.
# O código é pedido sem aparecer na tela, e nem ele nem o token são impressos.
# No fim, sai da conta (o token é apagado no servidor).
set -u

BASE="${1:-https://decasaemcasa.omarcosalmeida.com.br}"
FALHAS=0

confere() { # descrição, código esperado, código obtido
  if [ "$3" = "$2" ]; then
    echo "ok     $1"
  else
    echo "FALHA  $1 (esperado $2, veio $3)"
    FALHAS=1
  fi
}
status() { curl -s -o /dev/null -w '%{http_code}' --max-time 20 -H 'Accept: application/json' "$@"; }

echo "Conferindo $BASE"
confere "formulário da web (/inscricao)" 200 "$(status "$BASE/inscricao")"
confere "encontros do app (/api/v1/events)" 200 "$(status "$BASE/api/v1/events")"
confere "configuração do app (/api/v1/config)" 200 "$(status "$BASE/api/v1/config")"
confere "política de privacidade (/privacidade)" 200 "$(status "$BASE/privacidade")"
confere "exclusão de conta (/app/excluir-conta)" 200 "$(status "$BASE/app/excluir-conta")"

echo
read -r -p "E-mail da conta de revisão: " EMAIL
read -r -s -p "Código da conta de revisão (não aparece): " CODIGO
echo

RESPOSTA=$(EMAIL="$EMAIL" CODIGO="$CODIGO" python3 -c '
import json, os
print(json.dumps({"email": os.environ["EMAIL"], "code": os.environ["CODIGO"], "device_name": "conferencia-deploy"}))
' | curl -s --max-time 20 -w '\n%{http_code}' -H 'Accept: application/json' -H 'Content-Type: application/json' \
  --data @- "$BASE/api/v1/auth/verify")
unset CODIGO
CODIGO_HTTP=$(printf '%s' "$RESPOSTA" | tail -n 1)
confere "login da conta de revisão" 200 "$CODIGO_HTTP"

if [ "$CODIGO_HTTP" != "200" ]; then
  echo "       Confira APP_REVIEW_EMAIL e APP_REVIEW_CODE (12 números ou mais) no .env"
  echo "       e rode php artisan config:clear."
  exit 1
fi

TOKEN=$(printf '%s' "$RESPOSTA" | sed '$d' | python3 -c 'import json, sys; print(json.load(sys.stdin)["token"])')
AUTH="Authorization: Bearer $TOKEN"

ME=$(status -H "$AUTH" "$BASE/api/v1/me")
confere "chamada com login (/api/v1/me)" 200 "$ME"
if [ "$ME" = "401" ]; then
  echo "       O servidor está descartando o cabeçalho Authorization: falta a regra"
  echo "       no .htaccess (docs/infra/publicacao-lojas.md, seção 1)."
fi
confere "minhas inscrições (/api/v1/inscriptions)" 200 "$(status -H "$AUTH" "$BASE/api/v1/inscriptions")"
confere "avisos (/api/v1/notifications)" 200 "$(status -H "$AUTH" "$BASE/api/v1/notifications")"

SAIDA=$(status -X POST -H "$AUTH" "$BASE/api/v1/auth/logout")
case "$SAIDA" in
  200|204) echo "ok     saiu da conta (token apagado)" ;;
  *) echo "FALHA  sair da conta (veio $SAIDA)"; FALHAS=1 ;;
esac

echo
if [ "$FALHAS" = "0" ]; then echo "Tudo certo: a API do app está no ar."; else echo "Há falhas acima."; fi
exit "$FALHAS"
