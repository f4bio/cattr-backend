<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EditTest extends TestCase
{
    use WithFaker;

    private const URI = 'tasks/edit';

    private User $admin;
    private User $manager;
    private User $auditor;
    private Model $user;


    private Model $projectManager;
    private Model $projectAuditor;
    private Model $projectUser;

    private Model $task;
    private array $taskRequest;
    private array $taskRequestWithMultipleUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->asAdmin()->create();
        $this->manager = User::factory()->asManager()->create();
        $this->auditor = User::factory()->asAuditor()->create();
        $this->user = User::factory()->create();

        $this->task = Task::factory()->make()->makeHidden('can', 'updated_at', 'started_at');

        $this->taskRequest = array_merge($this->task->toArray(), [
            'users' => [User::factory()->create()->id],
        ]);

        $this->taskRequestWithMultipleUsers = array_merge($this->task->toArray(), [
            'users' => [
                User::factory()->create()->id,
                User::factory()->create()->id,
                User::factory()->create()->id,
            ],
        ]);

        $this->projectManager = User::factory()->create();
        $this->projectManager->projects()->attach($this->task->project_id, ['role_id' => 1]);

        $this->projectAuditor = User::factory()->create();
        $this->projectAuditor->projects()->attach($this->task->project_id, ['role_id' => 3]);

        $this->projectUser = User::factory()->create();
        $this->projectUser->projects()->attach($this->task->project_id, ['role_id' => 2]);
    }

    public function test_edit_without_user(): void
    {
        $this->task->users = $this->taskRequest['users'] = [];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->taskRequest);
        $response->assertSuccess();
        $response->assertJson(['res' => $this->task->toArray()]);
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id]);
    }

    public function test_edit_as_admin(): void
    {
        $this->task->description = $this->taskRequest['description'] = $this->faker->text;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->taskRequest);

        $response->assertOk();
        $response->assertJson(['res' => $this->task->toArray()]);
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id, 'description' => $this->taskRequest['description']]);

        foreach ($this->taskRequest['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['res']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_edit_with_multiple_users_as_admin(): void
    {
        $this->task->description = $this->taskRequestWithMultipleUsers['description'] = $this->faker->text;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->taskRequestWithMultipleUsers);

        $response->assertOk();
        $response->assertJson(['res' => $this->task->toArray()]);
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id, 'description' => $this->taskRequestWithMultipleUsers['description']]);

        foreach ($this->taskRequestWithMultipleUsers['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['res']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_edit_as_manager(): void
    {
        $this->task->description = $this->taskRequest['description'] = $this->faker->text;

        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->taskRequest);

        $response->assertOk();
        $response->assertJson(['res' => $this->task->toArray()]);
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id, 'description' => $this->taskRequest['description']]);

        foreach ($this->taskRequest['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['res']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_edit_with_multiple_users_as_manager(): void
    {
        $this->task->description = $this->taskRequestWithMultipleUsers['description'] = $this->faker->text;

        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->taskRequestWithMultipleUsers);

        $response->assertOk();
        $response->assertJson(['res' => $this->task->toArray()]);
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id, 'description' => $this->taskRequestWithMultipleUsers['description']]);

        foreach ($this->taskRequestWithMultipleUsers['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['res']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_edit_as_auditor(): void
    {
        $this->task->description = $this->taskRequest['description'] = $this->faker->text;

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->taskRequest);

        $response->assertForbidden();
    }

    public function test_edit_as_user(): void
    {
        $this->task->description = $this->taskRequest['description'] = $this->faker->text;
        $response = $this->actingAs($this->user)->postJson(self::URI, $this->taskRequest);
        $response->assertForbidden();
    }

    public function test_edit_as_project_manager(): void
    {
        $this->task->description = $this->taskRequest['description'] = $this->faker->text;

        $response = $this->actingAs($this->projectManager)->postJson(self::URI, $this->taskRequest);

        $response->assertOk();
        $response->assertJson(['res' => $this->task->toArray()]);
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id, 'description' => $this->taskRequest['description']]);

        foreach ($this->taskRequest['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['res']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_edit_with_multiple_users_as_project_manager(): void
    {
        $this->task->description = $this->taskRequestWithMultipleUsers['description'] = $this->faker->text;

        $response = $this->actingAs($this->projectManager)->postJson(self::URI, $this->taskRequestWithMultipleUsers);

        $response->assertOk();
        $response->assertJson(['res' => $this->task->toArray()]);
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id, 'description' => $this->taskRequestWithMultipleUsers['description']]);

        foreach ($this->taskRequestWithMultipleUsers['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['res']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_edit_as_project_auditor(): void
    {
        $this->task->description = $this->taskRequest['description'] = $this->faker->text;

        $response = $this->actingAs($this->projectAuditor)->postJson(self::URI, $this->taskRequest);

        $response->assertForbidden();
    }

    public function test_edit_as_project_project_user(): void
    {
        $this->task->description = $this->taskRequest['description'] = $this->faker->text;

        $response = $this->actingAs($this->projectUser)->postJson(self::URI, $this->taskRequest);

        $response->assertForbidden();
    }

    public function test_edit_not_existing(): void
    {
        $this->taskRequest['id'] = Task::withoutGlobalScopes()->count() + 20;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->taskRequest);

        $response->assertValidationError();
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
