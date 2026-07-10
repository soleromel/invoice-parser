<?php

declare(strict_types=1);

namespace App\Tests\Parser;

use App\Exception\InvoiceImportException;
use App\Parser\CsvInvoiceParser;
use PHPUnit\Framework\TestCase;

final class CsvInvoiceParserTest extends TestCase
{
    private CsvInvoiceParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CsvInvoiceParser();
    }

    public function testSupportsOnlyCsvExtension(): void
    {
        self::assertTrue($this->parser->supports('data/invoices.csv'));
        self::assertTrue($this->parser->supports('data/INVOICES.CSV'));
        self::assertFalse($this->parser->supports('data/invoices.json'));
        self::assertFalse($this->parser->supports('data/csv-export.json'));
    }

    public function testParseExtractsInvoices(): void
    {
        $invoices = iterator_to_array($this->parser->parse(__DIR__.'/../fixtures/invoices.csv'), false);

        self::assertCount(2, $invoices);
        self::assertSame(670.43, $invoices[0]->amount);
        self::assertSame('EUR', $invoices[0]->currency);
        self::assertSame('Frank Green', $invoices[0]->customerName);
        self::assertSame('2025-02-03', $invoices[0]->date->format('Y-m-d'));
        self::assertSame("Jane O'Brien", $invoices[1]->customerName);
    }

    public function testParseYieldsNothingForEmptyFile(): void
    {
        $invoices = iterator_to_array($this->parser->parse(__DIR__.'/../fixtures/empty.csv'), false);

        self::assertSame([], $invoices);
    }

    public function testParseRejectsInvalidAmount(): void
    {
        $this->expectException(InvoiceImportException::class);
        $this->expectExceptionMessageMatches('/Invalid amount "not-a-number" at line 1/');

        iterator_to_array($this->parser->parse(__DIR__.'/../fixtures/bad-amount.csv'), false);
    }

    public function testParseRejectsWrongDelimiter(): void
    {
        $this->expectException(InvoiceImportException::class);
        $this->expectExceptionMessageMatches('/Expected 4 columns at line 1 .* got 1/');

        iterator_to_array($this->parser->parse(__DIR__.'/../fixtures/semicolon.csv'), false);
    }

    public function testParseRejectsTooManyColumns(): void
    {
        $this->expectException(InvoiceImportException::class);
        $this->expectExceptionMessageMatches('/Expected 4 columns at line 1 .* got 5/');

        iterator_to_array($this->parser->parse(__DIR__.'/../fixtures/too-many-columns.csv'), false);
    }

    public function testParseRejectsImpossibleDate(): void
    {
        $this->expectException(InvoiceImportException::class);
        $this->expectExceptionMessageMatches('/Invalid date "2025-02-30" at line 1/');

        iterator_to_array($this->parser->parse(__DIR__.'/../fixtures/bad-date.csv'), false);
    }

    public function testParseRejectsUnreadableFile(): void
    {
        $this->expectException(InvoiceImportException::class);
        $this->expectExceptionMessageMatches('/Unable to read file/');

        iterator_to_array($this->parser->parse('/nonexistent/invoices.csv'), false);
    }
}
