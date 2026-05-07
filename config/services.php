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

    'support' => [
        'whatsapp_number' => env('SUPPORT_WHATSAPP_NUMBER', '+55 27 99820-1544'),
        'whatsapp_link' => env('SUPPORT_WHATSAPP_LINK', 'https://wa.me/5527998201544'),
    ],

];
