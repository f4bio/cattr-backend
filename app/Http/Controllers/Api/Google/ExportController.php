<?php


namespace App\Http\Controllers\Api\Google;

use App\Exceptions\ExternalServices\Google\AuthException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Google\Sheets\ExportReportRequest;
use App\Jobs\ExportReportInGoogleSheetsJob;
use App\Models\Property;
use App\Services\External\Google\IntegrationService;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

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

    /**
     * @param ExportReportRequest $request
     * @return JsonResponse|RedirectResponse
     */
    public function exportReportInit(ExportReportRequest $request)
    {
        $authUserId = Auth::id();

        if ($authUserId === null) {
            return new JsonResponse(['message' => 'Need to authenticate in Cattr'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $state = $request->prepareParams();
            $state['instanceId'] = Property::getInstanceId();
            $state['userId'] = $authUserId;
            $state['successRedirect'] = sprintf(
                "http://%s/time-intervals/dashboard/export-in-sheets/end?%s",
                config('app.domain') . ':10000', //TODO REMOVE PORT FROM REDIRECT
                http_build_query(['state' => base64_encode(json_encode($state, JSON_THROW_ON_ERROR))])
            );

            $this->logger->debug(sprintf(
                "Attempt to check access user with id = %s permission to export the report",
                $authUserId
            ));
            (new IntegrationService($this->httpClient, $this->logger))->auth($authUserId, $state);

            return response()->redirectTo($state['successRedirect']);
        } catch (AuthException $authException) {
            return new JsonResponse([
                'url' => $authException->getAuthUrl(),
            ], Response::HTTP_UNAUTHORIZED);
        } catch (RuntimeException $throwable) {
            $this->logger->alert(sprintf("%s%s%s", $throwable->getMessage(), PHP_EOL, $throwable->getTraceAsString()));

            return new JsonResponse(['message' => 'Operation was failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function exportReportEnd(Request $request): JsonResponse
    {
        try {
            $this->logger->debug(sprintf(
                "Request [start export in Google Sheet] was received. Content: %s",
                json_encode($request->query, JSON_THROW_ON_ERROR)
            ));

            $this->dispatch(new ExportReportInGoogleSheetsJob(json_decode(
                base64_decode($request->query->get('state')),
                true,
                512,
                JSON_THROW_ON_ERROR
            )));
            $this->logger->debug(sprintf("The job %s was pushed to a job queue", ExportReportInGoogleSheetsJob::class));

            return new JsonResponse([], Response::HTTP_NO_CONTENT);
        } catch (Throwable $throwable) {
            $this->logger->error(sprintf(
                "Failed of registering the job %s%s%s%s%s",
                ExportReportInGoogleSheetsJob::class,
                PHP_EOL,
                $throwable->getMessage(),
                PHP_EOL,
                $throwable->getTraceAsString()
            ));

            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
