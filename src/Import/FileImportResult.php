<?php

declare(strict_types=1);

namespace App\Import;

final class FileImportResult
{
    public function __construct(
        public readonly string $filePath,
        public readonly int $importedCount,
        public readonly ?string $error = null,
    ) {
    }

    public function isFailure(): bool
    {
        return null !== $this->error;
    }
}
