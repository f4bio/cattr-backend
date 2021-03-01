<?php

namespace App\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class HttpClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ClientInterface::class, static function ($app) {
            /* @var Container $app */
            return $app->make(Client::class);
        });
    }
}
