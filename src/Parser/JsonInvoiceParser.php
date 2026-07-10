<?php

declare(strict_types=1);

namespace App\Parser;

use App\Dto\InvoiceData;
use App\Exception\InvoiceImportException;

final class JsonInvoiceParser implements InvoiceFileParserInterface
{
    public function supports(string $filePath): bool
    {
        return 'json' === strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
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

        foreach (['montant', 'devise', 'nom', 'date'] as $key) {
            if (!isset($row[$key])) {
                throw new InvoiceImportException(sprintf('Missing key "%s" at index %s in "%s".', $key, $index, $filePath));
            }
        }

        if (!is_numeric($row['montant'])) {
            throw new InvoiceImportException(sprintf('Invalid amount "%s" at index %s in "%s".', $row['montant'], $index, $filePath));
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $row['date']);
        if (false === $date || $date->format('Y-m-d') !== $row['date']) {
            throw new InvoiceImportException(sprintf('Invalid date "%s" at index %s in "%s".', $row['date'], $index, $filePath));
        }

        return new InvoiceData(
            amount: (float) $row['montant'],
            currency: (string) $row['devise'],
            customerName: (string) $row['nom'],
            date: $date,
        );
    }
}
