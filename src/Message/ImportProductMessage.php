<?php declare(strict_types=1);

namespace Swag\AiAssistant\Message;

use Shopware\Core\Framework\MessageQueue\AsyncMessageInterface;

class ImportProductMessage implements AsyncMessageInterface
{
    private string $productName;
    private bool $publishImmediately;

    public function __construct(string $productName, bool $publishImmediately = false)
    {
        $this->productName = $productName;
        $this->publishImmediately = $publishImmediately;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function publishImmediately(): bool
    {
        return $this->publishImmediately;
    }
}
