<?php


namespace Tests\Feature\Users;

use App\Jobs\DeleteUser;
use App\Models\User;
use Faker\Factory;
use Illuminate\Http\Response;
use LogicException;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Class DeleteTest
 * @package Tests\Feature\Users
 *
 * @property-read User $admin
 * @property-read User $userInDeleteProcess
 * @property-read User $userIsNotDeleteProcess
 */
class DeleteTest extends TestCase
{
    private const DELETE_IN_PROCESS_TRUE_CASE = 'DELETE_IN_PROCESS_TRUE_CASE';
    private const DELETE_IN_PROCESS_FALSE_CASE = 'DELETE_IN_PROCESS_FALSE_CASE';

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->userInDeleteProcess = UserFactory::refresh()->asUser()
            ->setParams($this->loadUserData(self::DELETE_IN_PROCESS_TRUE_CASE))
            ->create(false);
        $this->userIsNotDeleteProcess = UserFactory::refresh()->asUser()
            ->setParams($this->loadUserData(self::DELETE_IN_PROCESS_FALSE_CASE))
            ->create(false);
    }

    public function test_endpoint_must_return_202_and_job_was_pushed_to_queue()
    {
        $mockAppService = $this->expectsJobs(DeleteUser::class);
        $response = $mockAppService->actingAs($this->admin)
            ->delete(sprintf("users/%s", $this->userIsNotDeleteProcess->id));

        $response->assertStatus(Response::HTTP_ACCEPTED);
    }


    public function test_endpoint_must_return_404()
    {
        $mockAppService = $this->doesntExpectJobs(DeleteUser::class);
        $response = $mockAppService->actingAs($this->admin)->delete(sprintf("users/%s", -1));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_endpoint_must_return_202_and_job_was_not_pushed_to_queue()
    {
        $mockAppService = $this->doesntExpectJobs(DeleteUser::class);
        $response = $mockAppService->actingAs($this->admin)
            ->delete(sprintf("users/%s", $this->userInDeleteProcess->id));

        $response->assertStatus(Response::HTTP_ACCEPTED);
    }

    private function loadUserData(string $type): array
    {
        $userAsArray = json_decode(
            file_get_contents(app_path('../tests/Fixtures/user/deleting/user.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $faker = Factory::create();

        $userAsArray['email'] = $faker->email;

        switch ($type) {
            case self::DELETE_IN_PROCESS_TRUE_CASE:
            {
                $userAsArray['delete_in_process'] = true;
                break;
            }
            case self::DELETE_IN_PROCESS_FALSE_CASE:
            {
                $userAsArray['delete_in_process'] = false;
                break;
            }
            default:
            {
                throw new LogicException('Test contains a error');
            }
        }

        return $userAsArray;
    }
}
