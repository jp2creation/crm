<?php

declare(strict_types=1);

use Brick\Math\RoundingMode;

return [
    'default_currency' => 'EUR',

    /**
     * Rounding mode used when parsing money
     */
    'rounding_mode' => RoundingMode::HalfUp,
];
