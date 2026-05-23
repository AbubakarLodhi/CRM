<?php

return [

    'enabled' => env('WHATSAPP_ENABLED', true),

    /*
    | api — Meta WhatsApp Cloud API
    | log — write messages to the application log (no external API)
    */
    'driver' => env('WHATSAPP_DRIVER', 'log'),

    /*
    | Business WhatsApp number that customers see as the sender (E.164).
    | Register this number in Meta Business → WhatsApp → API Setup, then copy
    | the "Phone number ID" (numeric) into WHATSAPP_PHONE_NUMBER_ID below.
    | The API does not accept the raw phone in the request; it uses phone_number_id.
    */
    'sender_phone' => env('WHATSAPP_SENDER_PHONE', '+923190240415'),

    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),

    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),

    'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),

    /*
    | When true, every outbound WhatsApp message is sent to WHATSAPP_TEST_PHONE
    | instead of the customer/vendor number (for safe testing).
    */
    'test_mode' => env('WHATSAPP_TEST_MODE', true),

    'test_phone' => env('WHATSAPP_TEST_PHONE', '+923461000454'),

];
