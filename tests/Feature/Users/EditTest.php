<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Faker\Factory as FakerFactory;

class EditTest extends TestCase
{
    use WithFaker;

    private const URI = '/users/edit';

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

    public function test_edit_as_admin(): void
    {
        $this->user->full_name = $this->faker->name;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->user->toArray());

        $response->assertOk();
        $response->assertJson(['res' => $this->user->toArray()]);
        $this->assertDatabaseHas('users', $this->user->only('id', 'full_name'));
    }

    public function test_edit_as_manager(): void
    {
        $this->user->full_name = $this->faker->name;

        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->user->toArray());

        $response->assertForbidden();
    }

    public function test_edit_as_auditor(): void
    {
        $this->user->full_name = $this->faker->name;

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->user->toArray());

        $response->assertForbidden();
    }

    public function test_edit_as_user(): void
    {
        $this->admin->full_name = $this->faker->name;

        $response = $this->actingAs($this->user)->postJson(self::URI, $this->admin->toArray());

        $response->assertForbidden();
    }

    public function test_edit_as_your_own_user(): void
    {
        $faker = FakerFactory::create();
        $user = clone $this->user;

        $user->full_name = $faker->unique()->firstName;
        $user->email = $faker->unique()->email;
        $user->password = $faker->unique()->password;
        $user->user_language = 'en';

        $response = $this->actingAs($this->user)->postJson(
            self::URI,
            $user->only('id', 'full_name', 'email', 'password', 'user_language')
        );

        $response->assertOk();
        $response->assertJson(['res' => $user->toArray()]);
        $this->assertDatabaseHas(
            'users',
            $user->only('id', 'full_name', 'email', 'user_language')
        );
    }

    public function test_edit_forbidden_field_as_user(): void
    {
        $user = clone $this->user;
        $user->is_admin = true;

        $response = $this->actingAs($this->user)->postJson(
            self::URI,
            $user->only('id', 'is_admin')
        );

        $response->assertValidationError();
    }

    public function test_not_existing(): void
    {
        $this->user->id++;
        $this->user->email = 'newemail@example.com';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->user->toArray());

        $response->assertNotFound();
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
