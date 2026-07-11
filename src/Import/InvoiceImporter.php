<?php

declare(strict_types=1);

namespace App\Import;

use App\Exception\InvoiceImportException;
use App\Parser\InvoiceFileParserInterface;
use App\Repository\InvoiceRepository;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class InvoiceImporter
{
    /**
     * @param iterable<InvoiceFileParserInterface> $parsers
     */
    public function __construct(
        #[TaggedIterator('app.invoice_file_parser')]
        private readonly iterable $parsers,
        private readonly InvoiceRepository $repository,
    ) {
    }

    /**
     * @param list<string> $filePaths
     */
    public function import(array $filePaths): ImportResult
    {
        $results = [];
        foreach ($filePaths as $filePath) {
            $results[] = $this->importFile($filePath);
        }

        return new ImportResult($results);
    }

    private function importFile(string $filePath): FileImportResult
    {
        $parser = $this->findParser($filePath);
        if (null === $parser) {
            return new FileImportResult($filePath, 0, sprintf('No parser supports "%s".', $filePath));
        }

        try {
            $imported = $this->repository->updateAmounts($parser->parse($filePath));
        } catch (InvoiceImportException $e) {
            return new FileImportResult($filePath, 0, $e->getMessage());
        }

        return new FileImportResult($filePath, $imported);
    }

    private function findParser(string $filePath): ?InvoiceFileParserInterface
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($filePath)) {
                return $parser;
            }
        }

        return null;
    }
}
