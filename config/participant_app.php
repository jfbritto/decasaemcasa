<?php

/*
 * App do participante (spec docs/superpowers/specs/2026-11-08-app-mobile-design.md).
 */
return [

    /*
     * Código de 6 dígitos do login (AM3 e AM4): quanto tempo vale e quantas
     * tentativas erradas ele aguenta antes de morrer.
     */
    'login_code_ttl_minutes' => 10,
    'login_code_max_attempts' => 5,

    /*
     * Conta de revisão das lojas (AM6): para este e-mail nenhum e-mail sai e
     * o código fixo vale. Sem as duas variáveis, não existe conta de revisão.
     */
    'review_email' => env('APP_REVIEW_EMAIL'),
    'review_code' => env('APP_REVIEW_CODE'),

];
