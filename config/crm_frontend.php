<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CRM Frontend Source Entries
    |--------------------------------------------------------------------------
    |
    | The CRM shell is built from resources/frontend. A reduced Adminex runtime
    | remains available only as a component library for transitional React chunks.
    |
    */

    'vite_entries' => [
        'resources/frontend/crm/shell.ts',
    ],

    'legacy' => [
        'adminex_stylesheet' => 'assets/legacy-adminex.css',
    ],
];
