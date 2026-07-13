<?php

declare(strict_types=1);

namespace App\Command;

use App\Import\InvoiceImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:parse',
    description: 'Parse invoice files and update invoices in database',
)]
final class ParseInvoicesCommand extends Command
{
    private const DEFAULT_PATH = 'data';

    public function __construct(
        private readonly InvoiceImporter $importer,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'path',
            InputArgument::OPTIONAL,
            'Path to an invoice file, or a directory containing invoice files',
            self::DEFAULT_PATH,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = $this->resolveAgainstProjectDir($input->getArgument('path'));

        if (!file_exists($path)) {
            $io->error(sprintf('Path "%s" does not exist.', $path));

            return Command::FAILURE;
        }

        $files = $this->resolveFiles($path);
        if ([] === $files) {
            $io->warning(sprintf('No file found in "%s".', $path));

            return Command::SUCCESS;
        }

        $result = $this->importer->import($files);

        foreach ($result->files as $fileResult) {
            if ($fileResult->isFailure()) {
                $io->writeln(sprintf('<error>✗</error> %s — %s', $fileResult->filePath, $fileResult->error));

                continue;
            }

            $io->writeln(sprintf('<info>✓</info> %s — %d invoice(s) imported', $fileResult->filePath, $fileResult->importedCount));
        }

        if ($result->hasFailures()) {
            $io->error(sprintf('%d file(s) failed, %d invoice(s) imported.', $result->failureCount(), $result->totalImported()));

            return Command::FAILURE;
        }

        $io->success(sprintf('%d invoice(s) imported from %d file(s).', $result->totalImported(), count($files)));

        return Command::SUCCESS;
    }

    private function resolveAgainstProjectDir(string $path): string
    {
        $isAbsolutePath = str_starts_with($path, '/');
        if ($isAbsolutePath) {
            return $path;
        }

        return $this->projectDir.'/'.$path;
    }

    /**
     * @return list<string>
     */
    private function resolveFiles(string $path): array
    {
        if (is_dir($path)) {
            return glob(rtrim($path, '/').'/*') ?: [];
        }

        return [$path];
    }
}
