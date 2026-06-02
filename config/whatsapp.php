<?php

return [

    'enabled' => env('WHATSAPP_ENABLED', true),

    /*
    | api    — Meta WhatsApp Cloud API (WHATSAPP_ACCESS_TOKEN + WHATSAPP_PHONE_NUMBER_ID)
    | twilio — Twilio WhatsApp API (TWILIO_SID + TWILIO_TOKEN + TWILIO_WHATSAPP_FROM)
    | log    — write messages to storage/logs only (no delivery)
    */
    'driver' => env('WHATSAPP_DRIVER', 'log'),

    'sender_phone' => env('WHATSAPP_SENDER_PHONE', '+14155238886'),

    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),

    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),

    'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),

    'test_mode' => env('WHATSAPP_TEST_MODE', true),

    'test_phone' => env('WHATSAPP_TEST_PHONE', '+923461000454'),

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM', env('WHATSAPP_SENDER_PHONE', '+14155238886')),
    ],

];
