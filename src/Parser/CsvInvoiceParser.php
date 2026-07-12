<?php

declare(strict_types=1);

namespace App\Parser;

use App\Dto\InvoiceData;
use App\Entity\Currency;
use App\Entity\Money;
use App\Exception\InvoiceImportException;

final class CsvInvoiceParser implements InvoiceFileParserInterface
{
    private const DELIMITER = "\t";
    private const COLUMNS = 4;

    public function supports(string $filePath): bool
    {
        return 'csv' === strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    }

    public function parse(string $filePath): iterable
    {
        if (!is_readable($filePath)) {
            throw new InvoiceImportException(sprintf('Unable to read file "%s".', $filePath));
        }

        $file = new \SplFileObject($filePath);
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl(self::DELIMITER);

        foreach ($file as $line => $row) {
            if ([null] === $row || false === $row) {
                continue;
            }

            yield $this->mapRow($row, $line + 1, $filePath);
        }
    }

    private function mapRow(array $row, int $line, string $filePath): InvoiceData
    {
        if (self::COLUMNS !== count($row)) {
            throw new InvoiceImportException(sprintf('Expected %d columns at line %d in "%s", got %d.', self::COLUMNS, $line, $filePath, count($row)));
        }

        [$amount, $currency, $customerName, $date] = $row;

        $parsedCurrency = Currency::tryFrom((string) $currency);
        if (null === $parsedCurrency) {
            throw new InvoiceImportException(sprintf('Unknown currency "%s" at line %d in "%s".', $currency, $line, $filePath));
        }

        try {
            $parsedAmount = Money::fromDecimalString((string) $amount, $parsedCurrency);
        } catch (\InvalidArgumentException $e) {
            throw new InvoiceImportException(sprintf('Invalid amount "%s" at line %d in "%s": %s', $amount, $line, $filePath, $e->getMessage()), previous: $e);
        }

        $parsedDate = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $date);
        if (false === $parsedDate || $parsedDate->format('Y-m-d') !== $date) {
            throw new InvoiceImportException(sprintf('Invalid date "%s" at line %d in "%s".', $date, $line, $filePath));
        }

        return new InvoiceData(
            amount: $parsedAmount,
            customerName: (string) $customerName,
            date: $parsedDate,
        );
    }
}
