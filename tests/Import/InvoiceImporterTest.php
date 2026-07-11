<?php

declare(strict_types=1);

namespace App\Tests\Import;

use App\Dto\InvoiceData;
use App\Exception\InvoiceImportException;
use App\Import\InvoiceImporter;
use App\Parser\InvoiceFileParserInterface;
use App\Repository\InvoiceRepository;
use PHPUnit\Framework\TestCase;

final class InvoiceImporterTest extends TestCase
{
    public function testImportCountsInvoicesPerFile(): void
    {
        $importer = new InvoiceImporter([
            $this->parserYielding('json', [$this->invoice('John Doe'), $this->invoice('Jane Smith')]),
            $this->parserYielding('csv', [$this->invoice('Frank Green')]),
        ], $this->countingRepository());

        $result = $importer->import(['a.json', 'b.csv']);

        self::assertFalse($result->hasFailures());
        self::assertSame(3, $result->totalImported());
        self::assertSame(2, $result->files[0]->importedCount);
        self::assertSame(1, $result->files[1]->importedCount);
    }

    public function testUnsupportedFileDoesNotBlockOthers(): void
    {
        $importer = new InvoiceImporter([
            $this->parserYielding('csv', [$this->invoice('Frank Green')]),
        ], $this->countingRepository());

        $result = $importer->import(['invoices.xml', 'b.csv']);

        self::assertTrue($result->hasFailures());
        self::assertTrue($result->files[0]->isFailure());
        self::assertStringContainsString('No parser supports "invoices.xml"', $result->files[0]->error);
        self::assertSame(0, $result->files[0]->importedCount);
        self::assertSame(1, $result->files[1]->importedCount);
        self::assertSame(1, $result->totalImported());
    }

    public function testParserFailureIsReportedWithItsMessage(): void
    {
        $importer = new InvoiceImporter([
            $this->parserFailingAfter('csv', $this->invoice('Frank Green'), 'Invalid amount at line 2'),
        ], $this->countingRepository());

        $result = $importer->import(['a.csv']);

        self::assertTrue($result->hasFailures());
        self::assertSame('Invalid amount at line 2', $result->files[0]->error);
        self::assertSame(0, $result->files[0]->importedCount);
        self::assertSame(0, $result->totalImported());
    }

    public function testImportWithoutFilesReportsNothing(): void
    {
        $importer = new InvoiceImporter([], $this->countingRepository());

        $result = $importer->import([]);

        self::assertFalse($result->hasFailures());
        self::assertSame(0, $result->totalImported());
        self::assertSame([], $result->files);
    }

    private function countingRepository(): InvoiceRepository
    {
        $repository = $this->createMock(InvoiceRepository::class);
        $repository->method('updateAmounts')->willReturnCallback(
            static function (iterable $invoices): int {
                $updated = 0;
                foreach ($invoices as $invoice) {
                    ++$updated;
                }

                return $updated;
            },
        );

        return $repository;
    }

    /**
     * @param list<InvoiceData> $invoices
     */
    private function parserYielding(string $extension, array $invoices): InvoiceFileParserInterface
    {
        return new class($extension, $invoices) implements InvoiceFileParserInterface {
            /**
             * @param list<InvoiceData> $invoices
             */
            public function __construct(
                private readonly string $extension,
                private readonly array $invoices,
            ) {
            }

            public function supports(string $filePath): bool
            {
                return str_ends_with($filePath, '.'.$this->extension);
            }

            public function parse(string $filePath): iterable
            {
                yield from $this->invoices;
            }
        };
    }

    private function parserFailingAfter(string $extension, InvoiceData $invoice, string $message): InvoiceFileParserInterface
    {
        return new class($extension, $invoice, $message) implements InvoiceFileParserInterface {
            public function __construct(
                private readonly string $extension,
                private readonly InvoiceData $invoice,
                private readonly string $message,
            ) {
            }

            public function supports(string $filePath): bool
            {
                return str_ends_with($filePath, '.'.$this->extension);
            }

            public function parse(string $filePath): iterable
            {
                yield $this->invoice;

                throw new InvoiceImportException($this->message);
            }
        };
    }

    private function invoice(string $customerName): InvoiceData
    {
        return new InvoiceData(
            amount: 100.0,
            currency: 'EUR',
            customerName: $customerName,
            date: new \DateTimeImmutable('2025-02-03'),
        );
    }
}
