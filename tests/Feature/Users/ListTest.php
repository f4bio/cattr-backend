<?php

namespace Tests\Feature\Users;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = 'users/list';

    private const USERS_AMOUNT = 10;

    private User $admin;
    private User $manager;
    private User $auditor;
    private Model $user;

    private $projectManager;
    private $projectAuditor;
    private $projectUser;

    private Model $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->asAdmin()->create();
        $this->manager = User::factory()->asManager()->create();
        $this->auditor = User::factory()->asAuditor()->create();
        $this->user = User::factory()->create();

//        UserFactory::createMany(self::USERS_AMOUNT);
        User::factory()->count(self::USERS_AMOUNT)->create();

        $this->project = Project::factory()->create();

        $this->projectManager = User::factory()->create();
        $this->projectManager->projects()->attach($this->project->id, ['role_id' => 1]);

        $this->projectAuditor = User::factory()->create();
        $this->projectAuditor->projects()->attach($this->project->id, ['role_id' => 3]);

        $this->projectUser = User::factory()->create();
        $this->projectUser->projects()->attach($this->project->id, ['role_id' => 2]);
    }

    public function test_list_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $users = User::withoutGlobalScopes()->setEagerLoads([])->get()->toArray();

        $response->assertOk();
        $response->assertExactJson($users);
    }

    public function test_list_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->getJson(self::URI);

        $users = User::withoutGlobalScopes()->setEagerLoads([])->get()->toArray();

        $response->assertOk();
        $response->assertExactJson($users);
    }

    public function test_list_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->getJson(self::URI);

        $users = User::withoutGlobalScopes()->setEagerLoads([])->get()->toArray();

        $response->assertOk();
        $response->assertExactJson($users);
    }

    public function test_list_as_user(): void
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);

        $user = User::withoutGlobalScopes()
            ->where('id', $this->user->id)
            ->setEagerLoads([])
            ->get()
            ->toArray();

        $response->assertOk();
        $response->assertExactJson($user);
    }

    public function test_list_as_project_manager(): void
    {
        $response = $this->actingAs($this->projectManager)->getJson(self::URI);

        $users = User::withoutGlobalScopes()
            ->whereHas('projects', function ($query) {
                $query->where('project_id', $this->project->id);
            })
            ->setEagerLoads([])
            ->get()
            ->toArray();

        $response->assertOk();
        $response->assertExactJson($users);
    }

    public function test_list_as_project_manager_with_global_scope(): void
    {
        $response = $this->actingAs($this->projectManager)->postJson(self::URI, ['global_scope' => true]);

        $users = User::withoutGlobalScope(\App\Scopes\UserScope::class)
            ->setEagerLoads([])
            ->get()
            ->toArray();

        $response->assertOk();
        $response->assertExactJson($users);
    }

    public function test_list_as_project_auditor(): void
    {
        $response = $this->actingAs($this->projectAuditor)->getJson(self::URI);

        $users = User::withoutGlobalScopes()
            ->whereHas('projects', function ($query) {
                $query->where('project_id', $this->project->id);
            })
            ->setEagerLoads([])
            ->get()
            ->toArray();

        $response->assertOk();
        $response->assertExactJson($users);
    }

    public function test_list_as_project_user(): void
    {
        $response = $this->actingAs($this->projectManager)->getJson(self::URI);

        $users = User::withoutGlobalScopes()
            ->whereHas('projects', function ($query) {
                $query->where('project_id', $this->project->id);
            })
            ->setEagerLoads([])
            ->get()
            ->toArray();

        $response->assertOk();
        $response->assertExactJson($users);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
