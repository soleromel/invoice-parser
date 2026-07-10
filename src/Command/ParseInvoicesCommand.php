<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\InvoiceParser;
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
        private readonly InvoiceParser $parser,
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
        $path = $input->getArgument('path');
        if (!str_starts_with($path, '/')) {
            $path = $this->projectDir.'/'.$path;
        }

        if (!file_exists($path)) {
            $io->error(sprintf('Path "%s" does not exist.', $path));

            return Command::FAILURE;
        }

        $files = [$path];
        if (is_dir($path)) {
            $files = glob(rtrim($path, '/').'/*') ?: [];
        }

        if ([] === $files) {
            $io->warning(sprintf('No file found in "%s".', $path));

            return Command::SUCCESS;
        }

        foreach ($files as $file) {
            $this->parser->parse($file);
            $io->writeln(sprintf('Parsed <info>%s</info>', $file));
        }

        $io->success(sprintf('%d file(s) processed.', count($files)));

        return Command::SUCCESS;
    }
}
