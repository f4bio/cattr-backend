<?php

namespace Tests\Jobs;

use App\Jobs\ExportReportInGoogleSheetsJob;
use App\Services\External\Google\SheetsService;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Tests\TestCase;

class ExportReportInGoogleSheetsJobTest extends TestCase
{
    public function test_success_case()
    {
        $state = [
            'startAt' => '2020-02-10 00:00:00',
            'end_at' => '2020-02-10 00:00:00',
            'timezoneOffset' => '+00:00',
            'projectIds' => null,
            'userIds' => null,
            'instanceId' => 'b2358330-5c7a-449b-8ed4-7c08160a40bf',
            'userId' => 1
        ];

        $job = new ExportReportInGoogleSheetsJob($state);
        /* @var SheetsService $sheetsService */
        $sheetsService = $this->instance(
            SheetsService::class,
            Mockery::mock(SheetsService::class, function (MockInterface $mock) {
                $mock->shouldReceive('exportDashboardReport');
            })
        );
        $job->handle(new NullLogger(), $sheetsService);
        $this->assertTrue(true);
    }

    public function test_fail_case()
    {
        $this->expectException(RuntimeException::class);
        $state = [
            'startAt' => '2020-02-10 00:00:00',
            'end_at' => '2020-02-10 00:00:00',
            'timezoneOffset' => '+00:00',
            'projectIds' => null,
            'userIds' => null,
            'instanceId' => 'b2358330-5c7a-449b-8ed4-7c08160a40bf',
        ];

        $job = new ExportReportInGoogleSheetsJob($state);
        /* @var SheetsService $sheetsService */
        $sheetsService = $this->instance(
            SheetsService::class,
            Mockery::mock(SheetsService::class, function (MockInterface $mock) {
                $mock->shouldReceive('exportDashboardReport')->andThrow(RuntimeException::class);
            })
        );
        $job->handle(new NullLogger(), $sheetsService);
    }
}
