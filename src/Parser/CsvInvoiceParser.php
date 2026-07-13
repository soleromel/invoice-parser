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
    private const EXPECTED_COLUMN_COUNT = 4;
    private const DATE_FORMAT = 'Y-m-d';

    public function supports(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return 'csv' === $extension;
    }

    public function parse(string $filePath): iterable
    {
        if (!is_readable($filePath)) {
            throw new InvoiceImportException(sprintf('Unable to read file "%s".', $filePath));
        }

        $file = new \SplFileObject($filePath);
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl(self::DELIMITER);

        foreach ($file as $lineIndex => $row) {
            $isBlankLine = [null] === $row || false === $row;
            if ($isBlankLine) {
                continue;
            }

            $lineNumber = $lineIndex + 1;

            yield $this->mapRow($row, $lineNumber, $filePath);
        }
    }

    private function mapRow(array $row, int $lineNumber, string $filePath): InvoiceData
    {
        if (self::EXPECTED_COLUMN_COUNT !== count($row)) {
            throw new InvoiceImportException(sprintf('Expected %d columns at line %d in "%s", got %d.', self::EXPECTED_COLUMN_COUNT, $lineNumber, $filePath, count($row)));
        }

        [$rawAmount, $rawCurrency, $customerName, $rawDate] = $row;

        $currency = Currency::tryFrom((string) $rawCurrency);
        if (null === $currency) {
            throw new InvoiceImportException(sprintf('Unknown currency "%s" at line %d in "%s".', $rawCurrency, $lineNumber, $filePath));
        }

        try {
            $amount = Money::fromDecimalString((string) $rawAmount, $currency);
        } catch (\InvalidArgumentException $e) {
            throw new InvoiceImportException(sprintf('Invalid amount "%s" at line %d in "%s": %s', $rawAmount, $lineNumber, $filePath, $e->getMessage()), previous: $e);
        }

        $date = \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, (string) $rawDate);
        $isRealCalendarDate = false !== $date && $date->format(self::DATE_FORMAT) === $rawDate;
        if (!$isRealCalendarDate) {
            throw new InvoiceImportException(sprintf('Invalid date "%s" at line %d in "%s".', $rawDate, $lineNumber, $filePath));
        }

        return new InvoiceData(
            amount: $amount,
            customerName: (string) $customerName,
            date: $date,
        );
    }
}
