<?php

declare(strict_types=1);

namespace App\Tests\Parser;

use App\Entity\Currency;
use App\Exception\InvoiceImportException;
use App\Parser\JsonInvoiceParser;
use PHPUnit\Framework\TestCase;

final class JsonInvoiceParserTest extends TestCase
{
    private JsonInvoiceParser $parser;

    protected function setUp(): void
    {
        $this->parser = new JsonInvoiceParser();
    }

    public function testSupportsOnlyJsonExtension(): void
    {
        self::assertTrue($this->parser->supports('data/invoices.json'));
        self::assertTrue($this->parser->supports('data/INVOICES.JSON'));
        self::assertFalse($this->parser->supports('data/invoices.csv'));
        self::assertFalse($this->parser->supports('data/json-export.csv'));
    }

    public function testParseExtractsInvoices(): void
    {
        $invoices = iterator_to_array($this->parser->parse(__DIR__.'/../fixtures/json/invoices.json'));

        self::assertCount(2, $invoices);
        self::assertSame(67043, $invoices[0]->amount->minorUnits);
        self::assertSame(Currency::EUR, $invoices[0]->amount->currency);
        self::assertSame('Frank Green', $invoices[0]->customerName);
        self::assertSame('2025-02-03', $invoices[0]->date->format('Y-m-d'));
        self::assertSame("Jane O'Brien", $invoices[1]->customerName);
    }

    public function testParseRejectsUnknownCurrency(): void
    {
        $this->expectException(InvoiceImportException::class);
        $this->expectExceptionMessageMatches('/Unknown currency "XXX" at index 0/');

        iterator_to_array($this->parser->parse(__DIR__.'/../fixtures/json/unknown-currency.json'));
    }

    public function testParseRejectsInvalidJson(): void
    {
        $this->expectException(InvoiceImportException::class);
        $this->expectExceptionMessageMatches('/Invalid JSON/');

        iterator_to_array($this->parser->parse(__DIR__.'/../fixtures/json/invalid.json'));
    }

    public function testParseRejectsMissingKey(): void
    {
        $this->expectException(InvoiceImportException::class);
        $this->expectExceptionMessageMatches('/Missing key "nom" at index 0/');

        iterator_to_array($this->parser->parse(__DIR__.'/../fixtures/json/missing-key.json'));
    }

    public function testParseRejectsImpossibleDate(): void
    {
        $this->expectException(InvoiceImportException::class);
        $this->expectExceptionMessageMatches('/Invalid date "2025-02-30" at index 0/');

        iterator_to_array($this->parser->parse(__DIR__.'/../fixtures/json/bad-date.json'));
    }

    public function testParseRejectsUnreadableFile(): void
    {
        $this->expectException(InvoiceImportException::class);
        $this->expectExceptionMessageMatches('/Unable to read file/');

        iterator_to_array($this->parser->parse('/nonexistent/invoices.json'));
    }
}
