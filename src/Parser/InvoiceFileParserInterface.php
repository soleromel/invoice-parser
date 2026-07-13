<?php

declare(strict_types=1);

namespace App\Parser;

use App\Dto\InvoiceData;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.invoice_file_parser')]
interface InvoiceFileParserInterface
{
    public function supports(string $filePath): bool;

    /**
     * @return iterable<InvoiceData>
     */
    public function parse(string $filePath): iterable;
}
