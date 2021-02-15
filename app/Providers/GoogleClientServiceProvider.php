<?php

namespace App\Providers;

use Google_Client;
use Illuminate\Support\ServiceProvider;

class GoogleClientServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(Google_Client::class, function () {
            $client = new Google_Client();
            $client->setApplicationName('Cattr');
            $config = [
                'web' => [
                    'client_id' => config('app.google.client_id'),
                    'project_id' => config('app.google.project_id'),
                    "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
                    "token_uri" => "https://oauth2.googleapis.com/token",
                    "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
                    "client_secret" => config('app.google.client_secret'),
                    "redirect_uris" => [
                        sprintf("https://%s/time-intervals/dashboard/export-in-sheets", config('app.domain'))
                    ]
                ]
            ];
            $client->setAuthConfig($config);
            $client->setAccessType('offline');
            $client->setPrompt('consent');

            return $client;
        });
    }
}
