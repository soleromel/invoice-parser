<?php

declare(strict_types=1);

namespace App\Parser;

use App\Dto\InvoiceData;
use App\Entity\Currency;
use App\Entity\Money;
use App\Exception\InvoiceImportException;

final class JsonInvoiceParser implements InvoiceFileParserInterface
{
    private const REQUIRED_KEYS = ['montant', 'devise', 'nom', 'date'];
    private const DATE_FORMAT = 'Y-m-d';

    public function supports(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return 'json' === $extension;
    }

    public function parse(string $filePath): iterable
    {
        $content = @file_get_contents($filePath);
        if (false === $content) {
            throw new InvoiceImportException(sprintf('Unable to read file "%s".', $filePath));
        }

        try {
            $rows = json_decode($content, true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvoiceImportException(sprintf('Invalid JSON in "%s": %s', $filePath, $e->getMessage()), previous: $e);
        }

        if (!is_array($rows)) {
            throw new InvoiceImportException(sprintf('Expected a list of invoices in "%s".', $filePath));
        }

        foreach ($rows as $index => $row) {
            yield $this->mapRow($row, $index, $filePath);
        }
    }

    private function mapRow(mixed $row, int|string $index, string $filePath): InvoiceData
    {
        if (!is_array($row)) {
            throw new InvoiceImportException(sprintf('Invalid invoice at index %s in "%s".', $index, $filePath));
        }

        foreach (self::REQUIRED_KEYS as $requiredKey) {
            if (!isset($row[$requiredKey])) {
                throw new InvoiceImportException(sprintf('Missing key "%s" at index %s in "%s".', $requiredKey, $index, $filePath));
            }
        }

        $currency = Currency::tryFrom((string) $row['devise']);
        if (null === $currency) {
            throw new InvoiceImportException(sprintf('Unknown currency "%s" at index %s in "%s".', $row['devise'], $index, $filePath));
        }

        try {
            $amount = Money::fromDecimalString((string) $row['montant'], $currency);
        } catch (\InvalidArgumentException $e) {
            throw new InvoiceImportException(sprintf('Invalid amount "%s" at index %s in "%s": %s', $row['montant'], $index, $filePath, $e->getMessage()), previous: $e);
        }

        $date = \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, (string) $row['date']);
        $isRealCalendarDate = false !== $date && $date->format(self::DATE_FORMAT) === $row['date'];
        if (!$isRealCalendarDate) {
            throw new InvoiceImportException(sprintf('Invalid date "%s" at index %s in "%s".', $row['date'], $index, $filePath));
        }

        return new InvoiceData(
            amount: $amount,
            customerName: (string) $row['nom'],
            date: $date,
        );
    }
}
