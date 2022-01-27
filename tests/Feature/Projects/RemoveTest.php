<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class RemoveTest extends TestCase
{
    private const URI = 'projects/remove';

    private $admin;
    private $manager;
    private $auditor;
    private Model $user;

    private Model $projectManager;
    private Model $projectAuditor;
    private Model $projectUser;

    private Model $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->manager = User::factory()->asManager()->create();
        $this->admin = User::factory()->asAdmin()->create();
        $this->auditor = User::factory()->asAuditor()->create();

        $this->project = Project::factory()->create()->makeHidden('can', 'created_at', 'updated_at');

        $this->projectManager = User::factory()->create();
        $this->projectManager->projects()->attach($this->project->id, ['role_id' => 1]);

        $this->projectAuditor = User::factory()->create();
        $this->projectAuditor->projects()->attach($this->project->id, ['role_id' => 3]);

        $this->projectUser = User::factory()->create();
        $this->projectUser->projects()->attach($this->project->id, ['role_id' => 2]);
    }

    public function test_remove_as_admin(): void
    {
        $this->assertDatabaseHas('projects', $this->project->toArray());

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->project->only('id'));

        $response->assertOk();
        $this->assertSoftDeleted('projects', $this->project->only('id'));
    }
    public function test_remove_as_manager(): void
    {
        $this->assertDatabaseHas('projects', $this->project->toArray());

        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->project->only('id'));

        $response->assertOk();
        $this->assertSoftDeleted('projects', $this->project->only('id'));
    }

    public function test_remove_as_auditor(): void
    {
        $this->assertDatabaseHas('projects', $this->project->toArray());
        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->project->only('id'));

        $response->assertForbidden();
    }

    public function test_remove_as_user(): void
    {
        $this->assertDatabaseHas('projects', $this->project->toArray());

        $response = $this->actingAs($this->user)->postJson(self::URI, $this->project->only('id'));

        $response->assertForbidden();
    }

    // TODO:change later
    public function test_remove_as_project_manager(): void
    {
        $this->assertDatabaseHas('projects', $this->project->toArray());
        $response = $this->actingAs($this->manager)
            ->postJson(self::URI, $this->project->only('id'));
        $response->assertOk();
        $this->assertSoftDeleted('projects', $this->project->only('id'));
    }

    public function test_remove_as_project_auditor(): void
    {
        $this->assertDatabaseHas('projects', $this->project->toArray());

        $response = $this->actingAs($this->projectAuditor)->postJson(self::URI, $this->project->only('id'));

        $response->assertForbidden();
    }

    public function test_remove_as_project_user(): void
    {
        $this->assertDatabaseHas('projects', $this->project->toArray());

        $response = $this->actingAs($this->projectUser)->postJson(self::URI, $this->project->only('id'));

        $response->assertForbidden();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_not_existing_project(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertValidationError();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertValidationError();
    }
}
