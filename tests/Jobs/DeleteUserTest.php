<?php

namespace Tests\Jobs;

use App\Helpers\ServiceAccountHelper;
use App\Jobs\DeleteUser;
use App\Models\User;
use Faker\Factory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Class DeleteUserTest
 * @package Tests\Jobs
 *
 * @property-read User $userInDeleteProcess
 */
class DeleteUserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->userInDeleteProcess = UserFactory::refresh()->asUser()
            ->setParams($this->loadUserData())
            ->create(false);
    }

    public function test_job_end_if_user_is_not_exists()
    {
        $job = new DeleteUser(-1);
        $job->handle(app(ServiceAccountHelper::class));
        self::assertTrue(true);
    }

    public function test_success_case()
    {
        $job = new DeleteUser($this->userInDeleteProcess->id);
        $serviceAccountHelper = app(ServiceAccountHelper::class);
        [$userTaskIds, $userTimeIntervalIds, $userTaskCommentIds] = [
            $this->getUsersTasks($this->userInDeleteProcess),
            $this->getUsersTimeIntervals($this->userInDeleteProcess),
            $this->getTasksComments($this->userInDeleteProcess)
        ];
        $job->handle($serviceAccountHelper);

        $this->checkThatTasksWasSavedInServiceAccount(
            $serviceAccountHelper,
            $userTaskIds,
            $userTimeIntervalIds,
            $userTaskCommentIds
        );
        self::assertNull(User::query()->find($this->userInDeleteProcess->id));
    }

    private function loadUserData(): array
    {
        $userAsArray = json_decode(
            file_get_contents(app_path('../tests/Fixtures/user/deleting/user.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $faker = Factory::create();
        $userAsArray['email'] = $faker->email;

        return $userAsArray;
    }

    /**
     * @param User $user
     * @return array|integer[]
     */
    private function getUsersTasks(User $user): array
    {
        return $user->tasks()
            ->get(['task_id'])
            ->map(fn($item) => $item->task_id)
            ->toArray();
    }

    /**
     * @return array|integer[]
     */
    private function getUsersTimeIntervals(User $user): array
    {
        return $user->timeIntervals()
            ->get(['id'])
            ->map(fn($item) => $item['id'])
            ->toArray();
    }

    /**
     * @return array|integer[]
     */
    private function getTasksComments(User $user): array
    {
        return $user->tasksComments()
            ->get(['id'])
            ->map(fn($item) => $item['id'])
            ->toArray();
    }

    private function checkThatTasksWasSavedInServiceAccount(
        ServiceAccountHelper $serviceAccountHelper,
        array $userTaskIds,
        array $userTimeIntervalIds,
        array $userTaskCommentIds
    ): void {
        $serviceAccount = $serviceAccountHelper->findOrCreate();
        [$SATaskIds, $SATimeIntervalIds, $SATaskCommentIds] = [
            $this->getUsersTasks($serviceAccount),
            $this->getUsersTimeIntervals($serviceAccount),
            $this->getTasksComments($serviceAccount)
        ];

        foreach ($userTaskIds as $item) {
            self::assertContains($item, $SATaskIds);
        }

        foreach ($userTimeIntervalIds as $item) {
            self::assertContains($item, $SATimeIntervalIds);
        }

        foreach ($userTaskCommentIds as $item) {
            self::assertContains($item, $SATaskCommentIds);
        }
    }
}
