<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class RemoveTest extends TestCase
{
    private const URI = 'users/remove';

    private $admin;
    private $manager;
    private $auditor;
    private Model $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->asAdmin()->create();
        $this->manager = User::factory()->asManager()->create();
        $this->auditor = User::factory()->asAuditor()->create();
        $this->user = User::factory()->create();
    }

    public function test_remove_as_admin(): void
    {
        $user = $this->user->makeHidden('online')->toArray();
        unset($user['online']);
        $this->assertDatabaseHas('users', $user);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->user->only('id'));

        $response->assertOk();
        $this->assertSoftDeleted('users', $this->user->only('id'));
    }

    public function test_remove_as_manager(): void
    {
        $this->assertDatabaseHas('users', $this->user->makeHidden('online')->toArray());

        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->user->only('id'));

        $response->assertForbidden();
    }

    public function test_remove_as_auditor(): void
    {
        $this->assertDatabaseHas('users', $this->user->makeHidden('online')->toArray());

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->user->only('id'));

        $response->assertForbidden();
    }

    public function test_remove_as_user(): void
    {
        $this->assertDatabaseHas('users', $this->user->makeHidden('online')->toArray());

        $response = $this->actingAs($this->user)->postJson(self::URI, $this->user->only('id'));

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
