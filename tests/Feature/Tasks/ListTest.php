<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = 'tasks/list';

    private const TASKS_AMOUNT = 10;

    private $admin;
    private $manager;
    private $auditor;
    private $user;

    private $projectManager;
    private $projectAuditor;
    private $projectUser;

    private $assignedUser;
    private $assignedTask;

    private $assignedProjectUser;
    private $assignedProjectTask;

    private $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->asAdmin()->create();
        $this->manager = User::factory()->asManager()->create();
        $this->auditor = User::factory()->asAuditor()->create();
        $this->user = User::factory()->create();

        $this->task = TaskFactory::create();

        $this->projectManager = User::factory()->create();
        $this->projectManager->projects()->attach($this->task->project_id, ['role_id' => 1]);

        $this->projectAuditor = User::factory()->create();
        $this->projectAuditor->projects()->attach($this->task->project_id, ['role_id' => 3]);

        $this->projectUser = User::factory()->create();
        $this->projectUser->projects()->attach($this->task->project_id, ['role_id' => 2]);

        $this->assignedUser = User::factory()->create();
        $this->assignedTask = TaskFactory::refresh()->forUser($this->assignedUser)->create();

        $this->assignedProjectUser = User::factory()->create();
        $this->assignedProjectTask = TaskFactory::refresh()->forUser($this->assignedProjectUser)->create();
        $this->assignedProjectUser->projects()->attach($this->assignedProjectTask->project_id, ['role_id' => 2]);
    }

    public function test_list_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertOk();

        $tasks = Task::query()
            ->leftJoin('statuses as s', 'tasks.status_id', '=', 's.id')
            ->select('tasks.*')
            ->orderBy('s.active', 'desc')
            ->orderBy('tasks.created_at', 'desc')
            ->get();

        $response->assertJson($tasks->toArray());
    }

    public function test_list_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->getJson(self::URI);

        $response->assertOk();

        $tasks = Task::query()
            ->leftJoin('statuses as s', 'tasks.status_id', '=', 's.id')
            ->select('tasks.*')
            ->orderBy('s.active', 'desc')
            ->orderBy('tasks.created_at', 'desc')
            ->get();

        $response->assertJson($tasks->toArray());
    }

    public function test_list_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->getJson(self::URI);

        $response->assertOk();

        $tasks = Task::query()
            ->leftJoin('statuses as s', 'tasks.status_id', '=', 's.id')
            ->select('tasks.*')
            ->orderBy('s.active', 'desc')
            ->orderBy('tasks.created_at', 'desc')
            ->get();

        $response->assertJson($tasks->toArray());
    }

    public function test_list_as_user(): void
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);

        $response->assertOk();
        $response->assertExactJson([]);
    }

    public function test_list_as_assigned_user(): void
    {
        $response = $this->actingAs($this->assignedUser)->getJson(self::URI);

        $response->assertOk();
        $response->assertExactJson(
            Task::query()
                ->where('id', '=', $this->assignedTask->id)
                ->get()->toArray()
        );
    }

    public function test_list_as_project_manager(): void
    {
        $response = $this->actingAs($this->projectManager)->getJson(self::URI);

        $task = Task::where('project_id', $this->task->project_id)->get()->toArray();

        $response->assertOk();
        $response->assertExactJson($task);
    }

    public function test_list_as_project_auditor(): void
    {
        $response = $this->actingAs($this->projectAuditor)->getJson(self::URI);

        $task = Task::where('project_id', $this->task->project_id)->get()->toArray();

        $response->assertOk();
        $response->assertExactJson($task);
    }

    public function test_list_as_project_user(): void
    {
        $response = $this->actingAs($this->projectUser)->getJson(self::URI);

        $task = Task::where('project_id', $this->task->project_id)->get()->toArray();

        $response->assertOk();
        $response->assertExactJson($task);
    }

    public function test_list_as_assigned_project_user(): void
    {
        $response = $this
            ->actingAs($this->assignedProjectUser)
            ->postJson(self::URI, $this->assignedProjectTask->only('id'));

        $task = Task::where('project_id', $this->assignedProjectTask->project_id)
            ->get()
            ->toArray();

        $response->assertOk();
        $response->assertExactJson($task);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
