<?php


namespace App\Services\External\Google;

use App\Helpers\TimeIntervalReports\Reports\DashboardLargeReportBuilder;
use App\Queries\TimeInterval\TimeIntervalReportForDashboard;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Response;
use JsonException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class SheetsService
{
    private LoggerInterface $logger;
    private TimeIntervalReportForDashboard $query;
    private Client $httpClient;

    public function __construct(LoggerInterface $logger, TimeIntervalReportForDashboard $query, Client $httpClient)
    {

        $this->logger = $logger;
        $this->query = $query;
        $this->httpClient = $httpClient;
    }

    public function exportDashboardReport(array $params): void
    {
        $pathToFile = sprintf("%s/%s.json", sys_get_temp_dir(), uniqid(time() . '_', true));
        $this->logger->debug(sprintf(
            "The system is going to build a report to export in Google Sheet intermediate file = %s",
            $pathToFile
        ));
        $reportBuilder = new DashboardLargeReportBuilder($pathToFile, $this->logger);
        $this->query->buildQuery($params)->chunk(10000, [$reportBuilder, 'build']);
        $this->logger->debug(sprintf(
            "The report to export in Google Sheet was built as a file with a path %s",
            $pathToFile
        ));
        $this->sendRequestExportReportToGoogleProxy($params, $reportBuilder->getBuiltReport());
    }

    /**
     * @param array $state
     * @param array $report
     * @throws RuntimeException
     */
    private function sendRequestExportReportToGoogleProxy(array $state, array $report): void
    {
        try {
            $this->trySendRequestExportReportToGoogleProxy($state, $report);
        } catch (ClientException $clientException) {
            $failedResponse = $clientException->getResponse();
            $this->logger->alert(sprintf(
                "Sending the request to export in Google Sheets was failed.%s Status: %s%s Body: %s%s%s%s%s",
                PHP_EOL,
                $failedResponse->getStatusCode(),
                PHP_EOL,
                $failedResponse->getBody()->getContents(),
                PHP_EOL,
                $clientException->getMessage(),
                PHP_EOL,
                $clientException->getTraceAsString()
            ));
        } catch (Throwable $throwable) {
            $this->logger->alert(sprintf(
                "Sending the request to export in Google Sheets was failed.%s%s%s%s",
                PHP_EOL,
                $throwable->getMessage(),
                PHP_EOL,
                $throwable->getTraceAsString()
            ));
        }
    }

    /**
     * @param array $state
     * @param array $report
     * @throws GuzzleException
     * @throws JsonException
     * @throws RuntimeException
     */
    private function trySendRequestExportReportToGoogleProxy(array $state, array $report): void
    {
        $body = [
            'report' => $report,
            'state' => $state
        ];
        $endpoint = sprintf("%s/api/v1/google-sheet-report", config('app.google_integration_bus.url'));

        $this->logger->debug(sprintf(
            "The system is going to send a request to export report in Google Sheet.%sBody: %s%sURI: %s",
            PHP_EOL,
            json_encode($body, JSON_THROW_ON_ERROR),
            PHP_EOL,
            $endpoint
        ));
        $successResponse = $this->httpClient->request(
            'POST',
            $endpoint,
            [
                RequestOptions::JSON => $body,
            ]
        );

        if ($successResponse->getStatusCode() === Response::HTTP_NO_CONTENT) {
            $this->logger->debug('Export in Google Sheets was done successfully');

            return;
        }

        throw new RuntimeException(sprintf(
            "The system received a response with unknown response code %sBody:%s%sStatus%s",
            PHP_EOL,
            $successResponse->getBody()->getContents(),
            PHP_EOL,
            $successResponse->getStatusCode()
        ));
    }
}
