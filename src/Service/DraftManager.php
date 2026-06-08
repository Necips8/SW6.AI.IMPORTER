<?php declare(strict_types=1);

namespace Swag\AiAssistant\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class DraftManager
{
    public const CUSTOM_FIELD_IMPORT_DRAFT = 'swag_ai_import_draft';
    public const CUSTOM_FIELD_IMPORT_SOURCE = 'swag_ai_import_source';

    private EntityRepository $productRepository;

    public function __construct(EntityRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function publish(string $productId, Context $context): void
    {
        $this->productRepository->update([
            [
                'id' => $productId,
                'active' => true,
                'customFields' => [
                    self::CUSTOM_FIELD_IMPORT_DRAFT => false,
                ],
            ],
        ], $context);
    }

    public function markAsDraft(array &$productData): void
    {
        $productData['active'] = false;
        $productData['customFields'] = array_merge(
            $productData['customFields'] ?? [],
            [
                self::CUSTOM_FIELD_IMPORT_DRAFT => true,
                self::CUSTOM_FIELD_IMPORT_SOURCE => 'ai_assistant',
            ]
        );
    }

    public function getDraftProducts(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('customFields.' . self::CUSTOM_FIELD_IMPORT_DRAFT, true)
        );

        return $this->productRepository->search($criteria, $context)->getElements();
    }

    public function publishAllDrafts(Context $context): int
    {
        $drafts = $this->getDraftProducts($context);
        $count = 0;

        foreach ($drafts as $draft) {
            $this->publish($draft->getId(), $context);
            $count++;
        }

        return $count;
    }

    public function deleteDraft(string $productId, Context $context): void
    {
        $this->productRepository->delete([['id' => $productId]], $context);
    }
}
