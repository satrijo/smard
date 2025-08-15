<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp API Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk WhatsApp API gateway
    |
    */

    'api_url' => env('WHATSAPP_API_URL', 'https://wa-gw.rack.my.id/send-message'),
    
    'default_number' => env('WHATSAPP_DEFAULT_NUMBER', '6282111119138'),
    
    'group' => env('WHATSAPP_GROUP', true),
    
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Message Templates
    |--------------------------------------------------------------------------
    |
    | Template pesan untuk berbagai jenis notifikasi
    |
    */
    
    'templates' => [
        'warning' => [
            'emoji' => '🚨',
            'title' => 'AERODROME WARNING',
        ],
        'cancellation' => [
            'emoji' => '❌',
            'title' => 'PEMBATALAN AERODROME WARNING',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Enable/Disable WhatsApp Notifications
    |--------------------------------------------------------------------------
    |
    | Setel ke false untuk menonaktifkan notifikasi WhatsApp
    |
    */
    
    'enabled' => env('WHATSAPP_ENABLED', true),
];
