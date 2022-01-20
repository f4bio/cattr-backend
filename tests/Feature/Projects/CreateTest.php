<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private const URI = 'projects/create';

    private $admin;
    private $manager;
    private $auditor;
    private Model $user;

    private array $projectData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->manager = User::factory()->asManager()->create();
        $this->admin = User::factory()->asAdmin()->create();
        $this->auditor = User::factory()->asAuditor()->create();

        $this->projectData = Project::factory()->make()
            ->makeHidden('can', 'updated_at', 'created_at')->toArray();
    }

    public function test_create_as_admin(): void
    {
        $this->assertDatabaseMissing('projects', $this->projectData);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->projectData);

        $response->assertOk();
        $response->assertJson(['res' => $this->projectData]);
        $this->assertDatabaseHas('projects', $this->projectData);
    }

    public function test_create_as_manager(): void
    {
        $this->assertDatabaseMissing('projects', $this->projectData);

        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->projectData);

        $response->assertOk();
        $response->assertJson(['res' => $this->projectData]);
        $this->assertDatabaseHas('projects', $this->projectData);
    }

    public function test_create_as_auditor(): void
    {
        $this->assertDatabaseMissing('projects', $this->projectData);

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->projectData);

        $response->assertForbidden();
    }

    public function test_create_as_user(): void
    {
        $this->assertDatabaseMissing('projects', $this->projectData);

        $response = $this->actingAs($this->user)->postJson(self::URI, $this->projectData);

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
