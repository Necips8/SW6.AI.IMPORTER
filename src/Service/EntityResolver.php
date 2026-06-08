<?php declare(strict_types=1);

namespace Swag\AiAssistant\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class EntityResolver
{
    private EntityRepository $manufacturerRepository;
    private EntityRepository $taxRepository;
    private EntityRepository $categoryRepository;
    private EntityRepository $propertyGroupRepository;
    private EntityRepository $propertyGroupOptionRepository;

    public function __construct(
        EntityRepository $manufacturerRepository,
        EntityRepository $taxRepository,
        EntityRepository $categoryRepository,
        EntityRepository $propertyGroupRepository,
        EntityRepository $propertyGroupOptionRepository
    ) {
        $this->manufacturerRepository = $manufacturerRepository;
        $this->taxRepository = $taxRepository;
        $this->categoryRepository = $categoryRepository;
        $this->propertyGroupRepository = $propertyGroupRepository;
        $this->propertyGroupOptionRepository = $propertyGroupOptionRepository;
    }

    public function resolveManufacturer(string $name, Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $name));

        $manufacturer = $this->manufacturerRepository->search($criteria, $context)->first();

        if ($manufacturer) {
            return $manufacturer->getId();
        }

        $id = Uuid::randomHex();
        $this->manufacturerRepository->create([
            ['id' => $id, 'name' => $name],
        ], $context);

        return $id;
    }

    public function resolveTaxRate(float $rate, Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('taxRate', $rate));

        $tax = $this->taxRepository->search($criteria, $context)->first();

        if ($tax) {
            return $tax->getId();
        }

        return $this->taxRepository->search(new Criteria(), $context)->first()->getId();
    }

    public function resolveCategories(array $categoryNames, Context $context): array
    {
        $ids = [];

        foreach ($categoryNames as $name) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('name', $name));
            $criteria->setLimit(1);

            $category = $this->categoryRepository->search($criteria, $context)->first();

            if ($category) {
                $ids[] = $category->getId();
            } else {
                $id = Uuid::randomHex();
                $this->categoryRepository->create([
                    ['id' => $id, 'name' => $name],
                ], $context);
                $ids[] = $id;
            }
        }

        return $ids;
    }

    public function resolveProperty(string $propertyName, string $value, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $propertyName));
        $criteria->setLimit(1);

        $group = $this->propertyGroupRepository->search($criteria, $context)->first();

        if (!$group) {
            $groupId = Uuid::randomHex();
            $this->propertyGroupRepository->create([
                [
                    'id' => $groupId,
                    'name' => $propertyName,
                    'displayType' => 'text',
                    'sortingType' => 'alphanumeric',
                ],
            ], $context);
        } else {
            $groupId = $group->getId();
        }

        $optionCriteria = new Criteria();
        $optionCriteria->addFilter(new EqualsFilter('name', $value));
        $optionCriteria->addFilter(new EqualsFilter('groupId', $groupId));
        $optionCriteria->setLimit(1);

        $option = $this->propertyGroupOptionRepository->search($optionCriteria, $context)->first();

        if ($option) {
            return ['groupId' => $groupId, 'optionId' => $option->getId()];
        }

        $optionId = Uuid::randomHex();
        $this->propertyGroupOptionRepository->create([
            [
                'id' => $optionId,
                'groupId' => $groupId,
                'name' => $value,
            ],
        ], $context);

        return ['groupId' => $groupId, 'optionId' => $optionId];
    }
}
