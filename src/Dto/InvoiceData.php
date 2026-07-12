<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Money;

final class InvoiceData
{
    public function __construct(
        public readonly Money $amount,
        public readonly string $customerName,
        public readonly \DateTimeImmutable $date,
    ) {
    }
}
