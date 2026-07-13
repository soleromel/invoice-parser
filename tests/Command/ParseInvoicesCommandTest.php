<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Entity\Currency;
use App\Repository\InvoiceRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ParseInvoicesCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;
    private InvoiceRepository $repository;

    protected function setUp(): void
    {
        $application = new Application(self::bootKernel());
        $this->commandTester = new CommandTester($application->find('app:parse'));
        $this->repository = self::getContainer()->get(InvoiceRepository::class);
    }

    public function testImportPersistsInvoicesWithExactValues(): void
    {
        $exitCode = $this->commandTester->execute(['path' => __DIR__.'/../fixtures/csv/invoices.csv']);

        self::assertSame(0, $exitCode);

        $invoices = $this->repository->findBy([], ['id' => 'ASC']);
        self::assertCount(2, $invoices);
        self::assertSame('Frank Green', $invoices[0]->getName());
        self::assertSame(67043, $invoices[0]->getAmount()->minorUnits);
        self::assertSame(Currency::EUR, $invoices[0]->getAmount()->currency);
        self::assertSame('2025-02-03', $invoices[0]->getDate()->format('Y-m-d'));
        self::assertSame("Jane O'Brien", $invoices[1]->getName());
        self::assertSame(13587, $invoices[1]->getAmount()->minorUnits);
        self::assertSame(Currency::USD, $invoices[1]->getAmount()->currency);
    }

    public function testFailedFileWritesNoRowAtAll(): void
    {
        $exitCode = $this->commandTester->execute(['path' => __DIR__.'/../fixtures/csv/bad-amount.csv']);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Invalid amount "not-a-number"', $this->commandTester->getDisplay());
        self::assertSame([], $this->repository->findAll());
    }

    public function testUnknownPathFails(): void
    {
        $exitCode = $this->commandTester->execute(['path' => 'nowhere/no-invoices.csv']);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('does not exist', $this->commandTester->getDisplay());
    }
}
