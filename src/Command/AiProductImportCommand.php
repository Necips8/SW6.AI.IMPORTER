<?php declare(strict_types=1);

namespace Swag\AiAssistant\Command;

use Shopware\Core\Framework\Context;
use Swag\AiAssistant\Service\DraftManager;
use Swag\AiAssistant\Service\ProductImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'sw:product:ai-import', description: 'Import products from a JSON file using AI')]
class AiProductImportCommand extends Command
{
    private ProductImportService $importService;
    private DraftManager $draftManager;

    public function __construct(
        ProductImportService $importService,
        DraftManager $draftManager
    ) {
        parent::__construct();
        $this->importService = $importService;
        $this->draftManager = $draftManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to JSON file with product list')
            ->addOption('publish', null, InputOption::VALUE_NONE, 'Publish products immediately instead of draft')
            ->addOption('publish-drafts', null, InputOption::VALUE_NONE, 'Publish all existing drafts and exit')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Validate JSON without importing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        if ($input->getOption('publish-drafts')) {
            return $this->publishDrafts($io, $context);
        }

        $filePath = $input->getArgument('file');

        if (!file_exists($filePath)) {
            $io->error("File not found: $filePath");
            return Command::FAILURE;
        }

        $jsonContent = file_get_contents($filePath);

        if ($input->getOption('dry-run')) {
            return $this->dryRun($io, $jsonContent);
        }

        $io->title('AI Product Import');
        $io->text('Processing product list via AI...');

        $publishImmediately = (bool) $input->getOption('publish');
        $results = $this->importService->importFromJson($jsonContent, $context, $publishImmediately);

        $this->renderResults($io, $results);

        $successCount = count(array_filter($results, fn($r) => $r->isSuccess()));
        $failedCount = count(array_filter($results, fn($r) => $r->getStatus() === 'failed'));

        if ($failedCount > 0) {
            $io->warning("$successCount products imported, $failedCount failed");
            return Command::FAILURE;
        }

        $io->success("$successCount products successfully imported!");
        return Command::SUCCESS;
    }

    private function publishDrafts(SymfonyStyle $io, Context $context): int
    {
        $drafts = $this->draftManager->getDraftProducts($context);
        $count = count($drafts);

        if ($count === 0) {
            $io->info('No drafts found to publish.');
            return Command::SUCCESS;
        }

        $io->section('Publishing Drafts');
        $io->progressStart($count);

        foreach ($drafts as $draft) {
            $this->draftManager->publish($draft->getId(), $context);
            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success("$count drafts published.");

        return Command::SUCCESS;
    }

    private function dryRun(SymfonyStyle $io, string $jsonContent): int
    {
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $io->error('Invalid JSON: ' . json_last_error_msg());
            return Command::FAILURE;
        }

        $io->section('Dry Run – JSON Validation');
        $io->text(sprintf('Valid JSON with %d product(s)', count($data)));

        $errors = [];
        foreach ($data as $i => $item) {
            if (!isset($item['produktname']) && !isset($item['name'])) {
                $errors[] = "Entry #$i: missing 'produktname' or 'name' field";
            }
        }

        if (!empty($errors)) {
            $io->listing($errors);
            return Command::FAILURE;
        }

        $io->success('JSON is valid and ready for import.');
        return Command::SUCCESS;
    }

    private function renderResults(SymfonyStyle $io, array $results): void
    {
        $rows = [];
        foreach ($results as $result) {
            $status = match ($result->getStatus()) {
                'published' => '<fg=green>✓ Published</>',
                'draft' => '<fg=yellow>◷ Draft</>',
                'failed' => '<fg=red>✗ Failed</>',
                'skipped' => '<fg=gray>– Skipped</>',
                default => $result->getStatus(),
            };
            $rows[] = [
                $result->getProductName(),
                $status,
                $result->getProductNumber() ?? '-',
                $result->getErrorMessage() ?? '',
            ];
        }

        $io->table(['Product', 'Status', 'SKU', 'Error'], $rows);
    }
}
