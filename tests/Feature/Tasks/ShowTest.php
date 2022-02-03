<?php

namespace Tests\Feature\Tasks;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Tests\TestCase;

class ShowTest extends TestCase
{
    private const URI = 'tasks/show';

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

        $this->task = Task::factory()->for(Project::factory())->create();

        $this->projectManager = User::factory()->asManager()->create();
        $this->projectManager->projects()->attach($this->task->project_id, ['role_id' => 1]);

        $this->projectAuditor = User::factory()->asAuditor()->create();
        $this->projectAuditor->projects()->attach($this->task->project_id, ['role_id' => 3]);

        $this->projectUser = User::factory()->create();
        $this->projectUser->projects()->attach($this->task->project_id, ['role_id' => 2]);

        $this->assignedUser = User::factory()->create();
        $this->assignedTask = Task::factory()->for(Project::factory())->create();
        $this->assignedTask->users()->attach($this->assignedUser->id);

        $this->assignedProjectUser = User::factory()->create();
        $this->assignedProjectTask = Task::factory()->for(Project::factory())->create();
        $this->assignedProjectTask->users()->attach($this->assignedProjectUser->id);
        $this->assignedProjectUser->projects()->attach($this->assignedProjectTask->project_id, ['role_id' => 2]);
    }

    public function test_show_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->task->only('id'));

        $response->assertOk();
        $response->assertJson($this->task->toArray());
    }

    public function test_show_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->task->only('id'));

        $response->assertOk();
        $response->assertJson($this->task->toArray());
    }

    public function test_show_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->task->only('id'));

        $response->assertOk();
        $response->assertJson($this->task->toArray());
    }

    public function test_show_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(self::URI, $this->task->only('id'));

        $response->assertForbidden();
    }

    public function test_show_as_assigned_user(): void
    {
        $response = $this
            ->actingAs($this->assignedUser)
            ->postJson(self::URI, $this->assignedTask->only('id'));

        $response->assertOk();
        $response->assertJson($this->assignedTask->toArray());
    }

    public function test_show_as_project_manager(): void
    {
        $response = $this->actingAs($this->projectManager)->postJson(self::URI, $this->task->only('id'));

        $response->assertOk();
        $response->assertJson($this->task->toArray());
    }

    public function test_show_as_project_auditor(): void
    {
        $response = $this->actingAs($this->projectAuditor)->postJson(self::URI, $this->task->only('id'));

        $response->assertOk();
        $response->assertJson($this->task->toArray());
    }

    public function test_show_as_project_user(): void
    {
        $response = $this->actingAs($this->projectUser)->postJson(self::URI, $this->task->only('id'));

        $response->assertOk();
        $response->assertJson($this->task->toArray());
    }

    public function test_show_as_assigned_project_user(): void
    {
        $response = $this
            ->actingAs($this->assignedProjectUser)
            ->postJson(self::URI, $this->assignedProjectTask->only('id'));

        $response->assertOk();
        $response->assertJson($this->assignedProjectTask->toArray());
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
