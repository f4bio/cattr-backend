<?php


namespace App\Services\External\Google;

use App\Exceptions\ExternalServices\Google\Sheets\ExportException;
use App\Helpers\ExternalServices\Google\Sheets\Reports\TimeIntervals\DashboardReportBuilder;
use App\Services\TimeIntervalService;
use Google_Client;
use JsonException;

class SheetsService
{
    private Google_Client $googleClient;
    private TimeIntervalService $timeIntervalService;

    public function __construct(Google_Client $googleClient, TimeIntervalService $timeIntervalService)
    {
        $this->googleClient = $googleClient;
        $this->timeIntervalService = $timeIntervalService;
    }

    /**
     * @param string $code - contains user's access token for Google service
     * @param string $state - encoded parameters of query
     * @return string - created sheet's URL
     * @throws ExportException
     */
    public function exportDashboardReport(string $code, string $state): string
    {
        try {
            $token = $this->googleClient->fetchAccessTokenWithAuthCode($code);
            $accessToken = $token['access_token'] ?? null;

            if (!$accessToken) {
                throw ExportException::fromMessageAndInvalidParams(
                    'Failed fetching of the access token',
                    ['code' => 'Parameter is invalid']
                );
            }

            $params = json_decode(base64_decode($state), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw ExportException::fromMessageAndInvalidParams(
                'Failed parse state parameter',
                ['state' => 'Parameter STATE must be base64(json()) encoded']
            );
        }

        $this->googleClient->setAccessToken($accessToken);
        $intervals = $this->timeIntervalService->getReportForDashBoard($params);

        return (new DashboardReportBuilder($this->googleClient))->build($intervals);
    }
}
