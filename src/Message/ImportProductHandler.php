<?php declare(strict_types=1);

namespace Swag\AiAssistant\Message;

use Shopware\Core\Framework\Context;
use Swag\AiAssistant\Service\ProductImportService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ImportProductHandler
{
    private ProductImportService $importService;

    public function __construct(ProductImportService $importService)
    {
        $this->importService = $importService;
    }

    public function __invoke(ImportProductMessage $message): void
    {
        $context = Context::createDefaultContext();
        $this->importService->importSingle(
            $message->getProductName(),
            $context,
            $message->publishImmediately()
        );
    }
}
