<?php

return [

    /*
     * Minutos que o inscrito aprovado tem para enviar o comprovante
     * antes de voltar automaticamente para a fila de espera.
     * Regra: eventos a mais de "threshold_hours" horas usam o prazo
     * estendido; eventos próximos (ou inscrição sem evento) usam o base.
     */
    'payment_deadline_minutes' => (int) env('INSCRIPTION_PAYMENT_DEADLINE_MINUTES', 60),
    'payment_deadline_extended_minutes' => (int) env('INSCRIPTION_PAYMENT_DEADLINE_EXTENDED_MINUTES', 360),
    'payment_deadline_threshold_hours' => (int) env('INSCRIPTION_PAYMENT_DEADLINE_THRESHOLD_HOURS', 48),

];
