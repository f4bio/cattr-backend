<?php


namespace Tests\Feature\ExportInGoogleSheets;

use App\Jobs\ExportReportInGoogleSheetsJob;
use Tests\TestCase;

class TestEndExport extends TestCase
{
    private const URI = 'time-intervals/dashboard/export-in-sheets/end';
    private const VALID_STATE = <<<VS
'eyJzdGFydEF0IjoiMjAyMC0wMi0xMCAwMDowMDowMCIsImVuZEF0IjoiMjAyMC0wMi0xMCAwMDowMDowMCIsInRpbWV6b25lT2Zmc2V0IjoiKzAwOjAwIiwicHJvamVjdElkcyI6bnVsbCwidXNlcklkcyI6bnVsbCwiaW5zdGFuY2VJZCI6ImIyMzU4MzMwLTVjN2EtNDQ5Yi04ZWQ0LTdjMDgxNjBhNDBiZiIsInVzZXJJZCI6MSwic3VjY2Vzc1JlZGlyZWN0IjoiaHR0cDpcL1wvbG9jYWxob3N0OjgwMDBcL3RpbWUtaW50ZXJ2YWxzXC9kYXNoYm9hcmRcL2V4cG9ydC1pbi1zaGVldHNcL2VuZD9zdGF0ZT1leUp6ZEdGeWRFRjBJam9pTWpBeU1DMHdNaTB4TUNBd01Eb3dNRG93TUNJc0ltVnVaRUYwSWpvaU1qQXlNQzB3TWkweE1DQXdNRG93TURvd01DSXNJblJwYldWNmIyNWxUMlptYzJWMElqb2lLekF3T2pBd0lpd2ljSEp2YW1WamRFbGtjeUk2Ym5Wc2JDd2lkWE5sY2tsa2N5STZiblZzYkN3aWFXNXpkR0Z1WTJWSlpDSTZJbUl5TXpVNE16TXdMVFZqTjJFdE5EUTVZaTA0WldRMExUZGpNRGd4TmpCaE5EQmlaaUlzSW5WelpYSkpaQ0k2TVgwJTNEIn0%3D'
VS;
    private const INVALID_STATE = 'ff';

    public function test_success_case(): void
    {
        $mockAppService = $this->expectsJobs(ExportReportInGoogleSheetsJob::class);
        $response = $mockAppService->get(sprintf("%s?state=%s", self::URI, self::VALID_STATE));
        $response->assertViewIs('google.sheets.export_end_success');
    }

    public function test_fail_case(): void
    {
        $mockAppService = $this->doesntExpectJobs(ExportReportInGoogleSheetsJob::class);
        $response = $mockAppService->get(sprintf("%s?state=%s", self::URI, self::INVALID_STATE));
        $response->assertViewIs('google.sheets.export_end_fail');
    }

    public function test_for_validation_error_case(): void
    {
        $response = $this->get(self::URI);
        $response->assertValidationError();
    }
}
