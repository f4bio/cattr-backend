<?php

namespace App\Jobs;

use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use Throwable;

class ExportReportInGoogleSheetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $state;

    public function __construct(array $state)
    {
        $this->state = $state;
    }

    /**
     * Execute the job.
     *
     * @param LoggerInterface $logger
     * @param Client $httpClient
     * @return void
     */
    public function handle(LoggerInterface $logger, Client $httpClient)
    {
        $logger->debug(sprintf("Job %s started. State: %s", self::class, json_encode($this->state)));

        try {

//            $httpClient->request(
//                'POST',
//                sprintf(
//                    "%s/api/v1/google-sheet-report",
//                    config('app.google_integration_bus.url'),
//                ),
//                [
//                    RequestOptions::JSON => [
//                        'state' => base64_encode(json_encode($this->state, JSON_THROW_ON_ERROR)),
//                        'userId' => $this->state['userId'],
//                        'instanceId' => $this->state['instanceId'],
//                    ]
//                ]
//            );
            // get report: extract & build
            // export via api
            // send notification OK
        } catch (Throwable $throwable) {
            $logger->alert(sprintf(
                "Job %s failed. %s%s%s%s",
                self::class,
                PHP_EOL,
                $throwable->getMessage(),
                PHP_EOL,
                $throwable->getTraceAsString()
            ));
            //send notification FAIL
        }
    }
}
