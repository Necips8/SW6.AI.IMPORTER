<?php declare(strict_types=1);

namespace Swag\AiAssistant\Service;

use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\AiAssistant\Struct\KiResult;

class ProductCreator
{
    private EntityRepository $productRepository;
    private EntityRepository $currencyRepository;
    private EntityRepository $salesChannelRepository;
    private DraftManager $draftManager;
    private EntityResolver $entityResolver;

    public function __construct(
        EntityRepository $productRepository,
        EntityRepository $currencyRepository,
        EntityRepository $salesChannelRepository,
        DraftManager $draftManager,
        EntityResolver $entityResolver
    ) {
        $this->productRepository = $productRepository;
        $this->currencyRepository = $currencyRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->draftManager = $draftManager;
        $this->entityResolver = $entityResolver;
    }

    public function createProduct(
        KiResult $kiResult,
        string $manufacturerId,
        string $taxId,
        array $categoryIds,
        Context $context,
        bool $publishImmediately = false
    ): string {
        $productId = Uuid::randomHex();
        $currencyId = $this->getDefaultCurrencyId($context);
        $currency = $this->currencyRepository->search(new \Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria([$currencyId]), $context)->first();
        $decimalPrecision = $currency ? $currency->getDecimalPrecision() : 2;

        $grossPrice = $kiResult->getPrice();
        $taxRate = $kiResult->getTaxRate() ?? 19.0;
        $netPrice = round($grossPrice / (1 + $taxRate / 100), $decimalPrecision);

        $data = [
            'id' => $productId,
            'name' => $kiResult->getName(),
            'productNumber' => $kiResult->getProductNumber() ?? 'AI-' . strtoupper(substr(Uuid::randomHex(), 0, 8)),
            'stock' => $kiResult->getStock(),
            'description' => $kiResult->getDescription(),
            'manufacturerId' => $manufacturerId,
            'taxId' => $taxId,
            'price' => [
                [
                    'currencyId' => $currencyId,
                    'gross' => $grossPrice,
                    'net' => $netPrice,
                    'linked' => true,
                ],
            ],
            'purchaseUnit' => $kiResult->getPurchaseUnit(),
            'referenceUnit' => $kiResult->getReferenceUnit(),
            'packUnit' => $kiResult->getPackUnit(),
            'ean' => $kiResult->getEan(),
            'active' => $publishImmediately,
        ];

        if (!empty($categoryIds)) {
            $data['categories'] = array_map(fn(string $id) => ['id' => $id], $categoryIds);
        }

        $properties = $kiResult->getProperties();
        if (!empty($properties)) {
            $propertyIds = [];
            foreach ($properties as $propertyName => $value) {
                $resolved = $this->entityResolver->resolveProperty($propertyName, $value, $context);
                $propertyIds[] = ['id' => $resolved['optionId']];
            }
            $data['properties'] = $propertyIds;
        }

        $seoTitle = $kiResult->getSeoTitle();
        if ($seoTitle) {
            $data['customSearchKeywords'] = [$seoTitle];
        }

        $salesChannelIds = $this->getSalesChannelIds($context);
        if (!empty($salesChannelIds)) {
            $data['visibilities'] = array_map(fn(string $id) => [
                'salesChannelId' => $id,
                'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
            ], $salesChannelIds);
        }

        if (!$publishImmediately) {
            $this->draftManager->markAsDraft($data);
        }

        $this->productRepository->create([$data], $context);

        return $productId;
    }

    private function getDefaultCurrencyId(Context $context): string
    {
        $criteria = new \Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria();
        $criteria->setLimit(1);

        $currency = $this->currencyRepository->search($criteria, $context)->first();

        return $currency ? $currency->getId() : Uuid::randomHex();
    }

    private function getSalesChannelIds(Context $context): array
    {
        $criteria = new \Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria();
        $criteria->addAssociation('salesChannels');

        $salesChannels = $this->salesChannelRepository->search($criteria, $context);

        return array_keys($salesChannels->getElements());
    }
}
