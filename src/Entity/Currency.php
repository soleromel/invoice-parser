<?php

declare(strict_types=1);

namespace App\Entity;

enum Currency: string
{
    case EUR = 'EUR';
    case GBP = 'GBP';
    case JPY = 'JPY';
    case USD = 'USD';
}
