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
            $client->setAuthConfig(app_path('../credentials.json'));
            $client->setAccessType('offline');

            return $client;
        });
    }
}
