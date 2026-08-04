<?php

return [

    /*
     * Minutos que o inscrito aprovado tem para enviar o comprovante
     * antes de voltar automaticamente para a fila de espera.
     *
     * O prazo cresce com a distância até o evento, em faixas no formato
     * "horasAteEvento:janelaEmMinutos" separadas por vírgula. A faixa vale
     * quando faltar MAIS que o número de horas indicado; caso nenhuma se
     * aplique (ou a inscrição não tenha evento), vale o prazo base.
     * Default: mais de 72h -> 24h; mais de 48h -> 6h; 48h ou menos -> 1h.
     */
    'payment_deadline_minutes' => (int) env('INSCRIPTION_PAYMENT_DEADLINE_MINUTES', 60),
    'payment_deadline_tiers' => (string) env('INSCRIPTION_PAYMENT_DEADLINE_TIERS', '72:1440,48:360'),

];
