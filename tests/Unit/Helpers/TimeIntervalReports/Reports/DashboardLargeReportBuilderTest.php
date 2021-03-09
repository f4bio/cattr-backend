<?php

namespace Tests\Unit\Helpers\TimeIntervalReports\Reports;

use App\Helpers\TimeIntervalReports\Reports\DashboardLargeReportBuilder;
use Illuminate\Support\Collection;
use RuntimeException;
use stdClass;
use Tests\TestCase;

class DashboardLargeReportBuilderTest extends TestCase
{
    /**
     * @dataProvider dataProvideForSuccessCases
     *
     * @param Collection $input
     * @param array $output
     */
    public function testSuccessCases(Collection $input, array $output)
    {

        $pathToFile = sprintf("%s/%s.test.json", sys_get_temp_dir(), uniqid(time() . '_', true));
        $reportBuilder = new DashboardLargeReportBuilder($pathToFile);

        $reportBuilder->build($input);
        self::assertSame($output, $reportBuilder->getBuiltReport());
    }

    public function dataProvideForSuccessCases()
    {
        return [
            'check output for valid input' => [$this->loadIntervals(), $this->loadReport()],
            'check output for empty input' => [new Collection(), []],
        ];
    }

    public function test_method_must_throw_exception_if_input_is_invalid()
    {
        $pathToFile = sprintf("%s/%s.test.json", sys_get_temp_dir(), uniqid(time() . '_', true));
        $reportBuilder = new DashboardLargeReportBuilder($pathToFile);
        $this->expectException(RuntimeException::class);
        $reportBuilder->build(new Collection([new stdClass()]));
    }

    public function test_method_must_returns_invalid_data()
    {
        $pathToFile = sprintf("%s/%s.test.json", sys_get_temp_dir(), uniqid(time() . '_', true));
        $reportBuilder = new DashboardLargeReportBuilder($pathToFile);

        $reportBuilder->build($this->loadIntervals());
        $report = $this->loadReport();
        array_pop($report);
        self::assertNotEquals($report, $reportBuilder->getBuiltReport());
    }

    private function loadIntervals(): Collection
    {
        return new Collection(json_decode(file_get_contents('./tests/Fixtures/export-in-google-sheets/intervals.json')));
    }

    private function loadReport(): array
    {
        return json_decode(
            file_get_contents('./tests/Fixtures/export-in-google-sheets/built-report-from-intervals.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
