<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Currency;
use App\Entity\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    /**
     * @dataProvider provideDecimalStrings
     */
    public function testFromDecimalStringConvertsToMinorUnits(string $amount, Currency $currency, int $expected): void
    {
        self::assertSame($expected, Money::fromDecimalString($amount, $currency)->minorUnits);
    }

    /**
     * @return iterable<string, array{string, Currency, int}>
     */
    public static function provideDecimalStrings(): iterable
    {
        yield 'two decimals' => ['670.43', Currency::EUR, 67043];
        yield 'one decimal padded' => ['12.3', Currency::EUR, 1230];
        yield 'no decimals' => ['12', Currency::USD, 1200];
        yield 'cents only' => ['0.05', Currency::EUR, 5];
        yield 'negative' => ['-12.34', Currency::EUR, -1234];
        yield 'uniform two decimals for JPY' => ['572.52', Currency::JPY, 57252];
    }

    /**
     * @dataProvider provideInvalidAmounts
     */
    public function testFromDecimalStringRejectsInvalidAmounts(string $amount, Currency $currency): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::fromDecimalString($amount, $currency);
    }

    /**
     * @return iterable<string, array{string, Currency}>
     */
    public static function provideInvalidAmounts(): iterable
    {
        yield 'not a number' => ['not-a-number', Currency::EUR];
        yield 'comma separator' => ['12,34', Currency::EUR];
        yield 'scientific notation' => ['1e3', Currency::EUR];
        yield 'too many decimals' => ['12.345', Currency::EUR];
    }

    public function testEquals(): void
    {
        self::assertTrue((new Money(1000, Currency::EUR))->equals(new Money(1000, Currency::EUR)));
        self::assertFalse((new Money(1000, Currency::EUR))->equals(new Money(1001, Currency::EUR)));
        self::assertFalse((new Money(1000, Currency::EUR))->equals(new Money(1000, Currency::USD)));
    }
}
