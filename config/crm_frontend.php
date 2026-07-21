<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CRM Frontend Source Entries
    |--------------------------------------------------------------------------
    |
    | The CRM shell is built from resources/frontend. The legacy Adminex runtime
    | is published from resources/frontend/static/assets while Reservations and
    | Equipment Rentals finish their migration into versioned TypeScript source.
    |
    */

    'vite_entries' => [
        'resources/frontend/crm/shell.ts',
    ],

    'legacy' => [
        'adminex_script' => 'assets/legacy-adminex-entry.js',
        'adminex_stylesheet' => 'assets/legacy-adminex.css',
    ],
];
