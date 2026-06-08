<?php declare(strict_types=1);

namespace Swag\AiAssistant\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\AiAssistant\Struct\ImportResult;
use Swag\AiAssistant\Struct\KiResult;

class ProductImportService
{
    private KiService $kiService;
    private ProductCreator $productCreator;
    private DraftManager $draftManager;
    private EntityResolver $entityResolver;

    public function __construct(
        KiService $kiService,
        ProductCreator $productCreator,
        DraftManager $draftManager,
        EntityResolver $entityResolver
    ) {
        $this->kiService = $kiService;
        $this->productCreator = $productCreator;
        $this->draftManager = $draftManager;
        $this->entityResolver = $entityResolver;
    }

    public function importSingle(string $productName, Context $context, bool $publishImmediately = false): ImportResult
    {
        try {
            $kiResult = $this->kiService->generateProductData($productName);

            if (!$kiResult->isValid()) {
                return ImportResult::failed($productName, 'KI returned incomplete data: missing name, price, or manufacturer');
            }

            $warnings = [];

            $manufacturerId = $this->entityResolver->resolveManufacturer($kiResult->getManufacturer(), $context);
            $taxId = $this->entityResolver->resolveTaxRate($kiResult->getTaxRate() ?? 19.0, $context);

            $categoryIds = [];
            if (!empty($kiResult->getCategories())) {
                $categoryIds = $this->entityResolver->resolveCategories($kiResult->getCategories(), $context);
            }

            $productNumber = $kiResult->getProductNumber();
            if (!$productNumber) {
                $productNumber = 'AI-' . strtoupper(substr(Uuid::randomHex(), 0, 8));
            }

            $productId = $this->productCreator->createProduct($kiResult, $manufacturerId, $taxId, $categoryIds, $context, $publishImmediately);

            if (!$publishImmediately) {
                $this->draftManager->markAsDraft($kiResult->getRawData());
            }

            if (empty($kiResult->getDescription())) {
                $warnings[] = 'No description was generated';
            }

            return ImportResult::published($productName, $productId, $productNumber, $warnings);
        } catch (\Throwable $e) {
            return ImportResult::failed($productName, $e->getMessage());
        }
    }

    public function importBatch(array $productNames, Context $context, bool $publishImmediately = false): array
    {
        $results = [];

        foreach ($productNames as $productName) {
            $results[] = $this->importSingle($productName, $context, $publishImmediately);
        }

        return $results;
    }

    public function importFromJson(string $jsonContent, Context $context, bool $publishImmediately = false): array
    {
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        $productNames = [];

        foreach ($data as $item) {
            if (isset($item['produktname'])) {
                $productNames[] = $item['produktname'];
            } elseif (isset($item['name'])) {
                $productNames[] = $item['name'];
            }
        }

        return $this->importBatch($productNames, $context, $publishImmediately);
    }
}
