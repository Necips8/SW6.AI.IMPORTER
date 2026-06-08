<?php declare(strict_types=1);

namespace Swag\AiAssistant\Struct;

class KiResult
{
    private array $rawData;
    private string $provider;

    public function __construct(array $rawData, string $provider)
    {
        $this->rawData = $rawData;
        $this->provider = $provider;
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getName(): ?string
    {
        return $this->rawData['name'] ?? null;
    }

    public function getDescription(): ?string
    {
        return $this->rawData['description'] ?? null;
    }

    public function getManufacturer(): ?string
    {
        return $this->rawData['manufacturer'] ?? null;
    }

    public function getProductNumber(): ?string
    {
        return $this->rawData['productNumber'] ?? null;
    }

    public function getPrice(): ?float
    {
        return isset($this->rawData['price']) ? (float) $this->rawData['price'] : null;
    }

    public function getTaxRate(): ?float
    {
        return isset($this->rawData['tax']) ? (float) $this->rawData['tax'] : null;
    }

    public function getProperties(): array
    {
        return $this->rawData['properties'] ?? [];
    }

    public function getCategories(): array
    {
        return $this->rawData['categories'] ?? [];
    }

    public function getSeoTitle(): ?string
    {
        return $this->rawData['seoTitle'] ?? null;
    }

    public function getSeoDescription(): ?string
    {
        return $this->rawData['seoDescription'] ?? null;
    }

    public function getEan(): ?string
    {
        return $this->rawData['ean'] ?? null;
    }

    public function getPackUnit(): ?string
    {
        return $this->rawData['packUnit'] ?? null;
    }

    public function getPurchaseUnit(): ?float
    {
        return isset($this->rawData['purchaseUnit']) ? (float) $this->rawData['purchaseUnit'] : null;
    }

    public function getReferenceUnit(): ?float
    {
        return isset($this->rawData['referenceUnit']) ? (float) $this->rawData['referenceUnit'] : null;
    }

    public function getStock(): int
    {
        return (int) ($this->rawData['stock'] ?? 10);
    }

    public function isValid(): bool
    {
        return $this->getName() !== null
            && $this->getPrice() !== null
            && $this->getManufacturer() !== null;
    }
}
