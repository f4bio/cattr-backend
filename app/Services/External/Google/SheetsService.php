<?php


namespace App\Services\External\Google;

use App\Helpers\TimeIntervalReports\Reports\DashboardLargeReportBuilder;
use App\Models\User;
use App\Notifications\Reports\ReportWasFailedNotification;
use App\Notifications\Reports\ReportWasSentSuccessfullyNotification;
use App\Queries\TimeInterval\TimeIntervalReportForDashboard;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use JsonException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class SheetsService
{
    private LoggerInterface $logger;
    private TimeIntervalReportForDashboard $query;
    private ClientInterface $httpClient;

    public function __construct(
        LoggerInterface $logger,
        TimeIntervalReportForDashboard $query,
        ClientInterface $httpClient
    ) {
        $this->logger = $logger;
        $this->query = $query;
        $this->httpClient = $httpClient;
    }

    public function exportDashboardReport(array $params): void
    {
        $user = $this->findUser($params);

        try {
            $this->handleExportSuccessEnd($user, $this->tryExportReport($params));
        } catch (Throwable $throwable) {
            $this->handleExportFailed($throwable, $user);
        }
    }

    private function findUser($params): ?User
    {
        $user = User::find($params['userId']);

        if ($user !== null) {
            $this->logger->warning(sprintf(
                'The system can\'t to send a notification because of user with id = %s was not found in the DB',
                $params['userId']
            ));
        }

        return $user;
    }

    private function handleExportSuccessEnd(?User $user, string $url): void
    {
        try {
            if ($user ?? null) {
                $user->sendNotificationExportWasEndedSuccessfully($url);
            }
        } catch (Throwable $throwable) {
            $this->logger->alert(sprintf(
                "Sending a notification %s was failed",
                ReportWasSentSuccessfullyNotification::class
            ));
        }
    }

    private function handleExportFailed(Throwable $throwable, ?User $user): void
    {
        $this->logger->alert(sprintf(
            "Export was failed.%s%s%s%s",
            PHP_EOL,
            $throwable->getMessage(),
            PHP_EOL,
            $throwable->getTraceAsString()
        ));

        try {
            if ($user ?? null) {
                $user->sendNotificationExportFailed();
            }
        } catch (Throwable $throwable) {
            $this->logger->alert('Sending a notification ' . ReportWasFailedNotification::class . ' was failed');
        }
    }

    /**
     * @param array $state
     * @param array $report
     * @return string - return url to the created sheet
     * @throws RuntimeException
     */
    private function sendRequestExportReportToGoogleProxy(array $state, array $report): string
    {
        try {
            return $this->trySendRequestExportReportToGoogleProxy($state, $report);
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

            throw $clientException;
        } catch (Throwable $throwable) {
            $this->logger->alert(sprintf(
                "Sending the request to export in Google Sheets was failed.%s%s%s%s",
                PHP_EOL,
                $throwable->getMessage(),
                PHP_EOL,
                $throwable->getTraceAsString()
            ));

            throw $throwable;
        }
    }

    /**
     * @param array $state
     * @param array $report
     * @return string - return url to the created sheet
     * @throws GuzzleException
     * @throws JsonException
     * @throws RuntimeException
     */
    private function trySendRequestExportReportToGoogleProxy(array $state, array $report): string
    {
        $body = ['report' => $report, 'state' => $state];
        $headers = ['Cattr-user-id' => $state['userId'], 'Cattr-instance-id' => $state['instanceId'],];
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
                RequestOptions::HEADERS => $headers,
                RequestOptions::JSON => $body,
            ]
        );
        $content = $successResponse->getBody()->getContents();
        $this->logger->debug(sprintf(
            "The system received success response. Status: %s Content: %s",
            $successResponse->getStatusCode(),
            $content
        ));

        try {
            $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            return $content['url'];
        } catch (Throwable $throwable) {
            throw new RuntimeException(sprintf("Response is not contains field 'url' (link to created spreadsheet)"));
        }
    }

    /**
     * @param array $params
     * @return string
     */
    private function tryExportReport(array $params): string
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
        $this->logger->debug('The system is going to send a request to export the report to Google Proxy');
        $url = $this->sendRequestExportReportToGoogleProxy($params, $reportBuilder->getBuiltReport());
        $this->logger->debug('Report was exported successfully');

        return $url;
    }
}
