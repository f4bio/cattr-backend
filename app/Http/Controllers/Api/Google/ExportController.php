<?php


namespace App\Http\Controllers\Api\Google;

use App\Exceptions\ExternalServices\Google\AuthException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Google\Sheets\ExportReportEndRequest;
use App\Http\Requests\Google\Sheets\ExportReportRequest;
use App\Jobs\ExportReportInGoogleSheetsJob;
use App\Services\External\Google\IntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class ExportController extends Controller
{
    private LoggerInterface $logger;
    private IntegrationService $integrationService;

    public function __construct(LoggerInterface $logger, IntegrationService $integrationService)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->integrationService = $integrationService;
    }

    /**
     * @api             {get} time-intervals/dashboard/export-in-sheets Export report in Google Sheets
     * @apiDescription  Init export to Google Sheets
     *
     * @apiVersion      1.0.0
     * @apiName         Export in Google Sheets
     * @apiGroup        Export in Google Sheets
     *
     * @apiUse          AuthHeader
     *
     * @apiParam (query string) {ISO8601} start_at
     * @apiParam (query string) {ISO8601} end_at
     * @apiParam (query string) {string} timezone example: Asia/Omsk
     * @apiParam (query string) {int[]} user_ids  Users ID whom need include in the report
     * @apiParam (query string) {[int[]]} project_ids  Projects ID which need include in the report \
     * (default: include all projects)
     *
     * @apiSuccessExample {redirect} Application has access to user's Google Account.
     *  Redirect to time-intervals/dashboard/export-in-sheets/end
     *  HTTP/1.1 302 OK
     *
     * @apiUse          UnauthorizedError
     *
     * @apiErrorExample {json} Need give access to your Google Account:
     *     HTTP/1.1 428 Returns url to auth in Google. If action will be authorized,
     * then user will be redirect to time-intervals/dashboard/export-in-sheets/end
     *     {
     *       "url": "http://accounts.google.com/some-path"
     *     }
     *
     * @apiErrorExample {json} Internal server error:
     *     HTTP/1.1 500 Internal server error
     *     {
     *       "message": "Operation was failed"
     *     }
     */
    public function exportReportInit(ExportReportRequest $request)
    {
        try {
            $state = $request->toState();
            $this->logger->debug(sprintf(
                "Attempt to check access user with id = %s permission to export the report",
                $request->getAuthUserId()
            ));
            $this->integrationService->auth($state);

            return response()->redirectTo($state['successRedirect']);
        } catch (AuthException $authException) {
            return new JsonResponse([
                'url' => $authException->getAuthUrl(),
            ], Response::HTTP_PRECONDITION_REQUIRED);
        } catch (RuntimeException $throwable) {
            $this->logger->alert(sprintf("%s%s%s", $throwable->getMessage(), PHP_EOL, $throwable->getTraceAsString()));

            return new JsonResponse(['message' => 'Operation was failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function exportReportEnd(ExportReportEndRequest $request)
    {
        try {
            $this->logger->debug(sprintf(
                "Request [start export in Google Sheet] was received. Content: %s",
                $request->getDecodedStateAsJson()
            ));

            $this->dispatch(new ExportReportInGoogleSheetsJob($request->getDecodedStateAsArray()));
            $this->logger->debug(sprintf("The job %s was pushed to a job queue", ExportReportInGoogleSheetsJob::class));

            return view('google.sheets.export_end_success');
        } catch (Throwable $throwable) {
            $this->logger->error(sprintf(
                "Failed of registering the job %s%s%s%s%s",
                ExportReportInGoogleSheetsJob::class,
                PHP_EOL,
                $throwable->getMessage(),
                PHP_EOL,
                $throwable->getTraceAsString()
            ));

            return view('google.sheets.export_end_fail');
        }
    }
}
