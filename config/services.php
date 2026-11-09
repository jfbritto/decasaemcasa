<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Twilio (WhatsApp)
    |--------------------------------------------------------------------------
    */
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
        'whatsapp_enabled' => env('WHATSAPP_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pix
    |--------------------------------------------------------------------------
    */
    'pix' => [
        'key' => env('PIX_KEY'),
        'holder' => env('PIX_HOLDER', 'Marcos Almeida'),
    ],

    /*
     * Push do app pelo serviço do Expo (spec 2026-11-08-app-mobile-design,
     * AM23). O token de acesso só é preciso com a "enhanced push security"
     * ligada na conta do Expo; vazio, os envios vão sem ele.
     */
    'expo' => [
        'access_token' => env('EXPO_ACCESS_TOKEN'),
    ],

    'support' => [
        'whatsapp_number' => env('SUPPORT_WHATSAPP_NUMBER', '+55 27 99820-1544'),
        'whatsapp_link' => env('SUPPORT_WHATSAPP_LINK', 'https://wa.me/5527998201544'),
    ],

];
