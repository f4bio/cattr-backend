<?php


namespace Tests\Unit\Services\External\Google;

use App\Models\User;
use App\Notifications\Reports\ReportWasFailedNotification;
use App\Notifications\Reports\ReportWasSentSuccessfullyNotification;
use App\Queries\TimeInterval\TimeIntervalReportForDashboard;
use App\Services\External\Google\SheetsService;
use Closure;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Notification;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Tests\Facades\UserFactory;
use Tests\TestCase;
use Tests\Utils\Http\Guzzle\Response\Exceptions\ClientExceptionFaker;
use Tests\Utils\Http\Guzzle\Response\FakeResponse;

/**
 * @property-read User $user
 */
class SheetsServiceTest extends TestCase
{
    private const TEST_CASE_PROXY_DO_NOT_SEND_CREATED_SHEET_URL = 'TEST_CASE_PROXY_DO_NOT_SEND_CREATED_SHEET_URL';
    private const TEST_CASE_INTERNAL_PROXY_ERROR = 'TEST_CASE_INTERNAL_PROXY_ERROR';
    private const TEST_CASE_VALIDATION_ERROR = 'TEST_CASE_VALIDATION_ERROR';

    private const TEST_SUCCESS_CASE = 'TEST_SUCCESS_CASE';

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = UserFactory::refresh()->create();
    }

    /**
     * @dataProvider dataProvideForFailCases
     */
    public function test_fail_cases(string $case)
    {
        Notification::fake();
        $service = $this->createMockedService($case);
        $service->exportDashboardReport($this->getValidInputForExport());
        Notification::assertSentTo([$this->user], ReportWasFailedNotification::class);
    }

    public function test_success_cases()
    {
        Notification::fake();
        $service = $this->createMockedService(self::TEST_SUCCESS_CASE);
        $service->exportDashboardReport($this->getValidInputForExport());
        Notification::assertSentTo([$this->user], ReportWasSentSuccessfullyNotification::class);
    }

    public function dataProvideForFailCases()
    {
        return [
            self::TEST_CASE_PROXY_DO_NOT_SEND_CREATED_SHEET_URL => [
                self::TEST_CASE_PROXY_DO_NOT_SEND_CREATED_SHEET_URL,
            ],
            self::TEST_CASE_INTERNAL_PROXY_ERROR => [self::TEST_CASE_INTERNAL_PROXY_ERROR],
            self::TEST_CASE_VALIDATION_ERROR => [self::TEST_CASE_VALIDATION_ERROR],
        ];
    }

    private function createMockedService(string $case): SheetsService
    {
        return new SheetsService(
            new NullLogger(),
            new TimeIntervalReportForDashboard(),
            $this->createMockedHttpClient($this->getMockeryCallbackForTestCase($case))
        );
    }

    /**
     * @param Closure $mockeryCallback
     * @return object|ClientInterface
     */
    private function createMockedHttpClient(Closure $mockeryCallback)
    {
        return $this->instance(
            ClientInterface::class,
            Mockery::mock(ClientInterface::class, $mockeryCallback)
        );
    }

    /**
     * @param string $case
     * @return Closure
     */
    private function getMockeryCallbackForTestCase(string $case)
    {
        switch ($case) {
            case self::TEST_CASE_PROXY_DO_NOT_SEND_CREATED_SHEET_URL:
                return static function (MockInterface $mock) {
                    $mock->shouldReceive('request')
                        ->andReturn(FakeResponse::createFromJsonEncodeBody(['message' => 'Ok'])->generate());
                };
            case self::TEST_CASE_INTERNAL_PROXY_ERROR:
                return static function (MockInterface $mock) {
                    $mock->shouldReceive('request')
                        ->andThrow(new RuntimeException('Export failed'));
                };
            case self::TEST_SUCCESS_CASE:
                return static function (MockInterface $mock) {
                    $mock->shouldReceive('request')
                        ->andReturn(FakeResponse::createFromJsonEncodeBody(['url' => 'url'])->generate());
                };
            case self::TEST_CASE_VALIDATION_ERROR:
                return static function (MockInterface $mock) {
                    $mock->shouldReceive('request')
                        ->andThrow(ClientExceptionFaker::createFromJsonEncodeBody(['message' => 'Validation Error'])
                            ->generate());
                };
            default:
                throw new LogicException('Logic exception. Your test case contains an error.');
        }
    }

    private function getValidInputForExport(): array
    {
        $data = json_decode(
            file_get_contents('tests/Fixtures/export-in-google-sheets/valid-input.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $data['userId'] = $this->user->id;

        return $data;
    }
}
