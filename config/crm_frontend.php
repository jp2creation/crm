<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CRM Frontend Source Entries
    |--------------------------------------------------------------------------
    |
    | The CRM shell is now built from resources/frontend. The Adminex bundle is
    | still loaded as a legacy vendor artifact until the customized reservation
    | and equipment pages are migrated back to source.
    |
    */

    'vite_entries' => [
        'resources/frontend/crm/shell.ts',
    ],

    'legacy' => [
        'adminex_script' => 'assets/index-CqSzWeas.js',
        'adminex_stylesheet' => 'assets/index-CVBlw941.css',
    ],
];
