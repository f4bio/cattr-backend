<?php

namespace Tests\Unit\Services\External\Google;

use App\Exceptions\ExternalServices\Google\AuthException;
use App\Http\Requests\Google\Sheets\ExportReportRequest;
use App\Services\External\Google\IntegrationService;
use Closure;
use GuzzleHttp\ClientInterface;
use Illuminate\Http\Response;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Tests\TestCase;
use Tests\Utils\Http\Guzzle\Response\Exceptions\ClientExceptionFaker;
use Tests\Utils\Http\Guzzle\Response\FakeResponse;

class IntegrationServiceTest extends TestCase
{
    private const TEST_CASE_NEED_AUTH = 'need_auth';
    private const TEST_CASE_OTHER_CLIENT_ERROR = 'client_err';
    private const TEST_CASE_ERROR = 'err';

    private const TEST_CASE_OK = 'ok';
    private const TEST_CASE_UNKNOWN_SUCCESS_RESPONSE = 'unknown success case';

    private const AUTH_URL = 'url';

    /**
     * @param string $expectedException
     * @param string $case
     *
     * @dataProvider dataProviderForFailCases
     */
    public function test_fail_cases(string $expectedException, string $case)
    {
        $service = $this->createIntegrationService($case);
        $this->expectException($expectedException);
        $service->auth($this->getValidState());
    }

    public function dataProviderForFailCases()
    {
        return [
            self::TEST_CASE_NEED_AUTH => [AuthException::class, self::TEST_CASE_NEED_AUTH],
            self::TEST_CASE_OTHER_CLIENT_ERROR => [RuntimeException::class, self::TEST_CASE_OTHER_CLIENT_ERROR],
            self::TEST_CASE_ERROR => [RuntimeException::class, self::TEST_CASE_ERROR],
        ];
    }

    /**
     * @param string $case
     *
     * @dataProvider dataProvideForSuccessCases
     */
    public function test_success_cases(string $case)
    {
        $service = $this->createIntegrationService($case);
        $service->auth($this->getValidState());
        self::assertTrue(true);
    }

    public function dataProvideForSuccessCases()
    {
        return [
            self::TEST_CASE_OK => [self::TEST_CASE_OK],
            self::TEST_CASE_UNKNOWN_SUCCESS_RESPONSE => [self::TEST_CASE_UNKNOWN_SUCCESS_RESPONSE],
        ];
    }

    private function createIntegrationService(string $case)
    {
        return new IntegrationService($this->createMockedHttpClient(
            $this->getMockeryCallbackForTestCase($case)
        ), new NullLogger());
    }

    /**
     * @param CLosure $mockeryCallback
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
            case self::TEST_CASE_NEED_AUTH:
                return function (MockInterface $mock) {
                    $mock->shouldReceive('request')
                        ->andThrow($this->getMockedUnauthorizedResponse());
                };
            case self::TEST_CASE_OTHER_CLIENT_ERROR:
                return function (MockInterface $mock) {
                    $mock->shouldReceive('request')
                        ->andThrow($this->getMockedOtherClientError());
                };
            case self::TEST_CASE_ERROR:
                return static function (MockInterface $mock) {
                    $mock->shouldReceive('request')
                        ->andThrow(RuntimeException::class);
                };
            case self::TEST_CASE_OK:
                return static function (MockInterface $mock) {
                    $mock->shouldReceive('request')
                        ->andReturn((new FakeResponse('', Response::HTTP_NO_CONTENT))
                            ->generate());
                };
            case self::TEST_CASE_UNKNOWN_SUCCESS_RESPONSE:
                return static function (MockInterface $mock) {
                    $mock->shouldReceive('request')
                        ->andReturn(FakeResponse::createFromJsonEncodeBody(['message' => 'Ok'])
                            ->generate());
                };
            default:
                throw new LogicException('Logic exception. Your test case contains an error.');
        }
    }

    private function getValidState(): array
    {
        return (new class(['start_at' => '2021-02-10', 'end_at' => '2021-02-10',]) extends ExportReportRequest {
            public function authorize(): bool
            {
                return true;
            }

            public function getAuthUserId(): int
            {
                return 1;
            }
        })->toState();
    }

    private function getMockedUnauthorizedResponse()
    {
        return ClientExceptionFaker::createFromJsonEncodeBody(
            ['url' => self::AUTH_URL],
            Response::HTTP_UNAUTHORIZED
        )->generate();
    }

    private function getMockedOtherClientError()
    {
        return ClientExceptionFaker::createFromJsonEncodeBody(
            ['message' => 'Validation error', 'invalid_params' => 'The state field is required.'],
            Response::HTTP_UNPROCESSABLE_ENTITY
        )->generate();
    }
}
