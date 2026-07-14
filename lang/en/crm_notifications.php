<?php

return [
    'invoice_reminder' => [
        'subject' => 'Invoice reminder: :invoice',
        'greeting' => 'Hello,',
        'body' => 'Invoice :invoice needs follow-up. Reminder delay: D+:days.',
        'action' => 'View invoice',
    ],
    'cash_receipt_archive' => [
        'subject' => 'Cash receipt archive',
        'body' => ':count cash receipt invoice line(s) were archived.',
    ],
];
