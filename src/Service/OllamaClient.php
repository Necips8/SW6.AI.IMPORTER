<?php declare(strict_types=1);

namespace Swag\AiAssistant\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OllamaClient
{
    private HttpClientInterface $httpClient;
    private SystemConfigService $systemConfigService;

    public function __construct(
        HttpClientInterface $httpClient,
        SystemConfigService $systemConfigService
    ) {
        $this->httpClient = $httpClient;
        $this->systemConfigService = $systemConfigService;
    }

    public function generateProductData(string $productName): array
    {
        $baseUrl = $this->systemConfigService->get('SwagAiAssistant.config.ollamaUrl') ?? 'http://localhost:11434';
        $model = $this->systemConfigService->get('SwagAiAssistant.config.ollamaModel') ?? 'llama3';

        $prompt = $this->getPrompt($productName);

        try {
            $response = $this->httpClient->request('POST', $baseUrl . '/api/generate', [
                'json' => [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'format' => 'json',
                ],
                'timeout' => 300,
                'max_duration' => 300,
            ]);

            $content = $response->toArray();
            $rawJson = $content['response'] ?? '{}';
            $data = json_decode($rawJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response from Ollama: ' . json_last_error_msg());
            }

            return $data;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Ollama API error: ' . $e->getMessage());
        }
    }

    private function getPrompt(string $productName): string
    {
        return <<<EOT
You are a professional e-commerce product manager for Shopware 6.
Create complete, realistic product data based ONLY on the product name provided.

Product Name: "$productName"

Respond with valid JSON only (no markdown, no explanation). Use this exact structure:
{
  "name": "Full product name",
  "description": "Detailed HTML product description with features and benefits",
  "manufacturer": "Manufacturer company name",
  "productNumber": "unique-SKU-identifier",
  "price": 99.99,
  "tax": 19,
  "stock": 25,
  "ean": "EAN-13 barcode number",
  "packUnit": "Stück",
  "purchaseUnit": 1,
  "referenceUnit": 1,
  "categories": ["Category1", "Category2"],
  "properties": {
    "Color": "Black",
    "Material": "Plastic",
    "Weight": "0.5 kg"
  },
  "seoTitle": "SEO-optimized page title",
  "seoDescription": "SEO meta description for search engines"
}

Rules:
- price must be a number (gross price in EUR)
- tax must be 7, 19, or 0
- manufacturer must be a real company name matching the product
- categories should be plausible shop categories
- properties should be relevant physical attributes
- productNumber should be unique and realistic
EOT;
    }
}
