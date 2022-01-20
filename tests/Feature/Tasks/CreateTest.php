<?php

namespace Tests\Feature\Tasks;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private const URI = 'tasks/create';

    private $admin;
    private $manager;
    private $auditor;
    private Model $user;

    private Model $projectManager;
    private Model $projectAuditor;
    private Model $projectUser;

    private array $taskData;
    private array $taskRequest;
    private array $taskRequestWithMultipleUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->manager = User::factory()->asManager()->create();
        $this->admin = User::factory()->asAdmin()->create();
        $this->auditor = User::factory()->asAuditor()->create();

        $this->taskData = array_merge(
            Task::factory()->make()
            ->makeHidden('can', 'updated_at', 'started_at')
            ->toArray(),
            [
                'project_id' => Project::factory()->create()->id,
            ]
        );

        $this->taskRequest = array_merge($this->taskData, [
            'users' => [User::factory()->create()->id],
        ]);

        $this->taskRequestWithMultipleUsers = array_merge($this->taskData, [
            'users' => [
                User::factory()->create()->id,
                User::factory()->create()->id,
                User::factory()->create()->id,
            ],
        ]);

        $this->projectManager = User::factory()->create();
        $this->projectManager->projects()->attach($this->taskData['project_id'], ['role_id' => 1]);

        $this->projectAuditor = User::factory()->create();
        $this->projectAuditor->projects()->attach($this->taskData['project_id'], ['role_id' => 3]);

        $this->projectUser = User::factory()->create();
        $this->projectUser->projects()->attach($this->taskData['project_id'], ['role_id' => 2]);
    }

    public function test_create_without_user(): void
    {
        $this->taskRequest['users'] = [];

        $this->assertDatabaseMissing('tasks', $this->taskData);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->taskRequest);

        $response->assertSuccess();
        $response->assertJson(['res' => $this->taskData]);
        $this->assertDatabaseHas('tasks', $this->taskData);
    }

    public function test_create_as_admin(): void
    {
        $this->assertDatabaseMissing('tasks', $this->taskData);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->taskRequest);

        $response->assertOk();
        $response->assertJson(['res' => $this->taskData]);
        $this->assertDatabaseHas('tasks', $this->taskData);

        foreach ($this->taskRequest['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['res']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_create_with_multiple_users_as_admin(): void
    {
        $this->assertDatabaseMissing('tasks', $this->taskData);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->taskRequestWithMultipleUsers);
        $response->assertOk();
        $response->assertJson(['res' => $this->taskData]);
        $this->assertDatabaseHas('tasks', $this->taskData);

        foreach ($this->taskRequestWithMultipleUsers['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['res']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_create_as_manager(): void
    {
        $this->assertDatabaseMissing('tasks', $this->taskData);

        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->taskRequest);

        $response->assertOk();
        $response->assertJson(['res' => $this->taskData]);
        $this->assertDatabaseHas('tasks', $this->taskData);

        foreach ($this->taskRequest['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['res']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_create_with_multiple_users_as_manager(): void
    {
        $this->assertDatabaseMissing('tasks', $this->taskData);

        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->taskRequestWithMultipleUsers);

        $response->assertOk();
        $response->assertJson(['res' => $this->taskData]);
        $this->assertDatabaseHas('tasks', $this->taskData);

        foreach ($this->taskRequestWithMultipleUsers['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['res']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_create_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->taskRequest);

        $response->assertForbidden();
    }

    public function test_create_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(self::URI, $this->taskRequest);

        $response->assertForbidden();
    }

    public function test_create_as_project_manager(): void
    {
        $this->assertDatabaseMissing('tasks', $this->taskData);

        $response = $this->actingAs($this->projectManager)->postJson(self::URI, $this->taskRequest);

        $response->assertOk();
        $response->assertJson(['res' => $this->taskData]);
        $this->assertDatabaseHas('tasks', $this->taskData);

        foreach ($this->taskRequest['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['res']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_create_with_multiple_users_as_project_manager(): void
    {
        $this->assertDatabaseMissing('tasks', $this->taskData);

        $response = $this->actingAs($this->projectManager)->postJson(self::URI, $this->taskRequestWithMultipleUsers);

        $response->assertOk();
        $response->assertJson(['res' => $this->taskData]);
        $this->assertDatabaseHas('tasks', $this->taskData);

        foreach ($this->taskRequestWithMultipleUsers['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['res']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_create_as_project_auditor(): void
    {
        $response = $this->actingAs($this->projectAuditor)->postJson(self::URI, $this->taskRequest);

        $response->assertForbidden();
    }

    public function test_create_as_project_user(): void
    {
        $response = $this->actingAs($this->projectUser)->postJson(self::URI, $this->taskRequest);

        $response->assertForbidden();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertValidationError();
    }
}
