<?php

return [

    /*
     * Minutos que o inscrito aprovado tem para enviar o comprovante
     * antes de voltar automaticamente para a fila de espera.
     */
    'payment_deadline_minutes' => (int) env('INSCRIPTION_PAYMENT_DEADLINE_MINUTES', 60),

];
