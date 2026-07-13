<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class Money
{
    private const DECIMALS = 2;
    private const DECIMAL_NUMBER_PATTERN = '/^(?<units>-?\d+)(\.(?<fraction>\d+))?$/';

    public function __construct(
        #[ORM\Column(type: 'bigint')]
        public int $minorUnits,
        #[ORM\Column(length: 3, enumType: Currency::class)]
        public Currency $currency,
    ) {
    }

    public static function fromDecimalString(string $amount, Currency $currency): self
    {
        $isDecimalNumber = 1 === preg_match(self::DECIMAL_NUMBER_PATTERN, $amount, $digits);
        if (!$isDecimalNumber) {
            throw new \InvalidArgumentException(sprintf('Invalid amount "%s".', $amount));
        }

        $units = $digits['units'];
        $fraction = $digits['fraction'] ?? '';

        if (strlen($fraction) > self::DECIMALS) {
            throw new \InvalidArgumentException(sprintf('Amount "%s" has too many decimals for %s.', $amount, $currency->value));
        }

        $minorUnits = (int) ($units.str_pad($fraction, self::DECIMALS, '0'));

        return new self($minorUnits, $currency);
    }

    public function equals(self $other): bool
    {
        return $this->minorUnits === $other->minorUnits && $this->currency === $other->currency;
    }
}
