<?php


namespace App\Http\Controllers\Api\Google;

use App\Exceptions\ExternalServices\Google\AuthException;
use App\Http\Controllers\Controller;
use App\Services\External\Google\IntegrationService;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

class ExportController extends Controller
{
    private LoggerInterface $logger;
    private ClientInterface $httpClient;

    public function __construct(LoggerInterface $logger, Client $httpClient)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->httpClient = $httpClient;
    }

    public function exportReportInit(Request $request): JsonResponse
    {
        $authUserId = Auth::id();

        if ($authUserId === null) {
            return new JsonResponse(['message' => 'Need to authenticate in Cattr'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $this->logger->debug(sprintf(
                "Attempt to check access user with id = %s permission export report",
                $authUserId
            ));
            (new IntegrationService($this->httpClient, $this->logger))->auth($authUserId, $request->request->all());

            return new JsonResponse([], Response::HTTP_NO_CONTENT);
        } catch (AuthException $authException) {
            return new JsonResponse([
                'url' => $authException->getAuthUrl(),
            ], Response::HTTP_UNAUTHORIZED);
        } catch (RuntimeException $throwable) {
            $this->logger->alert(sprintf("%s%s%s", $throwable->getMessage(), PHP_EOL, $throwable->getTraceAsString()));

            return new JsonResponse(['message' => 'Operation was failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
