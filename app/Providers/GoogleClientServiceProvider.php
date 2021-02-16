<?php

namespace App\Providers;

use App\Factories\Google\ClientFactory;
use Google_Client;
use Illuminate\Support\ServiceProvider;

class GoogleClientServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(Google_Client::class, function () {
            return $this->app->make(ClientFactory::class)->create();
        });
    }
}
