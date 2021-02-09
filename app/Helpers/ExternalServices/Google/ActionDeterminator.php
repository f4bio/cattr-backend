<?php

namespace App\Helpers\ExternalServices\Google;

use App\Exceptions\ExternalServices\Google\ActionDeterminationException;
use Google_Service_Oauth2;
use Google_Service_Sheets;

class ActionDeterminator
{
    public const ACTION_EXPORT_REPORT_TO_GOOGLE_SHEETS = 'EXPORT_REPORT_TO_GOOGLE_SHEETS';

    /**
     * @param string $actionId
     * @return DTO\Action
     * @throws ActionDeterminationException
     */
    public function determinate(string $actionId): DTO\Action
    {
        if ($actionId === self::ACTION_EXPORT_REPORT_TO_GOOGLE_SHEETS) {
            return new DTO\Action('https://example.com/google/oauth/end', [
                Google_Service_Sheets::SPREADSHEETS,
                Google_Service_Oauth2::USERINFO_PROFILE,
                Google_Service_Oauth2::USERINFO_EMAIL,
                Google_Service_Oauth2::OPENID,
            ]);
        }

        throw new ActionDeterminationException();
    }
}
