<?php

return [

    'name' => 'Flowdesk',

    'logo' => 'images/flowdesk-logo.svg',

    'login_visual' => 'images/flowdesk-login-visual.svg',

    'primary_merchant_email' => env('PRIMARY_MERCHANT_EMAIL', 'info@flowdesk.com'),

    'primary_merchant_website' => env('PRIMARY_MERCHANT_WEBSITE', 'https://flowdesk.app'),

    'legacy_merchant_email' => 'info@zgngreenpvt.com',

    'colors' => [
        'primary' => '#6366f1',
        'secondary' => '#64748b',
        'accent' => '#818cf8',
        'sidebar_dark' => '#0a0a0a',
        'shell_bg' => '#000000',
        'card_bg' => '#0b0f14',
        'success' => '#22c55e',
        'danger' => '#dc2626',
        'warning' => '#f59e0b',
        'default' => '#1e293b',
    ],

    'sidebar' => [
        'background' => '#0a0a0a',
        'surface' => '#111827',
        'text' => '#e2e8f0',
        'muted' => '#94a3b8',
        'active' => '#6366f1',
        'gradient_start' => '#6366f1',
        'gradient_mid' => '#7c3aed',
        'gradient_end' => '#818cf8',
        'icon' => '#94a3b8',
        'header' => '#64748b',
    ],

];
