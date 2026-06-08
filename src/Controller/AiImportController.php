<?php declare(strict_types=1);

namespace Swag\AiAssistant\Controller;

use Shopware\Core\Framework\Context;
use Swag\AiAssistant\Service\DraftManager;
use Swag\AiAssistant\Service\ProductImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class AiImportController extends AbstractController
{
    private ProductImportService $importService;
    private DraftManager $draftManager;

    public function __construct(
        ProductImportService $importService,
        DraftManager $draftManager
    ) {
        $this->importService = $importService;
        $this->draftManager = $draftManager;
    }

    /**
     * @Route("/api/_action/ai-assistant/import", name="api.action.ai_assistant.import", methods={"POST"})
     */
    public function import(Request $request, Context $context): JsonResponse
    {
        $productName = $request->request->get('name');

        if (!$productName) {
            return new JsonResponse(['error' => 'Product name is required'], 400);
        }

        $publishImmediately = (bool) $request->request->get('publish', false);

        $result = $this->importService->importSingle($productName, $context, $publishImmediately);

        if ($result->isSuccess()) {
            return new JsonResponse([
                'success' => true,
                'productId' => $result->getProductId(),
                'productNumber' => $result->getProductNumber(),
                'status' => $result->getStatus(),
                'warnings' => $result->getWarnings(),
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'error' => $result->getErrorMessage(),
        ], 500);
    }

    /**
     * @Route("/api/_action/ai-assistant/import-batch", name="api.action.ai_assistant.import_batch", methods={"POST"})
     */
    public function importBatch(Request $request, Context $context): JsonResponse
    {
        $products = $request->request->all('products');

        if (empty($products)) {
            return new JsonResponse(['error' => 'Product list is required'], 400);
        }

        $publishImmediately = (bool) $request->request->get('publish', false);
        $results = $this->importService->importBatch($products, $context, $publishImmediately);

        return new JsonResponse([
            'success' => true,
            'total' => count($results),
            'successCount' => count(array_filter($results, fn($r) => $r->isSuccess())),
            'failedCount' => count(array_filter($results, fn($r) => $r->getStatus() === 'failed')),
            'results' => array_map(fn($r) => $r->toArray(), $results),
        ]);
    }

    /**
     * @Route("/api/_action/ai-assistant/publish-drafts", name="api.action.ai_assistant.publish_drafts", methods={"POST"})
     */
    public function publishDrafts(Context $context): JsonResponse
    {
        $count = $this->draftManager->publishAllDrafts($context);

        return new JsonResponse([
            'success' => true,
            'publishedCount' => $count,
        ]);
    }

    /**
     * @Route("/api/_action/ai-assistant/drafts", name="api.action.ai_assistant.drafts", methods={"GET"})
     */
    public function getDrafts(Context $context): JsonResponse
    {
        $drafts = $this->draftManager->getDraftProducts($context);

        $result = [];
        foreach ($drafts as $draft) {
            $result[] = [
                'id' => $draft->getId(),
                'name' => $draft->getName(),
                'productNumber' => $draft->getProductNumber(),
                'createdAt' => $draft->getCreatedAt()?->format('c'),
            ];
        }

        return new JsonResponse([
            'success' => true,
            'drafts' => $result,
            'total' => count($result),
        ]);
    }

    /**
     * @Route("/api/_action/ai-assistant/delete-draft/{id}", name="api.action.ai_assistant.delete_draft", methods={"DELETE"})
     */
    public function deleteDraft(string $id, Context $context): JsonResponse
    {
        $this->draftManager->deleteDraft($id, $context);

        return new JsonResponse(['success' => true]);
    }
}
