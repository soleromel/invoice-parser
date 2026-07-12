<?php

declare(strict_types=1);

namespace App\Repository;

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
     * @param iterable<Invoice> $invoices
     */
    public function saveAll(iterable $invoices): int
    {
        $entityManager = $this->getEntityManager();

        try {
            $savedCount = 0;
            foreach ($invoices as $invoice) {
                $entityManager->persist($invoice);
                ++$savedCount;
            }
            $entityManager->flush();

            return $savedCount;
        } finally {
            $entityManager->clear();
        }
    }
}
