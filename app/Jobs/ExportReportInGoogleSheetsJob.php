<?php

namespace App\Jobs;

use App\Services\External\Google\SheetsService;
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
     * @param SheetsService $sheetsService
     * @return void
     */
    public function handle(
        LoggerInterface $logger,
        SheetsService $sheetsService
    ) {
        $logger->debug(sprintf("Job %s started. State: %s", self::class, json_encode($this->state)));

        try {
            $sheetsService->exportDashboardReport($this->state);
        } catch (Throwable $throwable) {
            $logger->alert(sprintf(
                "Job %s failed. %s%s%s%s",
                self::class,
                PHP_EOL,
                $throwable->getMessage(),
                PHP_EOL,
                $throwable->getTraceAsString()
            ));
            throw $throwable;
        }
    }
}
