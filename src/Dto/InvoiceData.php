<?php

declare(strict_types=1);

namespace App\Dto;

final class InvoiceData
{
    public function __construct(
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $customerName,
        public readonly \DateTimeImmutable $date,
    ) {
    }
}
