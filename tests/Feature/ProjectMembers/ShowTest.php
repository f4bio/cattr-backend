<?php

namespace Tests\Feature\ProjectMembers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use WithFaker;

    private const URI = 'project-members/show';

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

        $this->project = Project::factory()->create();

        $this->projectManager = User::factory()->create();
        $this->projectManager->projects()->attach($this->project->id, ['role_id' => 1]);

        $this->projectAuditor = User::factory()->create();
        $this->projectAuditor->projects()->attach($this->project->id, ['role_id' => 3]);

        $this->projectUser = User::factory()->create();
        $this->projectUser->projects()->attach($this->project->id, ['role_id' => 2]);
    }

    public function test_show_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, ['project_id' => $this->project->id]);

        $response->assertOk();
    }

    public function test_show_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->postJson(self::URI, ['project_id' => $this->project->id]);

        $response->assertOk();
    }

    public function test_show_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->postJson(self::URI, ['project_id' => $this->project->id]);

        $response->assertForbidden();
    }

    public function test_show_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(self::URI, ['project_id' => $this->project->id]);

        $response->assertForbidden();
    }

    public function test_show_as_project_manager(): void
    {
        $response = $this->actingAs($this->projectManager)->postJson(self::URI, ['project_id' => $this->project->id]);

        $response->assertOk();
    }

    public function test_show_as_project_auditor(): void
    {
        $response = $this->actingAs($this->projectAuditor)->postJson(self::URI, ['project_id' => $this->project->id]);

        $response->assertForbidden();
    }

    public function test_show_as_project_user(): void
    {
        $response = $this->actingAs($this->projectUser)->postJson(self::URI, ['project_id' => $this->project->id]);

        $response->assertForbidden();
    }

    public function test_not_existing_project(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, ['project_id' => $this->faker->randomNumber()]);

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
