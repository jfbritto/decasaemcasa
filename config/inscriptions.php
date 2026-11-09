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

    /*
     * Textos do formulário, num lugar só para a web (create.blade.php) e para
     * o app (GET /api/v1/config). Spec 2026-11-08-app-mobile-design, AM18.
     * Sem travessão (memória de copy do projeto).
     */
    'manifesto' => 'Esse encontro é sobre valorizar cada pessoa individualmente. Só é possível fazer uma inscrição de cada vez. Caso você queira levar mais alguém, uma nova inscrição será necessária. É bem rapidinho. Bora!',

    'terms' => [
        ['title' => 'Conduta', 'text' => 'Declaro que as informações são verdadeiras e estou ciente de que o evento ocorre em ambiente familiar, comprometendo-me a respeitar os valores e regras da casa.'],
        ['title' => 'Imagem e Som', 'text' => 'Autorizo o uso gratuito de minha imagem e voz captadas no evento para conteúdos da Turnê e do App.'],
        ['title' => 'Privacidade (LGPD)', 'text' => 'Autorizo o tratamento dos meus dados para logística, segurança e comunicações da turnê.'],
        ['title' => 'Contribuição', 'text' => 'Estou ciente de que, após a fase de curadoria, os aprovados serão convidados a enviar uma contribuição (via Pix) que ajuda a viabilizar esse encontro único. O valor mínimo é de R$ 100,00 por participante.'],
        ['title' => 'Inscrição individual', 'text' => 'Cada pessoa deve fazer sua própria inscrição, incluindo crianças e acompanhantes. Caso alguém queira te acompanhar, essa pessoa deve preencher a própria inscrição. Esse encontro é sobre valorizar cada pessoa individualmente!'],
    ],

    'terms_acceptance' => 'Declaro que as informações são verdadeiras. Estou ciente de que o evento ocorre em ambiente familiar, comprometendo-me a respeitar os valores e regras da casa. Autorizo o uso de minha imagem/voz e o tratamento de dados conforme a LGPD.',

    /*
     * Valor de referência da contribuição, em reais (o texto do Pix na página
     * de status e no app).
     */
    'reference_amount' => 100,

    'contribution_note' => 'Meu sonho era fazer uma bilheteria que subvertesse a lógica do entretenimento e nos colocasse no campo da arte, no fluxo da dádiva. Assim, essa é uma oportunidade para você deixar fluir a generosidade! Esse encontro tem valor inestimável e o preço é sugerido: R$ 100,00 (cem reais). Contribua conforme mandar seu coração, segundo as suas condições e recursos.',

];
