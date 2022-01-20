<?php

namespace Tests\Feature\Users;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class ShowTest extends TestCase
{
    private const URI = 'users/show';

    private $admin;
    private $manager;
    private $auditor;
    private Model $user;

    private Model $projectManager;
    private Model $projectAuditor;
    private Model $projectUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->asAdmin()->create();
        $this->manager = User::factory()->asManager()->create();
        $this->auditor = User::factory()->asAuditor()->create();
        $this->user = User::factory()->create();

        $project = Project::factory()->create();

        $this->projectManager = User::factory()->create();
        $this->projectManager->projects()->attach($project->id, ['role_id' => 1]);

        $this->projectAuditor = User::factory()->create();
        $this->projectAuditor->projects()->attach($project->id, ['role_id' => 3]);

        $this->projectUser = User::factory()->create();
        $this->projectUser->projects()->attach($project->id, ['role_id' => 2]);
    }

    public function test_show_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->user->only('id'));

        $response->assertOk();
        $response->assertJson($this->user->toArray());
    }

    public function test_show_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->user->only('id'));

        $response->assertOk();
        $response->assertJson($this->user->toArray());
    }

    public function test_show_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->user->only('id'));

        $response->assertOk();
        $response->assertJson($this->user->toArray());
    }

    public function test_show_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(self::URI, $this->admin->only('id'));

        $response->assertForbidden();
    }

    public function test_show_as_your_own_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(self::URI, $this->user->only('id'));

        $this->user->makeHidden('role');

        $response->assertOk();
        $response->assertJson($this->user->toArray());
    }

    public function test_show_as_project_manager(): void
    {
        $response = $this->actingAs($this->projectManager)->postJson(self::URI, $this->projectUser->only('id'));

        $user = User::where('id', $this->projectUser->id)
            ->setEagerLoads([])
            ->get()
            ->toArray();

        $response->assertOk();
        $response->assertExactJson($user[0]);
    }

    public function test_show_as_project_auditor(): void
    {
        $response = $this
            ->actingAs($this->projectAuditor)
            ->postJson(self::URI, $this->projectUser->only('id'));

        $user = User::where('id', $this->projectUser->id)
            ->setEagerLoads([])
            ->get()
            ->toArray();

        $response->assertOk();
        $response->assertExactJson($user[0]);
    }

    public function test_show_as_project_user(): void
    {
        $response = $this->actingAs($this->projectUser)->postJson(self::URI, $this->projectAuditor->only('id'));

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
