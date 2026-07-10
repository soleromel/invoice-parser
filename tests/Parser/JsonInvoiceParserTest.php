<?php

declare(strict_types=1);

namespace App\Tests\Parser;

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
        $invoices = iterator_to_array($this->parser->parse(__DIR__.'/../fixtures/invoices.json'));

        self::assertCount(2, $invoices);
        self::assertSame(670.43, $invoices[0]->amount);
        self::assertSame('EUR', $invoices[0]->currency);
        self::assertSame('Frank Green', $invoices[0]->customerName);
        self::assertSame('2025-02-03', $invoices[0]->date->format('Y-m-d'));
        self::assertSame("Jane O'Brien", $invoices[1]->customerName);
    }

    public function testParseRejectsInvalidJson(): void
    {
        $this->expectException(InvoiceImportException::class);
        $this->expectExceptionMessageMatches('/Invalid JSON/');

        iterator_to_array($this->parser->parse(__DIR__.'/../fixtures/invalid.json'));
    }

    public function testParseRejectsMissingKey(): void
    {
        $this->expectException(InvoiceImportException::class);
        $this->expectExceptionMessageMatches('/Missing key "nom" at index 0/');

        iterator_to_array($this->parser->parse(__DIR__.'/../fixtures/missing-key.json'));
    }

    public function testParseRejectsUnreadableFile(): void
    {
        $this->expectException(InvoiceImportException::class);
        $this->expectExceptionMessageMatches('/Unable to read file/');

        iterator_to_array($this->parser->parse('/nonexistent/invoices.json'));
    }
}
