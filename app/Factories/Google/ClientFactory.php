<?php


namespace App\Factories\Google;

use App\Services\CoreSettingsService;
use Google_Client;

class ClientFactory
{

    public function __construct(CoreSettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function create(): Google_Client
    {
        $client = new Google_Client();
        $client->setApplicationName('Cattr');
        $client->setAuthConfig([
            'web' => [
                'client_id' => $this->settings->get('google_client_id'),
                'project_id' => $this->settings->get('google_project_id'),
                "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
                "token_uri" => "https://oauth2.googleapis.com/token",
                "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
                "client_secret" => $this->settings->get('google_client_secret'),
                "redirect_uris" => [
                    sprintf("https://%s/time-intervals/dashboard/export-in-sheets", config('app.domain'))
                ]
            ]
        ]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }
}
