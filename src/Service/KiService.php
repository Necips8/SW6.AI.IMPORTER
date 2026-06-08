<?php declare(strict_types=1);

namespace Swag\AiAssistant\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\AiAssistant\Struct\KiResult;

class KiService
{
    public const PROVIDER_OLLAMA = 'ollama';
    public const PROVIDER_OPENAI = 'openai';
    public const PROVIDER_ANTHROPIC = 'anthropic';

    private OllamaClient $ollamaClient;
    private SystemConfigService $systemConfigService;

    public function __construct(
        OllamaClient $ollamaClient,
        SystemConfigService $systemConfigService
    ) {
        $this->ollamaClient = $ollamaClient;
        $this->systemConfigService = $systemConfigService;
    }

    public function generateProductData(string $productName): KiResult
    {
        $provider = $this->getActiveProvider();

        $rawData = match ($provider) {
            self::PROVIDER_OLLAMA => $this->ollamaClient->generateProductData($productName),
            default => throw new \RuntimeException("Unsupported AI provider: $provider"),
        };

        return new KiResult($rawData, $provider);
    }

    public function getActiveProvider(): string
    {
        return $this->systemConfigService->get(
            'SwagAiAssistant.config.aiProvider',
            self::PROVIDER_OLLAMA
        );
    }

    public function getAvailableProviders(): array
    {
        return [
            self::PROVIDER_OLLAMA => 'Ollama (lokal)',
        ];
    }
}
