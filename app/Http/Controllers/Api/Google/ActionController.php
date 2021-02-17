<?php


namespace App\Http\Controllers\Api\Google;

use App\Http\Controllers\Controller;
use App\Services\External\Google\IntegrationService;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

class ActionController extends Controller
{
    private LoggerInterface $logger;
    private ClientInterface $httpClient;

    public function __construct(LoggerInterface $logger, Client $httpClient)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->httpClient = $httpClient;
    }

    public function getUrlByActionId(string $actionId, Request $request): JsonResponse
    {
        try {
            return new JsonResponse([
                'url' => (new IntegrationService($this->httpClient, $this->logger))->getUrlByActionId(
                    $actionId,
                    $request->query->all()
                ),
            ]);
        } catch (RuntimeException $throwable) {
            $this->logger->alert(sprintf("%s%s%s", $throwable->getMessage(), PHP_EOL, $throwable->getTraceAsString()));

            return new JsonResponse(['message' => 'Operation was failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
