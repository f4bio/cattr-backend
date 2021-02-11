<?php


namespace App\Services\External\Google;

use App\Exceptions\ExternalServices\Google\Sheets\ExportException;
use App\Helpers\ExternalServices\Google\Sheets\Reports\TimeIntervals\DashboardReportBuilder;
use App\Services\TimeIntervalService;
use Carbon\Carbon;
use Google_Client;
use Illuminate\Support\Facades\Validator;
use JsonException;
use Throwable;

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
        $startAt = $params['start_at'];
        $endAt = $params['end_at'];
        $params = $this->prepareParamsForDashboardQuery($params);
        $intervals = $this->timeIntervalService->getReportForDashBoard($params);
        $title = sprintf("Project Report from %s to %s", $startAt, $endAt);

        try {
            return (new DashboardReportBuilder($this->googleClient))->build($intervals, $title);
        } catch (Throwable $throwable) {
            throw new ExportException($throwable->getMessage(), $throwable);
        }
    }

    /**
     * @param array $params
     * @return array
     * @throws ExportException
     */
    private function prepareParamsForDashboardQuery(array $params): array
    {
        $validator = Validator::make($params, [
            'user_ids' => 'exists:users,id|array',
            'project_ids' => 'nullable|exists:projects,id|array',
            'start_at' => 'date|required',
            'end_at' => 'date|required',
        ]);

        if ($validator->fails()) {
            throw ExportException::fromMessageAndInvalidParams(
                'Input parameters are invalid',
                $validator->messages()->all()
            );
        }

        $userIds = $params['user_ids'] ?? [];
        $projectIds = $params['projectIds'] ?? [];
        $timezone = $params['timezone'] ?: 'UTC';
        $timezoneOffset = (new Carbon())->setTimezone($timezone)->format('P');
        $startAt = Carbon::parse($params['start_at'], $timezone)
            ->tz('UTC')
            ->toDateTimeString();
        $endAt = Carbon::parse($params['end_at'], $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        return compact(
            'startAt',
            'endAt',
            'timezoneOffset',
            'projectIds',
            'userIds'
        );
    }
}
