<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EditTest extends TestCase
{
    use WithFaker;

    private const URI = 'projects/edit';

    private $admin;
    private $manager;
    private $auditor;
    private Model $user;

    private $projectManager;
    private $projectAuditor;
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

    public function test_edit_as_admin(): void
    {
        $this->project->name = $this->faker->text;
        $this->project->description = $this->faker->text;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->project->toArray());
        $response->assertOk();
        $response->assertJson(['res' => $this->project->toArray()]);
        $this->assertDatabaseHas('projects', $this->project->toArray());
    }

    public function test_edit_as_manager(): void
    {
        $this->project->name = $this->faker->text;
        $this->project->description = $this->faker->text;

        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->project->toArray());

        $response->assertOk();
        $response->assertJson(['res' => $this->project->toArray()]);
        $this->assertDatabaseHas('projects', $this->project->toArray());
    }

    public function test_edit_as_auditor(): void
    {
        $this->project->name = $this->faker->text;
        $this->project->description = $this->faker->text;

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->project->toArray());

        $response->assertForbidden();
    }

    public function test_edit_as_detached_user(): void
    {
        $this->project->name = $this->faker->text;
        $this->project->description = $this->faker->text;

        $response = $this->actingAs($this->user)->postJson(self::URI, $this->project->toArray());

        $response->assertForbidden();
    }

    public function test_edit_as_project_manager(): void
    {
        $this->project->name = $this->faker->text;
        $this->project->description = $this->faker->text;

        $response = $this->actingAs($this->projectManager)->postJson(self::URI, $this->project->toArray());

        $response->assertOk();
        $response->assertJson(['res' => $this->project->toArray()]);
        $this->assertDatabaseHas('projects', $this->project->toArray());
    }

    public function test_edit_as_project_auditor(): void
    {
        $this->project->name = $this->faker->text;
        $this->project->description = $this->faker->text;

        $response = $this->actingAs($this->projectAuditor)->postJson(self::URI, $this->project->toArray());

        $response->assertForbidden();
    }

    public function test_edit_as_project_user(): void
    {
        $this->project->name = $this->faker->text;
        $this->project->description = $this->faker->text;

        $response = $this->actingAs($this->projectUser)->postJson(self::URI, $this->project->toArray());

        $response->assertForbidden();
    }

    public function test_not_existing_project(): void
    {
        $this->project->id = 4815162342;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->project->toArray());

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
