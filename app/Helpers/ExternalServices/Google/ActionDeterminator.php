<?php

namespace App\Helpers\ExternalServices\Google;

use App\Exceptions\ExternalServices\Google\ActionDeterminationException;
use Google_Service_Oauth2;
use Google_Service_Sheets;

class ActionDeterminator
{
    public const ACTION_EXPORT_REPORT_TO_GOOGLE_SHEETS = 'export_report_to_google_sheets';

    /**
     * @param string $actionId
     * @param array $params
     * @return DTO\Action
     */
    public function determinate(string $actionId, array $params = []): DTO\Action
    {
        if ($actionId === self::ACTION_EXPORT_REPORT_TO_GOOGLE_SHEETS) {
            return $this->buildActionForExportInSheets($params);
        }

        throw new ActionDeterminationException();
    }

    private function buildActionForExportInSheets(array $params): DTO\Action
    {
        $uri = sprintf("https://%s/time-intervals/dashboard/export-in-sheets", config('app.domain'));

        return new DTO\Action($uri, [
            Google_Service_Sheets::SPREADSHEETS,
            Google_Service_Oauth2::USERINFO_PROFILE,
            Google_Service_Oauth2::USERINFO_EMAIL,
            Google_Service_Oauth2::OPENID,
        ], empty($params) ? null : base64_encode(json_encode($params)));
    }
}
