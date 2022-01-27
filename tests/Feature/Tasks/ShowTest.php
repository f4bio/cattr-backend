<?php

namespace Tests\Feature\Tasks;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ShowTest extends TestCase
{
    private const URI = 'tasks/show';

    private User $admin;
    private User $manager;
    private User $auditor;
    private $user;

    private User $projectManager;
    private User $projectAuditor;
    private User $projectUser;

    private User $assignedUser;
    private $assignedTask;

    private User $assignedProjectUser;
    private Task $assignedProjectTask;

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

        $this->projectUser = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectUser->projects()->attach($this->task->project_id, ['role_id' => 2]);

        $this->assignedUser = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->assignedTask = TaskFactory::refresh()->forUser($this->assignedUser)->create();
//        $this->assignedTask = Task::factory()->for(Project::factory())->create()
//            ->users()->attach($this->assignedUser->id);

        $this->assignedProjectUser = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->assignedProjectTask = TaskFactory::refresh()->forUser($this->assignedProjectUser)->create();
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
