<?php declare(strict_types=1);

namespace Swag\AiAssistant\Struct;

class ImportResult
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    private string $productName;
    private ?string $productId;
    private string $status;
    private ?string $errorMessage;
    private ?string $productNumber;
    private array $warnings;

    public function __construct(
        string $productName,
        string $status,
        ?string $productId = null,
        ?string $errorMessage = null,
        ?string $productNumber = null,
        array $warnings = []
    ) {
        $this->productName = $productName;
        $this->status = $status;
        $this->productId = $productId;
        $this->errorMessage = $errorMessage;
        $this->productNumber = $productNumber;
        $this->warnings = $warnings;
    }

    public static function draft(string $productName, string $productId, ?string $productNumber = null, array $warnings = []): self
    {
        return new self($productName, self::STATUS_DRAFT, $productId, null, $productNumber, $warnings);
    }

    public static function published(string $productName, string $productId, ?string $productNumber = null, array $warnings = []): self
    {
        return new self($productName, self::STATUS_PUBLISHED, $productId, null, $productNumber, $warnings);
    }

    public static function failed(string $productName, string $errorMessage): self
    {
        return new self($productName, self::STATUS_FAILED, null, $errorMessage);
    }

    public static function skipped(string $productName, string $reason): self
    {
        return new self($productName, self::STATUS_SKIPPED, null, $reason);
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getProductNumber(): ?string
    {
        return $this->productNumber;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function isSuccess(): bool
    {
        return \in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PUBLISHED], true);
    }

    public function toArray(): array
    {
        return [
            'productName' => $this->productName,
            'productId' => $this->productId,
            'productNumber' => $this->productNumber,
            'status' => $this->status,
            'errorMessage' => $this->errorMessage,
            'warnings' => $this->warnings,
        ];
    }
}
