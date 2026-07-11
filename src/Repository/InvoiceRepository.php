<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\InvoiceData;
use App\Entity\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * @param iterable<InvoiceData> $invoices
     */
    public function updateAmounts(iterable $invoices): int
    {
        $connection = $this->getEntityManager()->getConnection();

        return $connection->transactional(function () use ($connection, $invoices): int {
            $updated = 0;
            foreach ($invoices as $invoice) {
                $connection->executeStatement(
                    'UPDATE invoice SET amount = :amount WHERE name = :name',
                    ['amount' => $invoice->amount, 'name' => $invoice->customerName],
                );
                ++$updated;
            }

            return $updated;
        });
    }
}
