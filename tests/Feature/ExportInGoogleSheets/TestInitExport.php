<?php


namespace Tests\Feature\ExportInGoogleSheets;

use App\Http\Controllers\Api\Google\ExportController;
use App\Http\Requests\Google\Sheets\ExportReportRequest;
use App\Models\User;
use App\Services\External\Google\IntegrationService;
use Illuminate\Http\Response;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class TestInitExport extends TestCase
{
    private const URI = 'time-intervals/dashboard/export-in-sheets';
    private User $user;
    private User $admin;


    public function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::withTokens()->asUser()->create();
        $this->admin = UserFactory::withTokens()->asAdmin()->create();
    }

    public function test_as_user_without_required_params_must_return_validation_error(): void
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);
        $response->assertValidationError();
    }

    public function test_as_anonymous_must_request_authentication(): void
    {
        $this->getJson(self::URI)->assertUnauthorized();
    }

    public function test_need_auth_via_google(): void
    {
        $uri = self::URI . '?' . http_build_query(['start_at' => '2021-02-10', 'end_at' => '2021-02-10']);
        $response = $this->actingAs($this->user)->getJson($uri);
        $response->assertStatus(Response::HTTP_PRECONDITION_REQUIRED);
        $response->assertJsonStructure(['url']);
    }

    public function test_endpoint_must_return_internal_server_error_if_was_threw_runtime_exception(): void
    {
        $response = $this->instanceControllerForFailUseCase(function (MockInterface $mock) {
            $mock->shouldReceive('auth')->andThrow(RuntimeException::class);
        })->exportReportInit($this->instanceExportReportInit(['start_at' => '2021-02-10', 'end_at' => '2021-02-10',]));

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertSame(['message' => 'Operation was failed'], $response->getData(true));
    }

    public function test_redirect_to_end_if_auth_ok()
    {
        $request = $this->instanceExportReportInit(['start_at' => '2021-02-10', 'end_at' => '2021-02-10',]);
        $response = $this->instanceControllerForFailUseCase(function (MockInterface $mock) {
            $mock->shouldReceive('auth')->andReturn();
        })->exportReportInit($request);

        $successRedirect = $request->toState()['successRedirect'];
        self::assertTrue($response->isRedirect());
        self::assertSame($successRedirect, $response->getTargetUrl());
        [$splitURL, $queryParams] = explode('?', $response->getTargetUrl());
        $actualURL = str_replace('127.0.0.1', 'localhost', route('export-in-sheets-end'));
        self::assertSame($splitURL, $actualURL);
    }

    /**
     * @param callable $mockeryCallback
     * @return ExportController|object
     */
    private function instanceControllerForFailUseCase(callable $mockeryCallback)
    {
        /* @var IntegrationService $integrationService */
        $integrationService = $this->instance(
            IntegrationService::class,
            Mockery::mock(IntegrationService::class, $mockeryCallback)
        );

        return $this->instance(
            ExportController::class,
            new ExportController(new NullLogger(), $integrationService)
        );
    }

    private function instanceExportReportInit(array $queryParams = []): ExportReportRequest
    {
        return new class($queryParams) extends ExportReportRequest {
            public function authorize(): bool
            {
                return true;
            }

            public function getAuthUserId(): int
            {
                return 1;
            }
        };
    }
}
