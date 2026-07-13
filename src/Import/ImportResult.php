<?php

declare(strict_types=1);

namespace App\Import;

final class ImportResult
{
    /**
     * @param list<FileImportResult> $files
     */
    public function __construct(
        public readonly array $files,
    ) {
    }

    public function hasFailures(): bool
    {
        foreach ($this->files as $file) {
            if ($file->isFailure()) {
                return true;
            }
        }

        return false;
    }

    public function failureCount(): int
    {
        $failures = 0;
        foreach ($this->files as $file) {
            if ($file->isFailure()) {
                ++$failures;
            }
        }

        return $failures;
    }

    public function totalImported(): int
    {
        $total = 0;
        foreach ($this->files as $file) {
            $total += $file->importedCount;
        }

        return $total;
    }
}
